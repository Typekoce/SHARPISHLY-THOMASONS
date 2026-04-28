import os
import json
import requests
from app.views import render_template

class NatsController:
    # The Shared Track
    NATS_PATH = "storage/nats/001_jobs.json"
    PROC_PATH = "storage/nats/processing.json"
    
    # The Communication Line
    PHP_BASE_URL = "http://sharpishly.dev/php/job/update/"

    @staticmethod
    def index():
        job = NatsController.get_job()
        data = {
            "title": "PyMVC Racecar",
            "message": job if job else "Track Clear",
            "status": "Listening for PHP Handshake"
        }
        return render_template("index.html", data)

    @staticmethod
    def get_job():
        """Fast read of the current job state."""
        path = NatsController.PROC_PATH if os.path.exists(NatsController.PROC_PATH) else NatsController.NATS_PATH
        if not os.path.exists(path):
            return None
        try:
            with open(path, 'r') as f:
                return json.load(f)
        except Exception:
            return None

    @staticmethod
    def consume():
        """Atomic Rename: The high-speed ingestion."""
        if os.path.exists(NatsController.NATS_PATH):
            try:
                # Rename is atomic: PHP can no longer see/touch this job
                os.replace(NatsController.NATS_PATH, NatsController.PROC_PATH)
                return NatsController.get_job()
            except OSError:
                pass
        return None

    @staticmethod
    def update_php(job_id, status, error=None):
        """The HTTP Callback: Updates the source of truth on the PHP side."""
        payload = {"status": status, "error_message": error}
        try:
            # We use PUT to match your PHP Controller's update method
            response = requests.put(f"{NatsController.PHP_BASE_URL}{job_id}", json=payload)
            return response.status_code == 200
        except Exception as e:
            print(f"🏎️ Callback failed: {e}")
            return False

    @staticmethod
    def finalize():
        """Clear the track for the next job."""
        if os.path.exists(NatsController.PROC_PATH):
            os.remove(NatsController.PROC_PATH)