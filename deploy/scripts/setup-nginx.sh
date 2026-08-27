#!/usr/bin/env bash
# Install nginx site + Let's Encrypt certs for booking + admin subdomains.
# Usage (on VPS as root):
#   sudo bash deploy/scripts/setup-nginx.sh booking.yourmechaniconline.com admin.yourmechaniconline.com
#
# Prerequisites: GoDaddy A records for booking + admin → this server's public IP.
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
WEBROOT="/var/www/html"

mkdir -p "${WEBROOT}/.well-known/acme-challenge"
chown -R www-data:www-data /var/www/html 2>/dev/null || true

write_http_config() {
  cat > "$AVAILABLE" <<NGINX
upstream ymo_app {
    server 127.0.0.1:8080;
    keepalive 16;
}

server {
    listen 80;
    listen [::]:80;
    server_name ${BOOKING_HOST} ${ADMIN_HOST};

    location /.well-known/acme-challenge/ {
        root ${WEBROOT};
    }

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
}

ensure_letsencrypt_nginx_snippets() {
  # certbot --webroot does not create these; certbot --nginx does.
  mkdir -p /etc/letsencrypt
  local opts="/etc/letsencrypt/options-ssl-nginx.conf"
  local dh="/etc/letsencrypt/ssl-dhparams.pem"
  local bundled_opts="${ROOT}/deploy/nginx/options-ssl-nginx.conf"
  local bundled_dh="${ROOT}/deploy/nginx/ssl-dhparams.pem"
  local certbot_raw="https://raw.githubusercontent.com/certbot/certbot/master"

  if [[ ! -f "$opts" ]]; then
    echo "==> Installing ${opts}..."
    if [[ -f "$bundled_opts" ]]; then
      cp "$bundled_opts" "$opts"
    else
      curl -fsSL "${certbot_raw}/certbot-nginx/certbot_nginx/_internal/tls_configs/options-ssl-nginx.conf" -o "$opts"
    fi
  fi
  if [[ ! -f "$dh" ]]; then
    echo "==> Installing ${dh}..."
    if [[ -f "$bundled_dh" ]]; then
      cp "$bundled_dh" "$dh"
    else
      curl -fsSL "${certbot_raw}/certbot/certbot/ssl-dhparams.pem" -o "$dh"
    fi
  fi
}

write_tls_config() {
  local cert_dir="$1"
  cat > "$AVAILABLE" <<NGINX
upstream ymo_app {
    server 127.0.0.1:8080;
    keepalive 16;
}

server {
    listen 80;
    listen [::]:80;
    server_name ${BOOKING_HOST} ${ADMIN_HOST};
    location /.well-known/acme-challenge/ { root ${WEBROOT}; }
    location / { return 301 https://\$host\$request_uri; }
}

server {
    listen 443 ssl;
    listen [::]:443 ssl;
    http2 on;
    server_name ${BOOKING_HOST};

    ssl_certificate ${cert_dir}/fullchain.pem;
    ssl_certificate_key ${cert_dir}/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

    client_max_body_size 16M;

    location / {
        add_header X-Robots-Tag "noindex, nofollow" always;
        proxy_http_version 1.1;
        proxy_set_header Connection "";
        proxy_set_header Host \$host;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_pass http://ymo_app;
    }
}

server {
    listen 443 ssl;
    listen [::]:443 ssl;
    http2 on;
    server_name ${ADMIN_HOST};

    ssl_certificate ${cert_dir}/fullchain.pem;
    ssl_certificate_key ${cert_dir}/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

    client_max_body_size 16M;

    location / {
        add_header X-Robots-Tag "noindex, nofollow" always;
        proxy_http_version 1.1;
        proxy_set_header Connection "";
        proxy_set_header Host \$host;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_pass http://ymo_app;
    }
}
NGINX
}

echo "==> Checking DNS (A records must point to this server)..."
VPS_IP="$(curl -fsS --max-time 5 ifconfig.me 2>/dev/null || curl -fsS --max-time 5 icanhazip.com 2>/dev/null || true)"
for host in "$BOOKING_HOST" "$ADMIN_HOST"; do
  RESOLVED="$(getent ahostsv4 "$host" 2>/dev/null | awk '{print $1; exit}' || true)"
  if [[ -z "$RESOLVED" ]]; then
    echo "ERROR: No A record for ${host} (NXDOMAIN or not propagated yet)." >&2
    echo "Add GoDaddy A records: booking + admin → your VPS IP. See deploy/GODADDY_DNS.md" >&2
    exit 1
  fi
  if [[ -n "$VPS_IP" && "$RESOLVED" != "$VPS_IP" ]]; then
    echo "WARN: ${host} → ${RESOLVED} (this server is ${VPS_IP}). Certbot may fail if wrong." >&2
  else
    echo "  OK ${host} → ${RESOLVED}"
  fi
done

echo "==> Writing HTTP-only nginx config (no TLS yet)..."
write_http_config
ln -sf "$AVAILABLE" "$ENABLED"
rm -f /etc/nginx/sites-enabled/default 2>/dev/null || true
nginx -t
systemctl reload nginx

echo "==> Obtaining Let's Encrypt certificate (webroot)..."
if certbot certificates 2>/dev/null | grep -q "Certificate Name: ${BOOKING_HOST}"; then
  echo "  Certificate already issued — skipping certbot."
else
  certbot certonly --webroot -w "$WEBROOT" \
    -d "$BOOKING_HOST" -d "$ADMIN_HOST" \
    --non-interactive --agree-tos \
    --register-unsafely-without-email
fi

CERT_DIR="/etc/letsencrypt/live/${BOOKING_HOST}"
if [[ ! -f "${CERT_DIR}/fullchain.pem" ]]; then
  CERT_DIR="$(find /etc/letsencrypt/live -mindepth 1 -maxdepth 1 -type d ! -name 'README' | head -1)"
fi
if [[ ! -f "${CERT_DIR}/fullchain.pem" ]]; then
  echo "ERROR: Certificate not found after certbot." >&2
  exit 1
fi

echo "==> Enabling TLS in nginx..."
ensure_letsencrypt_nginx_snippets
write_tls_config "$CERT_DIR"
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
