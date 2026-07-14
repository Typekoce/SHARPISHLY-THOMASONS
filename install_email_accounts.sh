#!/bin/sh
set -eu

# Define paths
CONFIG_DIR="$HOME/.config/himalaya"
CONFIG_FILE="$CONFIG_DIR/config.toml"

# Ensure dependencies exist
if ! command -v curl >/dev/null 2>&1; then
    echo "curl is required. Aborting."
    exit 1
fi

# Create directory and set permissions
mkdir -p "$CONFIG_DIR"
chmod 700 "$CONFIG_DIR"

# Write configuration using unquoted heredoc for variable expansion
cat > "$CONFIG_FILE" <<EOF
[accounts.personal]
default = true
email = "user@gmail.com"
backend.type = "imap"
backend.host = "imap.gmail.com"
backend.port = 993
backend.encryption.type = "tls"
backend.login = "user@gmail.com"
backend.auth.cmd = "pass show email/personal"
message.send.backend.type = "smtp"
message.send.backend.host = "smtp.gmail.com"
message.send.backend.port = 587
message.send.backend.encryption.type = "start-tls"
message.send.backend.login = "user@gmail.com"
message.send.backend.auth.cmd = "pass show email/personal-smtp"
folder.aliases.inbox = "INBOX"
folder.aliases.sent = "[Gmail]/Sent Mail"
folder.aliases.drafts = "[Gmail]/Drafts"
folder.aliases.trash = "[Gmail]/Trash"
EOF

# Harden permissions
chmod 600 "$CONFIG_FILE"

echo "Himalaya configuration initialized at $CONFIG_FILE"
