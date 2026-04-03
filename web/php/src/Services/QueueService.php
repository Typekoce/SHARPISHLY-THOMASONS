<?php
namespace App\Services;

class QueueService {
    public function pushJob(array $data) {
        $redis = new \Redis();
        $redis->connect('redis', 6379);
        // Push to the 'neural_queue' that Python is watching
        return $redis->lPush('neural_queue', json_encode($data));
    }
}