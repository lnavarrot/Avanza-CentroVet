FROM php:8.3-apache

RUN docker-php-ext-install pdo pdo_mysql

RUN echo "=== MPM HABILITADOS ===" && ls -la /etc/apache2/mods-enabled/*mpm* || true

RUN a2enmod rewrite

WORKDIR /var/www/html

COPY . /var/www/html/

EXPOSE 80
