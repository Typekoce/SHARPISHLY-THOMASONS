#!/bin/bash
set -e  # Exit immediately if any command fails

AI_DIR="./ai"
SRC_DIR="$AI_DIR/src"

echo "🚀 Generating Clean PYMVC Architecture (FastAPI + MySQL Pool + Redis)..."
echo "================================================================"

# 1. Create Directory Structure with proper Python packages
mkdir -p "$SRC_DIR/Controllers" \
         "$SRC_DIR/Models" \
         "$SRC_DIR/Services" \
         "$SRC_DIR/Config"

# Create __init__.py files so Python recognizes packages
touch "$SRC_DIR/__init__.py"
touch "$SRC_DIR/Controllers/__init__.py"
touch "$SRC_DIR/Models/__init__.py"
touch "$SRC_DIR/Services/__init__.py"
touch "$SRC_DIR/Config/__init__.py"

# 2. Database Config (Improved pooling + error handling + charset)
cat <<'EOF' > "$SRC_DIR/Config/Database.py"
import os
from dotenv import load_dotenv
from mysql.connector import pooling, Error

load_dotenv()

class Database:
    _pool = None

    @classmethod
    def get_pool(cls):
        if cls._pool is None:
            try:
                cls._pool = pooling.MySQLConnectionPool(
                    pool_name="pymvc_pool",
                    pool_size=5,
                    pool_reset_session=True,
                    host=os.getenv("DB_HOST", "sharpishly-db"),
                    user=os.getenv("DB_USER", "root"),
                    password=os.getenv("DB_PASS"),
                    database=os.getenv("DB_NAME", "sharpishly"),
                    port=int(os.getenv("DB_PORT", 3306)),
                    charset="utf8mb4",
                    collation="utf8mb4_unicode_ci",
                    autocommit=True
                )
                print("✅ Database connection pool initialized.")
            except Error as err:
                print(f"❌ Database pool creation failed: {err}")
                raise
        return cls._pool

    @classmethod
    def get_connection(cls):
        try:
            return cls.get_pool().get_connection()
        except Error as err:
            print(f"❌ Failed to get DB connection: {err}")
            raise
EOF

# 3. Job Model (Fixed resource management + better error handling)
cat <<'EOF' > "$SRC_DIR/Models/Job.py"
from src.Config.Database import Database

class Job:
    @staticmethod
    def get_pending():
        conn = None
        cursor = None
        try:
            conn = Database.get_connection()
            cursor = conn.cursor(dictionary=True)
            cursor.execute("""
                SELECT id, payload 
                FROM jobs 
                WHERE status = 'pending' 
                LIMIT 1 FOR UPDATE
            """)
            return cursor.fetchone()
        finally:
            if cursor:
                cursor.close()
            if conn and conn.is_connected():
                conn.close()

    @staticmethod
    def update_status(job_id, status, progress=0, message=None):
        conn = None
        cursor = None
        try:
            conn = Database.get_connection()
            cursor = conn.cursor()
            if message:
                cursor.execute("""
                    UPDATE jobs 
                    SET status=%s, progress=%s, current_step=%s 
                    WHERE id=%s
                """, (status, progress, message, job_id))
            else:
                cursor.execute("""
                    UPDATE jobs 
                    SET status=%s, progress=%s 
                    WHERE id=%s
                """, (status, progress, job_id))
        finally:
            if cursor:
                cursor.close()
            if conn and conn.is_connected():
                conn.close()
EOF

# 4. QueueService (Redis) - Optional but clean
cat <<'EOF' > "$SRC_DIR/Services/QueueService.py"
import os
import redis
from dotenv import load_dotenv

load_dotenv()

class QueueService:
    def __init__(self):
        self.r = redis.Redis(
            host=os.getenv('REDIS_HOST', 'sharpishly-redis'),
            port=6379,
            db=0,
            decode_responses=True
        )
        self.queue_name = "inventory_queue"

    def listen(self):
        print(f"📡 Listening on Redis queue: {self.queue_name}")
        while True:
            _, job_id = self.r.blpop(self.queue_name, timeout=30)
            if job_id:
                yield job_id

    def get_queue_depth(self):
        return self.r.llen(self.queue_name)
EOF

# 5. HomeController (Clean & FastAPI friendly)
cat <<'EOF' > "$SRC_DIR/Controllers/HomeController.py"
from src.Services.QueueService import QueueService

class HomeController:
    def __init__(self):
        self.queue = QueueService()

    def get_worker_status(self):
        """Returns current worker status and queue depth."""
        depth = self.queue.get_queue_depth()
        return {
            "status": "active",
            "controller": "HomeController",
            "method": "get_worker_status",
            "queue_depth": depth,
            "message": "PYMVC worker is running"
        }
EOF

# 6. FastAPI Entry Point (server.py) - Improved
cat <<'EOF' > "$AI_DIR/server.py"
from fastapi import FastAPI
from src.Controllers.HomeController import HomeController
from dotenv import load_dotenv

load_dotenv()

app = FastAPI(title="Sharpishly PYMVC API", version="1.0")

# Dependency
home_controller = HomeController()

@app.get("/pymvc/home/worker")
async def get_worker_status():
    return home_controller.get_worker_status()

@app.get("/health")
async def health_check():
    return {"status": "healthy", "service": "pymvc-ai"}

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000, reload=True)
EOF

# 7. requirements.txt for the AI service (updated)
cat <<'EOF' > "$AI_DIR/requirements.txt"
fastapi
uvicorn[standard]
python-dotenv
mysql-connector-python
redis
EOF

echo "✅ PYMVC Architecture Generated Successfully!"
echo ""
echo "Next steps:"
echo "   1. cd ai && pip install -r requirements.txt"
echo "   2. Add Redis service to your docker-compose.yml if using QueueService"
echo "   3. Run with: uvicorn ai.server:app --reload   (or via Docker)"
echo "   4. Test endpoint: http://localhost:8000/pymvc/home/worker"
echo ""
echo "Structure created under ./ai/src/"