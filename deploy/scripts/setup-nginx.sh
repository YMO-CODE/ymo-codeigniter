#!/usr/bin/env bash
# Install nginx site + Let's Encrypt certs for booking + admin subdomains.
# Usage (on VPS as root):
#   sudo bash deploy/scripts/setup-nginx.sh booking.yourmechaniconline.com admin.yourmechaniconline.com
set -euo pipefail

if [[ "${EUID:-$(id -u)}" -ne 0 ]]; then
  echo "Run as root: sudo bash $0 booking.example.com admin.example.com" >&2
  exit 1
fi

BOOKING_HOST="${1:-}"
ADMIN_HOST="${2:-}"

if [[ -z "$BOOKING_HOST" || -z "$ADMIN_HOST" ]]; then
  echo "Usage: sudo bash $0 booking.yourdomain.com admin.yourdomain.com" >&2
  exit 1
fi

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
SITE_NAME="ymo-booking-admin"
AVAILABLE="/etc/nginx/sites-available/${SITE_NAME}"
ENABLED="/etc/nginx/sites-enabled/${SITE_NAME}"

echo "==> Writing nginx config for $BOOKING_HOST and $ADMIN_HOST..."
sed -e "s/booking.yourmechaniconline.com/${BOOKING_HOST}/g" \
    -e "s/admin.yourmechaniconline.com/${ADMIN_HOST}/g" \
    "$ROOT/deploy/nginx/booking-admin.conf" > "$AVAILABLE"

# Temporary HTTP-only config for certbot (comment SSL blocks if certs missing)
if ! nginx -t 2>/dev/null; then
  echo "Note: nginx test failed — you may need certbot first. Installing HTTP config..."
fi

ln -sf "$AVAILABLE" "$ENABLED"
rm -f /etc/nginx/sites-enabled/default 2>/dev/null || true

echo "==> Obtaining Let's Encrypt certificate..."
certbot certonly --nginx \
  -d "$BOOKING_HOST" -d "$ADMIN_HOST" \
  --non-interactive --agree-tos \
  --register-unsafely-without-email \
  || certbot certonly --webroot -w /var/www/html \
       -d "$BOOKING_HOST" -d "$ADMIN_HOST" \
       --non-interactive --agree-tos \
       --register-unsafely-without-email

CERT_DIR="/etc/letsencrypt/live/${BOOKING_HOST}"
if [[ ! -f "${CERT_DIR}/fullchain.pem" ]]; then
  # certbot may use first domain as folder name
  CERT_DIR=$(find /etc/letsencrypt/live -mindepth 1 -maxdepth 1 -type d | head -1)
fi

echo "==> Enabling SSL in nginx config..."
cat > "$AVAILABLE" <<NGINX
upstream ymo_app {
    server 127.0.0.1:8080;
    keepalive 16;
}

server {
    listen 80;
    listen [::]:80;
    server_name ${BOOKING_HOST} ${ADMIN_HOST};
    location /.well-known/acme-challenge/ { root /var/www/html; }
    location / { return 301 https://\$host\$request_uri; }
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name ${BOOKING_HOST};

    ssl_certificate ${CERT_DIR}/fullchain.pem;
    ssl_certificate_key ${CERT_DIR}/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

    client_max_body_size 16M;

    location / {
        proxy_http_version 1.1;
        proxy_set_header Connection "";
        proxy_set_header Host \$host;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_pass http://ymo_app;
    }
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name ${ADMIN_HOST};

    ssl_certificate ${CERT_DIR}/fullchain.pem;
    ssl_certificate_key ${CERT_DIR}/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

    client_max_body_size 16M;

    location / {
        proxy_http_version 1.1;
        proxy_set_header Connection "";
        proxy_set_header Host \$host;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_pass http://ymo_app;
    }
}
NGINX

nginx -t
systemctl reload nginx

echo ""
echo "TLS configured. Verify:"
echo "  curl -I https://${BOOKING_HOST}/"
echo "  curl -I https://${ADMIN_HOST}/login"
echo ""
echo "Ensure .env has:"
echo "  YMO_PUBLIC_APP_URL=https://${BOOKING_HOST}"
echo "  YMO_ADMIN_APP_URL=https://${ADMIN_HOST}"
echo "  YMO_TRUST_PROXY_HEADERS=1"
echo "  CI_ENV=production"
echo ""
echo "Then restart app: docker compose ... restart app"
