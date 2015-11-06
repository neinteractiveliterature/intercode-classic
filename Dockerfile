FROM php:5.6-apache

RUN apt-get update && apt-get install -y \
        libmysqlclient-dev mysql-client \
    && docker-php-ext-install mysql

# session save path
RUN mkdir -p /var/lib/php5

COPY php.ini /usr/local/etc/php/
COPY src/ /var/www/html/
COPY app-config/ /etc/intercode/