# ===================== HEALTH-CHECK UTILITY =====================
echo -e "\n=== Installing Health-Check Utility ==="
if [ ! -f /usr/local/bin/db-check ]; then
    cat <<'EOF' | sudo tee /usr/local/bin/db-check > /dev/null
#!/usr/bin/env bash
if mysqladmin ping -h localhost --silent; then
    echo "MariaDB is UP."
    exit 0
else
    echo "MariaDB is DOWN."
    exit 1
fi
EOF
    sudo chmod +x /usr/local/bin/db-check
fi

echo -e "\n=== Installation Complete ==="
echo "Tip: Run 'db-check' to verify your MariaDB status."