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
