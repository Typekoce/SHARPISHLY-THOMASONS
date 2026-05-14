import os
import json
import glob
import time
import requests
from app.views import render_template
from app.utils.Config import Config
# Grounded Imports for Neural Path
from app.utils.VectorStorageService import VectorStorageService
from app.models.neural_model import NeuralModel

class NatsController:
    @staticmethod
    def vectors(job_data):
        """
        Encapsulated Vectorization:
        Extracts content from job_data and generates a 512-dim vector via Jina.
        """
        job_id = job_data.get('job_id')
        # Extract content (assumes 'payload' or 'description' based on your MVC context)
        content = job_data.get('content') or job_data.get('payload', '')

        if not content:
            print(f"⚠️ No content for Job #{job_id}. Skipping vectorization.")
            return False

        try:
            # 1. Generate Embedding via Jina-Small (30MB) verified on seaview
            service = VectorStorageService()
            embedding = service.generate_embedding(content)

            if embedding:
                # 2. Persist to MariaDB via the NeuralModel (No raw SQL)
                model = NeuralModel()
                return model.save_vector(job_id, 'job', embedding)
        except Exception as e:
            print(f"⚠️ Neural Bridge Error: {e}")
            return False
        
        return False

    # Defining 'Subjects' as Directory Channels
    # PHP writes to 'ingest', Python works in 'process'
    BASE_DIR = "storage/uploads/nats"
    CHANNELS = {
        "ingest": f"{BASE_DIR}/ingest",
        "process": f"{BASE_DIR}/process",
        "results": f"{BASE_DIR}/results"
    }

    @staticmethod
    def index():
        """UI Dashboard: Shows what's currently in the engine."""
        current_job = NatsController.get_job()
        data = {
            "title": "PyMVC NATS-Lite",
            "message": current_job if current_job else "Queue Empty - Waiting for PHP...",
            "status": "Listening on subjects: " + ", ".join(NatsController.CHANNELS.keys())
        }
        return render_template("index.html", data)

    @staticmethod
    def get_job():
        """Peek at the current job in the processing channel."""
        files = glob.glob(f"{NatsController.CHANNELS['process']}/*.json")
        if not files:
            # If nothing is processing, peek at the next in ingest
            files = glob.glob(f"{NatsController.CHANNELS['ingest']}/*.json")
        
        if not files: return None

        try:
            with open(files[0], 'r') as f:
                return json.load(f)
        except Exception:
            return None

    @staticmethod
    def subscribe():
        """
        [The Consumer] Moves a job from 'ingest' to 'process'.
        This is the Atomic Handover.
        """
        # 1. Look for the oldest job in the ingest channel
        files = glob.glob(f"{NatsController.CHANNELS['ingest']}/*.json")
        if not files:
            return None
        
        files.sort(key=os.path.getmtime) # FIFO
        source_path = files[0]
        job_name = os.path.basename(source_path)
        dest_path = os.path.join(NatsController.CHANNELS['process'], job_name)

        try:
            # Atomic Rename: No other worker can grab this job
            os.replace(source_path, dest_path)
            with open(dest_path, 'r') as f:
                return json.load(f), dest_path
        except Exception as e:
            print(f"Subscription Error: {e}")
            return None

    @staticmethod
    def update_php(job_id, status):
        """Standard HTTP Callback to update the source of truth."""
        url = Config.api_url(f"job/update/{job_id}")
        try:
            requests.put(url, json={"status": status}, timeout=2)
        except Exception as e:
            print(f"PHP Callback Failed: {e}")

    @staticmethod
    def acknowledge(file_path):
        """[The Ack] Job complete. Remove from the processing channel."""
        if os.path.exists(file_path):
            os.remove(file_path)

    @staticmethod
    def consume():
        """
        The orchestrator: Subscribes to a job and notifies the source of truth.
        """
        result = NatsController.subscribe()
        
        if result:
            job_data, file_path = result
            job_id = job_data.get('job_id')
            
            # THE CRITICAL LINK: 
            # Notify PHP/MariaDB that the worker has claimed this job.
            NatsController.update_php(job_id, 'processing')
            
            # Return the data for the PyMVC runner to display
            return job_data, file_path
            
        return None

    def get_payload(job_id):
        """
        Fetches the job data from the PHP API.
        No SQL, no DB connections, just a simple API call.
        """
        url = Config.api_url(f"job/payload/{job_id}")
        try:
            response = requests.get(url, timeout=5)
            if response.status_code == 200:
                # This is your raw data (CSV text, PDF bytes, etc.)
                return response.content 
            else:
                print(f"❌ Failed to fetch payload: {response.status_code}")
                return None
        except Exception as e:
            print(f"❌ Connection Error: {e}")
            return None
    @staticmethod
    def embeddings(job_id):
        """
        Phase 2: Transition from raw payload to vector-ready chunks.
        """
        # 1. Update PHP that we are starting the Neural work
        NatsController.update_php(job_id, 'processing_embeddings')

        # 2. Fetch the raw CSV/Text from the PHP API
        payload = NatsController.get_payload(job_id)
        if not payload:
            NatsController.update_php(job_id, 'failed_payload_fetch')
            return None

        # 3. Hand over to the ChunkingService (to be implemented in utils)
        # We don't do the work here; we orchestrate it.
        from app.utils.ChunkingService import ChunkingService
        chunker = ChunkingService()
        chunks = chunker.create_chunks(payload)

        return chunks

    @staticmethod
    def vectorstorage(job_id, collection_name, vector_count):
        """
        Phase 3: Finalize the Vector DB link and signal completion.
        """
        # 1. Final status update to PHP
        # This signals Path B in the PHP router to mark the job as 'completed'
        status = f"vectorized:{collection_name}:{vector_count}"
        NatsController.update_php(job_id, status)
        
        print(f"✅ Job {job_id} grounded in Vector DB: {collection_name}")


    @staticmethod
    def process_neural_path(job_id):
        """
        The full NP surgery: Ingest -> Chunk -> Embed -> Store -> Notify
        """
        # 1. Chunking (using the updated row-aware service)
        from app.utils.ChunkingService import ChunkingService
        payload = NatsController.get_payload(job_id)
        chunks = ChunkingService.create_chunks(payload, job_id)
        
        # 2. Storage & Vectorization
        from app.utils.VectorStorageService import VectorStorageService
        # Passing mock_vector as the embedder for now
        coll, count = VectorStorageService.store_chunks(
            job_id, 
            chunks, 
            ChunkingService.mock_vector
        )
        
        # 3. Grounding Handshake
        VectorStorageService.finalize_handshake(job_id, coll, count)
        
        # 4. Acknowledge and cleanup 'process' folder
        # NatsController.acknowledge(file_path)
