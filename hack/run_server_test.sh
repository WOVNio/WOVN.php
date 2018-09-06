#!/usr/bin/env bash
set -eux
docker_name=$1
dummy_container="dummy_$(date +%s)"

function waitServerUp {
  try_count=0
  status=`docker exec $(docker ps -q) curl localhost -o /dev/null -v 2>&1 | grep 200` || true
  while [ ${#status} = 0 ] && [ ${try_count} -lt 30 ]; do
    try_count=$[${try_count}+1];
    echo 'retry curl to equalizer....'
    sleep 1;
    status=`docker exec $(docker ps -q) curl localhost -o /dev/null -v 2>&1 | grep 200` || true
  done
}

# Create a dummy container which will hold a volume with source
docker create -v /var/www/html --name $dummy_container $docker_name /bin/true

# Copy source to dummy container
docker cp $(pwd)/.  $dummy_container:/var/www/html/WOVN.php
docker cp $(pwd)/integration_test/. $dummy_container:/var/www/html

# Kill all running containers
docker kill $(docker ps -q) || true

# Run apache container
docker run -d --volumes-from $dummy_container $docker_name

waitServerUp

# diff returns non 0 if there are differences or troubles.
docker exec $(docker ps -q) curl "localhost/index.php?wovn=ja" -o /tmp/result.txt
docker exec $(docker ps -q) diff /tmp/result.txt /var/www/html/index_expected.html

# diff returns non 0 if there are differences or troubles.
docker exec $(docker ps -q) curl "localhost/amp.php" -o /tmp/result.txt
docker exec $(docker ps -q) diff /tmp/result.txt /var/www/html/amp_expected.html

# STATIC CONTENT INTERCEPTION

mod_rewrite_activation="a2enmod rewrite"
if [ "${docker_name}" == "php:5.3-apache" ]; then
  mod_rewrite_activation="${mod_rewrite_activation}; apache2 -D FOREGROUND"
else
  mod_rewrite_activation="${mod_rewrite_activation}; apache2-foreground"
fi

# Kill all running containers
docker kill $(docker ps -q) || true

docker cp $(pwd)/htaccess_sample  $dummy_container:/var/www/html/.htaccess
docker cp $(pwd)/wovn_index_sample.php $dummy_container:/var/www/html/wovn_index.php

docker run -d --volumes-from $dummy_container $docker_name /bin/bash -c "${mod_rewrite_activation}"

waitServerUp

docker exec $(docker ps -q) curl "localhost/static.html?a=b" -o /tmp/result.txt
docker exec $(docker ps -q) diff /tmp/result.txt /var/www/html/static_expected.html
