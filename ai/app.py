import os
import uvicorn
from fastapi import FastAPI, BackgroundTasks, HTTPException
from pydantic import BaseModel
from typing import Dict, Any

# Import the logic from your merged worker
from worker import run_neural_pipeline 

app = FastAPI(title="Neural Organism API", version="2026.03")

class IngestRequest(BaseModel):
    file_path: str
    document_id: str
    job_id: int  # Added to track the MariaDB job record
    metadata: Dict[str, Any] = {}

@app.post("/ingest")
async def ingest_document(req: IngestRequest, background_tasks: BackgroundTasks):
    """
    Catches the PHP Handshake and offloads to the background processor.
    """
    # Validate file existence before accepting
    if not os.path.exists(req.file_path):
        raise HTTPException(status_code=404, detail=f"File not found: {req.file_path}")

    # Offload to the worker.py logic
    background_tasks.add_task(
        run_neural_pipeline, 
        req.file_path, 
        req.document_id, 
        req.job_id
    )
    
    return {
        "status": "accepted",
        "document_id": req.document_id,
        "job_id": req.job_id,
        "message": "Neural Pipeline initiated in background."
    }

if __name__ == "__main__":
    # Pull port from env to match EnvironmentService.php expectations
    port = int(os.getenv("AI_PORT", 8000))
    uvicorn.run(app, host="0.0.0.0", port=port)