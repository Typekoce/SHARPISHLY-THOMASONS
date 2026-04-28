import time
from app.controllers.NatsController import NatsController

def run_worker():
    print("🚀 NATS-Lite Worker Started. Listening for jobs...")
    
    while True:
        # Try to consume a job
        result = NatsController.consume()
        
        if result:
            job_data, file_path = result
            job_id = job_data.get('job_id')
            print(f"📦 Claimed Job #{job_id}. Processing...")
            
            # Simulate AI/Neural Work
            time.sleep(5) 
            
            # Update PHP to 'completed'
            NatsController.update_php(job_id, 'completed')
            
            # Remove the file from 'process'
            NatsController.acknowledge(file_path)
            print(f"✅ Job #{job_id} Completed and Acknowledged.")
        
        time.sleep(2) # Polling interval

if __name__ == "__main__":
    run_worker()