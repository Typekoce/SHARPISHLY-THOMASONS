import requests
import os
import redis
import json
import time
from src.Config.Database import Database
from src.Services.QueueService import QueueService

class RedisController:
    def __init__(self):
        """
        Initialize Controller with required Services.
        Uses shared QueueService for Redis interaction.
        """
        self.queue = QueueService()
        # Ensure we use the internal Docker network hostname
        self.ollama_host = os.getenv("OLLAMA_HOST", "http://llm:11434")
    def test(self):
        # Connect to the Broker
        r = redis.Redis(host='sharpishly-redis', port=6379, db=0)

        print("Neural Worker: Standing by for tasks...")

        while True:
            # BLPOP: Blocks until an item is available (timeout=0 means wait forever)
            # This is the "Observer" heart—no busy-waiting, no high CPU.
            _, message = r.blpop("task_queue")
            
            task = json.loads(message)
            print(f"Processing: {task['action']} for {task['file']}")
            
            # Simulate work (AI/Ollama processing)
            time.sleep(2) 
            
            print("Task Complete. Returning to standby.")