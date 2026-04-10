import os
import requests

class NeuralPipeline:
    def __init__(self, file_path):
        if not os.path.exists(file_path):
            raise FileNotFoundError(f"File not found: {file_path}")
        self.file_path = file_path
        self.raw_data = ""
        self.chunks = []
        self.vectors = []

    def clean(self):
        with open(self.file_path, 'r', encoding='utf-8', errors='ignore') as f:
            self.raw_data = " ".join(f.read().split())
        return self

    def chunk(self, size=500, overlap=50):
        words = self.raw_data.split()
        for i in range(0, len(words), size - overlap):
            self.chunks.append(" ".join(words[i:i + size]))
        return self

    def vectorize(self):
        for text in self.chunks:
            response = requests.post(
                "http://sharpishly-ollama:11434/api/embeddings",
                json={"model": "nomic-embed-text", "prompt": text},
                timeout=30
            )
            response.raise_for_status()
            self.vectors.append(response.json().get('embedding'))
        return self

    def insert_into_vectordb(self, job_id):
        print(f"💾 Processed {len(self.vectors)} vectors for job {job_id}")
        return True
