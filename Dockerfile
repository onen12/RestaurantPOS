FROM php:8.1-apache

# PostgreSQL extensions for Supabase
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpq-dev libzip-dev unzip \
    && docker-php-ext-install pdo pdo_pgsql zip \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

ENV APACHE_DOCUMENT_ROOT=/var/www/html
WORKDIR /var/www/html

COPY . /var/www/html/

RUN printf '<Directory /var/www/html>\n    AllowOverride All\n    Require all granted\n</Directory>\n' > /etc/apache2/conf-available/app.conf \
    && a2enconf app \
    && mkdir -p Restro/admin/assets/img/products \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod +x /var/www/html/docker-entrypoint.sh

ENV PORT=10000
EXPOSE 10000

CMD ["/var/www/html/docker-entrypoint.sh"]
