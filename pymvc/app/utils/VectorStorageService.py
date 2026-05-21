import os
import time
import chromadb
import ollama

class VectorStorageService:
    MODEL_NAME = "jina/jina-embeddings-v2-small-en"
    # Hardcoded to the path verified to contain 15 documents
    PERSIST_PATH = "/home/seaview/Documents/SHARPISHLY-THOMASONS/storage/vector_db"
    GLOBAL_COLLECTION = "sharpishly_knowledge_base"
    EXPECTED_DIM = 512

    @staticmethod
    def generate_jina_embedding(text: str, retries=3):
        """Generates a 512-dim embedding with an exponential retry safety net."""
        for attempt in range(retries):
            try:
                response = ollama.embed(
                    model=VectorStorageService.MODEL_NAME,
                    input=text
                )
                embedding = response["embeddings"][0]
                
                if len(embedding) != VectorStorageService.EXPECTED_DIM:
                    raise ValueError(f"Dimension mismatch: Expected {VectorStorageService.EXPECTED_DIM}, got {len(embedding)}")
                
                return embedding
            except Exception as e:
                if attempt == retries - 1:
                    raise e
                time.sleep(2)

    @staticmethod
    def get_client():
        """Ensures the SQLite-backed Chroma database directory exists and spins up client."""
        os.makedirs(VectorStorageService.PERSIST_PATH, exist_ok=True)
        return chromadb.PersistentClient(path=VectorStorageService.PERSIST_PATH)

    @staticmethod
    def store_chunks(job_id: int, processed_chunks: list):
        """
        Persists chunks into a unified Chroma collection space and builds the 
        payload array required for tracking updates in the MariaDB database.
        """
        client = VectorStorageService.get_client()
        collection = client.get_or_create_collection(name=VectorStorageService.GLOBAL_COLLECTION)
        
        ids, documents, metadatas, embeddings = [], [], [], []
        vector_chunks_for_php = []
        
        for idx, chunk in enumerate(processed_chunks):
            # Normalizes data shapes coming out of your ChunkingService structure
            meta = chunk.get("meta", {"chunk_num": idx + 1})
            content = chunk.get("content", chunk)
            
            # Inject identity metrics to support where-clause lookups later
            meta["job_id"] = int(job_id)
            
            chunk_id = f"j{job_id}_c{meta['chunk_num']}"
            embedding_vector = VectorStorageService.generate_jina_embedding(content)
            
            ids.append(chunk_id)
            documents.append(content)
            metadatas.append(meta)
            embeddings.append(embedding_vector)
            
            # Formats item matching your remote MariaDB table column shapes
            vector_chunks_for_php.append({
                "content": content,
                "embedding": embedding_vector,
                "pref": meta['chunk_num']
            })

        if ids:
            collection.upsert(
                ids=ids,
                embeddings=embeddings,
                metadatas=metadatas,
                documents=documents
            )
            
        return VectorStorageService.GLOBAL_COLLECTION, len(ids), vector_chunks_for_php

    @staticmethod
    def query_relevant_context(query_text: str, n_results: int = 3, job_id: int = None) -> list:
        """
        Queries ChromaDB globally, or uses a 'where' filter to scope context 
        strictly to a specific job's source materials.
        """
        client = VectorStorageService.get_client()
        collection = client.get_or_create_collection(name=VectorStorageService.GLOBAL_COLLECTION)
        
        query_vector = VectorStorageService.generate_jina_embedding(query_text)
        
        # Build metadata filters on the fly if scoped by runtime context
        query_kwargs = {
            "query_embeddings": [query_vector],
            "n_results": n_results
        }
        if job_id is not None:
            query_kwargs["where"] = {"job_id": int(job_id)}

        results = collection.query(**query_kwargs)
        
        if results and 'documents' in results and results['documents']:
            return results['documents'][0]
        return []

    @staticmethod
    def finalize_handshake(job_id, collection_name, count):
        """Notifies PHP that the Neural Path is grounded."""
        from app.controllers.nats_controller import NatsController
        status = f"completed:{collection_name}:{count}"
        NatsController.update_php(job_id, status)
