# A simple ingestion script (e.g., ingest_test.py)
import chromadb
import os

# Use the same path as your diagnostics
storage_dir = os.path.abspath("../storage/vector_db")
client = chromadb.PersistentClient(path=storage_dir)
collection = client.get_or_create_collection("sharpishly_knowledge_base")

# Add a test document
collection.add(
    documents=["Charles Xavier is a fictional character and the founder of the X-Men."],
    ids=["id1"]
)

print("Document ingested successfully.")