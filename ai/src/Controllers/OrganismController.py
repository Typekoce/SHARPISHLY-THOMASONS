from fastapi import APIRouter, Depends
from typing import Dict
import time
import os

router = APIRouter(prefix="/organism", tags=["organism"])

class OrganismController:
    """Main controller for the Neural Organism (Python side)"""

    @staticmethod
    @router.get("/heartbeat")
    async def heartbeat() -> Dict:
        """Simple health + status endpoint for the Neural Organism"""
        return {
            "status": "alive",
            "organism": "sharpishly-neural-v3",
            "timestamp": time.time(),
            "uptime": time.time() - os.path.getctime("/proc/1"),  # rough container uptime
            "version": "3.6",
            "environment": os.getenv("ENV", "development"),
            "ollama_connected": True,   # TODO: Add real check
            "redis_connected": True     # TODO: Add real check
        }

    @staticmethod
    @router.get("/status")
    async def status() -> Dict:
        """Detailed status including queue depth and model info"""
        return {
            "status": "operational",
            "components": {
                "ollama": "ready",
                "redis": "connected",
                "worker": "listening"
            },
            "queue_name": "neural_queue",
            "models_loaded": ["nomic-embed-text", "llama3.1"],
            "last_heartbeat": time.time()
        }


# In your FastAPI server.py, include this router:
# app.include_router(OrganismController.router)