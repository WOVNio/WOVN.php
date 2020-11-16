# WOVN.php debug environment

## How to use docker
### Start
1. build docker image  
Build docker is needed only for the first time.
```
make build
```

2. Configure wovn.ini  
Rewrite wovn.ini.sample with your config.

3. Configure your .htaccess  
Rewrite htaccess_sample with your config.

4. Run docker  
Run the following command.  
You should be able to see the content located at `docker/public` when you access `localhost`.
```
make start
```

### Stop
Stop docker.  
Make sure that old docker is stopped before start again.
```
make stop
```

### Remove all
Remove docker and those volumes.
```
make clean
```

### Debug by XDebug
#### XDebug configuration for VSCode
You can use the following setting for `launch.json`.  
After start debugging, you can break by break points.  
```
{
    "version": "0.2.0",
    "configurations": [
        {
            "name": "Listen for XDebug",
            "type": "php",
            "request": "launch",
            "port": 9001,
            "pathMappings": {
                "/var/www/html/WOVN.php": "${workspaceRoot}",
            },
        },
        {
            "name": "Listen for XDebug in tests",
            "type": "php",
            "request": "launch",
            "port": 9001,
            "pathMappings": {
                "/opt/project": "${workspaceRoot}"
            },
        }
    ]
}
```

#### Debug by XDebug
Start `Listen for XDebug` from `Run` menu in VSCode.  
You can set break point.  

If you have a trouble, Check `zend_extension` in `docker/php.ini`.  
You can see `zend_extension` info by running `phpinfo()` method in PHP.  

You can see log of XDebug by `docker/apache/log/xdebug.log`.

## How to run tests in your local

### Phpunit options tips
You can run spesific tests by the followings way.
```
vendor/bin/phpunit --filter <test name> <relative path to target test file>
```
`--filter`: You can specify test name.  
`--debug`: You can see test details.

### Run tests
If you run the following command, tests will be run with PHP in your local PC.
```
vendor/bin/phpunit
```

When you want to run tests with Docker, you can do that by the following way.  

1. Run Docker with `docker/test.yml` (`DOCKER_COMPOSE_YML` in `makefile`)
```
make stop && make start_test
```

2. Start `Listen for XDebug in tests` from VSCode.
You can set break point.  
If you don't need to debug, skip this step.

3. Run tests
```
make test_unit_with_docker
make test_integration_with_docker
```
Or you can run it step by step.
```
docker exec -it apache bash
```
```
cd /opt/project
vendor/bin/phpunit
vendor/bin/phpunit --configuration phpunit_integration.xml
```

## Nginx environment
You can use with Nginx, if you change from `apache.yml` to `nginx.yml` in `makefile`.

## With WordPress environment
You can use with WordPress, if you change from `apache.yml` to `wp_apache.yml` in `makefile`.  
Don't forget to configure the followings.
- .htaccess
- wovn_intercepter.php

### Change directory of WordPress files
You can change WordPress files directory with `working_dir: /var/www/html/anywhere` in `wp_apache.yml`.
