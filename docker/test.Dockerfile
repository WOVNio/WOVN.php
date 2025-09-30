FROM base-image

ARG DOCKER_IMAGE

# apt-get doesn't work for OS which depends on jessie.
# So, change apt-get targets.
RUN bash -c 'if [[ "${DOCKER_IMAGE}" =~ ^.*php:?(5\.[3-6]|7\.0).*$ ]]; then \
    echo "deb http://archive.debian.org/debian/ stretch main" > "/etc/apt/sources.list"; \
    echo "deb http://archive.debian.org/debian-security stretch/updates main" >> "/etc/apt/sources.list"; \
fi'

RUN bash -c 'if [[ "${DOCKER_IMAGE}" =~ ^.*php:?(7\.[1-2]).*$ ]]; then \
    echo "deb http://archive.debian.org/debian/ buster main" > "/etc/apt/sources.list"; \
    echo "deb http://archive.debian.org/debian-security buster/updates main" >> "/etc/apt/sources.list"; \
fi'

RUN apt-get autoclean
RUN apt-get clean all
RUN apt-get update -qq
RUN apt-get -y --force-yes install git unzip

WORKDIR /var/www/html
