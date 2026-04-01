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
