<?php

public function testPushJobToRedisQueue()
{
    $service = new QueueService();
    $data = ['action' => 'process_text', 'payload' => 'Hello AI'];
    
    // The unit test verifies the push returns the new length of the list (int)
    $result = $service->pushJob($data);
    
    $this->assertIsInt($result);
    $this->assertGreaterThan(0, $result);
}