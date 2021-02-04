#!/usr/bin/env bash

set -eux
DOCKER_IMAGE=$1
NEW_DOCKER_IMAGE=wovnphp_${DOCKER_IMAGE}
CONTAINER_NAME="dummy"
WORK_DIR=/opt/project
INTGTEST_REPORT_DIR=.phpunit/phpunit.integration

# Prepare directory to store test results
mkdir -p ${PWD}/${INTGTEST_REPORT_DIR}

# Make start command
MOD_REWRITE_ACTIVATION="a2enmod rewrite"
if [ "${DOCKER_IMAGE}" == "php:5.3-apache" ]; then
    START_APACHE="apache2 -D FOREGROUND"
else
    START_APACHE="apache2-foreground"
fi

# Create a dummy container which will hold a volume with source
docker build --build-arg DOCKER_IMAGE=${DOCKER_IMAGE} -t ${NEW_DOCKER_IMAGE} ./docker/apache

# Start running docker and copy files (Volume feature doesn't work with CircleCI.)
APACHE_CONTAINER_ID=`docker run -d -e WOVN_ENV=development --name ${CONTAINER_NAME} ${NEW_DOCKER_IMAGE} /bin/bash -c "${MOD_REWRITE_ACTIVATION}; ${START_APACHE}"`
docker cp $(pwd) ${APACHE_CONTAINER_ID}:${WORK_DIR}

# Install modules
docker exec ${APACHE_CONTAINER_ID} /bin/bash -c "cd ${WORK_DIR}; php ./scripts/composer-setup.php"
docker exec ${APACHE_CONTAINER_ID} /bin/bash -c "cd ${WORK_DIR}; ./composer.phar --version"
docker exec ${APACHE_CONTAINER_ID} /bin/bash -c "cd ${WORK_DIR}; ./composer.phar install"

# Run integration test
docker exec ${APACHE_CONTAINER_ID} /bin/bash -c "cd ${WORK_DIR}; set -e; vendor/bin/phpunit --configuration phpunit_integration.xml --log-junit ${INTGTEST_REPORT_DIR}/results.xml"

# Copy test results to host OS
docker cp ${APACHE_CONTAINER_ID}:"${WORK_DIR}/${INTGTEST_REPORT_DIR}" ${PWD}/${INTGTEST_REPORT_DIR}

# Remove container
docker rm -f ${APACHE_CONTAINER_ID}
