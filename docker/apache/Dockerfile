ARG DOCKER_IMAGE=php:7.4-apache
FROM ${DOCKER_IMAGE}
RUN apt-get update
RUN apt-get -y --force-yes install vim git unzip

# enable mod_rewrite
RUN a2enmod rewrite

# install xdebug
# RUN pecl install xdebug mbstring
# RUN docker-php-ext-enable xdebug
# RUN docker-php-ext-install mbstring

WORKDIR /var/www/html

