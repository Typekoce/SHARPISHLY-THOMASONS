#!/bin/bash

line='----------------: '

echo $line"Test Curl Command"

curl -i -X POST http://localhost/php/rag/chat \
     -H "Content-Type: application/json" \
     -H "Content-Length: 26" \
     -d '{"query": "How are you?"}'