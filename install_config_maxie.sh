#!/bin/bash

# Define the config block
CONFIG_ENTRY="
Host maxie
    HostName 192.168.0.90
    User maxie
    ConnectTimeout 5
    ServerAliveInterval 60
    IdentitiesOnly yes
    # IdentityFile ~/.ssh/id_ed25519_maxie
"

SSH_CONFIG="$HOME/.ssh/config"

# Ensure ~/.ssh directory exists
mkdir -p "$HOME/.ssh"
touch "$SSH_CONFIG"

# Check if 'Host maxie' already exists to avoid duplicates
if grep -q "Host maxie" "$SSH_CONFIG"; then
    echo "Configuration for 'maxie' already exists in $SSH_CONFIG."
else
    # Append the configuration
    echo "$CONFIG_ENTRY" >> "$SSH_CONFIG"
    # Ensure correct permissions for SSH config
    chmod 600 "$SSH_CONFIG"
    echo "Successfully added 'maxie' configuration to $SSH_CONFIG."
fi
