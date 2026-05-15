import os
import json
import requests

class NatsController:

    @staticmethod
    def consume():
        """
        Scans the NATS ingest directory for pending job handshakes.
        Moves valid items to 'process' atomically to prevent race conditions.
        """
        base_dir = os.path.abspath(os.path.join(os.path.dirname(__file__), '../../../storage/uploads/nats'))
        ingest_dir = os.path.join(base_dir, 'ingest')
        process_dir = os.path.join(base_dir, 'process')

        if not os.path.exists(ingest_dir):
            return None

        for filename in os.listdir(ingest_dir):
            if filename.endswith('.json'):
                ingest_path = os.path.join(ingest_dir, filename)
                process_path = os.path.join(process_dir, filename)
                
                try:
                    os.rename(ingest_path, process_path)
                    
                    with open(process_path, 'r') as f:
                        job_wrapper = json.load(f)
                    
                    job_data = job_wrapper.get('data', {})
                    job_data['job_id'] = job_wrapper.get('job_id')
                    
                    return job_data, process_path
                except Exception:
                    continue
        return None

    @staticmethod
    def vectors(job_data: dict) -> list:
        """
        Splits raw text strings into sentences/chunks and vectors them via Ollama.
        """
        content_to_parse = job_data.get('extracted_text')
        if not content_to_parse or len(content_to_parse.strip()) == 0:
            return []

        # Minimalist string chunker (~200 character boundary)
        chunks = []
        words = content_to_parse.split(' ')
        current_chunk = []
        current_length = 0

        for word in words:
            current_chunk.append(word)
            current_length += len(word) + 1
            if current_length >= 200:
                chunks.append(" ".join(current_chunk).strip())
                current_chunk = []
                current_length = 0
        if current_chunk:
            chunks.append(" ".join(current_chunk).strip())

        payload_chunks = []
        ollama_url = "http://localhost:11434/api/embeddings"

        for idx, chunk_text in enumerate(chunks):
            if not chunk_text:
                continue
            try:
                response = requests.post(ollama_url, json={
                    "model": "jina/jina-embeddings-v2-small-en",
                    "prompt": chunk_text
                }, timeout=10)
                
                if response.status_code == 200:
                    embedding = response.json().get("embedding", [])
                    payload_chunks.append({
                        "content": chunk_text,
                        "embedding": embedding,
                        "pref": idx + 1
                    })
            except Exception as e:
                print(f"⚠️ Ollama embedding generation failed for chunk {idx}: {e}")
                continue

        return payload_chunks

    @staticmethod
    def update_php(job_id: int, status: str, chunks: list = None):
        """
        Pushes state tracking payloads back up to the main MVC framework via PUT.
        Accommodates optional vector chunks collections seamlessly.
        """
        url = f"http://sharpishly.dev/php/job/update/{job_id}"
        payload = {
            "status": status,
            "chunks": chunks or []
        }
        try:
            headers = {"Content-Type": "application/json"}
            requests.put(url, json=payload, headers=headers, timeout=5)
        except Exception as e:
            print(f"⚠️ Communication breakdown updating PHP state for job {job_id}: {e}")

    @staticmethod
    def acknowledge(file_path: str):
        """
        Cleans up the file system transaction safely.
        """
        try:
            if os.path.exists(file_path):
                os.remove(file_path)
        except Exception as e:
            print(f"⚠️ Failed to acknowledge handshake file context: {e}")
