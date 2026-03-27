#!/bin/bash

line='----------'

echo "$line Setting up storage permissions"
# Ensure the host folders exist
mkdir -p storage/logs storage/uploads 

# Grant write access to the group (Docker/WWW-DATA)
chmod -R 775 storage
# Ensure the log file specifically is world-writable to avoid the PHP Warning
touch storage/logs/app.log
chmod 666 storage/logs/app.log

echo "$line Migrate database tables"
# Use -s to hide progress bar, -S to show errors
curl -sS http://localhost:8080/php/scaffold/migrate

echo -e "\n$line Migration Complete"