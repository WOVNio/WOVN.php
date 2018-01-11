#!/usr/bin/env bash

set -eux
docker_name=$1

docker run -t -v $(pwd):/opt/source -w /opt/source ${docker_name} /bin/bash -c 'a=$(find /opt/source -type f -name "*.php" !  -path "*/vendor/*" -print0 | xargs -0 -n 1 -P 8 php -l | grep -v "No syntax errors" | wc -l) && exit $a'
docker run -t -v $(pwd):/opt/source -w /opt/source ${docker_name} /bin/bash -c /opt/source/vendor/bin/phpunit
