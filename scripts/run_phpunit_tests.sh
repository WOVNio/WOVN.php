#!/usr/bin/env bash

set -eux
docker_name=$1
dummy_container="dummy_$(date +%s)"

# Create a dummy container which will hold a volume with source
docker create -v /opt --name $dummy_container $docker_name /bin/true

# Copy source to dummy container
docker cp $(pwd)  $dummy_container:/opt/project

# Check syntax
docker run -t -w /opt/project --volumes-from $dummy_container $docker_name /bin/bash -c 'a=$(find /opt/project -type f -name "*.php" !  -path "*/vendor/*" -print0 | xargs -0 -n 1 -P 8 php -l | grep -v "No syntax errors" | wc -l) && exit $a'

# Run test
docker run -t -w /opt/project --volumes-from $dummy_container $docker_name /bin/bash -c "set -e; /opt/project/vendor/bin/phpunit;"
