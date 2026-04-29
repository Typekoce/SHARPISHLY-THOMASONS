import os

class Config:
    # Default to the production-like URL, but allow overrides
    PHP_BASE_URL = os.getenv('PHP_API_URL', 'http://sharpishly.dev/php')

    @staticmethod
    def api_url(endpoint):
        """
        Usage: Config.api_url(f"job/payload/{job_id}")
        Result: http://sharpishly.dev/php/job/payload/1
        """
        # Ensure we don't double-slash if the endpoint starts with /
        endpoint = endpoint.lstrip('/')
        return f"{Config.PHP_BASE_URL}/{endpoint}"