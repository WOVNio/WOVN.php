#!/usr/bin/env bash

set -eux
docker_name=$1
dummy_container="dummy_$(date +%s)"

# Create a dummy container which will hold a volume with source
docker create -v /opt --name $dummy_container $docker_name /bin/true

# Copy source to dummy container
docker cp $(pwd)  $dummy_container:/opt/project

# Check syntax
docker run --rm -t -w /opt/project --volumes-from $dummy_container $docker_name \
       /bin/bash -c 'a=$(find /opt/project -type f -name "*.php" !  -path "*/vendor/*" -print0 | xargs -0 -n 1 -P 8 php -l | grep -v "No syntax errors" | wc -l) && exit $a'

# Run unit test
if [[ "${docker_name}" =~ ^php:7.*$ ]]; then
    docker run --rm -t -w /opt/project -v /tmp:/tmp --volumes-from $dummy_container $docker_name \
           /bin/bash -c "set -e; phpdbg -qrr /opt/project/vendor/bin/phpunit --log-junit /tmp/phpunit/junit.xml -d memory_limit=1024M --coverage-html /tmp/phpunit/coverage-report"
else
    docker run --rm -t -w /opt/project -v /tmp:/tmp --volumes-from $dummy_container $docker_name \
           /bin/bash -c "set -e; /opt/project/vendor/bin/phpunit --log-junit /tmp/phpunit/junit.xml"
fi

# Run Apache for integration test
mod_rewrite_activation="a2enmod rewrite"
if [ "${docker_name}" == "php:5.3-apache" ]; then
    mod_rewrite_activation="${mod_rewrite_activation}; apache2 -D FOREGROUND"
else
    mod_rewrite_activation="${mod_rewrite_activation}; apache2-foreground"
fi

docker run -d -w /var/www/html \
       -v /tmp:/tmp \
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
       /bin/bash -c "set -e; ln -s /var/www/html /opt/project/test/docroot && /opt/project/vendor/bin/phpunit --configuration phpunit_integration.xml --log-junit /tmp/phpunit/junit.integration.xml"

