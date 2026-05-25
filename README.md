# YMO Booking — CodeIgniter 3 backend

The booking engine for **Your Mechanic Online**. Runs on a separate
subdomain (`booking.yourmechaniconline.com`) alongside the existing
WordPress marketing site.

## Stack

- CodeIgniter 3.1.13 (vendored under `system/`)
- PHP 7.2+ (8.x supported)
- MySQL 5.7+ / MariaDB 10.3+
- PHPMailer over SMTP
- MSG91 SMS gateway (swappable via `Sms_gateway` driver)
- Bootstrap 5 in views (loaded from CDN to keep the bundle tiny)

## Layout

```
ymo-codeigniter/
├── application/         CI3 application code (controllers, models, views, libraries, config)
│   └── third_party/PHPMailer/   Vendored PHPMailer (no Composer needed)
├── system/              CI3 framework (vendored, do not edit)
├── public/              Web docroot (only this folder should be web-accessible)
│   ├── index.php        Front controller
│   ├── .htaccess        Pretty URLs + security headers
│   ├── assets/          Static CSS/JS/images
│   └── uploads/         User-uploaded vehicle images (PHP execution blocked)
├── storage/logs/        Application + cron logs (outside docroot)
├── database/            SQL migrations + seeds
├── composer.json        PHPMailer + autoload
├── .env.example         Sample env vars
└── README.md
```

## Local setup

PHPMailer is **vendored** at `application/third_party/PHPMailer/`, so the
project runs out-of-the-box on any host that has PHP. Composer is
optional — only run `composer install` if you want to add new
dependencies later.

Pick whichever of the three options below matches what you have
installed.

### Option A — Docker (easiest, recommended)

If you have Docker Desktop, this is the fastest path: PHP, Apache, and
MySQL come up in one command. Schema + seed are loaded automatically on
first boot.

```bash
docker compose up -d
# Wait ~20s for MySQL to seed itself, then visit:
#   http://localhost:8080
# MySQL is exposed on localhost:3307 (user: ymo_user / pass: ymo_pass)

# Create the first admin once the stack is running:
docker compose exec app php /var/www/html/index.php cli/install create_admin admin@yourmechaniconline.com

# Stop everything:
docker compose down

# Wipe data and start clean:
docker compose down -v
```

Edit code on your host — files are bind-mounted, no rebuild needed.

### Option B — Laragon / XAMPP / WAMP (Windows-native)

These bundle PHP + MySQL + Apache + Composer:

1. Install one of:
   [Laragon](https://laragon.org/) (lightest, recommended) ·
   [XAMPP](https://www.apachefriends.org/) ·
   [WAMP](https://www.wampserver.com/)
2. Launch the stack so its `mysql` binary lands on `PATH`. With Laragon
   that's automatic; with XAMPP open the Shell from the control panel.
3. From the Laragon/XAMPP shell:
   ```bash
   cp .env.example .env
   mysql -u root -e "CREATE DATABASE ymo_booking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
   mysql -u root ymo_booking < database/schema.sql
   mysql -u root ymo_booking < database/seed.sql
   php public/index.php cli/install create_admin admin@yourmechaniconline.com
   ```
4. Point Laragon/XAMPP's docroot at `<project>/public/` and visit the
   auto-assigned URL (e.g. `http://ymo-codeigniter.test`).

### Option C — Skip local dev, deploy straight to staging

If a remote host with PHP + MySQL is easier than installing a local
stack, follow [DEPLOY.md](DEPLOY.md) and treat the staging subdomain as
your dev environment.

## Deployment notes

- Web server docroot must be `public/`. `application/` and `system/` must
  not be reachable over HTTP.
- Set `CI_ENV=production` in the web server's environment.
- Daily cron for service reminders:
  ```cron
  0 7 * * * cd /var/www/ymo-codeigniter && /usr/bin/php public/index.php cli/cron run >> storage/logs/cron.log 2>&1
  ```
- Enable HTTPS, then uncomment the `Strict-Transport-Security` header in
  `public/.htaccess` and the HTTPS redirect block.

## Default admin

The seed file does **not** ship with an admin row (no shared default
password). Create the first admin by running the CLI installer once:

```bash
php public/index.php cli/install create_admin admin@yourmechaniconline.com
```

A 16-character random password will be printed to STDOUT. Use it to log in
at `/admin/login`, then rotate it from the admin profile page.

## License

Proprietary — © Your Mechanic Online.
