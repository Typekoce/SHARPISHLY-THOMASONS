import time
import json
import os
import mysql.connector
from src.ollama import OllamaService
from src.processor import TextProcessor

def get_db_connection():
    """Connect to the Docker service name 'sharpishly-db' using .env credentials."""
    return mysql.connector.connect(
        host=os.getenv("DB_HOST", "sharpishly-db"),
        user=os.getenv("DB_USER", "root"),
        password=os.getenv("DB_PASSWORD", "password"),
        database=os.getenv("DB_NAME", "sharpishly"),
        autocommit=True
    )

def update_job(cursor, db, job_id, status, step_id, message):
    """
    Updates the job for SPA visual feedback.
    step_id matches frontend: 'upload' | 'chunk' | 'embed' | 'index'
    """
    timestamp = time.strftime('%H:%M:%S')
    log_entry = json.dumps({"t": timestamp, "m": message})
    
    query = """
        UPDATE jobs 
        SET status = %s, 
            current_step = %s, 
            steps_json = JSON_ARRAY_APPEND(steps_json, '$', CAST(%s AS JSON)) 
        WHERE id = %s
    """
    cursor.execute(query, (status, step_id, log_entry, job_id))
    db.commit()

def work_loop():
    print("🚀 Neural Worker Online. Monitoring 'neural_ingest' jobs...")
    ollama = OllamaService()
    processor = TextProcessor()
    
    while True:
        db = None
        try:
            db = get_db_connection()
            cursor = db.cursor(dictionary=True)

            # 1. Fetch pending ingestion jobs
            cursor.execute(
                "SELECT id, payload FROM jobs WHERE status = 'pending' AND type = 'neural_ingest' LIMIT 1"
            )
            job = cursor.fetchone()

            if not job:
                db.close()
                time.sleep(5)
                continue

            job_id = job['id']
            payload = json.loads(job['payload'])
            file_path = payload.get('path')

            # --- STAGE: CHUNK ---
            update_job(cursor, db, job_id, 'processing', 'chunk', 'Splitting document into semantic blocks...')
            
            if not os.path.exists(file_path):
                raise Exception(f"File missing in shared storage: {file_path}")

            with open(file_path, 'r', encoding='utf-8') as f:
                content = f.read()

            chunks = processor.prepare_for_ollama(content)

            # --- STAGE: EMBED ---
            update_job(cursor, db, job_id, 'processing', 'embed', f'Generating vectors for {len(chunks)} chunks...')
            embeddings = ollama.get_embeddings(chunks)

            if not embeddings:
                raise Exception("Ollama failed to return embeddings.")

            # --- STAGE: INDEX ---
            update_job(cursor, db, job_id, 'processing', 'index', 'Persisting vectors to database...')
            
            for i, vector in enumerate(embeddings):
                cursor.execute(
                    "INSERT INTO vectors (job_id, content, embedding) VALUES (%s, %s, %s)",
                    (job_id, chunks[i], json.dumps(vector))
                )
            
            # --- FINALIZE ---
            update_job(cursor, db, job_id, 'completed', 'index', 'Neural Ingestion Complete.')
            print(f"✅ Job {job_id} successfully indexed.")
            
            db.close()

        except Exception as e:
            error_msg = str(e)
            print(f"❌ Error: {error_msg}")
            if db and db.is_connected():
                try:
                    update_job(cursor, db, job_id, 'failed', 'upload', f"Critical Error: {error_msg}")
                    db.close()
                except:
                    pass
            time.sleep(10)

if __name__ == "__main__":
    work_loop()