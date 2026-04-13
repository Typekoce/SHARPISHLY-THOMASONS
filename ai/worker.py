#!/usr/bin/env python3
"""
Sharpishly Neural Worker - Cleaning Focus
Polls PHP backend for pending jobs and processes text cleaning.
"""

import requests
import time
import sys
import signal
import os
from typing import Optional, Dict

from pipeline import NeuralPipeline   # We'll improve this too

# Configuration
BASE_URL = os.getenv('API_URL', 'http://sharpishly-app/php/job')
POLL_INTERVAL = 5  # seconds


class NeuralWorker:
    def __init__(self):
        self.running = True
        signal.signal(signal.SIGTERM, self.handle_shutdown)
        signal.signal(signal.SIGINT, self.handle_shutdown)

    def handle_shutdown(self, signum, frame):
        print("\n🛑 Shutdown signal received. Stopping worker gracefully...")
        self.running = False

    def fetch_pending_job(self) -> Optional[Dict]:
        """Fetch one pending job from the PHP backend."""
        try:
            response = requests.get(f"{BASE_URL}/index", timeout=10)
            response.raise_for_status()
            jobs = response.json()

            if isinstance(jobs, list) and jobs:
                return jobs[0]
            return None

        except requests.RequestException as e:
            print(f"⚠️ Network error fetching jobs: {e}")
        except Exception as e:
            print(f"⚠️ Unexpected error fetching jobs: {e}")

        return None

    def update_job_status(self, job_id: int, status: str, error: str = None) -> bool:
        """Send status update back to PHP."""
        try:
            payload = {"status": status}
            if error:
                payload["error_message"] = error

            response = requests.post(
                f"{BASE_URL}/update/{job_id}",
                json=payload,
                timeout=10
            )
            response.raise_for_status()
            return response.status_code in (200, 204)

        except requests.RequestException as e:
            print(f"❌ Failed to update job {job_id}: {e}")
        except Exception as e:
            print(f"❌ Unexpected error updating job {job_id}: {e}")

        return False

    def process_job(self, job: Dict):
        """Process cleaning stage for a single job."""
        job_id = job.get('id')
        payload = job.get('payload')

        if not job_id:
            print("⚠️ Received job without ID")
            return

        if not payload:
            print(f"⚠️ Job {job_id} has no payload")
            self.update_job_status(job_id, "failed", "Missing payload")
            return

        print(f"⚡ Processing Job #{job_id} (Cleaning Stage)...")

        try:
            # Pass the raw text directly to the pipeline
            pipeline = NeuralPipeline(payload)
            pipeline.clean()

            final_text = pipeline.get_processed_text()

            if len(final_text.strip()) > 50:   # Arbitrary minimum meaningful length
                success = self.update_job_status(job_id, "completed")
                if success:
                    print(f"✅ Job #{job_id} cleaning completed successfully.")
            else:
                raise ValueError("Cleaned text too short or empty")

        except Exception as e:
            error_msg = str(e)
            print(f"❌ Cleaning failed for Job #{job_id}: {error_msg}")
            self.update_job_status(job_id, "failed", error_msg)

    def run(self):
        """Main worker loop."""
        print(f"🧠 Neural Worker started. Polling {BASE_URL} every {POLL_INTERVAL}s")

        while self.running:
            job = self.fetch_pending_job()

            if job:
                self.process_job(job)
            else:
                print("😴 No pending jobs. Waiting...")

            time.sleep(POLL_INTERVAL)

        print("👋 Neural Worker shutting down.")


if __name__ == "__main__":
    try:
        worker = NeuralWorker()
        worker.run()
    except KeyboardInterrupt:
        print("\n🛑 Worker stopped by user.")
    except Exception as e:
        print(f"💥 Fatal worker error: {e}")
        sys.exit(1)