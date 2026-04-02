import requests
import os

class HomeController:
    def __init__(self):
        self.queue = QueueService()
        self.ollama_host = os.getenv("OLLAMA_HOST", "http://sharpishly-ollama:11434")

    def get_worker_status(self):
        """Returns current worker status, queue depth, and model readiness."""
        depth = self.queue.get_queue_depth()
        model_status = self._check_models()
        
        return {
            "status": "active",
            "queue_depth": depth,
            "neural_models": model_status,
            "timestamp": os.getlogin() if hasattr(os, 'getlogin') else "system"
        }

    def _check_models(self):
        """Internal helper to ping Ollama for model availability."""
        required = ["nomic-embed-text", "llama3.1"]
        status = {}
        try:
            response = requests.get(f"{self.ollama_host}/api/tags", timeout=2)
            if response.status_code == 200:
                local_models = [m['name'] for m in response.json().get('models', [])]
                for model in required:
                    # If model exists in tags, it's 100% downloaded
                    status[model] = "100%" if any(model in m for m in local_models) else "0% (Pending)"
            else:
                status["error"] = "Ollama API Unreachable"
        except Exception:
            status["error"] = "Service Offline"
            
        return status