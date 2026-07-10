#!/bin/bash
# ===================== SYSTEM SETUP =====================
echo -e "\n=== Installing System Dependencies ==="
sudo apt-get update -qq
# Added default-jdk to ensure javac is available
sudo apt-get install -y ca-certificates apt-transport-https lsb-release gnupg curl nginx mariadb-server php${PHP_VERSION}-fpm php${PHP_VERSION}-mysql php${PHP_VERSION}-curl php${PHP_VERSION}-mbstring php${PHP_VERSION}-xml php${PHP_VERSION}-zip python3-venv python3-pip default-jdk