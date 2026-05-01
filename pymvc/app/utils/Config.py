import os

class Config:
    # Existing URL logic
    PHP_BASE_URL = os.getenv('PHP_API_URL', 'http://sharpishly.dev/php')

    # Path logic for the 1GB-friendly filesystem bridge
    # Assumes storage is at the project root
    BASE_DIR = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
    VECTOR_STORAGE = os.path.join(BASE_DIR, 'storage', 'vectors')

    @staticmethod
    def api_url(endpoint):
        endpoint = endpoint.lstrip('/')
        return f"{Config.PHP_BASE_URL}/{endpoint}"

    @staticmethod
    def get_vector_path(filename=""):
        """Usage: Config.get_vector_path(f"job_{id}.json")"""
        return os.path.join(Config.VECTOR_STORAGE, filename)