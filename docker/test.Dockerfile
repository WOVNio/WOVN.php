ARG DOCKER_IMAGE=php:8.0-apache
FROM ${DOCKER_IMAGE}

RUN cat '/etc/apt/sources.list'

# apt-get doesn't work for OS which depends on jessie.
# So, change apt-get targets.
RUN bash -c "if [[ \"${DOCKER_IMAGE}\" =~ ^.*php:?5\.[3-4].*$ ]]; then \
    echo 'deb http://deb.debian.org/debian jessie main' > '/etc/apt/sources.list'; \
    echo 'deb http://security.debian.org jessie/updates main' >> '/etc/apt/sources.list'; \
fi"

RUN cat '/etc/apt/sources.list'

RUN apt-get autoclean
RUN apt-get clean all
RUN apt-get update -qq
RUN apt-get -y --force-yes install git unzip

WORKDIR /var/www/html

