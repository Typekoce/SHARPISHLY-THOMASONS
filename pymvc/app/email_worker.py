import os
import time
import json
import logging
import requests
from watchdog.observers import Observer
from watchdog.events import FileSystemEventHandler

# Configuration
#WATCH_DIR = os.getenv("EMAIL_WATCH_DIR", "/var/www/storage/agents/emails/waiting")
# Assuming the file is in pymvc/app/, we go up two levels to the root
BASE_DIR = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
WATCH_DIR = os.getenv("EMAIL_WATCH_DIR", os.path.join(BASE_DIR, "storage/agents/emails/waiting"))
PHP_ENDPOINT = "http://localhost/php/letterbox/"

logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(message)s')

class EmailHandler(FileSystemEventHandler):
    def on_created(self, event):
        if event.is_directory or not event.src_path.endswith(".json"):
            return

        # Small delay to ensure the file is fully written/renamed
        time.sleep(0.1)
        
        job_id = os.path.basename(event.src_path).replace("job_", "").replace(".json", "")
        self.process_job(job_id)

    def process_job(self, job_id):
        logging.info(f"Processing job: {job_id}")
        try:
            response = requests.get(f"{PHP_ENDPOINT}{job_id}", timeout=10)
            if response.status_code == 200:
                logging.info(f"Job {job_id} processed successfully.")
            else:
                logging.error(f"Job {job_id} failed with status {response.status_code}")
        except Exception as e:
            logging.error(f"Worker connection error: {e}")

if __name__ == "__main__":
    if not os.path.exists(WATCH_DIR):
        os.makedirs(WATCH_DIR, exist_ok=True)

    observer = Observer()
    observer.schedule(EmailHandler(), WATCH_DIR, recursive=False)
    observer.start()
    
    logging.info(f"Worker active. Watching: {WATCH_DIR}")
    try:
        while True:
            time.sleep(1)
    except KeyboardInterrupt:
        observer.stop()
    observer.join()
