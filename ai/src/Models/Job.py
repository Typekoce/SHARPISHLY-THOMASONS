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
