# ===================== SYSTEM DEPENDENCIES =====================
echo -e "\n=== Installing System Dependencies ==="
export DEBIAN_FRONTEND=noninteractive
CLI_TOOLS=("tmux" "vim" "zsh" "git" "htop" "curl" "wget" "pass" "jq" "ripgrep" "fzf" "mtr" "nmap" "tree" "mariadb-client" "sqlmap")

sudo apt-get update -qq
sudo apt-get install -yq \
  ca-certificates apt-transport-https lsb-release gnupg curl nginx mariadb-server default-jdk \
  php${PHP_VERSION}-fpm php${PHP_VERSION}-mysql php${PHP_VERSION}-curl php${PHP_VERSION}-mbstring php${PHP_VERSION}-xml php${PHP_VERSION}-zip \
  python3-venv python3-pip "${CLI_TOOLS[@]}"

sudo systemctl enable --now "php${PHP_VERSION}-fpm"