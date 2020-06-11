#!/usr/bin/env bash

set -eux
docker_name=$1
dummy_container="dummy_$(date +%s)"

VOLUME=/opt
WORK_DIR=${VOLUME}/project
PHPUNIT_OUTDIR=.phpunit

# Create a dummy container which will hold a volume with source
docker create -v /opt --name $dummy_container $docker_name /bin/true
# Copy source to dummy container
docker cp $(pwd) $dummy_container:${WORK_DIR}

# Check syntax
docker run --rm -t -w ${WORK_DIR} --volumes-from $dummy_container $docker_name \
       /bin/bash -c 'a=$(find /opt/project -type f -name "*.php" !  -path "*/vendor/*" -print0 | xargs -0 -n 1 -P 8 php -l | grep -v "No syntax errors" | wc -l) && exit $a'

# Run unit test
if [[ "${docker_name}" =~ ^php:7.*$ ]]; then
    docker run -t -w ${WORK_DIR} --volumes-from $dummy_container $docker_name \
           /bin/bash -c "set -e; phpdbg -qrr vendor/bin/phpunit --log-junit ${PHPUNIT_OUTDIR}/phpunit/results.xml -d memory_limit=1024M --coverage-html ${PHPUNIT_OUTDIR}/coverage-report"
    docker cp $dummy_container:"${WORK_DIR}/${PHPUNIT_OUTDIR}" ${PWD}/
else
    docker run -t -w ${WORK_DIR} --volumes-from $dummy_container $docker_name \
           /bin/bash -c "set -e; vendor/bin/phpunit --log-junit ${PHPUNIT_OUTDIR}/phpunit/results.xml"
    docker cp $dummy_container:"${WORK_DIR}/${PHPUNIT_OUTDIR}" ${PWD}/
fi

# Replace for Integration test
if [ "${docker_name}" == "vectorface/php5.4" ]; then
    docker_name="php:5.4-apache"
elif [ "${docker_name}" == "vectorface/php5.3" ]; then
    docker_name="php:5.3-apache"
elif [ "${docker_name}" == "vectorface/hhvm" ]; then
    # TODO: Not supported yet.
    echo Not supported integration test for hhvm
    exit 0
fi

# Run Apache for integration test
mod_rewrite_activation="a2enmod rewrite"
if [ "${docker_name}" == "php:5.3-apache" ]; then
    mod_rewrite_activation="${mod_rewrite_activation}; apache2 -D FOREGROUND"
else
    mod_rewrite_activation="${mod_rewrite_activation}; apache2-foreground"
fi

docker run -d -w /var/www/html \
       --volumes-from $dummy_container \
       $docker_name /bin/bash -c "${mod_rewrite_activation}"

APACHE_CONTAINER_ID=$(docker ps -q)

function cleanup_container()
{
    docker stop ${APACHE_CONTAINER_ID} && docker rm ${APACHE_CONTAINER_ID}
    docker rm $dummy_container
}
trap cleanup_container EXIT

# Run integration test
docker exec -w /opt/project ${APACHE_CONTAINER_ID} \
       /bin/bash -c "set -e; ln -s /var/www/html /opt/project/test/docroot && vendor/bin/phpunit --configuration phpunit_integration.xml --log-junit ${PHPUNIT_OUTDIR}/phpunit.integration/results.xml"
docker cp ${APACHE_CONTAINER_ID}:${WORK_DIR}/${PHPUNIT_OUTDIR}/phpunit/junit.integration.xml ${PWD}/${PHPUNIT_OUTDIR}/phpunit/junit.integration.xml
