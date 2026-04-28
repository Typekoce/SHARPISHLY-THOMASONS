import os
import json
import glob
import time
import requests
from app.views import render_template

class NatsController:
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
        url = f"http://sharpishly.dev/php/job/update/{job_id}"
        try:
            requests.put(url, json={"status": status}, timeout=2)
        except Exception as e:
            print(f"PHP Callback Failed: {e}")

    @staticmethod
    def acknowledge(file_path):
        """[The Ack] Job complete. Remove from the processing channel."""
        if os.path.exists(file_path):
            os.remove(file_path)