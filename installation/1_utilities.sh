#!/bin/bash
set -euo pipefail

# Clear terminal
clear

# ===================== UTILITIES =====================
string_replace() {
    local search="$1"
    local replace="$2"
    local file="$3"
    if [[ -f "$file" ]]; then
        sudo sed -i "s|${search}|${replace}|g" "$file"
        echo "    ✅ Grounded ${search} in ${file}"
    fi
}