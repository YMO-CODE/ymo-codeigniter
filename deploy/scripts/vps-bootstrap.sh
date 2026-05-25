#!/usr/bin/env bash
# Bootstrap a fresh Ubuntu 22.04/24.04 VPS for YMO (Docker + nginx + certbot + ufw).
# Run as root or with sudo on the VPS:
#   curl -fsSL ... | bash
#   OR: sudo bash deploy/scripts/vps-bootstrap.sh
set -euo pipefail

if [[ "${EUID:-$(id -u)}" -ne 0 ]]; then
  echo "Run as root: sudo bash $0" >&2
  exit 1
fi

export DEBIAN_FRONTEND=noninteractive

echo "==> Updating packages..."
apt-get update -qq
apt-get upgrade -y -qq

echo "==> Installing base tools..."
apt-get install -y -qq ca-certificates curl gnupg ufw git unzip

echo "==> Configuring firewall (ufw)..."
ufw --force reset
ufw default deny incoming
ufw default allow outgoing
ufw allow OpenSSH
ufw allow 80/tcp
ufw allow 443/tcp
ufw --force enable

echo "==> Installing Docker..."
install -m 0755 -d /etc/apt/keyrings
if [[ ! -f /etc/apt/keyrings/docker.gpg ]]; then
  curl -fsSL https://download.docker.com/linux/ubuntu/gpg | gpg --dearmor -o /etc/apt/keyrings/docker.gpg
  chmod a+r /etc/apt/keyrings/docker.gpg
fi
echo \
  "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu \
  $(. /etc/os-release && echo "$VERSION_CODENAME") stable" \
  > /etc/apt/sources.list.d/docker.list
apt-get update -qq
apt-get install -y -qq docker-ce docker-ce-cli containerd.io docker-compose-plugin

echo "==> Installing nginx + certbot..."
apt-get install -y -qq nginx certbot python3-certbot-nginx

mkdir -p /var/www/html/.well-known/acme-challenge
chown -R www-data:www-data /var/www/html

echo "==> Creating deploy user (optional)..."
if ! id -u ymo >/dev/null 2>&1; then
  useradd -m -s /bin/bash ymo || true
  usermod -aG docker ymo || true
fi

echo ""
echo "Bootstrap complete."
echo "  - Docker: $(docker --version)"
echo "  - Compose: $(docker compose version)"
echo "  - nginx: $(nginx -v 2>&1)"
echo ""
echo "Next steps:"
echo "  1. Clone the repo to /opt/ymo-codeigniter"
echo "  2. cp .env.example .env && edit production values"
echo "  3. bash deploy/scripts/deploy-app.sh"
echo "  4. bash deploy/scripts/setup-nginx.sh booking.yourdomain.com admin.yourdomain.com"
echo "  5. Add GoDaddy A records (see deploy/GODADDY_DNS.md)"
