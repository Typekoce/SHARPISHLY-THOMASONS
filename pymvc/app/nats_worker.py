import time
import sys
import os
import requests

# Add the 'pymvc' directory to sys.path so 'app' is found
sys.path.append(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

# Corrected Imports: matching the snake_case filenames in your tree
from app.controllers.nats_controller import NatsController

def run_worker():
    print("🚀 NATS-Lite Worker Started. Listening for jobs...")
    
    while True:
        try:
            # 1. Consume an atomic file handshake from the filesystem
            result = NatsController.consume()
            
            if result:
                job_data, file_path = result
                job_id = job_data.get('job_id')
                print(f"📦 Claimed Job #{job_id}. Processing...")
                
                # 2. Update status to 'processing' in MariaDB
                NatsController.update_php(job_id, 'processing')

                # 3. Fetch the raw parsed string payload from the PHP endpoint
                text_content = fetch_payload(job_id)

                if not text_content:
                    print(f"⚠️ No content found for Job #{job_id} via API. Skipping vectorization.")
                    NatsController.update_php(job_id, 'failed')
                    NatsController.acknowledge(file_path)
                    continue

                # 4. Inject raw text into job_data for the vectorizer method
                job_data['extracted_text'] = text_content

                # 5. Generate neural chunk arrays and embeddings
                vector_chunks = NatsController.vectors(job_data)

                # 6. Push chunks to MariaDB via PHP PUT handler and mark 'completed'
                NatsController.update_php(job_id, 'completed', chunks=vector_chunks)
                
                # 7. Complete the NATS file handshake transaction safely
                NatsController.acknowledge(file_path)
                print(f"✅ Job #{job_id} Completed and Acknowledged.")
                
        except Exception as e:
            print(f"⚠️ Worker Error Loop: {e}")
            
        time.sleep(2)

def fetch_payload(job_id: int) -> str | None:
    """
    Queries the PHP MVC framework to fetch the clean string data stored in MariaDB.
    """
    try:
        url = f"http://sharpishly.dev/php/job/payload/{job_id}"
        response = requests.get(url, timeout=5)
        
        if response.status_code == 200:
            data = response.json()
            if data.get('status') == 'success':
                return data.get('payload')
        return None
    except Exception as e:
        print(f"⚠️ Failed to pull payload from PHP Endpoint: {e}")
        return None

if __name__ == "__main__":
    run_worker()
