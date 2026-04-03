#!/usr/bin/env python3
"""
Sharpishly Neural Redis Listener
Listens to Redis queue and hands off tasks to NeuralWorker
"""

import json
import time
import signal
import sys
from typing import Optional

from src.Config.Database import Database
from src.Services.NeuralWorker import NeuralWorker

class NeuralListener:
    def __init__(self):
        self.queue_name = "neural_queue"
        self.redis_client = None
        self.worker = NeuralWorker()
        self.running = True

    def setup_redis(self, retries: int = 10, delay: int = 3) -> bool:
        """
        Initialize Redis connection with backoff retry logic.
        Ensures stability during Docker orchestration cold-boots.
        """
        attempt = 0
        while attempt < retries:
            try:
                self.redis_client = Database.get_redis_client()
                # The .ping() is the definitive health check
                self.redis_client.ping()
                print(f"✅ Connected to Redis on attempt {attempt + 1}. Listening on: {self.queue_name}")
                return True
            except Exception as e:
                attempt += 1
                if attempt < retries:
                    print(f"⚠️ Redis connection attempt {attempt}/{retries} failed. Retrying in {delay}s...")
                    time.sleep(delay)
                else:
                    print(f"❌ Final connection attempt failed: {e}")
        return False

    def handle_shutdown(self, signum, frame):
        """Graceful shutdown on SIGTERM/SIGINT (Docker stop / Ctrl+C)"""
        print("\n🛑 Shutdown signal received. Draining listener...")
        self.running = False

    def start(self):
        """Main listener loop using BRPOP for non-blocking orchestration"""
        print("🧬 Sharpishly Neural Listener Initializing...")

        # Register signal handlers for clean container exits
        signal.signal(signal.SIGTERM, self.handle_shutdown)
        signal.signal(signal.SIGINT, self.handle_shutdown)

        # Setup Redis with resilience
        if not self.setup_redis():
            print("💥 Critical Failure: Could not establish Redis CNS. Exiting.")
            sys.exit(1)

        print(f"📡 Neural Link Established. Mode: BRPOP (Timeout: 5s)")

        while self.running:
            try:
                # BRPOP blocks until a message arrives OR timeout occurs
                # Timeout allows the loop to check 'self.running' status
                result = self.redis_client.brpop(self.queue_name, timeout=5)

                if result is None:
                    # Heartbeat: Just a loop cycle with no task
                    continue

                _, raw_payload = result
                # Decode bytes to string before parsing JSON
                payload_str = raw_payload.decode('utf-8')
                task = json.loads(payload_str)

                print(f"📥 Signal → Action: {task.get('action', 'N/A')} | Job: {task.get('job_id', 'N/A')}")

                # Dispatch to Service Layer
                self.worker.process_task(task)

            except json.JSONDecodeError as e:
                print(f"❌ Corrupt Signal (JSON Error): {e}")
            except Exception as e:
                print(f"❌ Listener runtime error: {e}")
                time.sleep(2)  # Avoid CPU spikes if a service dependency flickers

        print("👋 Neural Listener safely dormant.")


if __name__ == "__main__":
    listener = NeuralListener()
    try:
        listener.start()
    except KeyboardInterrupt:
        print("\n🛑 Execution interrupted by developer.")
    except Exception as e:
        print(f"💥 Fatal error in Organism: {e}")
        sys.exit(1)