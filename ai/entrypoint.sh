#!/bin/bash
# Start the FastAPI server in the background
python app.py &

# Start the persistent Work Loop in the foreground
python worker.py