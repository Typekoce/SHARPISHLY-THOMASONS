import chromadb
import shutil
import os

# Path aligned with the directory verified in our diagnostics
PERSIST_PATH = "/home/seaview/Documents/SHARPISHLY-THOMASONS/pymvc/storage/vector_db"

def reset():
    try:
        # Full directory cleanup ensures no stale metadata remains
        if os.path.exists(PERSIST_PATH):
            shutil.rmtree(PERSIST_PATH)
            print(f"Successfully wiped: {PERSIST_PATH}")
        else:
            print("Directory already clean.")
    except Exception as e:
        print(f"Error during cleanup: {e}")

if __name__ == "__main__":
    reset()