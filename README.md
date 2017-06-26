# Welcome to WOVN.php

## I. Basic install
### 1. Clone the repository in your application root folder
### 2. Edit the configuration file
These are the two main settings for your project
 - project_token: your WOVNio's project token
 - url_pattern_name: query, path or subdomain (default is query)

Please copy the sample file from the library `wovn.ini.sample`, rename it as `wovn.ini` and paste it on your framework root directory (same level as wovnphp directory)
### 3. Require the wovn_interceptor.php file in your application
 `require_once('/path/to/wovnphp/src/wovn_interceptor');`
<!--
## Composer install
### 1. Require the package in your composer.json file
```
require: {
    "WOVNio/WOVN.php": "*"
}
```
### 2. Edit the configuration file
These are the two main settings for your project
 - project_token: your WOVNio's project token
 - url_pattern: query, path or subdomain (default is query)

Please copy the sample file from the library `wovn.ini.sample`, rename it as `wovn.ini` and paste it on your framework root directory (same level as wovnphp directory)
-->
