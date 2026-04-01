#!/bin/bash

# Wipe the old unhealthy state
docker compose down -v 

# Bring up the corrected organism
docker compose up -d


