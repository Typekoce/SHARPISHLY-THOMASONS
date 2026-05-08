import random
from app.utils.Config import Config

class VectorStorageService:
    @staticmethod
    def store_chunks(job_id, chunks, embedder_func):
        """
        [The Grounding]
        Iterates through chunks, generates embeddings, and persists to the Vector DB.
        """
        # 1. Initialize your Vector DB collection (e.g., Chroma, Qdrant)
        # Using a job-specific collection name for isolation
        collection_name = f"job_{job_id}"
        
        # 2. Iterate and Embed
        stored_count = 0
        for chunk in chunks:
            content = chunk["content"]
            meta = chunk["meta"]
            
            # Generate the vector using the provided embedder function
            # Dimensions should match your model (e.g., 1536)
            vector = embedder_func(content)
            
            # 3. UPSERT to Vector DB
            # Note: Replace this pseudo-code with your specific DB driver logic
            # collection.upsert(
            #     ids=[f"j{job_id}_c{meta['chunk_num']}"],
            #     embeddings=[vector],
            #     documents=[content],
            #     metadatas=[meta]
            # )
            stored_count += 1

        return collection_name, stored_count

    @staticmethod
    def finalize_handshake(job_id, collection_name, count):
        """
        Notifies the PHP layer that the Neural Path is complete.
        """
        from app.controllers.nats_controller import NatsController
        
        # Use the existing callback mechanism to update MariaDB via PHP
        # We send the collection name so PHP knows where to look for future searches
        status = f"completed:{collection_name}:{count}"
        NatsController.update_php(job_id, status)
