#!/bin/bash

# Probe the RAG service
# -s: silent, -f: fail on HTTP error, -m: 2 second timeout
RESPONSE=$(curl -s -f -m 2 http://localhost:8765/health 2>/dev/null)

if [ $? -eq 0 ]; then
    echo '{"status": "ok", "message": "RAG service is online"}'
else
    echo '{"status": "error", "message": "RAG service is offline or unreachable"}'
fi