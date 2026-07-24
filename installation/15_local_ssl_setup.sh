#!/usr/bin/env bash
set -euo pipefail

# Configuration
SSL_DIR="./storage/ssl"
DOMAIN="sharpishly.local"
CA_NAME="Sharpishly Local Root CA"
DAYS_VALID=3650

echo "=== Setting up Local SSL Certificates ==="

# Establish workspace
mkdir -p "$SSL_DIR"
cd "$SSL_DIR"

# Cleanup temporary files automatically on exit (success or failure)
trap 'rm -f dev.csr domain.ext' EXIT

# 1. Generate Local Root CA (Idempotent)
if [[ ! -f "rootCA.key" || ! -f "rootCA.crt" ]]; then
    echo "--> Generating Local Root CA..."
    openssl req -x509 -nodes -newkey rsa:4096 \
        -keyout rootCA.key \
        -out rootCA.crt \
        -days "$DAYS_VALID" \
        -subj "/CN=$CA_NAME/O=Sharpishly Dev/C=UK"
    echo "Root CA created successfully."
else
    echo "--> Existing Root CA found. Skipping CA generation."
fi

# 2. Generate Leaf Certificate & Key (Idempotent)
if [[ ! -f "dev.key" || ! -f "dev.crt" ]]; then
    echo "--> Creating SAN extension config..."
    cat <<EOF > domain.ext
authorityKeyIdentifier=keyid,issuer
basicConstraints=CA:FALSE
keyUsage = digitalSignature, nonRepudiation, keyEncipherment, dataEncipherment
subjectAltName = @alt_names

[v3_req]
subjectAltName = @alt_names

[alt_names]
DNS.1 = ${DOMAIN}
DNS.2 = *.${DOMAIN}
DNS.3 = localhost
IP.1  = 127.0.0.1
EOF

    echo "--> Generating Domain Private Key and CSR..."
    openssl req -new -nodes -newkey rsa:2048 \
        -keyout dev.key \
        -out dev.csr \
        -subj "/CN=*.$DOMAIN/O=Sharpishly Dev/C=UK"

    echo "--> Signing Certificate with Root CA..."
    openssl x509 -req -in dev.csr \
        -CA rootCA.crt \
        -CAkey rootCA.key \
        -CAcreateserial \
        -out dev.crt \
        -days "$DAYS_VALID" \
        -extfile domain.ext \
        -extensions v3_req
    echo "Leaf certificate issued successfully."
else
    echo "--> Existing leaf certificate (dev.key / dev.crt) found. Skipping generation."
fi

# 3. System Trust Store Installation (Distro Aware)
echo "--> Checking system trust store integration..."

if command -v update-ca-certificates >/dev/null 2>&1; then
    # Debian / Ubuntu / Mint
    echo "--> Debian-based system detected. Updating trust store..."
    sudo cp rootCA.crt /usr/local/share/ca-certificates/sharpishly_rootCA.crt
    sudo update-ca-certificates
elif command -v update-ca-trust >/dev/null 2>&1; then
    # RHEL / CentOS / Fedora / Rocky
    echo "--> RedHat-based system detected. Updating trust store..."
    sudo cp rootCA.crt /etc/pki/ca-trust/source/anchors/sharpishly_rootCA.crt
    sudo update-ca-trust extract
else
    echo "--> Warning: No recognized system CA updater tool found."
    echo "    To manually trust the CA, import '$SSL_DIR/rootCA.crt' into your system/browser certificate store."
fi

echo ""
echo "=== Local SSL Setup Complete ==="
echo "Certificates ready at:"
echo "  - Private Key: $SSL_DIR/dev.key"
echo "  - Certificate: $SSL_DIR/dev.crt"
echo "  - Root CA:     $SSL_DIR/rootCA.crt"
