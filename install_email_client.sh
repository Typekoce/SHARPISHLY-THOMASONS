#!/bin/sh
set -eu

# Verify dependencies
if ! command -v curl >/dev/null 2>&1; then
    echo "curl is required but not installed. Aborting."
    exit 1
fi

# Detect distribution and install
if command -v apt-get >/dev/null 2>&1 || command -v dnf >/dev/null 2>&1; then
    echo "Installing Himalaya..."
    curl -sSL https://raw.githubusercontent.com/pimalaya/himalaya/master/install.sh | sudo sh
else
    echo "Unsupported distribution."
    exit 1
fi

echo "Himalaya installed successfully."
echo "Run 'himalaya --help' to verify the installation."
