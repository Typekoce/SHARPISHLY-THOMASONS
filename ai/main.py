import time
import threading
import json
from fastapi import FastAPI
from models.database import JobModel
from services.neural_pipeline import NeuralPipeline

app = FastAPI(title="Sharpishly Neural Organism")

def worker_loop():
    print("🧠 Neural Worker active...")
    while True:
        try:
            job = JobModel.find_pending()
            if job:
                job_id = job['id']
                payload = json.loads(job['payload'])
                file_path = payload.get('path') or payload.get('file_path')
                
                pipeline = NeuralPipeline(file_path)
                pipeline.clean().chunk().vectorize().insert_into_vectordb(job_id)
                
                JobModel.update_status(job_id, 'completed')
                print(f"✅ Job {job_id} done.")
            
        except Exception as e:
            if 'job_id' in locals():
                JobModel.update_status(job_id, 'failed', str(e))
            print(f"❌ Error: {e}")
        
        time.sleep(5)

@app.on_event("startup")
async def startup_event():
    threading.Thread(target=worker_loop, daemon=True).start()

@app.get("/")
async def index():
    return {"status": "active"}
