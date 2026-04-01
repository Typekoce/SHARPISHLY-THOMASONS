#!/usr/bin/env python3
"""
Sharpishly Neural Worker Entry Point
Main entry script for the background neural processing service.
"""

import sys
import time
import signal
from dotenv import load_dotenv

from src.Services.NeuralWorker import NeuralWorker

# Load environment variables at startup
load_dotenv()

def handle_shutdown(signum, frame):
    """Graceful shutdown handler for Docker SIGTERM"""
    print("\n🛑 Received shutdown signal. Stopping Neural Worker gracefully...")
    sys.exit(0)

def main():
    print("🚀 Initializing Sharpishly Neural Worker Service...")
    print("   → Environment loaded")
    print("   → Connecting to Redis queue and MariaDB...")

    worker = None
    try:
        # Register graceful shutdown handlers (important for Docker)
        signal.signal(signal.SIGTERM, handle_shutdown)
        signal.signal(signal.SIGINT, handle_shutdown)

        worker = NeuralWorker()
        print("✅ Neural Worker initialized successfully.")
        print("📡 Starting main processing loop...")

        worker.run()

    except KeyboardInterrupt:
        print("\n🛑 Worker stopped by keyboard interrupt.")
    except Exception as e:
        print(f"💥 Fatal Service Error: {e}")
        if worker:
            try:
                print("Attempting to log critical failure...")
                # Optional: Add final failure logging to DB here if needed
                pass
            except:
                pass
        sys.exit(1)
    finally:
        print("👋 Neural Worker shutting down.")


if __name__ == "__main__":
    main()
