#!/usr/bin/env python3
"""
Sharpishly Neural Worker - Vector & Redis Stage
Polls PHP for text payloads, chunks/vectorizes them, and buffers results in Redis.
"""

import requests
import time
import sys
import signal
import os
import json
import redis
from typing import Optional, Dict

from pipeline import NeuralPipeline

# Configuration - Environment-ready for @tardis VM or Docker
BASE_URL = os.getenv('API_URL', 'http://sharpishly-app/php/job')
REDIS_HOST = os.getenv('REDIS_HOST', 'sharpishly-redis')
POLL_INTERVAL = 5 

class NeuralWorker:
    def __init__(self):
        self.running = True
        
        # Initialize Redis Client
        try:
            self.redis_client = redis.Redis(
                host=REDIS_HOST,
                port=6379,
                db=0,
                decode_responses=True # Returns strings instead of bytes
            )
            print(f"📡 Connected to Redis at {REDIS_HOST}")
        except Exception as e:
            print(f"💥 Could not connect to Redis: {e}")
            sys.exit(1)

        # Signal handling for graceful shutdowns
        signal.signal(signal.SIGTERM, self.handle_shutdown)
        signal.signal(signal.SIGINT, self.handle_shutdown)

    def handle_shutdown(self, signum, frame):
        print("\n🛑 Shutdown signal received. Cleaning up...")
        self.running = False

    def fetch_pending_job(self) -> Optional[Dict]:
        """Fetch one pending job from the PHP backend."""
        try:
            response = requests.get(f"{BASE_URL}/index", timeout=10)
            response.raise_for_status()
            jobs = response.json()
            return jobs[0] if isinstance(jobs, list) and jobs else None
        except Exception as e:
            print(f"⚠️ Error polling API: {e}")
            return None

    def update_job_status(self, job_id: int, status: str, error: str = None) -> bool:
        """Standard status updates for failures or non-vector tasks."""
        try:
            payload = {"status": status}
            if error:
                payload["error_message"] = error
            response = requests.post(f"{BASE_URL}/update/{job_id}", json=payload, timeout=10)
            return response.status_code in (200, 204)
        except Exception as e:
            print(f"❌ Status update failed for Job {job_id}: {e}")
            return False

    def finalize_job(self, job_id: int):
        """Signal PHP that the Redis buffer is ready for ingestion."""
        try:
            response = requests.post(
                f"{BASE_URL}/finalize/{job_id}",
                json={"status": "completed", "source": "redis"},
                timeout=15
            )
            response.raise_for_status()
            return True
        except Exception as e:
            print(f"❌ Finalization signal failed for Job {job_id}: {e}")
            return False

    def process_job(self, job: Dict):
        """Path B: Process text directly from payload and buffer vectors in Redis."""
        job_id = job.get('id')
        clean_text = job.get('payload') # The cleaned text from PHP

        if not job_id or not clean_text:
            print(f"⚠️ Job {job_id} missing ID or Payload.")
            return

        print(f"⚡ Vectorizing Job #{job_id}...")

        try:
            # 1. Pipeline Execution
            pipeline = NeuralPipeline(clean_text)
            # Chain: Clean -> Chunk -> Vectorize
            pipeline.clean().chunk().vectorize()

            # 2. Redis Buffering
            redis_key = f"np:job:{job_id}:chunks"
            
            # Clear any stale data for this job ID just in case
            self.redis_client.delete(redis_key)

            for i, (chunk_text, embedding) in enumerate(zip(pipeline.chunks, pipeline.vectors)):
                vector_entry = {
                    "chunk_index": i,
                    "content": chunk_text,
                    "embedding": embedding
                }
                # Push to list for PHP to drain
                self.redis_client.lpush(redis_key, json.dumps(vector_entry))

            # 3. Completion Handshake
            if self.finalize_job(job_id):
                print(f"✅ Job #{job_id} vectors buffered and finalized.")
            else:
                raise Exception("Failed to signal PHP finalizer.")

        except Exception as e:
            error_msg = str(e)
            print(f"❌ Processing failed for Job #{job_id}: {error_msg}")
            self.update_job_status(job_id, "failed", error_msg)

    def run(self):
        print(f"🧠 Neural Worker active. API: {BASE_URL}")
        while self.running:
            job = self.fetch_pending_job()
            if job:
                self.process_job(job)
            else:
                print("😴 Waiting for jobs...")
            time.sleep(POLL_INTERVAL)
        print("👋 Neural Worker offline.")

if __name__ == "__main__":
    try:
        worker = NeuralWorker()
        worker.run()
    except Exception as e:
        print(f"💥 Fatal error: {e}")
        sys.exit(1)