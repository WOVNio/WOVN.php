# set docker image
ARG DOCKER_IMAGE="php:7.4-apache"

FROM ${DOCKER_IMAGE}
RUN apt-get update

# install xdebug
RUN pecl install xdebug-2.9.0
RUN docker-php-ext-enable xdebug

WORKDIR /var/www/html
