# Build from the repository root: docker build -f backend/Dockerfile .
FROM php:8.5-apache AS php-runtime
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev libicu-dev libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql intl gd zip exif bcmath pcntl \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /app
COPY backend/apache.conf /etc/apache2/sites-available/000-default.conf
COPY backend/php-production.ini /usr/local/etc/php/conf.d/production.ini

FROM php-runtime AS dependencies
COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist
COPY backend/ ./
COPY frontend/public/ ./resources/frontend-public/
RUN rm -f bootstrap/cache/packages.php bootstrap/cache/services.php \
    && mkdir -p bootstrap/cache storage/framework/views storage/framework/cache/data storage/framework/sessions storage/logs \
    && composer dump-autoload --no-dev --optimize --no-interaction \
    && test ! -f .env

FROM node:24-bookworm-slim AS assets
WORKDIR /app
COPY backend/package.json backend/package-lock.json ./
RUN npm ci --ignore-scripts
COPY --from=dependencies /app/ ./
RUN npm run build

FROM php-runtime AS testing
COPY backend/ ./
COPY frontend/public/ ./resources/frontend-public/
RUN mkdir -p bootstrap/cache storage/framework/views storage/framework/cache/data storage/framework/sessions storage/logs \
    && composer install --no-scripts --no-interaction --prefer-dist \
    && composer dump-autoload --optimize --no-interaction
ENTRYPOINT []
CMD ["vendor/bin/phpunit", "--configuration", "phpunit.postgres.xml"]

FROM php-runtime AS production
COPY --from=dependencies /app/ ./
COPY --from=assets /app/public/build/ ./public/build/
RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod +x docker-entrypoint.sh
ENV PORT=10000
EXPOSE 10000
ENTRYPOINT ["/app/docker-entrypoint.sh"]
CMD ["apache2-foreground"]
