#!/usr/bin/env python3
"""
Sharpishly Neural Worker
Polls PHP backend for pending jobs and updates status after processing.
"""

import requests
import time
import sys
import signal
from typing import Optional, Dict

# Configuration
BASE_URL = "http://sharpishly-app/php/job"   # Use service name inside Docker network
POLL_INTERVAL = 5                            # seconds
PROCESSING_TIME = 2                          # mock neural work time

class NeuralWorker:
    def __init__(self):
        self.running = True
        # Register graceful shutdown
        signal.signal(signal.SIGTERM, self.handle_shutdown)
        signal.signal(signal.SIGINT, self.handle_shutdown)

    def handle_shutdown(self, signum, frame):
        print("\n🛑 Shutdown signal received. Stopping worker gracefully...")
        self.running = False

    def fetch_pending_job(self) -> Optional[Dict]:
        """Fetch one pending job from PHP backend."""
        try:
            response = requests.get(f"{BASE_URL}/index", timeout=10)
            response.raise_for_status()
            jobs = response.json()

            if isinstance(jobs, list) and jobs:
                return jobs[0]
            return None

        except requests.RequestException as e:
            print(f"⚠️ Failed to fetch pending jobs: {e}")
            return None
        except Exception as e:
            print(f"⚠️ Unexpected error fetching jobs: {e}")
            return None

    def update_job_status(self, job_id: int, status: str) -> bool:
        """Update job status back to PHP."""
        try:
            payload = {"status": status}
            response = requests.post(
                f"{BASE_URL}/update/{job_id}",
                json=payload,
                timeout=10
            )
            response.raise_for_status()
            return response.status_code == 200

        except requests.RequestException as e:
            print(f"❌ Failed to update job {job_id}: {e}")
            return False
        except Exception as e:
            print(f"❌ Unexpected error updating job {job_id}: {e}")
            return False

    def process_job(self, job: Dict):
        """Mock neural processing + real status update."""
        job_id = job.get('id')
        if not job_id:
            print("⚠️ Received job without ID")
            return

        print(f"⚡ Processing Job #{job_id}...")

        try:
            # --- Mock Neural Processing ---
            # Replace this with real embedding / RAG logic later
            time.sleep(PROCESSING_TIME)
            # --------------------------------

            # Update status back to PHP
            success = self.update_job_status(job_id, "completed")

            if success:
                print(f"✅ Job #{job_id} completed and updated.")
            else:
                print(f"❌ Failed to update status for Job #{job_id}")

        except Exception as e:
            print(f"❌ Error processing Job #{job_id}: {e}")
            self.update_job_status(job_id, "failed")

    def run(self):
        """Main worker loop."""
        print("🧠 Sharpishly Neural Worker started. Polling for pending jobs...")

        while self.running:
            job = self.fetch_pending_job()

            if job:
                self.process_job(job)
            else:
                # No pending jobs
                print("😴 No pending jobs. Sleeping...")

            time.sleep(POLL_INTERVAL)

        print("👋 Neural Worker shutting down.")


if __name__ == "__main__":
    try:
        worker = NeuralWorker()
        worker.run()
    except KeyboardInterrupt:
        print("\n🛑 Worker stopped by user.")
    except Exception as e:
        print(f"💥 Fatal error in worker: {e}")
        sys.exit(1)