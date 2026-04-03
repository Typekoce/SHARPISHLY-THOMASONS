import requests
import os

class OllamaService:
    def __init__(self):
        self.host = os.getenv("Ollama_HOST", "http://sharpishly-ollama:11434")

    def get_embeddings(self, chunks):
        embeddings = []
        for chunk in chunks:
            response = requests.post(
                f"{self.host}/api/embeddings",
                json={"model": "nomic-embed-text", "prompt": chunk}
            )
            embeddings.append(response.json().get("embedding"))
        return embeddings