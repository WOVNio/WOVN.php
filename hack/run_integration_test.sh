#!/usr/bin/env bash
set -eux
docker_name=$1

function waitServerUp {
  try_count=0
  status=`curl localhost -o /dev/null -v 2>&1 | grep 200` || true
  while [ ${#status} = 0 ] && [ ${try_count} -lt 30 ]; do
    try_count=$[${try_count}+1];
    echo 'retry curl to equalizer....'
    sleep 1;
    status=`curl localhost -o /dev/null -v 2>&1 | grep 200` || true
  done
}

docker ps | grep -v CONTAINER | cut -d " " -f 1 | xargs docker kill || true

docker run -d -p 80:80 -v $(pwd):/var/www/html/WOVN.php -v $(pwd)/integration_test:/var/www/html ${docker_name}

waitServerUp

curl "localhost/index.php?wovn=ja" -o /tmp/result.txt

# diff returns non 0 if there are differences or troubles.
diff /tmp/result.txt integration_test/index_expected.html
