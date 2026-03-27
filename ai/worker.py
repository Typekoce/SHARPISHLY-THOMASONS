import time
import json
import os
import mysql.connector
from src.ollama import OllamaService

# ai/worker.py
from src.processor import TextProcessor
# from src.ollama import OllamaService

# Now your logic is lean:
# processor = TextProcessor()
# ollama = OllamaService()
# chunks = processor.prepare_for_ollama(content)
# vectors = ollama.get_embeddings(chunks)

def get_db_connection():
    # Connect to the Docker service name 'sharpishly-db'
    return mysql.connector.connect(
        host=os.getenv("DB_HOST", "sharpishly-db"),
        user=os.getenv("DB_USER", "root"),
        password=os.getenv("DB_PASSWORD", "password"),
        database=os.getenv("DB_NAME", "sharpishly"),
        autocommit=True
    )

def update_job(cursor, db, job_id, status, step):
    cursor.execute(
        "UPDATE jobs SET status = %s, current_step = %s WHERE id = %s",
        (status, step, job_id)
    )
    db.commit()

def work_loop():
    print("🚀 Neural Worker Started. Polling for 'neural_ingest' jobs...")
    ollama = OllamaService()
    
    while True:
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

            # 2. Start Processing
            update_job(cursor, db, job_id, 'processing', 'Reading file from storage...')
            
            if not os.path.exists(file_path):
                raise Exception(f"File not found: {file_path}")

            with open(file_path, 'r', encoding='utf-8') as f:
                content = f.read()

            # 3. Embedding Generation
            update_job(cursor, db, job_id, 'processing', 'Generating vectors via Ollama...')
            
            # Simple chunking logic (Flat Pattern)
            chunks = [content[i:i+1000] for i in range(0, len(content), 1000)]
            embeddings = ollama.get_embeddings(chunks)

            if embeddings:
                # 4. Save to Vectors Table
                for i, vector in enumerate(embeddings):
                    cursor.execute(
                        "INSERT INTO vectors (job_id, content, embedding) VALUES (%s, %s, %s)",
                        (job_id, chunks[i], json.dumps(vector))
                    )
                
                update_job(cursor, db, job_id, 'completed', 'Ingestion complete.')
                print(f"✅ Job {job_id} finalized.")
            
            db.close()

        except Exception as e:
            print(f"❌ Error: {str(e)}")
            # Attempt to mark as failed if we still have a DB connection
            try:
                update_job(cursor, db, job_id, 'failed', str(e))
                db.close()
            except:
                pass
            time.sleep(10)

if __name__ == "__main__":
    work_loop()