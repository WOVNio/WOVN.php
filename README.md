# WOVN.php Instructions

## Table of Contents
1. [Requirements](#1-requirements)
2. [Getting Started](#2-getting-started)
3. [Configuration](#3-configuration)
4. [Bug Report](#4-bug-report)
5. [Troubleshooting](#5-troubleshooting)

## 1. Requirements
WOVN.php requires PHP 5.3 or higher. WOVN.php has no third-party dependencies.
Depending on your configuration, you might have to install and/or activate the
PHP module `mod_rewrite` (see [Section 2.3.2.](#232-for-static-websites) and
[Section 3.2.](#32-optional-parameters)).

WOVN.php has been tested with Apache 2 and Nginx. We provide installation
instructions for both. If you use other technologies, we encourage you to
[contact us](mailto:support@wovn.io) for support.

## 2. Getting Started
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
**Note for SSI users:** if you are using our sample `wovn_index.php`, please
follow the `# SSI USER` instructions inside the code.

Once your `wovn_index.php` is setup, you need to configure your website so that
any request to HTML pages are redirected to `wovn_index.php`. If you are using
an Apache server, please follow the
[instructions for Apache](#redirect-to-wovn_indexphp-with-apache). If you are
using Nginx (without Apache), please follow the
[instructions for Nginx](#redirect-to-wovn_indexphp-with-nginx).

#### Redirect to `wovn_index.php` with Apache
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

#### Redirect to `wovn_index.php` with Nginx
For redirecting to `wovn_index.php`, you need to update your Nginx configuration
(`/etc/nginx/conf.d/site.conf`). Below is an highlight of the configurations
you need to add in the configuration file.
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
You can configure WOVN.php from the `wovn.ini` file. Below we describe all
parameters you can set.

### 3.1. Required parameters
Below is the list of all parameters that you have to set for WOVN.php to work.

| Parameter         | Description                                                        | Example |
|-------------------|--------------------------------------------------------------------|-------- |
| `project_token`   | WOVN.io project token.                                             | `project_token = TOKEN` |
| `default_lang`    | Website's original language.                                       | `default_lang = en` |
| `supported_langs` | Website's original language<br>and WOVN.io translatable languages. | `supported_langs[] = ja`<br>`supported_langs[] = fr` |

### 3.2. Optional parameters
In this section we detail more options you can use with WOVN.php. Some of them
are dependant to the structure of your website whileothers are more advanced
and should be used for performance optimization.

#### `url_pattern_name`
This parameter defines how Web page URLs will be modified to include the
language information. WOVN.php supports three patterns.

| Option                               | URL Example                                | Example's language |
|--------------------------------------|--------------------------------------------|:------------------:|
| `url_pattern_name = query` (default) | `https://my-website.com/index.php`<br>`https://my-website.com/index.php?wovn=ja`<br>`https://my-website.com/index.php?wovn=fr`         | *Original*<br>Japanese<br>French         |
| `url_pattern_name = path`            | `https://my-website.com/index.php`<br>`https://my-website.com/ja/index.php`<br>`https://my-website.com/fr/index.php`         | *Original*<br>Japanese<br>French         |
| `url_pattern_name = subdomain`       | `https://my-website.com/index.php`<br>`https://ja.my-website.com/index.php`<br>`https://fr.my-website.com/index.php`         | *Original*<br>Japanese<br>French         |

**Note for path pattern users:** you need to change your server settings to
strip the languages code off of the URL before it is processed by you scripts.

For Apache users, you can add the following to your `.htaccess`. You will need
to activate the `mo_rewrite` PHP module. Please follow the
[official instructions](https://httpd.apache.org/docs/2.4/) for installing and
activating `mod_rewrite` module (in some cases, `mod_rewrite` is already
installed but not activated).
```
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteRule ^/?(?:ar|eu|bn|bg|ca|zh-CHS|zh-CHT|da|nl|en|fi|fr|gl|de|el|he|hu|id|it|ja|ko|lv|ms|my|ne|no|fa|pl|pt|ru|es|sw|sv|tl|th|hi|tr|uk|vi)($|/.*$) $1 [L]
</IfModule>
```

For Nginx (without Apache) users, you need to update your Nginx configuration
(`/etc/nginx/conf.d/site.conf`). Below is an highlight of the configurations
you need to add in the configuration file.
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

		# strip language code off of $uri
		rewrite ^/(ar|eu|bn|bg|ca|zh-CHS|zh-CHT|da|nl|en|fi|fr|gl|de|el|he|hu|id|it|ja|ko|lv|ms|my|ne|no|fa|pl|pt|ru|es|sw|sv|tl|th|hi|tr|uk|vi)(/.*)$ $2;

		# ...
  }
```

#### `custom_lang_aliases`
This parameter allows you to redefine the language codes used by WOVN.php. For
instance, if you want to use "japanese" instead of "ja" and "french" instead of
"fr", then you should set WOVN.php as below.
```
custom_lang_aliases[ja] = japanese
custom_lang_aliases[fr] = french
```

**Note for path URL pattern users:** you need to update your `.htacces` or Nginx
configuration accordingly.
`ar|eu|bn|bg|ca|zh-CHS|zh-CHT|da|nl|en|fi|fr|gl|de|el|he|hu|id|it|ja|ko|lv|ms|my|ne|no|fa|pl|pt|ru|es|sw|sv|tl|th|hi|tr|uk|vi`
would become
`ar|eu|bn|bg|ca|zh-CHS|zh-CHT|da|nl|en|fi|french|gl|de|el|he|hu|id|it|japanese|ko|lv|ms|my|ne|no|fa|pl|pt|ru|es|sw|sv|tl|th|hi|tr|uk|vi`
for the above example.

#### `query`
This parameter tells WOVN.php which query parameters make pages unique. Indeed,
WOVN.io ignores query parameters when searching a translated page (by default).
If you've created pages on WOVN.io with specific query parameters, you should
add those query parameters to WOVN.php settings.

For instance, if you have all three pages `https://my-website.com/index.php`,
`https://my-website.com/index.php?login=1` and
`https://my-website.com/index.php?forgot_password=1` on WOVN.io, then you should
set WOVN.php as below.
```
query[] = login
query[] = forgot_password
```

#### `ignore_paths`
#### `ignore_regex`
#### `ignore_class`
#### `encoding`
#### `api_timeout`
#### `disable_api_request_for_default_lang`
#### `use_proxy`
#### `override_content_length`
#### `clean_unprocessable_characters`
#### `use_server_error_settings`

Below is the list of all optional parameters that you can to set for WOVN.php.
We marked some of them as advanced, you should use them only if you have a good
understanding of your website's code and servers. If not, please
[contact us](mailto:support@wovn.io) before trying them out.

Parameter                              | Advanced | Description | Default | Example
---------------------------------------|:--------:|-------------|---------|--------
`query`                                |          |             |         |
`custom_lang_aliases`                  |          |             |         |
`ignore_paths`                         |          |             |         |
`ignore_regex`                         |          |             |         |
`ignore_class`                         |          |             |         |
`url_pattern_name`                     | •        | URL component where the language information will be added in your Web page URLs. | `query`
`encoding`                             | •        | Your content encoding. | `UTF-8`
`api_timeout`                          | •        | Maximum amount of time allowed to localize content (in seconds). | `1.0`
`disable_api_request_for_default_lang` | •        | Set to `1` for optimization. When set to `1`, Web pages requested in original language won't be sent to our translation API. | `0`
`use_proxy`                            | •        |             | `0`     |
`override_content_length`              | •        |             | `0`     |
`clean_unprocessable_characters`       | •        |             | `0`     |
`use_server_error_settings`            | •        |             | `0`     |

## 4. Bug Report

## 5. Troubleshooting
