FROM php:7.1-apache

# enable mod_rewrite
RUN a2enmod rewrite

# install xdebug
RUN pecl install xdebug
RUN docker-php-ext-enable xdebug

WORKDIR /var/www/html


