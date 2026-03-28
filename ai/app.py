from fastapi import FastAPI, BackgroundTasks, HTTPException
from pydantic import BaseModel
from typing import Dict, Any
import uvicorn

app = FastAPI(title="Neural Organism API", version="2026.03")

class IngestRequest(BaseModel):
    file_path: str
    document_id: str
    metadata: Dict[str, Any] = {}

@app.post("/ingest")
async def ingest_document(req: IngestRequest, background_tasks: BackgroundTasks):
    """
    Catches the PHP Handshake and offloads to the background processor.
    """
    # Trigger the heavy lifting in processor.py
    # background_tasks.add_task(your_processor_function, req.file_path, req.document_id)
    
    return {
        "status": "accepted",
        "document_id": req.document_id,
        "message": "Document handed off to Neural Pipeline"
    }

if __name__ == "__main__":
    uvicorn.run(app, host="0.0.0.0", port=8000)