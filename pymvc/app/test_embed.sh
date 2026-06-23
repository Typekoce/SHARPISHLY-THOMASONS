#!/bin/bash

line='----------------------'

echo $line"Clear terminal"

# echo $line"Kill previous rag service"
# pkill -f rag_service.py

echo $line"Start test service"

# Set PYTHONPATH to the directory containing the 'app' folder (which is pymvc/)
# Since we are in pymvc/app/, '..' is the pymvc/ folder.
PYTHONPATH=.. ../../venv/bin/python3 test_embed.py