import requests
import os
from src.Config.Database import Database
from src.Services.QueueService import QueueService

class HomeController:
    def __init__(self):
        """
        Initialize Controller with required Services.
        Uses shared QueueService for Redis interaction.
        """
        self.queue = QueueService()
        # Ensure we use the internal Docker network hostname
        self.ollama_host = os.getenv("OLLAMA_HOST", "http://llm:11434")

    def get_worker_status(self):
        """
        Primary endpoint for PHP/PyMVC status reporting.
        Returns queue depth, model readiness, and system metadata.
        """
        try:
            depth = self.queue.get_queue_depth()
            model_status = self._check_models()
            
            return {
                "status": "active",
                "queue_depth": depth,
                "neural_models": model_status,
                "environment": os.getenv("APP_ENV", "production"),
                # Fallback for systems where getlogin() might fail in Docker
                "operator": os.getenv("USER", "sharpishly-system") 
            }
        except Exception as e:
            return {
                "status": "degraded",
                "error": str(e),
                "hint": "Check Redis and Ollama container connectivity."
            }

    def _check_models(self):
        """
        Internal Auditor: Queries the Ollama API to verify local model availability.
        Prevents tasks from firing before the 'Nerves' (Models) are fully downloaded.
        """
        # Define the core stack required for Thomasons V3
        required_models = ["nomic-embed-text", "llama3.1"]
        status = {}
        
        try:
            # Short timeout to prevent FastAPI from hanging if Ollama is booting
            response = requests.get(f"{self.ollama_host}/api/tags", timeout=2)
            
            if response.status_code == 200:
                data = response.json()
                # Extract names from the 'models' list
                local_tags = [m.get('name') for m in data.get('models', [])]
                
                for model in required_models:
                    # Check if the required model string exists in any of the local tags
                    is_present = any(model in tag for tag in local_tags)
                    status[model] = "100% (Ready)" if is_present else "0% (Pending/Downloading)"
            else:
                status["error"] = f"Ollama returned status {response.status_code}"
        except requests.exceptions.RequestException:
            status["error"] = "Ollama Service Offline"
            
        return status