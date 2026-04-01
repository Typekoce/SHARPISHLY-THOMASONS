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
