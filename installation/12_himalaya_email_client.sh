# ===================== HIMALAYA EMAIL CLIENT =====================
echo -e "\n=== Configuring Himalaya Email Client ==="
if ! command -v himalaya &>/dev/null; then
    curl -sSL https://raw.githubusercontent.com/pimalaya/himalaya/master/install.sh | sudo sh
fi

mkdir -p "$HOME/.config/himalaya"
chmod 700 "$HOME/.config/himalaya"

if [ ! -f "$HOME/.config/himalaya/config.toml" ]; then
    touch "$HOME/.config/himalaya/config.toml"
fi
chmod 600 "$HOME/.config/himalaya/config.toml"