ARG DOCKER_IMAGE=php:8.0-apache
FROM ${DOCKER_IMAGE}

# RUN echo "deb http://deb.debian.org/debian jessie main" > /etc/apt/sources.list
# RUN echo "deb http://security.debian.org jessie/updates main" >> /etc/apt/sources.list

RUN apt-get autoclean
RUN apt-get clean all
RUN apt-get update -qq
RUN apt-get -y --force-yes install git unzip

WORKDIR /var/www/html

