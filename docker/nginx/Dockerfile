FROM php:7.2-fpm
RUN apt-get update
RUN pecl install xdebug && \
    docker-php-ext-enable xdebug
