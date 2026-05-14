import os
import time
import chromadb
import ollama
from app.utils.Config import Config

class VectorStorageService:
    MODEL_NAME = "jina/jina-embeddings-v2-small-en"
    PERSIST_PATH = "storage/vector_db"
    EXPECTED_DIM = 512

    @staticmethod
    def generate_jina_embedding(text: str, retries=3):
        """Generates a 512-dim embedding with a retry safety net."""
        for attempt in range(retries):
            try:
                response = ollama.embed(
                    model=VectorStorageService.MODEL_NAME,
                    input=text
                )
                embedding = response["embeddings"][0]
                
                # Fast-fail if dimensions mismatch
                if len(embedding) != VectorStorageService.EXPECTED_DIM:
                    raise ValueError(f"Dimension mismatch: Expected {VectorStorageService.EXPECTED_DIM}, got {len(embedding)}")
                
                return embedding
            except Exception as e:
                if attempt == retries - 1:
                    raise e
                time.sleep(2) # Wait for model to potentially load in Ollama

    @staticmethod
    def get_client():
        os.makedirs(VectorStorageService.PERSIST_PATH, exist_ok=True)
        return chromadb.PersistentClient(path=VectorStorageService.PERSIST_PATH)

    @staticmethod
    def store_chunks(job_id, chunks):
        client = VectorStorageService.get_client()
        collection_name = f"job_{job_id}"
        collection = client.get_or_create_collection(name=collection_name)
        
        ids, documents, metadatas, embeddings = [], [], [], []
        
        for chunk in chunks:
            meta = chunk["meta"]
            content = chunk["content"]
            
            ids.append(f"j{job_id}_c{meta['chunk_num']}")
            documents.append(content)
            metadatas.append(meta)
            embeddings.append(VectorStorageService.generate_jina_embedding(content))

        if ids:
            collection.upsert(
                ids=ids,
                embeddings=embeddings,
                metadatas=metadatas,
                documents=documents
            )
            
        return collection_name, len(ids)

    @staticmethod
    def finalize_handshake(job_id, collection_name, count):
        """Notifies PHP that the Neural Path is grounded."""
        from app.controllers.nats_controller import NatsController
        status = f"completed:{collection_name}:{count}"
        NatsController.update_php(job_id, status)
