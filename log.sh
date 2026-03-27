#!/bin/bash

line='----------------'

# Clear terminal
clear

# Create log folders if they don't exist

sudo mkdir -p storage/log
sudo chmod 777 -R storage/log
sudo touch storage/log/app.log

# View tail of log
tail -f storage/log/*.log
