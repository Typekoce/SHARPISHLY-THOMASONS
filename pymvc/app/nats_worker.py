import time
import sys
import os

# Add the 'pymvc' directory to sys.path so 'app' is found
sys.path.append(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

# Corrected Imports: matching the snake_case filenames in your tree
from app.controllers.nats_controller import NatsController

def run_worker():
    print("🚀 NATS-Lite Worker Started. Listening for jobs...")
    
    while True:
        try:
            # Try to consume a job
            result = NatsController.consume()
            
            if result:
                job_data, file_path = result
                job_id = job_data.get('job_id')
                print(f"📦 Claimed Job #{job_id}. Processing...")
                
                # Simulate AI/Neural Work
                vector = NatsController.vectors(job_data)

                if vector:
                    print(f"   🧠 Vector generated ({len(vector)} dimensions).")
                
                # Update PHP to 'completed'
                NatsController.update_php(job_id, 'completed')
                
                # Remove the file from 'process'
                NatsController.acknowledge(file_path)
                print(f"✅ Job #{job_id} Completed and Acknowledged.")
        except Exception as e:
            print(f"⚠️ Error: {e}")
            
        time.sleep(2)

if __name__ == "__main__":
    run_worker()
