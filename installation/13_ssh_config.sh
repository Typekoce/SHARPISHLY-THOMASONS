# ===================== SSH CONFIGURATION (INDIVIDUAL ANCHORED GUARDS) =====================
echo -e "\n=== Configuring SSH Aliases ==="
SSH_CONFIG="$HOME/.ssh/config"
mkdir -p "$HOME/.ssh"
touch "$SSH_CONFIG"
chmod 600 "$SSH_CONFIG"

add_ssh_host() {
    local host_alias="$1"
    local hostname="$2"
    local user="$3"

    if ! grep -qE "^Host[[:space:]]+${host_alias}\$" "$SSH_CONFIG"; then
        cat <<EOF >> "$SSH_CONFIG"

Host ${host_alias}
    HostName ${hostname}
    User ${user}
EOF
        echo "    ✅ Added SSH host '${host_alias}' (${hostname})."
    else
        echo "    ⏩ SSH host '${host_alias}' already exists."
    fi
}

add_ssh_host "maxie" "192.168.0.90" "maxie"
add_ssh_host "joe90" "192.168.1.50" "dev"
add_ssh_host "foozie" "192.168.1.51" "dev"
add_ssh_host "tardis" "192.168.1.52" "dev"