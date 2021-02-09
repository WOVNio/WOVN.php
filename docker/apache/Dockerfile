ARG DOCKER_IMAGE=php:7.4-apache
FROM ${DOCKER_IMAGE}

RUN echo "deb http://deb.debian.org/debian jessie main" > /etc/apt/sources.list
RUN echo "deb http://security.debian.org jessie/updates main" >> /etc/apt/sources.list

RUN apt-get autoclean
RUN apt-get clean all
RUN apt-get update -qq
RUN apt-get -y -f install git unzip
RUN apt-get clean

# enable mod_rewrite
RUN a2enmod rewrite

# install xdebug
RUN pecl install xdebug-2.9.0
RUN docker-php-ext-enable xdebug

WORKDIR /var/www/html
