# WOVN.php debug environment

## Start
1. build docker image
Build docker only for the first time.
```
docker-compose build
```

2. Set wovn.ini
Rewrite wovn.ini.sample

3. Run docker
You should be able to see the content when you access `localhost` .
```
docker-compose up -d
```

## Stop
```
docker-compose rm -fs
```

## use local html-swapper
1. wovn.ini
Add the following to wovn.ini.sample
```
api_url = "http://host.docker.internal:3001/v0/"
```

2. change widget url
Change from `j.wovn.io/1` to `j.dev-wovn.io:3000/1` at `src/wovnio/html/HtmlConverter.php`

3. run local html-swapper
