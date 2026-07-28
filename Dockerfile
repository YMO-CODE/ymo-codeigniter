# Local-dev image for the YMO booking app.
# Apache + PHP 8.1 + extensions CI3 needs (mysqli, gd for image resize, etc.)

FROM php:8.1-apache

# System libraries needed by gd (image resize) and intl
RUN apt-get update \
 && apt-get install -y --no-install-recommends \
        libpng-dev libjpeg-dev libfreetype-dev libwebp-dev libicu-dev \
        ca-certificates curl git unzip \
 && rm -rf /var/lib/apt/lists/*

# PHP extensions: mysqli + pdo_mysql for the DB; gd for image resize;
# intl + opcache for general goodness
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
 && docker-php-ext-install -j$(nproc) mysqli pdo_mysql gd intl opcache

# Apache: enable mod_rewrite + allow .htaccess overrides under /var/www
RUN a2enmod rewrite headers deflate expires \
 && sed -ri 's!<Directory /var/www/>!<Directory /var/www/>\n    AllowOverride All!g' \
        /etc/apache2/apache2.conf

# Sensible PHP defaults for development
RUN { \
        echo 'display_errors = On'; \
        echo 'log_errors = On'; \
        echo 'error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT'; \
        echo 'upload_max_filesize = 8M'; \
        echo 'post_max_size = 16M'; \
        echo 'memory_limit = 256M'; \
        echo 'date.timezone = Asia/Kolkata'; \
    } > /usr/local/etc/php/conf.d/ymo.ini

WORKDIR /var/www/html
