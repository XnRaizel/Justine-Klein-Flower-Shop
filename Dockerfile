ARG PHP_VERSION=8.3

FROM php:${PHP_VERSION}-apache

RUN docker-php-ext-install pdo_mysql \
    && a2enmod rewrite

COPY . /var/www/html/
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