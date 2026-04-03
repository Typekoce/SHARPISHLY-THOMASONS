def test_pop_and_decode_job(self):
    # Setup: Manually inject a known JSON string into Redis
    self.redis.lpush('neural_queue', '{"action": "test", "payload": "data"}')
    
    # Test: The service should pop and return a Dict, not a String
    job = self.queue_service.pop_job()
    
    self.assertIsInstance(job, dict)
    self.assertEqual(job['action'], 'test')