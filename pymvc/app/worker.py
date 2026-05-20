import time
import sys
import os
import requests

# Add the 'pymvc' directory to sys.path
sys.path.append(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

from app.controllers.job_queue_controller import JobQueueController
from app.utils.ChunkingService import ChunkingService
from app.utils.VectorStorageService import VectorStorageService

def run_worker():
    print("🚀 Worker Started. Listening for filesystem jobs...")
    
    while True:
        try:
            # 1. Consume an atomic file handshake from the filesystem
            result = JobQueueController.consume()
            
            if result:
                job_data, file_path = result
                job_id = job_data.get('job_id')
                print(f"📦 Claimed Job #{job_id}. Fetching footprint...")
                
                # 2. Fetch the raw payload from the PHP endpoint
                text_content = fetch_payload(job_id)

                if not text_content:
                    print(f"⚠️ No content found for Job #{job_id}. Skipping.")
                    JobQueueController.update_php(job_id, 'failed')
                    JobQueueController.fail_job(file_path)
                    continue

                # 3. Transition to processing
                JobQueueController.update_php(job_id, 'processing')

                # 4. Chunking
                chunks = ChunkingService.create_chunks(text_content, job_id)

                if not chunks:
                    JobQueueController.update_php(job_id, 'failed')
                    JobQueueController.fail_job(file_path)
                    continue

                # 5. Persist
                collection_name, count, vector_chunks = VectorStorageService.store_chunks(job_id, chunks)

                # 6. Finalize via PHP PUT
                JobQueueController.update_php(job_id, 'completed', chunks=vector_chunks)
                
                # 7. Complete transaction
                JobQueueController.acknowledge(file_path)
                print(f"✅ Job #{job_id} Completed ({count} vectors).")
                
        except Exception as e:
            print(f"⚠️ Worker Error Loop: {e}")
            
        time.sleep(2)

def fetch_payload(job_id: int) -> str | None:
    try:
        url = f"http://sharpishly.dev/php/job/payload/{job_id}"
        response = requests.get(url, timeout=5)
        if response.status_code == 200:
            # ... (keep existing logic)
            return response.text.strip()
        return None
    except Exception as e:
        print(f"⚠️ Failed to pull payload: {e}")
        return None

if __name__ == "__main__":
    run_worker()
