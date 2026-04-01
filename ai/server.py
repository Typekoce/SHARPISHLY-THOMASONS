from fastapi import FastAPI
from src.Controllers.HomeController import HomeController
from dotenv import load_dotenv

load_dotenv()

app = FastAPI(title="Sharpishly PYMVC API", version="1.0")

# Dependency
home_controller = HomeController()

@app.get("/pymvc/home/worker")
async def get_worker_status():
    return home_controller.get_worker_status()

@app.get("/health")
async def health_check():
    return {"status": "healthy", "service": "pymvc-ai"}

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000, reload=True)
