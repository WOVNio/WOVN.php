# Welcome to WOVN.php

## I. Basic install
### 1. Clone the repository in your application root folder
### 2. Edit the configuration file
 - project_token: your WOVNio's project token
 - url_pattern: query, path or subdomain (default is query)
### 3. Require the wovn_interceptor.php file in your application
 `require_once('/path/to/wovnphp/src/wovn_interceptor');`

## Composer install
### 1. Require the package in your composer.json file
```
require: {
    "WOVNio/WOVN.php": "*"
}
```
### 2. Edit the configuration file
 - project_token: your WOVNio's project token
 - url_pattern: query, path or subdomain (default is query)
