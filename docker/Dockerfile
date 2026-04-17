FROM php:8.5-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends default-mysql-client \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install pdo_mysql mbstring gd

WORKDIR /var/www/html

COPY docker/entrypoint.sh /usr/local/bin/on4crd-entrypoint
RUN chmod +x /usr/local/bin/on4crd-entrypoint && a2enmod rewrite

ENTRYPOINT ["on4crd-entrypoint"]
CMD ["apache2-foreground"]
