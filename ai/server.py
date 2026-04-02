from fastapi import FastAPI, HTTPException
from src.Controllers.HomeController import HomeController
from dotenv import load_dotenv
import os

# Load Neural Environment
load_dotenv()

app = FastAPI(
    title="Sharpishly PYMVC API", 
    version="1.0",
    description="Neural Orchestration Layer for Thomasons V3"
)

# Controller Injection
# Note: Ensure HomeController handles its own Service/Model instantiation
home_controller = HomeController()

@app.get("/pymvc/home/worker")
async def get_worker_status():
    try:
        return home_controller.get_worker_status()
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@app.get("/health")
@app.get("/pymvc/health")
async def health_check():
    """
    Unified Health Endpoint.
    Can be expanded to return model download percentages.
    """
    return {
        "status": "healthy", 
        "service": "pymvc-ai",
        "environment": os.getenv("APP_ENV", "production")
    }

if __name__ == "__main__":
    import uvicorn
    # Reload=True is great for dev, but we'll use the CMD in Docker for prod
    uvicorn.run("server:app", host="0.0.0.0", port=8000, reload=True)