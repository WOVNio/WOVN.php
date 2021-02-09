ARG DOCKER_IMAGE=vectorface/php5.4
FROM ${DOCKER_IMAGE}

RUN echo "deb http://deb.debian.org/debian jessie main" > /etc/apt/sources.list
RUN echo "deb http://security.debian.org jessie/updates main" >> /etc/apt/sources.list

RUN apt-get autoclean
RUN apt-get clean all
RUN apt-get update -qq
RUN apt-get -y install git unzip
RUN apt-get clean

# enable mod_rewrite
# RUN a2enmod rewrite

WORKDIR /var/www/html

