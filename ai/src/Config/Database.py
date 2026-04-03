import os
import redis
from dotenv import load_dotenv
from mysql.connector import pooling, Error
from contextlib import contextmanager

load_dotenv()

class Database:
    _pool = None

    @classmethod
    def get_pool(cls):
        """Initialize the Singleton MySQL Connection Pool."""
        if cls._pool is None:
            try:
                cls._pool = pooling.MySQLConnectionPool(
                    pool_name="pymvc_pool",
                    pool_size=5,  # Allows up to 5 concurrent AI tasks
                    pool_reset_session=True,
                    host=os.getenv("DB_HOST", "db"), # Matches service key
                    user=os.getenv("DB_USER", "root"),
                    password=os.getenv("DB_PASS", "root_password"),
                    database=os.getenv("DB_NAME", "sharpishly"),
                    port=int(os.getenv("DB_PORT", 3306)),
                    charset="utf8mb4",
                    collation="utf8mb4_unicode_ci"
                )
                print("✅ MySQL Connection Pool initialized.")
            except Error as err:
                print(f"❌ Database pool creation failed: {err}")
                raise
        return cls._pool

    @classmethod
    def get_connection(cls):
        """Fetch a connection from the pool."""
        try:
            return cls.get_pool().get_connection()
        except Error as err:
            print(f"❌ Failed to get DB connection from pool: {err}")
            raise

    @staticmethod
    @contextmanager
    def get_cursor(dictionary=True):
        """
        Senior Utility: Context manager for safe DB operations.
        Usage: with Database.get_cursor() as cursor: cursor.execute(...)
        """
        connection = Database.get_connection()
        cursor = connection.cursor(dictionary=dictionary)
        try:
            yield cursor
            connection.commit()
        except Error as e:
            connection.rollback()
            raise e
        finally:
            cursor.close()
            connection.close() # Returns connection to the pool

    @staticmethod
    def get_redis_client():
        """CNS Connection for the Neural Listener."""
        return redis.Redis(
            host=os.getenv("REDIS_HOST", "redis"),
            port=6379,
            decode_responses=True
        )