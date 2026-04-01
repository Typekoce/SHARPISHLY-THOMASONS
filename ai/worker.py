import os
import time
import json
import signal
import sys
from dotenv import load_dotenv
import mysql.connector
from mysql.connector import pooling

from src.ollama import OllamaService
from src.processor import TextProcessor

load_dotenv()

# Global connection pool
db_pool = None

def init_db_pool():
    global db_pool
    try:
        db_pool = pooling.MySQLConnectionPool(
            pool_name="sharpishly_worker_pool",
            pool_size=5, # Increased slightly for stability
            pool_reset_session=True,
            host=os.getenv("DB_HOST", "sharpishly-db"),
            user=os.getenv("DB_USER", "root"),
            password=os.getenv("DB_PASS"),
            database=os.getenv("DB_NAME", "sharpishly"),
            port=int(os.getenv("DB_PORT", 3306)),
            autocommit=True,
            charset='utf8mb4',
            collation='utf8mb4_unicode_ci'
        )
        print("✅ Database connection pool initialized.")
    except mysql.connector.Error as err:
        print(f"❌ Failed to create DB pool: {err}")
        sys.exit(1)

def get_db_connection():
    try:
        return db_pool.get_connection()
    except Exception as err:
        print(f"❌ Could not get connection from pool: {err}")
        raise

def update_job(cursor, job_id, status, step_id, progress, message):
    timestamp = time.strftime('%H:%M:%S')
    print(f"[{timestamp}] Job {job_id} [{step_id}]: {message} (progress: {progress}%)")
    
    query = """
        UPDATE jobs 
        SET status = %s, current_step = %s, progress = %s 
        WHERE id = %s
    """
    cursor.execute(query, (status, step_id, progress, job_id))

def handle_shutdown(signum, frame):
    print("🛑 Received shutdown signal. Exiting gracefully...")
    sys.exit(0)

def work_loop():
    print("🚀 Neural Worker Online. Monitoring for pending jobs...")
    
    ollama = OllamaService()
    processor = TextProcessor()
    
    signal.signal(signal.SIGTERM, handle_shutdown)
    signal.signal(signal.SIGINT, handle_shutdown)

    while True:
        conn = None
        cursor = None
        job_id = None
        try:
            conn = get_db_connection()
            cursor = conn.cursor(dictionary=True)

            # Find one pending job
            cursor.execute(
                "SELECT id, payload FROM jobs WHERE status = 'pending' LIMIT 1 FOR UPDATE"
            )
            job = cursor.fetchone()

            if not job:
                # Proper cleanup before sleeping
                cursor.close()
                conn.close()
                conn = None # Reset to prevent double-close in finally
                time.sleep(5)
                continue

            job_id = job['id']
            payload = json.loads(job['payload'])
            
            # Use absolute path if mapping requires it
            file_path = payload.get('path') or payload.get('file_path')
            
            if not file_path or not os.path.exists(file_path):
                raise FileNotFoundError(f"File not found: {file_path}")

            # --- STAGE: CHUNK (25%) ---
            update_job(cursor, job_id, 'processing', 'chunk', 25, 'Processing semantic segments...')
            
            with open(file_path, 'r', encoding='utf-8') as f:
                content = f.read()

            chunks = processor.prepare_for_ollama(content, file_path=file_path)

            # --- STAGE: EMBED (50%) ---
            update_job(cursor, job_id, 'processing', 'embed', 50, f'Generating embeddings for {len(chunks)} chunks...')
            embeddings = ollama.get_embeddings(chunks)

            if not embeddings or len(embeddings) != len(chunks):
                raise ValueError("Ollama returned incomplete embeddings.")

            # --- STAGE: INDEX (75%) ---
            update_job(cursor, job_id, 'processing', 'index', 75, 'Storing vectors in database...')
            
            for i, vector in enumerate(embeddings):
                cursor.execute(
                    "INSERT INTO vectors (job_id, content, embedding) VALUES (%s, %s, %s)",
                    (job_id, chunks[i], json.dumps(vector))
                )

            # --- FINALIZE (100%) ---
            cursor.execute(
                """
                UPDATE jobs 
                SET status = 'completed', current_step = 'done', progress = 100, finished_at = NOW() 
                WHERE id = %s
                """,
                (job_id,)
            )
            
            print(f"✅ Job {job_id} completed successfully.")

        except Exception as e:
            print(f"❌ Error processing job {job_id or 'unknown'}: {str(e)}")
            # Defensive check for connection status during failure
            if conn:
                try:
                    if not cursor:
                        cursor = conn.cursor()
                    cursor.execute(
                        "UPDATE jobs SET status = 'failed', current_step = 'error', finished_at = NOW() WHERE id = %s",
                        (job_id,) if job_id else (0,)
                    )
                except Exception as inner_e:
                    print(f"⚠️ Failed to mark job as failed: {inner_e}")
        finally:
            # ROBUST CLEANUP: Using try blocks to prevent 'NoneType' AttributeErrors
            if cursor:
                try:
                    cursor.close()
                except:
                    pass
            if conn:
                try:
                    # In pooling, .close() returns the connection to the pool
                    conn.close()
                except:
                    pass

        time.sleep(2)

if __name__ == "__main__":
    init_db_pool()
    work_loop()