import requests
import os
import json

class OllamaService:
    """
    Handles internal Docker communication with the Ollama API.
    Uses the 'sharpishly-ollama' DNS name from docker-compose.
    """

    def __init__(self):
        # Use Docker DNS: http://[container_name]:[port]
        self.base_url = os.getenv("OLLAMA_HOST", "http://sharpishly-ollama:11434")
        self.model = os.getenv("EMBED_MODEL", "nomic-embed-text")

    def get_embeddings(self, text_chunks: list) -> list:
        """
        Sends a batch of text chunks to the /api/embed endpoint.
        Returns a list of vector arrays.
        """
        url = f"{self.base_url}/api/embed"
        
        try:
            response = requests.post(
                url,
                json={
                    "model": self.model,
                    "input": text_chunks
                },
                timeout=120  # Neural processing can be slow
            )
            response.raise_for_status()
            
            # Ollama returns {"embeddings": [[0.1, ...], [0.2, ...]]}
            return response.json().get("embeddings", [])

        except requests.exceptions.RequestException as e:
            print(f"Ollama Connection Error: {e}")
            return []

    def generate_response(self, prompt: str, model: str = "llama3") -> str:
        """
        Optional: Direct text generation for chat/summarization.
        """
        url = f"{self.base_url}/api/generate"
        
        try:
            response = requests.post(
                url,
                json={
                    "model": model,
                    "prompt": prompt,
                    "stream": False
                },
                timeout=180
            )
            response.raise_for_status()
            return response.json().get("response", "")

        except requests.exceptions.RequestException as e:
            print(f"Ollama Generation Error: {e}")
            return "Neural engine timeout."