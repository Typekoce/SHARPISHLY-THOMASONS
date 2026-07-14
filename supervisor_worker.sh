#!/bin/bash
QUEUE_DIR="/home/seaview/Documents/SHARPISHLY-THOMASONS/storage/cmd/jobs/waiting"
PROC_DIR="/home/seaview/Documents/SHARPISHLY-THOMASONS/storage/cmd/jobs/processing"
DONE_DIR="/home/seaview/Documents/SHARPISHLY-THOMASONS/storage/cmd/jobs/completed"

while true; do
    # Find jobs in waiting
    for job in "$QUEUE_DIR"/*.sh; do
        [ -e "$job" ] || continue
        
        # Atomically move to processing
        mv "$job" "$PROC_DIR/"
        jobname=$(basename "$job")
        
        # Execute the script
        bash "$PROC_DIR/$jobname" > "$PROC_DIR/$jobname.log" 2>&1
        
        # Move to completed
        mv "$PROC_DIR/$jobname" "$DONE_DIR/"
        mv "$PROC_DIR/$jobname.log" "$DONE_DIR/"
    done
    sleep 1
done
