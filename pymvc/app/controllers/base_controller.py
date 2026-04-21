class BaseController:
    """Handles common response logic and shared utilities."""
    
    @staticmethod
    def json_response(result, success_msg, error_msg):
        if result:
            return f"200 - OK: {success_msg}"
        return f"500 - Error: {error_msg}"