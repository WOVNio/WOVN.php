# WOVN.php debug environment with Apache

## Start
1. build docker image  
Build docker for the first time.
```
make build
```

2. Set wovn.ini  
Rewrite wovn.ini.sample with your config.

3. Set your .htaccess
Rewrite htaccess_sample with your config.

4. Run docker  
Run the following command.  
You should be able to see the content located at `/docker/public` when you access `localhost`.
```
make start
```

## Stop
```
make stop
```

## Remove all
```
make clean
```

## Run test with PHP version which you like
1. Change makefile to use `test.yml`.
```
DOCKER_COMPOSE_YML = docker/test.yml
```
2. Run docker 
```
make stop && make start
```
3. Enable to run command inside docker
```
docker exec -it apache bash
```
4. Go to target dir
```
cd /opt/project
```
5. Run tests
```
vendor/bin/phpunit
vendor/bin/phpunit --configuration phpunit_integration.xml
```

## Nginx environment
You can use with Nginx, if you change from `apache.yml` to `nginx.yml` in `makefile`.

## With Wordpress environment
You can use with Wordpress, if you change from `apache.yml` to `wp_apache.yml` in `makefile`.
You can set wordpress directory with `working_dir: /var/www/html/anywhere` in `wp_apache.yml`.
