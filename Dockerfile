FROM composer:latest AS deps

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install --ignore-platform-reqs --no-scripts

FROM php:8.2-apache AS dev

WORKDIR /var/www/html

# Cài các extension cần thiết cho Laravel
RUN apt-get update && apt-get install -y \
    git curl unzip libpng-dev libonig-dev libxml2-dev zip \
    libzip-dev libcurl4-openssl-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Bật mod_rewrite cho Laravel route đẹp
RUN a2enmod rewrite

COPY ./apache/vhost.conf /etc/apache2/sites-available/000-default.conf

# Đặt thư mục làm việc cho Laravel
COPY --from=deps /app/vendor /var/www/html/vendor

# Copy toàn bộ mã nguồn Laravel vào container
COPY . .

EXPOSE 80

FROM deps AS deps.prod

RUN composer install --ignore-platform-reqs --no-scripts 

FROM php:8.2-apache AS prod

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y \
    git curl unzip libpng-dev libonig-dev libxml2-dev zip \
    libzip-dev libcurl4-openssl-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip opcache

RUN a2enmod rewrite

COPY --from=deps.prod /app/vendor /var/www/html/vendor

COPY ./apache/vhost.conf /etc/apache2/sites-available/000-default.conf

COPY ./opcache-config/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

COPY . .

EXPOSE 80
