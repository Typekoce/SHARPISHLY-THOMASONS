#!/bin/bash

# Clear terminal
clear

# Wipe the old unhealthy state
docker compose down

# Remove old db container
#docker volume rm sharpishly_mysql_data

# Bring up the corrected organism
docker compose up -d


