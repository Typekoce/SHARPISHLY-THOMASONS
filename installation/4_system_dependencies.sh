#!/usr/bin/env bash
set -Eeuo pipefail

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=2_config.sh
source "${SCRIPT_DIR}/installation/2_config.sh"

echo -e "\n=== [1/3] Pre-Flight OS Validation ==="
if [[ -r /etc/os-release ]]; then
    . /etc/os-release
else
    echo "ERROR: Cannot read /etc/os-release." >&2
    exit 1
fi

case "${ID:-}:${VERSION_ID:-}" in
    ubuntu:25.04)
        echo "ERROR: Ubuntu 25.04 reached End-of-Life on January 15, 2026. Deployment refused." >&2
        exit 1
        ;;
    ubuntu:22.04|ubuntu:24.04|debian:12)
        echo "Platform supported: ${PRETTY_NAME:-$ID $VERSION_ID}"
        ;;
    *)
        echo "ERROR: Unsupported platform: ${PRETTY_NAME:-$ID $VERSION_ID}" >&2
        exit 1
        ;;
esac

echo -e "\n=== [2/3] Installing System Dependencies ==="
export DEBIAN_FRONTEND=noninteractive

sudo apt-get update -qq

packages=(
    ca-certificates
    apt-transport-https
    lsb-release
    gnupg
    curl
    nginx
    mariadb-server
    default-jdk
    php-fpm
    php-mysql
    php-curl
    php-mbstring
    php-xml
    php-zip
    python3-venv
    python3-pip
)

sudo apt-get install -yq "${packages[@]}" "${CLI_TOOLS[@]}"

echo -e "\n=== [3/3] Service & Socket Discovery ==="
# Discover FPM unit name
PHP_FPM_SERVICE="$(
    systemctl list-unit-files \
        --type=service \
        --no-legend \
        'php*-fpm.service' |
    awk 'NR == 1 { print $1 }'
)"

if [[ -z "${PHP_FPM_SERVICE}" ]]; then
    echo "ERROR: No PHP-FPM service was found after package installation." >&2
    exit 1
fi

sudo systemctl enable --now "${PHP_FPM_SERVICE}"

# Poll briefly for the UNIX socket to be created
PHP_FPM_SOCKET=""
for _ in {1..20}; do
    PHP_FPM_SOCKET="$(
        find /run/php -maxdepth 1 -type s \
            -name 'php*-fpm.sock' \
            -print -quit 2>/dev/null || true
    )"

    [[ -n "${PHP_FPM_SOCKET}" ]] && break
    sleep 1
done

if [[ -z "${PHP_FPM_SOCKET}" ]]; then
    echo "ERROR: PHP-FPM socket was not created under /run/php." >&2
    sudo systemctl status "${PHP_FPM_SERVICE}" --no-pager || true
    exit 1
fi

# Persist state securely
sudo install -m 600 /dev/null "${RUNTIME_ENV}"
sudo tee "${RUNTIME_ENV}" >/dev/null <<EOF
PHP_FPM_SERVICE=$(printf '%q' "${PHP_FPM_SERVICE}")
PHP_FPM_SOCKET=$(printf '%q' "${PHP_FPM_SOCKET}")
EOF

echo "Discovered PHP-FPM Service : ${PHP_FPM_SERVICE}"
echo "Discovered FastCGI Socket : ${PHP_FPM_SOCKET}"