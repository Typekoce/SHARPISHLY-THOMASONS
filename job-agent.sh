#!/bin/bash

line='---------------'

echo $line"Testing job agent functionality"

curl -X POST http://localhost:8080/php/agents/job-seeker \
     -H "Content-Type: application/json" \
     -d '{"role": "Surveyor"}'
