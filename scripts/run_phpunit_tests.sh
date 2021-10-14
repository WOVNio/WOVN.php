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
docker pull ${DOCKER_IMAGE}
docker tag ${DOCKER_IMAGE} base-image
docker build --build-arg DOCKER_IMAGE=${DOCKER_IMAGE} --no-cache -t ${NEW_DOCKER_IMAGE} -f ./docker/test.Dockerfile ./docker/apache

# Start running docker and copy files (Volume feature doesn't work with CircleCI.)
APACHE_CONTAINER_ID=`docker run -itd -e WOVN_ENV=development --name ${CONTAINER_NAME} ${NEW_DOCKER_IMAGE} /bin/bash`
docker cp $(pwd) ${APACHE_CONTAINER_ID}:${WORK_DIR}

# Display PHP version
docker exec ${APACHE_CONTAINER_ID} /bin/bash -c "php --version"

if [[ "${DOCKER_IMAGE}" =~ ^.*php:?(7\.[1-9]|8\.[0-9]).*$ ]]; then
    # Convert test to support PHP8 syntax
    docker exec ${APACHE_CONTAINER_ID} /bin/bash -c "find ${WORK_DIR}/test -type f -name \"*.php\" -print0 | xargs -0 sed -i 's/function setUp(.*)$/function setUp(): void/g'"
    docker exec ${APACHE_CONTAINER_ID} /bin/bash -c "find ${WORK_DIR}/test -type f -name \"*.php\" -print0 | xargs -0 sed -i 's/function setUpBeforeClass(.*)$/function setUpBeforeClass(): void/g'"
    docker exec ${APACHE_CONTAINER_ID} /bin/bash -c "find ${WORK_DIR}/test -type f -name \"*.php\" -print0 | xargs -0 sed -i 's/function tearDown(.*)$/function tearDown(): void/g'"
    docker exec ${APACHE_CONTAINER_ID} /bin/bash -c "find ${WORK_DIR}/test -type f -name \"*.php\" -print0 | xargs -0 sed -i 's/function tearDownAfterClass(.*)$/function tearDownAfterClass(): void/g'"
fi

# Set up modules
if [[ "${DOCKER_IMAGE}" =~ ^.*php:?5\.3.*$ ]]; then
    # Convert test to support PHP5.3 syntax
    docker exec ${APACHE_CONTAINER_ID} /bin/bash -c "find ${WORK_DIR}/test -type f -name \"*.php\" -print0 | xargs -0 sed -i 's/^use PHPUnit\\\Framework\\\TestCase;$//g'"
    docker exec ${APACHE_CONTAINER_ID} /bin/bash -c "find ${WORK_DIR}/test -type f -name \"*.php\" -print0 | xargs -0 sed -i 's/extends TestCase$/extends \\\PHPUnit_Framework_TestCase/g'"

    # Copy modules for PHP53
    docker exec ${APACHE_CONTAINER_ID} /bin/bash -c "rm -rf ${WORK_DIR}/vendor"
    docker exec ${APACHE_CONTAINER_ID} /bin/bash -c "unzip -q -o -d ${WORK_DIR} ${WORK_DIR}/test/vendor_for_php53.zip"
    docker exec ${APACHE_CONTAINER_ID} /bin/bash -c "mv ${WORK_DIR}/vendor_for_php53 ${WORK_DIR}/vendor"
else
    # Re-install modules
    # Remove modules
    docker exec ${APACHE_CONTAINER_ID} /bin/bash -c "cd ${WORK_DIR}; rm -rf vendor"
    docker exec ${APACHE_CONTAINER_ID} /bin/bash -c "cd ${WORK_DIR}; rm composer.lock"

    # install isrg-root-x1-cross-signed CA
    docker exec ${APACHE_CONTAINER_ID} /bin/bash -c "cd ${WORK_DIR}; cp ./scripts/isrg-root-x1-cross-signed.crt /usr/share/ca-certificates/isrg-root-x1-cross-signed.crt"
    docker exec ${APACHE_CONTAINER_ID} /bin/bash -c "echo /usr/share/ca-certificates/isrg-root-x1-cross-signed.crt >> /etc/ca-certificates.conf"
    docker exec ${APACHE_CONTAINER_ID} /bin/bash -c "update-ca-certificates"

    # Install modules
    docker exec ${APACHE_CONTAINER_ID} /bin/bash -c "cd ${WORK_DIR}; php -d suhosin.executor.include.whitelist='phar' ./scripts/composer-setup.php --disable-tls --install-dir=/usr/local/bin --filename=composer"
    docker exec ${APACHE_CONTAINER_ID} /bin/bash -c "cd ${WORK_DIR}; composer update"
fi

# Check syntax
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

