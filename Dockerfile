FROM php:5.6-apache

RUN apt-get update && apt-get install -y \
        libmysqlclient-dev mysql-client \
    && docker-php-ext-install mysql

COPY php.ini /usr/local/etc/php/
COPY src/ /var/www/html/
COPY app-config/ /etc/intercode/