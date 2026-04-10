#!/bin/bash
# =============================================================================
# Sharpishly V3 – Neural Pipeline Generator (FIXED)
# =============================================================================

set -e

echo "🧬 Generating Sharpishly Neural Pipeline v3.6 (Fixed)..."

# Create directory structure
mkdir -p ai/models ai/services

# 1. Database Model
cat <<'EOF' > ai/models/database.py
import os
import mysql.connector
from mysql.connector import Error

class JobModel:
    """Database model mirroring PHP Db.php logic."""

    @staticmethod
    def get_connection():
        try:
            return mysql.connector.connect(
                host=os.getenv('DB_HOST', 'sharpishly-db'),
                user=os.getenv('DB_USER', 'root'),
                password=os.getenv('DB_PASS', 'sharpishly'),
                database=os.getenv('DB_NAME', 'sharpishly')
            )
        except Error as e:
            print(f"❌ DB Error: {e}")
            raise

    @staticmethod
    def find_pending():
        conn = None
        try:
            conn = JobModel.get_connection()
            cursor = conn.cursor(dictionary=True)
            cursor.execute("SELECT id, payload FROM jobs WHERE status = 'pending' LIMIT 1")
            return cursor.fetchone()
        finally:
            if conn and conn.is_connected():
                conn.close()

    @staticmethod
    def update_status(job_id, status, error=None):
        conn = None
        try:
            conn = JobModel.get_connection()
            cursor = conn.cursor()
            cursor.execute("""
                UPDATE jobs SET status = %s, error_message = %s, updated_at = NOW() WHERE id = %s
            """, (status, error, job_id))
            conn.commit()
        finally:
            if conn and conn.is_connected():
                conn.close()
EOF

# 2. Neural Pipeline Service
cat <<'EOF' > ai/services/neural_pipeline.py
import os
import requests

class NeuralPipeline:
    def __init__(self, file_path):
        if not os.path.exists(file_path):
            raise FileNotFoundError(f"File not found: {file_path}")
        self.file_path = file_path
        self.raw_data = ""
        self.chunks = []
        self.vectors = []

    def clean(self):
        with open(self.file_path, 'r', encoding='utf-8', errors='ignore') as f:
            self.raw_data = " ".join(f.read().split())
        return self

    def chunk(self, size=500, overlap=50):
        words = self.raw_data.split()
        for i in range(0, len(words), size - overlap):
            self.chunks.append(" ".join(words[i:i + size]))
        return self

    def vectorize(self):
        for text in self.chunks:
            response = requests.post(
                "http://sharpishly-ollama:11434/api/embeddings",
                json={"model": "nomic-embed-text", "prompt": text},
                timeout=30
            )
            response.raise_for_status()
            self.vectors.append(response.json().get('embedding'))
        return self

    def insert_into_vectordb(self, job_id):
        print(f"💾 Processed {len(self.vectors)} vectors for job {job_id}")
        return True
EOF

# 3. FastAPI Worker
cat <<'EOF' > ai/main.py
import time
import threading
import json
from fastapi import FastAPI
from models.database import JobModel
from services.neural_pipeline import NeuralPipeline

app = FastAPI(title="Sharpishly Neural Organism")

def worker_loop():
    print("🧠 Neural Worker active...")
    while True:
        try:
            job = JobModel.find_pending()
            if job:
                job_id = job['id']
                payload = json.loads(job['payload'])
                file_path = payload.get('path') or payload.get('file_path')
                
                pipeline = NeuralPipeline(file_path)
                pipeline.clean().chunk().vectorize().insert_into_vectordb(job_id)
                
                JobModel.update_status(job_id, 'completed')
                print(f"✅ Job {job_id} done.")
            
        except Exception as e:
            if 'job_id' in locals():
                JobModel.update_status(job_id, 'failed', str(e))
            print(f"❌ Error: {e}")
        
        time.sleep(5)

@app.on_event("startup")
async def startup_event():
    threading.Thread(target=worker_loop, daemon=True).start()

@app.get("/")
async def index():
    return {"status": "active"}
EOF

chmod +x ai/main.py
echo "✅ Done! Files generated in ./ai/"
