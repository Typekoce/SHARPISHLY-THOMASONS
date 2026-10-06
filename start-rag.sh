#!/usr/bin/env bash
cd "$(dirname "$0")"
export PYTHONPATH=pymvc
exec venv/bin/python3 pymvc/app/rag_service.py "$@"
