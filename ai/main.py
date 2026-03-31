import os
import threading
from fastapi import FastAPI, BackgroundTasks, HTTPException
from pydantic import BaseModel
from dotenv import load_dotenv
import mysql.connector

# Import your existing work_loop logic
# Assuming your previous script is named worker.py
from worker import work_loop 

load_dotenv()

app = FastAPI(title="Sharpishly Neural Engine")

class IngestRequest(BaseModel):
    file_path: str
    document_id: str
    metadata: dict = {}

def run_worker_daemon():
    """Starts the background polling loop in a separate thread."""
    work_loop()

@app.on_event("startup")
async def startup_event():
    """Ensure the background worker starts when the container boots."""
    thread = threading.Thread(target=run_worker_daemon, daemon=True)
    thread.start()
    print("🧠 Neural Background Worker started via Thread.")

@app.get("/status")
async def get_status():
    """Health check for Nginx/PHP discovery."""
    return {"status": "online", "engine": "Ollama/MariaDB"}

@app.post("/ingest")
async def handle_ingest(request: IngestRequest, background_tasks: BackgroundTasks):
    """
    Receives the 'Handshake' from PHP.
    Since the worker is already polling, we just return 202.
    """
    # Validation: Ensure the file is actually where PHP says it is
    if not os.path.exists(request.file_path):
        raise HTTPException(status_code=404, detail=f"File not found: {request.file_path}")

    return {
        "status": "accepted",
        "document_id": request.document_id,
        "message": "Worker notified. Processing will begin shortly."
    }

if __name__ == "__main__":
    import uvicorn
    # Bound to 0.0.0.0 so PHP can reach it via 'http://ai:8000'
    uvicorn.run(app, host="0.0.0.0", port=8000)