FROM php:7.3-fpm
RUN apt-get update

# install xdebug
RUN pecl install xdebug
RUN docker-php-ext-enable xdebug

WORKDIR /var/www/html
