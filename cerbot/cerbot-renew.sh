#!/usr/bin/env bash
set -euo pipefail

Minimal certbot renewal script (ready to paste)
- Uses certbot renew with a deploy-hook so reload runs only on successful renewals
- Logs to /var/log/certbot-renew.log
- Intended to be run as root (systemd timer or cron)
- Usage: sudo /usr/local/bin/certbot-renew.sh
LOGFILE=/var/log/certbot-renew.log
CERTBOT="/usr/bin/certbot"
NGINX_SERVICE="nginx"

TIMESTAMP() { date -u +"%Y-%m-%dT%H:%M:%SZ"; }

echo "$(TIMESTAMP) certbot-renew: starting" >> "$LOGFILE"

Basic checks
if [ ! -x "$CERTBOT"]; then
echo "$(TIMESTAMP) certbot-renew: ERROR certbot not found at $CERTBOT" | tee -a "$LOGFILE"
exit 1
fi
