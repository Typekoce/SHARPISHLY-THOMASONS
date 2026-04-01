from src.Services.QueueService import QueueService
from src.Models.Job import Job
from src.Config.Database import Database
import json
import time
import os
from dotenv import load_dotenv

load_dotenv()

class NeuralWorker:
    """
    Main Neural Worker using Redis Queue + MySQL Job tracking
    Follows PYMVC pattern: Service layer handles business logic
    """

    def __init__(self):
        self.queue = QueueService()
        # Optional: You can inject OllamaService and TextProcessor here later
        # self.ollama = OllamaService()
        # self.processor = TextProcessor()

    def run(self):
        """Main worker loop - listens to Redis queue forever"""
        print("🧠 Neural Engine Heartbeat Started...")
        print("📡 Connected to Redis queue. Waiting for jobs...")

        for job_id in self.queue.listen():
            self.process_job(job_id)

    def process_job(self, job_id: str):
        """Process a single job with proper error handling and status updates"""
        print(f"🧬 Starting processing for Job ID: {job_id}")

        conn = None
        try:
            # 1. Update job status to processing
            Job.update_status(job_id, status="processing", progress=10, message="Starting neural processing")

            # 2. Fetch full job details
            job_data = self._get_job(job_id)
            if not job_data:
                raise ValueError(f"Job {job_id} not found or already processed")

            payload = json.loads(job_data['payload'])
            file_path = payload.get('path') or payload.get('file_path')

            if not file_path or not os.path.exists(file_path):
                raise FileNotFoundError(f"File not found: {file_path}")

            # --- STAGE 1: Chunking (25%) ---
            Job.update_status(job_id, status="processing", progress=25, message="Chunking document...")
            chunks = self._chunk_content(file_path)
            print(f"   → Created {len(chunks)} semantic chunks")

            # --- STAGE 2: Embedding (60%) ---
            Job.update_status(job_id, status="processing", progress=60, message="Generating embeddings...")
            embeddings = self._generate_embeddings(chunks)
            print(f"   → Generated {len(embeddings)} embeddings")

            # --- STAGE 3: Indexing (90%) ---
            Job.update_status(job_id, status="processing", progress=90, message="Storing vectors in database...")
            self._store_embeddings(job_id, chunks, embeddings)

            # --- STAGE 4: Complete ---
            Job.update_status(job_id, status="completed", progress=100, message="Job successfully indexed")
            print(f"✅ Job {job_id} completed successfully.")

        except Exception as e:
            print(f"❌ Failed to process job {job_id}: {str(e)}")
            try:
                Job.update_status(job_id, status="failed", progress=0, message=f"Error: {str(e)[:200]}")
            except Exception as inner_e:
                print(f"⚠️ Could not update job status to failed: {inner_e}")
        finally:
            # Optional: close any lingering resources
            if conn and conn.is_connected():
                conn.close()

    def _get_job(self, job_id: str):
        """Helper to fetch job by ID"""
        conn = None
        try:
            conn = Database.get_connection()
            cursor = conn.cursor(dictionary=True)
            cursor.execute("SELECT id, payload FROM jobs WHERE id = %s", (job_id,))
            return cursor.fetchone()
        finally:
            if cursor:
                cursor.close()
            if conn and conn.is_connected():
                conn.close()

    def _chunk_content(self, file_path: str):
        """Placeholder - replace with your TextProcessor logic"""
        # TODO: Integrate src.processor.TextProcessor or your own chunker
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()

        # Simple chunking for now (improve with semantic chunking later)
        chunk_size = 1000
        chunks = [content[i:i+chunk_size] for i in range(0, len(content), chunk_size)]
        return chunks

    def _generate_embeddings(self, chunks: list):
        """Placeholder - integrate with OllamaService"""
        # TODO: Replace with real call to OllamaService.get_embeddings(chunks)
        print("   → [MOCK] Generating embeddings via Ollama...")
        time.sleep(1)  # Simulate work
        return [[0.1] * 768 for _ in chunks]   # Mock 768-dim embeddings (nomic-embed-text size)

    def _store_embeddings(self, job_id: str, chunks: list, embeddings: list):
        """Store vectors in MariaDB"""
        conn = None
        try:
            conn = Database.get_connection()
            cursor = conn.cursor()

            for chunk_text, vector in zip(chunks, embeddings):
                cursor.execute(
                    """
                    INSERT INTO vectors (job_id, content, embedding)
                    VALUES (%s, %s, %s)
                    """,
                    (job_id, chunk_text, json.dumps(vector))
                )
        finally:
            if cursor:
                cursor.close()
            if conn and conn.is_connected():
                conn.close()


# ========================
# Entry Point (for direct running)
# ========================
if __name__ == "__main__":
    worker = NeuralWorker()
    try:
        worker.run()
    except KeyboardInterrupt:
        print("\n🛑 Neural Worker stopped by user.")
    except Exception as e:
        print(f"💥 Critical worker failure: {e}")