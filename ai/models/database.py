import os
import mysql.connector
from mysql.connector import Error

class JobModel:
    """Database model mirroring PHP Db.php logic."""

    @staticmethod
    def get_connection():
        try:
            return mysql.connector.connect(
                host=os.getenv('DB_HOST', 'sharpishly-db'),
                user=os.getenv('DB_USER', 'root'),
                password=os.getenv('DB_PASS', 'sharpishly'),
                database=os.getenv('DB_NAME', 'sharpishly')
            )
        except Error as e:
            print(f"❌ DB Error: {e}")
            raise

    @staticmethod
    def find_pending():
        conn = None
        try:
            conn = JobModel.get_connection()
            cursor = conn.cursor(dictionary=True)
            cursor.execute("SELECT id, payload FROM jobs WHERE status = 'pending' LIMIT 1")
            return cursor.fetchone()
        finally:
            if conn and conn.is_connected():
                conn.close()

    @staticmethod
    def update_status(job_id, status, error=None):
        conn = None
        try:
            conn = JobModel.get_connection()
            cursor = conn.cursor()
            cursor.execute("""
                UPDATE jobs SET status = %s, error_message = %s, updated_at = NOW() WHERE id = %s
            """, (status, error, job_id))
            conn.commit()
        finally:
            if conn and conn.is_connected():
                conn.close()
