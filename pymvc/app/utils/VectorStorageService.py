import os
import chromadb
from app.utils.Config import Config

class VectorStorageService:
    @staticmethod
    def get_client():
        """
        Initializes a persistent Chroma client.
        Data is grounded in storage/vector_db to survive container restarts.
        """
        persist_path = "storage/vector_db"
        if not os.path.exists(persist_path):
            os.makedirs(persist_path, exist_ok=True)
            
        return chromadb.PersistentClient(path=persist_path)

    @staticmethod
    def store_chunks(job_id, chunks, embedder_func):
        """
        [The Grounding]
        Iterates through chunks, generates embeddings, and persists to ChromaDB.
        """
        client = VectorStorageService.get_client()
        
        # Isolation: Each job gets its own collection
        collection_name = f"job_{job_id}"
        collection = client.get_or_create_collection(name=collection_name)
        
        ids = []
        documents = []
        metadatas = []
        embeddings = []

        for chunk in chunks:
            meta = chunk["meta"]
            content = chunk["content"]
            
            # Prepare batch data
            ids.append(f"j{job_id}_c{meta['chunk_num']}")
            documents.append(content)
            metadatas.append(meta)
            embeddings.append(embedder_func(content))

        # Atomic Upsert to ChromaDB
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
        """
        Notifies the PHP layer that the Neural Path is complete.
        """
        from app.controllers.nats_controller import NatsController
        
        # Grounding the final state in the PHP/MariaDB source of truth
        status = f"completed:{collection_name}:{count}"
        NatsController.update_php(job_id, status)
