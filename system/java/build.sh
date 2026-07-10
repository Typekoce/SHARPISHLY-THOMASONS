#!/bin/bash
set -e

# Configuration
# Since we are in system/java, the source root is the current directory (.)
SOURCE_ROOT="."
TARGET_DIR="app"

# Clean and recreate target directory (relative to system/java)
rm -rf "$TARGET_DIR"
mkdir -p "$TARGET_DIR"

echo "Compiling project..."

# Compile all .java files found within the current directory
# The -d flag tells javac to place the output in the app folder at project root
find "$SOURCE_ROOT" -name "*.java" | xargs javac -d "$TARGET_DIR"

echo "Build successful."
echo "Run using: java -cp ../../app App.App"