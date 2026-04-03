<?php
# Location: web/php/src/Services/DbServiceTest.php

class DbServiceTest {

    public function __construct(){
        $this->test();        
    }

    public function test(){
        try {
            echo "🔍 Testing MySQL... ";
            $dsn = "mysql:host=" . getenv('DB_HOST') . ";dbname=" . getenv('DB_NAME');
            $pdo = new PDO($dsn, getenv('DB_USER'), getenv('DB_PASS'), [
                PDO::ATTR_TIMEOUT => 5,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            echo "✅ Connected.\n";
        } catch (\PDOException $e) {
            echo "❌ MySQL Failed: " . $e->getMessage() . "\n";
        }

        try {
            echo "🔍 Testing Redis... ";
            $redis = new \Redis();
            // Using 2.5s timeout for the handshake
            $redis->connect(getenv('REDIS_HOST'), 6379, 2.5);
            echo "✅ Connected (" . $redis->ping() . ").\n";
        } catch (\Exception $e) {
            echo "❌ Redis Failed: " . $e->getMessage() . "\n";
        }        
    }
}

new DbServiceTest();