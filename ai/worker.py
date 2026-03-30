import os
import time
import json
import mysql.connector
from dotenv import load_dotenv
from src.ollama import OllamaService
from src.processor import TextProcessor

# Load .env for local development; Docker will inject these naturally
load_dotenv()

def get_db_connection():
    """Connect to MariaDB using environment variables for security."""
    try:
        return mysql.connector.connect(
            host=os.getenv("DB_HOST", "sharpishly-db"),
            user=os.getenv("DB_USER"),
            password=os.getenv("DB_PASSWORD"),
            database=os.getenv("DB_NAME"),
            port=int(os.getenv("DB_PORT", 3306)),
            autocommit=True
        )
    except mysql.connector.Error as err:
        print(f"❌ Critical: Could not connect to MariaDB. Error: {err}")
        raise

def update_job(cursor, job_id, status, step_id, progress, message):
    """
    Updates the job status for SPA feedback.
    Maps to the columns: status, current_step, progress.
    """
    timestamp = time.strftime('%H:%M:%S')
    # We log the message to stdout for Docker logs, and update the DB for the UI
    print(f"[{timestamp}] Job {job_id} [{step_id}]: {message}")
    
    query = """
        UPDATE jobs 
        SET status = %s, 
            current_step = %s, 
            progress = %s
        WHERE id = %s
    """
    cursor.execute(query, (status, step_id, progress, job_id))

def work_loop():
    print("🚀 Neural Worker Online. Monitoring 'neural_ingest' jobs...")
    ollama = OllamaService()
    processor = TextProcessor()
    
    while True:
        db = None
        try:
            db = get_db_connection()
            cursor = db.cursor(dictionary=True)

            # 1. Fetch one pending job
            cursor.execute(
                "SELECT id, payload FROM jobs WHERE status = 'pending' AND type = 'neural_ingest' LIMIT 1"
            )
            job = cursor.fetchone()

            if not job:
                cursor.close()
                db.close()
                time.sleep(5) # Back off to save CPU/DB cycles
                continue

            job_id = job['id']
            payload = json.loads(job['payload'])
            file_path = payload.get('path') or payload.get('file_path')

            # --- STAGE: CHUNK (25%) ---
            update_job(cursor, job_id, 'processing', 'chunk', 25, 'Splitting document into semantic blocks...')
            
            if not os.path.exists(file_path):
                raise Exception(f"File missing in shared storage: {file_path}")

            with open(file_path, 'r', encoding='utf-8') as f:
                content = f.read()

            chunks = processor.prepare_for_ollama(content)

            # --- STAGE: EMBED (50%) ---
            update_job(cursor, job_id, 'processing', 'embed', 50, f'Generating vectors for {len(chunks)} chunks...')
            embeddings = ollama.get_embeddings(chunks)

            if not embeddings:
                raise Exception("Ollama failed to return embeddings.")

            # --- STAGE: INDEX (75%) ---
            update_job(cursor, job_id, 'processing', 'index', 75, 'Persisting vectors to database...')
            
            for i, vector in enumerate(embeddings):
                cursor.execute(
                    "INSERT INTO vectors (job_id, content, embedding) VALUES (%s, %s, %s)",
                    (job_id, chunks[i], json.dumps(vector))
                )
            
            # --- FINALIZE (100%) ---
            query_finalize = """
                UPDATE jobs 
                SET status = 'completed', 
                    current_step = 'index', 
                    progress = 100, 
                    finished_at = NOW() 
                WHERE id = %s
            """
            cursor.execute(query_finalize, (job_id,))
            print(f"✅ Job {job_id} successfully indexed.")
            
            cursor.close()
            db.close()

        except Exception as e:
            error_msg = str(e)
            print(f"❌ Error: {error_msg}")
            if db and db.is_connected():
                try:
                    # Mark job as failed so the UI stops polling
                    cursor.execute(
                        "UPDATE jobs SET status = 'failed', current_step = 'error' WHERE id = %s",
                        (job_id,)
                    )
                    cursor.close()
                    db.close()
                except:
                    pass
            time.sleep(10)

if __name__ == "__main__":
    work_loop()