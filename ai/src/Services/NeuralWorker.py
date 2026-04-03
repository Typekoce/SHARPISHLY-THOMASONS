import json
import time
from typing import Dict, Any, Optional
from pathlib import Path

from src.Config.Database import Database
from src.Services.OllamaService import OllamaService
from src.Services.TextProcessor import TextProcessor

class NeuralWorker:
    """
    Core Neural Worker that processes tasks from Redis queue.
    Service layer handles business logic, database updates, and AI orchestration.
    """

    def __init__(self):
        # Service dependencies
        self.ollama = OllamaService()
        self.processor = TextProcessor()

    def process_task(self, task: Dict[str, Any]) -> bool:
        """Main entry point for processing Redis queue tasks."""
        action = task.get("action")
        payload = task.get("payload", {})
        job_id = task.get("job_id") or payload.get("job_id")

        if not action:
            print("⚠️ Task missing 'action' field")
            return False

        print(f"🧠 Processing: {action} | Job: {job_id}")

        try:
            if action == "embed_document":
                return self._handle_embedding(payload, job_id)
            elif action == "agent_query":
                return self._handle_agent_reasoning(payload, job_id)
            elif action == "chat_query":
                return self._handle_chat_query(payload, job_id)
            else:
                print(f"⚠️ Unknown action: {action}")
                self._mark_job_failed(job_id, f"Unknown action: {action}")
                return False

        except Exception as e:
            print(f"❌ Critical Worker Error: {e}")
            self._mark_job_failed(job_id, str(e))
            return False

    def _handle_embedding(self, data: Dict[str, Any], job_id: Optional[str] = None) -> bool:
        """Handle document embedding task with state tracking."""
        try:
            raw_path = data.get("path") or data.get("file_path")
            if not raw_path:
                raise ValueError("No file path provided in payload.")
            
            file_path = Path(raw_path)
            if not file_path.exists():
                raise FileNotFoundError(f"File not found: {file_path}")

            self._update_job_status(job_id, "processing", 10, "Reading document")

            with open(file_path, 'r', encoding='utf-8') as f:
                content = f.read()

            # 1. Chunking
            chunks = self.processor.prepare_for_ollama(content)
            self._update_job_status(job_id, "processing", 40, f"Chunked into {len(chunks)} segments")

            # 2. Embedding
            embeddings = self.ollama.get_embeddings(chunks)
            if not embeddings:
                raise ValueError("Ollama failed to return embeddings.")
            
            self._update_job_status(job_id, "processing", 70, "Storing vectors")

            # 3. Persistence
            self._store_vectors(job_id, chunks, embeddings)
            self._update_job_status(job_id, "completed", 100, "Embedding complete")

            return True

        except Exception as e:
            self._mark_job_failed(job_id, str(e))
            return False

    # ======================
    # Database Integration (Senior Pattern)
    # ======================

    def _update_job_status(self, job_id: str, status: str, progress: int, message: str):
        """Updates the MySQL jobs table using the connection pool."""
        if not job_id: return
        
        try:
            with Database.get_cursor() as cursor:
                sql = """
                    UPDATE jobs 
                    SET status = %s, progress = %s, status_message = %s, updated_at = NOW() 
                    WHERE id = %s
                """
                cursor.execute(sql, (status, progress, message, job_id))
            print(f"📈 Job {job_id} updated: {progress}% - {message}")
        except Exception as e:
            print(f"⚠️ Status Update Failed: {e}")

    def _mark_job_failed(self, job_id: Optional[str], error: str):
        """Records failure in the database."""
        if not job_id: return
        self._update_job_status(job_id, "failed", 0, f"Error: {error[:255]}")

    def _store_vectors(self, job_id: str, chunks: list, embeddings: list):
        """Stores high-dimensional vectors in the database."""
        try:
            with Database.get_cursor() as cursor:
                sql = "INSERT INTO vectors (job_id, content, embedding) VALUES (%s, %s, %s)"
                # Zip chunks and embeddings to iterate together
                vector_data = [
                    (job_id, chunk, json.dumps(emb)) 
                    for chunk, emb in zip(chunks, embeddings)
                ]
                cursor.executemany(sql, vector_data)
            print(f"💾 {len(chunks)} vectors persisted for Job {job_id}")
        except Exception as e:
            print(f"❌ Vector Storage Failed: {e}")
            raise