FROM php:7.3-apache
RUN apt-get update

# enable mod_rewrite
RUN a2enmod rewrite

# install xdebug
RUN pecl install xdebug-2.9.0
RUN docker-php-ext-enable xdebug

WORKDIR /var/www/html

