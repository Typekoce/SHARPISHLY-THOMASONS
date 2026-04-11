import time
import requests

# The internal Docker URL to your PHP Gateway
PHP_URL = "http://sharpishly-app/php/job" 

def work():
    print("🤖 AI Worker connected. Monitoring queue...")

    while True:
        try:
            # 1. Ask PHP for the latest pending job
            response = requests.get(PHP_URL)
            job_list = response.json()

            if job_list and len(job_list) > 0:
                job = job_list[0]
                job_id = job['id']
                print(f"⚡ Processing Job #{job_id}...")

                # 2. PERFORM THE LOGIC (The "Neural" bit)
                # For now, we simulate the work
                time.sleep(2) 

                # 3. TELL PHP IT'S DONE
                # This ensures the 'status' moves out of pending
                requests.post(f"{PHP_URL}/update/{job_id}", json={"status": "completed"})
                print(f"✅ Job #{job_id} finished.")

        except Exception as e:
            print(f"📡 Waiting for connection... | {e}")
        
        # 5-second heartbeat
        time.sleep(5)

if __name__ == "__main__":
    work()