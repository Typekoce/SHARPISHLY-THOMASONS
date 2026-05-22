#!/bin/bash

line='----------------------'

echo $line"Clear terminal"
clear

echo $line"Kill previous rag service"
pkill -f app/rag_service.py

echo $line"Start rag service"
PYTHONPATH=. ../venv/bin/python3 app/rag_service.py
