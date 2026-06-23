#!/bin/bash
line='----------------------'
PERSIST_PATH="/home/seaview/Documents/SHARPISHLY-THOMASONS/pymvc/storage/vector_db"

echo "$line Resetting Vector Database (Hard Wipe)"
rm -rf "$PERSIST_PATH"

echo "✅ Vector Database storage cleared. Ready for fresh ingestion."