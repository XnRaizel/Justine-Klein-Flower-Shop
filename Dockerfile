ARG PHP_VERSION=8.3

FROM node:22-alpine AS frontend

WORKDIR /frontend
COPY package*.json ./
RUN npm ci
COPY src ./src
COPY vite.config.js ./
RUN npm run build

FROM php:${PHP_VERSION}-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libcurl4-openssl-dev \
    && docker-php-ext-install curl pdo_mysql \
    && rm -rf /var/lib/apt/lists/* \
    && a2enmod rewrite

COPY . /var/www/html/
COPY --from=frontend /frontend/public/assets/react /var/www/html/public/assets/react
COPY docker-entrypoint.sh /usr/local/bin/bloom-entrypoint

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN set -eux; \
    sed -ri "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/000-default.conf; \
    sed -ri '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf; \
    mkdir -p /var/www/html/runtime/cache /var/www/html/runtime/logs /var/www/html/runtime/session; \
    chown -R root:root /var/www/html; \
    chown -R www-data:www-data /var/www/html/runtime; \
    find /var/www/html/runtime -type d -exec chmod 775 {} \;; \
    find /var/www/html/runtime -type f -exec chmod 664 {} \;; \
    chmod 755 /usr/local/bin/bloom-entrypoint

EXPOSE 10000

CMD ["/usr/local/bin/bloom-entrypoint"]