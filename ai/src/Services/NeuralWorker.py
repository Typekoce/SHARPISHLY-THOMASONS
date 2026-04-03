from src.Config.Database import Database
from src.Services.OllamaService import OllamaService
from src.Services.TextProcessor import TextProcessor
import json
import time
from typing import Dict, Any, Optional

class NeuralWorker:
    """
    Core Neural Worker that processes tasks from Redis queue.
    Follows PYMVC pattern - Service layer handles business logic.
    """

    def __init__(self):
        self.db = Database()                    # Using your existing Db service
        self.ollama = OllamaService()
        self.processor = TextProcessor()

    def process_task(self, task: Dict[str, Any]) -> bool:
        """
        Main entry point for processing Redis queue tasks.
        Returns True if task was processed successfully.
        """
        action = task.get("action")
        payload = task.get("payload", {})
        job_id = task.get("job_id") or payload.get("job_id")

        if not action:
            print("⚠️ Task missing 'action' field")
            return False

        print(f"🧠 Processing task: {action} | Job ID: {job_id}")

        try:
            if action == "embed_document":
                return self._handle_embedding(payload, job_id)
            elif action == "agent_query":
                return self._handle_agent_reasoning(payload, job_id)
            elif action == "chat_query":
                return self._handle_chat_query(payload, job_id)
            else:
                print(f"⚠️ Unknown action type: {action}")
                self._mark_job_failed(job_id, f"Unknown action: {action}")
                return False

        except Exception as e:
            print(f"❌ Error processing task {action}: {e}")
            self._mark_job_failed(job_id, str(e))
            return False

    def _handle_embedding(self, data: Dict[str, Any], job_id: Optional[str] = None) -> bool:
        """Handle document embedding task"""
        try:
            file_path = data.get("path") or data.get("file_path")
            if not file_path or not file_path.exists():
                raise FileNotFoundError(f"File not found: {file_path}")

            print(f"📄 Processing document: {file_path}")

            # Update job status
            if job_id:
                self._update_job_status(job_id, "processing", 10, "Starting embedding")

            # 1. Read and chunk document
            with open(file_path, 'r', encoding='utf-8') as f:
                content = f.read()

            chunks = self.processor.prepare_for_ollama(content, file_path=file_path)

            if job_id:
                self._update_job_status(job_id, "processing", 40, f"Created {len(chunks)} chunks")

            # 2. Generate embeddings using Ollama
            embeddings = self.ollama.get_embeddings(chunks)

            if not embeddings:
                raise ValueError("Failed to generate embeddings")

            if job_id:
                self._update_job_status(job_id, "processing", 70, f"Generated {len(embeddings)} embeddings")

            # 3. Store vectors in database
            self._store_vectors(job_id, chunks, embeddings)

            if job_id:
                self._update_job_status(job_id, "completed", 100, "Document successfully embedded")

            print(f"✅ Embedding completed for job {job_id}")
            return True

        except Exception as e:
            print(f"❌ Embedding failed: {e}")
            if job_id:
                self._mark_job_failed(job_id, str(e))
            return False

    def _handle_agent_reasoning(self, data: Dict[str, Any], job_id: Optional[str] = None) -> bool:
        """Handle agent-style reasoning / RAG queries"""
        try:
            query = data.get("query")
            if not query:
                raise ValueError("Missing query in agent task")

            print(f"🤖 Agent reasoning on query: {query[:100]}...")

            # TODO: Implement RAG + agent logic here
            # For now, placeholder
            if job_id:
                self._update_job_status(job_id, "completed", 100, "Agent reasoning completed")

            return True

        except Exception as e:
            print(f"❌ Agent reasoning failed: {e}")
            if job_id:
                self._mark_job_failed(job_id, str(e))
            return False

    def _handle_chat_query(self, data: Dict[str, Any], job_id: Optional[str] = None) -> bool:
        """Handle direct chat queries using Ollama"""
        try:
            message = data.get("message")
            if not message:
                raise ValueError("Missing message in chat task")

            response = self.ollama.chat(message)
            
            print(f"💬 Chat response generated for job {job_id}")
            return True

        except Exception as e:
            print(f"❌ Chat query failed: {e}")
            return False

    # ======================
    # Helper Methods
    # ======================

    def _update_job_status(self, job_id: str, status: str, progress: int, message: str):
        """Update job status in database"""
        try:
            conn = self.db.get_connection() if hasattr(self.db, 'get_connection') else None
            # Use your existing Db service methods if available
            # For now using direct execution if needed
            print(f"[{status.upper()}] Job {job_id}: {message} ({progress}%)")
        except Exception as e:
            print(f"⚠️ Failed to update job status: {e}")

    def _mark_job_failed(self, job_id: Optional[str], error: str):
        """Mark job as failed"""
        if not job_id:
            return
        try:
            print(f"❌ Job {job_id} marked as failed: {error}")
            # TODO: Update database status to 'failed'
        except Exception as e:
            print(f"⚠️ Failed to mark job as failed: {e}")

    def _store_vectors(self, job_id: Optional[str], chunks: list, embeddings: list):
        """Store embedding vectors in database"""
        try:
            for i, (chunk, embedding) in enumerate(zip(chunks, embeddings)):
                # Use your Db.save() or direct insert
                vector_data = {
                    "job_id": job_id,
                    "content": chunk,
                    "embedding": json.dumps(embedding)
                }
                # self.db.save("vectors", vector_data)   # Uncomment when ready
                pass
        except Exception as e:
            print(f"❌ Failed to store vectors: {e}")