FROM php:8.3-apache

RUN docker-php-ext-install pdo pdo_mysql

RUN a2dismod mpm_event mpm_worker || true && a2enmod mpm_prefork rewrite

WORKDIR /var/www/html

COPY . /var/www/html/

EXPOSE 80
