# YMO production deployment (GoDaddy + VPS)

This folder contains scripts and configs for the hosting plan:

- **WordPress** stays on GoDaddy (`@` / `www`)
- **Booking + admin + CRM** run on a Linux VPS via Docker
- **DNS** on GoDaddy: `booking` and `admin` A records → VPS IP

## Quick start (VPS)

```bash
# 1. Bootstrap server (once, as root)
sudo bash deploy/scripts/vps-bootstrap.sh

# 2. Clone repo
sudo mkdir -p /opt/ymo-codeigniter && sudo chown $USER:$USER /opt/ymo-codeigniter
git clone <your-repo-url> /opt/ymo-codeigniter
cd /opt/ymo-codeigniter

# 3. Configure environment
cp .env.example .env
nano .env   # set production URLs, DB password, SMTP, MSG91, CRM secrets

# 4. Deploy app
bash deploy/scripts/deploy-app.sh

# 5. TLS + nginx (after GoDaddy DNS points booking/admin to this server)
sudo bash deploy/scripts/setup-nginx.sh booking.yourmechaniconline.com admin.yourmechaniconline.com

# 6. Cron + backups
sudo crontab -e   # paste from deploy/cron/ymo.crontab.example (adjust paths)
```

## GoDaddy DNS

See [GODADDY_DNS.md](GODADDY_DNS.md).

## WordPress

See [wordpress/README.md](wordpress/README.md).

## Compose files

| File | Purpose |
|------|---------|
| `docker-compose.yml` | Base (dev-friendly ports) |
| `deploy/docker-compose.vps.yml` | Loopback bind + restart |
| `deploy/docker-compose.prod.yml` | `.env` loading, no public MySQL |

Production command:

```bash
docker compose -f docker-compose.yml -f deploy/docker-compose.vps.yml -f deploy/docker-compose.prod.yml up -d
```

## Full checklist

See [DEPLOY.md](../DEPLOY.md) and [CRM_SETUP.md](../CRM_SETUP.md).

**CRM Meta/WhatsApp go-live on VPS:**

```bash
bash deploy/scripts/sync-code.sh          # pull latest code
bash deploy/scripts/deploy-crm-config.sh  # secrets + verify + smoke tests
# Then Meta Developer console — see deploy/scripts/META_CRM_CHECKLIST.md
```
