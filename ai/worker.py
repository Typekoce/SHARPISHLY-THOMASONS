import os
import time
import json
import mysql.connector
from dotenv import load_dotenv
from src.ollama import OllamaService
from src.processor import TextProcessor

load_dotenv()

def get_db_connection():
    """Connect to MariaDB using environment variables."""
    try:
        return mysql.connector.connect(
            host=os.getenv("DB_HOST", "sharpishly-db"),
            user=os.getenv("DB_USER", "root"),
            password=os.getenv("DB_PASS", "root_password"), # SYNCED WITH PHP
            database=os.getenv("DB_NAME", "sharpishly"),
            port=int(os.getenv("DB_PORT", 3306)),
            autocommit=True
        )
    except mysql.connector.Error as err:
        print(f"❌ Critical: Could not connect to MariaDB. Error: {err}")
        raise

def update_job(cursor, job_id, status, step_id, progress, message):
    """Updates the job status for SPA feedback."""
    timestamp = time.strftime('%H:%M:%S')
    print(f"[{timestamp}] Job {job_id} [{step_id}]: {message}")
    
    query = """
        UPDATE jobs 
        SET status = %s, current_step = %s, progress = %s 
        WHERE id = %s
    """
    cursor.execute(query, (status, step_id, progress, job_id))

def work_loop():
    print("🚀 Neural Worker Online. Monitoring jobs...")
    ollama = OllamaService()
    processor = TextProcessor()
    
    while True:
        db = None
        try:
            db = get_db_connection()
            cursor = db.cursor(dictionary=True)

            # Look for any pending neural jobs
            cursor.execute(
                "SELECT id, payload FROM jobs WHERE status = 'pending' LIMIT 1"
            )
            job = cursor.fetchone()

            if not job:
                cursor.close()
                db.close()
                time.sleep(5)
                continue

            job_id = job['id']
            payload = json.loads(job['payload'])
            
            # Resolve path from PHP UploadController or Ingest handshake
            file_path = payload.get('path') or payload.get('file_path')

            # --- STAGE: CHUNK (25%) ---
            update_job(cursor, job_id, 'processing', 'chunk', 25, 'Processing semantic segments...')
            
            if not os.path.exists(file_path):
                raise Exception(f"File missing: {file_path}")

            with open(file_path, 'r', encoding='utf-8') as f:
                content = f.read()

            # Pass file_path to trigger CSV vs Text logic
            chunks = processor.prepare_for_ollama(content, file_path=file_path)

            # --- STAGE: EMBED (50%) ---
            update_job(cursor, job_id, 'processing', 'embed', 50, f'Generating vectors for {len(chunks)} chunks...')
            embeddings = ollama.get_embeddings(chunks)

            if not embeddings:
                raise Exception("Ollama returned empty embeddings.")

            # --- STAGE: INDEX (75%) ---
            update_job(cursor, job_id, 'processing', 'index', 75, 'Storing vectors in MariaDB...')
            
            for i, vector in enumerate(embeddings):
                # Ensure 'content' and 'embedding' columns exist in your 'vectors' table
                cursor.execute(
                    "INSERT INTO vectors (job_id, content, embedding) VALUES (%s, %s, %s)",
                    (job_id, chunks[i], json.dumps(vector))
                )
            
            # --- FINALIZE (100%) ---
            query_finalize = """
                UPDATE jobs 
                SET status = 'completed', current_step = 'done', progress = 100, finished_at = NOW() 
                WHERE id = %s
            """
            cursor.execute(query_finalize, (job_id,))
            print(f"✅ Job {job_id} successfully indexed.")
            
            cursor.close()
            db.close()

        except Exception as e:
            print(f"❌ Error: {str(e)}")
            if db and db.is_connected():
                cursor.execute(
                    "UPDATE jobs SET status = 'failed', current_step = 'error' WHERE id = %s",
                    (job_id,)
                )
                cursor.close()
                db.close()
            time.sleep(10)

if __name__ == "__main__":
    work_loop()