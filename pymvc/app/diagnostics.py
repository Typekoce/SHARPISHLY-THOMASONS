import os
import chromadb

def run_diagnostics():
    print("--- Starting RAG Diagnostics ---")
    
    # Updated to the operational directory
    storage_dir = os.path.abspath("../storage/vector_db")
    print(f"Checking Persistence Directory: {storage_dir}")
    print(f"Directory exists: {os.path.exists(storage_dir)}")
    
    client = chromadb.PersistentClient(path=storage_dir)
    
    # List collections to identify the correct name
    collections = client.list_collections()
    print(f"Collections found: {[c.name for c in collections]}")
    
    if not collections:
        print("Result: No collections found in this directory.")
        return

    # Targeting the first collection found if the name is unknown
    collection = collections[0]
    print(f"Analyzing collection: {collection.name}")
    
    # Presence & Integrity Check
    count = collection.count()
    print(f"Total document count: {count}")
    
    if count > 0:
        peek = collection.peek(limit=1)
        print(f"Peek sample: {peek}")
    else:
        print("CRITICAL: Collection is empty.")

if __name__ == "__main__":
    run_diagnostics()
