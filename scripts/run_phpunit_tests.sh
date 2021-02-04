#!/usr/bin/env bash

set -eux
DOCKER_IMAGE=$1
NEW_DOCKER_IMAGE=wovnphp_${DOCKER_IMAGE}
CONTAINER_NAME="dummy"
WORK_DIR=/opt/project
UNITTEST_REPORT_DIR=.phpunit/phpunit
GITHUB_AUTH_TOKEN=f137458a82b1af1fac7aec42732db9cc517ca9fb

# Prepare directory to store test results
mkdir -p ${PWD}/${UNITTEST_REPORT_DIR}

# Create a dummy container which will hold a volume with source
docker build --build-arg DOCKER_IMAGE=${DOCKER_IMAGE} -t ${NEW_DOCKER_IMAGE} ./docker/apache

# Start running docker and copy files (Volume feature doesn't work with CircleCI.)
APACHE_CONTAINER_ID=`docker run -itd -e WOVN_ENV=development --name ${CONTAINER_NAME} ${NEW_DOCKER_IMAGE} /bin/bash`
docker cp $(pwd) ${APACHE_CONTAINER_ID}:${WORK_DIR}

# Install modules
docker exec -w ${WORK_DIR} ${APACHE_CONTAINER_ID} /bin/bash -c "php ./scripts/composer-setup.php"
docker exec -w ${WORK_DIR} ${APACHE_CONTAINER_ID} /bin/bash -c "./composer.phar --version"
docker exec -w ${WORK_DIR} ${APACHE_CONTAINER_ID} /bin/bash -c "./composer.phar config --global github-oauth.github.com ${GITHUB_AUTH_TOKEN}"
docker exec -w ${WORK_DIR} ${APACHE_CONTAINER_ID} /bin/bash -c "./composer.phar install"

# Check syntax
docker exec -w ${WORK_DIR} ${APACHE_CONTAINER_ID} \
       /bin/bash -c 'a=$(find /opt/project -type f -name "*.php" ! -path "*/vendor/*" -print0 | xargs -0 -n 1 -P 8 php -l | grep -v "No syntax errors" | wc -l) && exit $a'

# Run unit test
if [[ "${DOCKER_IMAGE}" =~ ^php:7.*$ ]]; then
       docker exec -w ${WORK_DIR} ${APACHE_CONTAINER_ID} \
              /bin/bash -c "set -e; phpdbg -qrr vendor/bin/phpunit --log-junit ${UNITTEST_REPORT_DIR}/results.xml -d memory_limit=1024M --coverage-html ${UNITTEST_REPORT_DIR}/coverage-report"
else
       docker exec -w ${WORK_DIR} ${APACHE_CONTAINER_ID} \
              /bin/bash -c "set -e; vendor/bin/phpunit --log-junit ${UNITTEST_REPORT_DIR}/results.xml"
fi

# Copy test results to host from docker
docker cp ${APACHE_CONTAINER_ID}:"${WORK_DIR}/${UNITTEST_REPORT_DIR}" ${PWD}/${UNITTEST_REPORT_DIR}

# Remove running docker
docker rm -f ${APACHE_CONTAINER_ID}

