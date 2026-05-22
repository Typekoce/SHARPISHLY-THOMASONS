#!/bin/bash

line='--------------------------'

echo $line"Clear terminal"
clear

echo $line"Test rag"
curl -s "http://localhost:8765/rag/ask?query=What%20is%20Steve%20Austin%27s%20email%3F" | jq

