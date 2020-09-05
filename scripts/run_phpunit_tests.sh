#!/usr/bin/env bash

set -eux
docker_name=$1
dummy_container="dummy_$(date +%s)"

VOLUME=/opt
WORK_DIR=${VOLUME}/project

UNITTEST_REPORT_DIR=.phpunit/phpunit

mkdir -p ${PWD}/${UNITTEST_REPORT_DIR}

# Create a dummy container which will hold a volume with source
docker create -v ${VOLUME} --name $dummy_container $docker_name /bin/true
# Copy source to dummy container
docker cp $(pwd) $dummy_container:${WORK_DIR}

# Check syntax
docker run --rm -t -w ${WORK_DIR} --volumes-from $dummy_container $docker_name \
       /bin/bash -c 'a=$(find /opt/project -type f -name "*.php" !  -path "*/vendor/*" -print0 | xargs -0 -n 1 -P 8 php -l | grep -v "No syntax errors" | wc -l) && exit $a'


# Run unit test
if [[ "${docker_name}" =~ ^php:7.*$ ]]; then
    docker run -t -w ${WORK_DIR} --volumes-from $dummy_container $docker_name \
           /bin/bash -c "set -e; phpdbg -qrr vendor/bin/phpunit --log-junit ${UNITTEST_REPORT_DIR}/results.xml -d memory_limit=1024M --coverage-html ${UNITTEST_REPORT_DIR}/coverage-report"
else
    docker run -t -w ${WORK_DIR} --volumes-from $dummy_container $docker_name \
           /bin/bash -c "set -e; vendor/bin/phpunit --log-junit ${UNITTEST_REPORT_DIR}/results.xml"
fi
docker cp $dummy_container:"${WORK_DIR}/${UNITTEST_REPORT_DIR}" ${PWD}/${UNITTEST_REPORT_DIR}

