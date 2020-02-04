# WOVN.php debug environment with Apache

## Start
1. build docker image  
Build docker for the first time.
```
docker-compose -f apache.yml build
```

2. Set wovn.ini  
Rewrite wovn.ini.sample with your config.

3. Set your .htaccess
Rewrite htaccess_sample with your config.

4. Run docker  
Run the following command.  
You should be able to see the content located at `/docker/public` when you access `localhost`.
```
docker-compose -f apache.yml up -d
```

## Stop
```
docker-compose -f apache.yml rm -fs
```

## Nginx environment
You can use with Nginx, if you change from `apache.yml` to `nginx.yml`.

## With Wordpress environment
You can use with Wordpress, if you change from `apache.yml` to `wp_apache.yml`.
You can set wordpress directory with `working_dir: /var/www/html/anywhere` in `wp_apache.yml`.
