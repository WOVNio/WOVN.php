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

## Use local html-swapper and widget
1. change html-swapper url  
Add the following line to wovn.ini.sample  
`host.docker.internal` is understood by docker as the localhost of host OS.
```
api_url = "http://host.docker.internal:3001/v0/"
```

2. change widget url  
Change from `j.wovn.io/1` to `j.dev-wovn.io:3000/1` at `src/wovnio/html/HtmlConverter.php`

3. run local html-swapper and widget

## Nginx environment
If you change from apache.yml to nginx.yml, you can use with Nginx.
