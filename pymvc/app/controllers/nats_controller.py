import os
import json
from app.views import render_template

class NatsController:
    NATS_PATH = "storage/nats/001_jobs.json"
    @staticmethod
    def index():
        current_job = NatsController.get_job()
        data = {
            "title": "PyMVC Home",
            "message": current_job if current_job else "Welcome to your Python MVC project!",
            "status": "Running successfully"
        }
        return render_template("index.html", data)
    
    @staticmethod
    def get_job():
        """
        Polls the filesystem for the handshake file dropped by PHP.
        """
        if not os.path.exists(NatsController.NATS_PATH):
            return None

        try:
            with open(NatsController.NATS_PATH, 'r') as f:
                return json.load(f)
        except (json.JSONDecodeError, IOError) as e:
            # Silent fail for the UI; logging for the console
            print(f"Error reading job: {e}")
            return None