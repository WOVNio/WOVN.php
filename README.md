# WOVN.php Instructions

## Table of Contents
1. [Requirements](#1-requirements)
2. [Installation](#2-installation)
3. [Configuration](#3-configuration)
4. [Bug Report](#4-bug-report)
5. [Troubleshooting](#5-troubleshooting)

## 1. Requirements
WOVN.php requires PHP 5.3 or higher. WOVN.php has no third-party dependencies.
Depending on your configuration, you might have to install and/or activate the
PHP module `mod_rewrite` (see [Section 2.3.2.](#232-for-static-websites) and
[Section 3.](#3-configuration)).

WOVN.php has been tested with Apache 2 and Nginx. We provide installation
instructions for both. If you use other technologies, we encourage you to
[contact us](mailto:support@wovn.io) for support.

## 2. Installation
### 2.1. Download WOVN.php
To install WOVN.php, you have to manually download WOVN.php from our Github
repository. The root directory of WOVN.php must be place  at the root of your
website's directory. In the rest of this document, we consider the root
directory of your website to be `/website/root/directory`.
```
$ cd /website/root/directory
$ wget https://github.com/WOVNio/WOVN.php/archive/master.zip -O WOVN.php.zip
$ unzip WOVN.php.zip; mv WOVN.php-master WOVN.php
```

**Note on updates:** When you need to update WOVN.php, you can simply replace
all the content of `WOVN.php` directory by the content of the new version.

### 2.2. Basic configuration
In order for WOVN.php to work with your WOVN.io project, you need to fill a
configuration file. The configuration file must be named `wovn.ini` and be
placed at the root of your website's directory. You can start from the sample
file at `WOVN.php/wovn.ini.sample`.
```
$ cp WOVN.php/wovn.ini.sample wovn.ini
```

In this section, we give you the basic configuration you should use to get
started. You can find complete details of WOVN.php configuration at
[Section 3.](#3-configuration). To get started, you need to know at least your
WOVN.io project token, the original language of your website and the languages
your website can be translated into by WOVN.io. To obtain your project token,
you can visit your project dashboard, click on "INTEGRATION METHODS" and then
select the "PHP Library" installation method.

Bellow is an example of `wovn.ini` for a project with token "TOKEN", original
language English (`en`) and translated languages Japanese (`ja`) and French
(`fr`).
```
project_token = TOKEN
url_pattern_name = query
default_lang = en
supported_langs[] = ja
supported_langs[] = fr
```

At the end of this stage, the file structure of you website should look like
below.
```
+ /website/root/directory
  + WOVN.php
  - wovn.ini
  [...]
```

### 2.3. Activate WOVN.php
In order for WOVN.php to localize your website, you need to activate content
interception. There are two activation methods depending on how you Web pages
are generated. If your Web pages are generated by PHP files, please follow the
[instructions for dynamic websites](#231-for-dynamic-websites). If your Web
pages are pure HTML, please follow the
[instructions for static websites](#232-for-static-websites).

#### 2.3.1. For dynamic websites
When your Web pages are generated by PHP files, you need to require WOVN.php
interception script within each PHP file generating content. Please use the
following code. It must be inserted at the beginning of PHP files.
```
require_once('/website/root/directory/WOVN.php/src/wovn_interceptor.php');
```

#### 2.3.2. For static websites
When your Web pages are pure HTML, you need to create a `wovn_index.php` file
that you will use to serve and localize your HTML pages. We recommend you to
start with the sample that we provide.
```
$ cp WOVN.php/wovn_index_sample.php wovn_index.php
```
**Note for SSI users:** If you are using our sample `wovn_index.php`, please
follow the `# SSI USER` instructions inside the code.

Once your `wovn_index.php` is setup, you need to configure your website so that
any request to HTML pages are redirected to `wovn_index.php`. If you are using
an Apache server, please follow the
[instructions for Apache](#redirect-to-wovn_indexphp-with-apache). If you are
using Nginx withtout Apache, please follow the
[instructions for Nginx](#redirect-to-wovn_indexphp-with-nginx).

##### Redirect to `wovn_index.php` with Apache
For redirecting requests to `wovn_index.php`, we recommend using `.htaccess`
configuration with `mod_rewrite` PHP module. Please follow the
[official instructions](https://httpd.apache.org/docs/2.4/) for installing and
activating `mod_rewrite` module (in some cases, `mod_rewrite` is already
installed but not activated).

Bellow is the `.htaccess` configuration you should use.
```
<IfModule mod_rewrite.c>
  RewriteEngine On

  # Don't intercept .cgi files, as they won't execute
  RewriteCond %{THE_REQUEST} \.cgi
  RewriteRule .? - [L]

  # Intercept only static content: html and htm urls
  # Warning: do not remove this line or other content could be loaded
  RewriteCond %{REQUEST_URI} /$ [OR]
  RewriteCond %{REQUEST_URI} \.(html|htm|shtml|php|php3|phtml)
  # Use the wovn_index.php to handle static pages
  RewriteRule .? wovn_index.php [L]
</IfModule>
```

Alternatively, you can also copy the file `htaccess_sample` from `WOVN.php`
directory.
```
$ cp WOVN.php/htaccess_sample .htaccess
```

##### Redirect to `wovn_index.php` with Nginx
For redirecting to `wovn_index.php`, you need to update your Nginx configuration
(`/etc/nginx/conf.d/site.conf`). Bellow is an highlight of the configurations
you need to add in the congiguration file.
```
server {
  # ...

  # php configuration
  location ~ \.php$ {
    # ...
  }

  location / {
    # ...

    # WOVN.php interception ####################################################

    # intercept static content with WOVN.php
    if ($uri ~ (/|\.(html|htm))$) {
      rewrite .? /wovn_index.php;
    }
  }
```

## 3. Configuration

## 4. Bug Report

## 5. Troubleshooting
