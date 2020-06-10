#!/usr/bin/env bash

set -eux
docker_name=$1
dummy_container="dummy_$(date +%s)"

mod_rewrite_activation="a2enmod rewrite"
if [ "${docker_name}" == "php:5.3-apache" ]; then
    mod_rewrite_activation="${mod_rewrite_activation}; apache2 -D FOREGROUND"
else
    mod_rewrite_activation="${mod_rewrite_activation}; apache2-foreground"
fi

docker create -v /opt --name $dummy_container $docker_name /bin/true
docker cp $(pwd) $dummy_container:/opt/project

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

docker exec -w /opt/project ${APACHE_CONTAINER_ID} /bin/bash -c "set -e; ln -s /var/www/html /opt/project/test/docroot"
docker exec -w /opt/project ${APACHE_CONTAINER_ID} /bin/bash -c "set -e; /opt/project/vendor/bin/phpunit --configuration phpunit_integration.xml"

