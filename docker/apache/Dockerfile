ARG DOCKER_IMAGE=php:7.4-apache
FROM ${DOCKER_IMAGE}

# ARG will be reset by FROM. So, re-define same ARG.
ARG DOCKER_IMAGE
ENV DOCKER_IMAGE=${DOCKER_IMAGE}

RUN apt-get autoclean
RUN apt-get clean all
RUN apt-get update -qq
# Fix GPG error
RUN apt-get install -y --force-yes debian-keyring debian-archive-keyring
RUN apt-get -y -f install git unzip
RUN apt-get clean

# enable mod_rewrite/mod_include(for SSI)
RUN a2enmod rewrite include

# For SSL
RUN a2enmod ssl

# install xdebug
# RUN pecl install xdebug-2.9.0
# RUN docker-php-ext-enable xdebug

WORKDIR /var/www/html
