import time
import sys
import os
import requests

# Add the 'pymvc' directory to sys.path so 'app' is found
sys.path.append(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

# Corrected Imports: matching the snake_case filenames in your tree
from app.controllers.nats_controller import NatsController
from app.utils.ChunkingService import ChunkingService
from app.utils.VectorStorageService import VectorStorageService

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

                # 4. Segment CSV rows using the correct ChunkingService signature
                # Passing text_content and job_id explicitly as required by the method signature
                chunks = ChunkingService.create_chunks(text_content, job_id)

                if not chunks:
                    print(f"⚠️ Chunking returned an empty set for Job #{job_id}. Aborting.")
                    NatsController.update_php(job_id, 'failed')
                    NatsController.acknowledge(file_path)
                    continue

                # 5. Persist chunks inside ChromaDB and compile array payload for MariaDB
                collection_name, count, vector_chunks = VectorStorageService.store_chunks(job_id, chunks)

                # 6. Push chunks to MariaDB via PHP PUT handler and mark 'completed'
                NatsController.update_php(job_id, 'completed', chunks=vector_chunks)
                
                # 7. Complete the NATS file handshake transaction safely
                NatsController.acknowledge(file_path)
                print(f"✅ Job #{job_id} Completed and Acknowledged ({count} vectors mapped).")
                
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
            content_type = response.headers.get('Content-Type', '')
            if 'application/json' in content_type:
                data = response.json()
                if data.get('status') == 'success':
                    return data.get('payload')
            else:
                return response.text.strip()
        return None
    except Exception as e:
        print(f"⚠️ Failed to pull payload from PHP Endpoint: {e}")
        return None

if __name__ == "__main__":
    run_worker()
