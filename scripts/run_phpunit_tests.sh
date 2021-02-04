#!/usr/bin/env bash

set -eux
DOCKER_IMAGE=$1
NEW_DOCKER_IMAGE=wovnphp_${DOCKER_IMAGE}
CONTAINER_NAME="dummy_$(date +%s)"
WORK_DIR=/opt/project
UNITTEST_REPORT_DIR=.phpunit/phpunit

# Prepare directory to store test results
mkdir -p ${PWD}/${UNITTEST_REPORT_DIR}

# Create a dummy container which will hold a volume with source
docker build --build-arg DOCKER_IMAGE=${DOCKER_IMAGE} -t ${NEW_DOCKER_IMAGE} ./docker/apache

# Start running docker and copy files (Volume feature doesn't work with CircleCI.)
APACHE_CONTAINER_ID=`docker run -itd -e WOVN_ENV=development --name ${CONTAINER_NAME} ${NEW_DOCKER_IMAGE} /bin/bash`
docker cp $(pwd) ${APACHE_CONTAINER_ID}:${WORK_DIR}

if [[ "${DOCKER_IMAGE}" =~ ^php:[8].*$ ]]; then
    # Convert test to support PHP8 syntax
    docker exec ${APACHE_CONTAINER_ID} /bin/bash -c "find ${WORK_DIR}/test -type f -name \"*.php\" -print0 | xargs -0 sed -i \"s/function setUp(.*)$/function setUp(): void/g\""
    docker exec ${APACHE_CONTAINER_ID} /bin/bash -c "find ${WORK_DIR}/test -type f -name \"*.php\" -print0 | xargs -0 sed -i \"s/function tearDown(.*)$/function tearDown(): void/g\""
fi

# Remove modules to install modules for PHP8
#   If there is a composer.lock file, composer installs modules depends on lock file.
#   So, remove lock file and installed modules first, and re-install modules with current PHP version.
docker exec ${APACHE_CONTAINER_ID} /bin/bash -c "cd ${WORK_DIR}; rm -rf vendor"
docker exec ${APACHE_CONTAINER_ID} /bin/bash -c "cd ${WORK_DIR}; rm composer.lock"

# Install modules
docker exec ${APACHE_CONTAINER_ID} /bin/bash -c "cd ${WORK_DIR}; php ./scripts/composer-setup.php"
docker exec ${APACHE_CONTAINER_ID} /bin/bash -c "cd ${WORK_DIR}; ./composer.phar update"

# Check syntax
docker exec ${APACHE_CONTAINER_ID} \
    /bin/bash -c 'find /opt/project -type f -name "*.php" ! -path "*/vendor/*" -print0 | xargs -0 -n 1 -P 8 php -l | grep -v "No syntax errors"'
docker exec ${APACHE_CONTAINER_ID} \
    /bin/bash -c 'a=$(find /opt/project -type f -name "*.php" ! -path "*/vendor/*" -print0 | xargs -0 -n 1 -P 8 php -l | grep -v "No syntax errors" | wc -l) && exit $a'

# Run unit test
if [[ "${DOCKER_IMAGE}" =~ ^php:7.*$ ]]; then
    docker exec ${APACHE_CONTAINER_ID} \
        /bin/bash -c "cd ${WORK_DIR}; set -e; phpdbg -qrr vendor/bin/phpunit --log-junit ${UNITTEST_REPORT_DIR}/results.xml -d memory_limit=1024M --coverage-html ${UNITTEST_REPORT_DIR}/coverage-report"
else
    docker exec ${APACHE_CONTAINER_ID} \
        /bin/bash -c "cd ${WORK_DIR}; set -e; vendor/bin/phpunit --log-junit ${UNITTEST_REPORT_DIR}/results.xml"
fi

# Copy test results to host from docker
docker cp ${APACHE_CONTAINER_ID}:"${WORK_DIR}/${UNITTEST_REPORT_DIR}" ${PWD}/${UNITTEST_REPORT_DIR}

# Remove running docker
docker rm -f ${APACHE_CONTAINER_ID}

