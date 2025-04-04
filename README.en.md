# WOVN.php Instructions

## Table of Contents
1. [Requirements](#1-requirements)
2. [Getting Started](#2-getting-started)
3. [Configuration](#3-configuration)
4. [Environment Variable](#4-Environment-Variable)
5. [Bug Report](#4-bug-report)

## 1. Requirements
WOVN.php requires PHP 5.3 or higher. WOVN.php has no third-party dependencies.
Depending on your configuration, you might have to install and/or activate the
Apache module `mod_rewrite` (see [Section 2.3.2.](#232-for-static-websites) and
[Section 3.2.](#32-optional-parameters)).

WOVN.php has been tested with Apache 2 and Nginx. We provide installation
instructions for both.

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
all the content of `WOVN.php` directory with the content of the new version.

### 2.2. Basic configuration
In order for WOVN.php to work with your WOVN.io project, you need to fill a configuration file. You have to choose from either a `.ini` configuration file or a `.json` configuration file.

For `.ini` configuration files, the file must be named `wovn.ini` and be placed at the root of your website's directory. You can start from the sample file at `WOVN.php/wovn.ini.sample`.

```
$ cp WOVN.php/wovn.ini.sample wovn.ini
```

**Note**: Starting from version 1.3.0, you must create the configuration file in JSON format if you want to make use of the `custom_domain` URL pattern. The `.json` configuration file must be named `wovn.json` and be placed at the root of your website's directory. You can start from the sample file at `WOVN.php/wovn.json.sample`. Additionally, you need to set a `mod_env` Apache internal environment variable called `WOVN_CONFIG` for WOVN.php to start using the JSON config file. For example, you can set this variable by adding `SetEnv WOVN_CONFIG` to your `.htaccess` file.

```
$ cp WOVN.php/wovn.json.sample wovn.json
```

In this section, we give you the basic configuration you should use to get
started. You can find complete details of WOVN.php configuration at
[Section 3.](#3-configuration) To get started, you need to know at least your
WOVN.io project token, the original language of your website and the languages
your website can be translated into by WOVN.io. To obtain your project token,
you can visit your project dashboard, click on "INTEGRATION METHODS" and then
select the "PHP Library" installation method.

Bellow is an example of `wovn.ini` for a project with token "TOKEN", original
language English (`en`) and translated languages Japanese (`ja`) and French
(`fr`).

`wovn.ini`

```ini
project_token = TOKEN
url_pattern_name = query
default_lang = en
supported_langs[] = ja
supported_langs[] = fr
```

`wovn.json`

```json
{
    "project_token": "TOKEN",
    "url_pattern_name": "query",
    "default_lang": "en",
    "supported_langs": [
        "ja",
        "fr",
        "en"
    ],
    "encoding": "UTF-8"
}
```



At the end of this stage, the file structure of you website should look like
below.

`wovn.ini`

```
+ /website/root/directory
  + WOVN.php
  - wovn.ini
  [...]
```

`wovn.json`

```
+ /website/root/directory
  + WOVN.php
  - wovn.json
  [...]
```

### 2.3. Activate WOVN.php

In order for WOVN.php to localize your website, you need to activate content
interception. There are two activation methods depending on how you web pages
are generated. If your web pages are generated by PHP files, please follow the
instructions for dynamic websites below. If your web pages are pure HTML, please
follow the instructions for static websites below.

#### 2.3.1. For dynamic websites
When your web pages are generated by PHP files, you need to require WOVN.php
interception script within each PHP file generating content. Please use the
following code. It must be inserted at the beginning of PHP files.
```
require_once('/website/root/directory/WOVN.php/src/wovn_interceptor.php');
```

#### 2.3.2. For static websites
When your web pages are pure HTML, you need to create a `wovn_index.php` file
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
  # For path pattern, remove language code
  # RewriteRule ^/?(?:ar|eu|bn|bg|ca|zh-CHS|zh-CHT|da|nl|en|fi|fr|gl|de|el|he|hu|id|it|ja|ko|lv|ms|my|ne|no|fa|pl|pt|ru|es|sw|sv|tl|th|hi|tr|uk|vi|km)($|/.*$) $1 [L]

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
directory. We suggest that you use this file as a starting point of your customized `.htaccess` file.

```
$ cp WOVN.php/htaccess_sample .htaccess
```

#### Redirect to `wovn_index.php` with Nginx
For redirecting to `wovn_index.php`, you need to update your Nginx configuration
(`/etc/nginx/conf.d/site.conf`). Below is an highlight of the code you need to
add in the configuration file.
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

| Parameter         | Description                                         | Example |
|-------------------|-----------------------------------------------------|-------- |
| `project_token`   | WOVN.io project token.                              | `project_token = TOKEN`     |
| `default_lang`    | Website's original language.                        | `default_lang = en`         |
| `supported_langs` | Website's original language<br>and WOVN.io translatable languages. | `supported_langs[] = ja`<br>`supported_langs[] = fr` |
| `url_pattern_name`| Pattern how to set language code into URL.          | `url_pattern_name = query`  |

#### `url_pattern_name`
This parameter defines how web page URLs will be modified to include the
language information. WOVN.php supports three patterns.

| Option                           | Description                            |  URL Examples                               |
|----------------------------------|----------------------------------------|---------------------------------------------|
|`url_pattern_name = query`        |Insert language code into query.        | [Original] `https://my-website.com/index.php`<br>[Japanese] `https://my-website.com/index.php?wovn=ja`<br>[French] `https://my-website.com/index.php?wovn=fr`|
|`url_pattern_name = path`         |Insert language code into head of path. | [Original] `https://my-website.com/index.php`<br>[Japanese] `https://my-website.com/ja/index.php`<br> [French] `https://my-website.com/fr/index.php`         |
|`url_pattern_name = subdomain`    |Insert language code into domain.       | [Original] `https://my-website.com/index.php`<br>[Japanese] `https://ja.my-website.com/index.php`<br>[French] `https://fr.my-website.com/index.php`          |
|`url_pattern_name = custom_domain`|Set domain and path.                    | [Original] `https://my-website.com/index.php`<br>[Japanese] `https://ja.my-website.com/index.php`<br>[French] `https://fr.my-website.com/index.php`          |

**Note for path pattern users:**
You need to change your server settings to strip the language codes off of the
URL before it is processed by you scripts.

For Apache users, you can add the following rule at the top of your `.htaccess`.
You will need to activate the `mod_rewrite` Apache module. Please follow the
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
(`/etc/nginx/conf.d/site.conf`). Below is an highlight of the code you need to
add in the configuration file.
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

**Way to set custom_domain pattern:**  

With `custom_domain` pattern, you can set domain and path for all languages in `supported_langs`.
Starting from version 1.3.0, you must create the configuration file in JSON format if you want to make use of the `custom_domain` URL pattern.

`wovn.ini`

```ini
NOT SUPPORTED
```

`wovn.json`

```
{
  "url_pattern_name": "custom_domain"
}

```

#### `custom_domain_langs`

This parameter is valid and required, when `url_pattern_name` is `custom_domain`.
Set `custom_domain_langs` for all languages declared in `supported_langs`.

`wovn.ini`

```ini
NOT SUPPORTED
```

`wovn.json`

```json
{
  "custom_domain_langs": {
    "en": { "url": "www.site.com/english" },
    "ja": { "url": "www.site.co.jp/" },
    "fr": { "url": "fr.site.co.jp/" }
  }
}
```

For the example above, all request URLs that match `www.site.com/english/*` will be considered as requests in English language.
All request URLs other than the above that match `www.site.co.jp/*` will be considered as requests in Japanese langauge.
And, request URLs that match `fr.site.co.jp/*` will be considered as requests in French langauge.
With the above example configuration, the page `http://www.site.co.jp/about.html` in Japanese language will have the URL http://www.site.com/english/about.html as English language.

`custom_domain_langs` setting may only be used together with the `url_pattern_name = custom_domain` setting.

If this setting is used, each language declared in `supported_langs` must be given a custom domain.

The path declared for your original language must match the structure of the actual web server.
In other words, you cannot use this setting to change the request path of your content in original language.

### 3.2. Optional parameters
In this section we detail more options you can use with WOVN.php. Some of them
are dependant to the structure of your website whileothers are more advanced
and should be used for performance optimization.

| Parameter                                                                     | required url_pattern_name | Description                                                                 |
|-------------------------------------------------------------------------------| ------------------------- |-----------------------------------------------------------------------------|
| [lang_param_name](#lang_param_name)                                           | query                     | Setting query parameter                                                     |
| [custom_lang_aliases](#custom_lang_aliases)                                   | query, path, subdomain    | Setting language codes different from Wovn's default values                 |
| [ignore_paths](#ignore_paths)                                                 | all                       | Setting paths that should be excluded from translation                      |
| [ignore_regex](#ignore_regex)                                                 | all                       | Setting regex expressions on paths that should be excluded from translation |
| [ignore_class](#ignore_class)                                                 | all                       | Setting the HTML classes that should be excluded from translation           |
| [no_index_langs](#no_index_langs)                                             | all                       | Prevents search indexing, specified languages will not be embedded in SEO optimization tags |
| [no_hreflang_langs](#no_hreflang_langs)                                       | all                       | Specified languages will not be embedded in SEO optimization tags           |
| [encoding](#encoding)                                                         | all                       | Setting HTML content encoding                                               |
| [api_timeout](#api_timeout)                                                   | all                       | Setting timeout for translation requests                                    |
| [api_timeout_search_engine_bots](#api_timeout_search_engine_bots)             | all                       | Setting timeout for translation requests for search engine bots             |
| [disable_api_request_for_default_lang](#disable_api_request_for_default_lang) | all                       | Enable/disable translation requests for the defaut language                 |
| [use_proxy](#use_proxy)                                                       | all                       | Enable/disable use of proxy                                                 |
| [override_content_length](#override_content_length)                           | all                       | Enable/Disable recalculation of Content-Length and update                   |
| [check_amp](#check_amp)                                                       | all                       | Enable/Disable translation for AMP pages                                    |
| [site_prefix_path](#site_prefix_path)                                         | path                      | Changes where the language code is inserted                                 |
| [custom_domain_langs](#custom_domain_langs)                                   | custom_domain             | Use custom domains for supported languages                                  |
| [insert_hreflangs](#custom_domain_langs)                                      | all                       | Enable/disable addition of link tag with hreflang attribute                 |
| [use_cookie_lang](#use_cookie_lang)                                           | all                       | Enable redirect based on WOVN language cookie, if present                   |
| [compress_api_requests](#compress_api_requests)                               | all                       | Enable gzip compression for outbound requests to translation API            |
| [logging](#logging)                                                           | all                       | Enable and configure error logging                                          |
| [translate_canonical_tag](#translate_canonical_tag)                           | all                       | Enable the translation of canonical tag URL                                 |
| [outbound_proxy_host](#outbound_proxy_host--outbound_proxy_port)              | all                       | HTTP proxy server host used to connect to WOVN API                          |
| [outbound_proxy_port](#outbound_proxy_host--outbound_proxy_port)              | all                       | HTTP proxy server port used to connect to WOVN API                          |
| [hreflang_x_default_lang](#hreflang_x_default_lang)                           | all                       | Setting language used for "x-default hreflang"                         |

#### `lang_param_name`
This parameter is only valid for when `url_pattern_name = query`.

It allows you to set the query parameter name for declaring the language of the page.
The default value for this setting is `lang_param_name = wovn`, such that a page URL
in translated language English has the form
```
https://my-website.com/index.php?wovn=en
```
If you instead set the value like this

`wovn.ini`

```ini
lang_param_name = language
```
`wovn.json`

```json
{
"lang_param_name": "langauge"
}
```



The above URL example would have the form

```
https://my-website.com/index.php?language=en
```

#### `custom_lang_aliases`
This parameter allows you to redefine the language codes used by WOVN.php. For
instance, if you want to use "japanese" instead of "ja" and "french" instead of
"fr", then you should configure the config file as below.

`wovn.ini`

```ini
custom_lang_aliases[ja] = japanese
custom_lang_aliases[fr] = french
```

`wovn.json`

```json
{
"custom_lang_aliases": {
  "ja": "japanese",
  "fr": "french"
	}
}
```



**Note for path URL pattern users:**
You need to update your `.htacces` or Nginx configuration accordingly. For the
example above, `|ja|` and `|fr|` would become `|japanese|` and `|french|`
respectively in the expression
`ar|eu|bn|bg|ca|zh-CHS|zh-CHT|da|nl|en|fi|fr|gl|de|el|he|hu|id|it|ja|ko|lv|ms|my|ne|no|fa|pl|pt|ru|es|sw|sv|tl|th|hi|tr|uk|vi`.

#### `ignore_paths`

This parameter tells WOVN.php to not localize content withing given directories.

The directories given will only be matched against the beginning of the URL path.

For instance, if you want to not localize the `admin` directory of your website, you
should configure WOVN.php as below.

`wovn.ini`

```ini
ignore_paths[] = /admin
```
`wovn.json`

```json
{
  "ignore_paths": ["/admin"]
}
```

With this configuration, WOVN.php will ignore the following URLs

```Text
https://my-wesite.com/admin
https://my-wesite.com/admin/
https://my-website.com/admin/plugin.html
```
but allow the following
```Text
https://my-website.com/index.html
https://my-website.com/user/admin
https://my-website.com/adminpage
```

#### `ignore_regex`
This parameter is similar to `ignore_paths` (see [above](#ignore_paths)) except
that you can use regular expressions instead.

For instance, if you want to not localize the search pages, you should configure
`wovn.ini` as below. WOVN.php will localize
`https://my-website.com/search/index.php` but not
`https://my-website.com/search/01/` nor `https://my-website.com/search/02/`.

`wovn.ini`

```ini
ignore_regex[] = "/\/search\/\d\d\//"
```

`wovn.json`

```json
{
  "ignore_regex": ["/\/search\/\d\d\//"]
}
```

#### `ignore_class`

This parameter tells WOVN.php which HTML fragments it should ignore when
localizing. The classes given by `ignore_class` are HTML element classes. All
HTML elements with one or more ignored class would not be translated by
WOVN.php.

For instance, if you want to ignore every HTML elements of class `ignore` and
`no-translate`, you should configure WOVN.php as below.

`wonv.ini`

```ini
ignore_class[] = ignore
ignore_class[] = no-translate
```

`wovn.json`

```json
{
  "ignore_class": ["ignore", "no-translate"]
}
```


#### `no_hreflang_langs`

This parameter tells WOVN.php which languages should not have `hreflang` tags embedded (used for SEO).

```html
<link rel="alternate" hreflang="en" href="https://my-website.com/en/">
```

`wovn.ini`

```ini
no_hreflang_langs[] = en
```

`wovn.json`

```json
{
  "no_hreflang_langs": ["en"]
}
```

#### `no_index_langs`

This parameter tells WOVN.php which languages's HTML should be set `noindex`
to avoid index by web crawler. It also prevents `hreflang` tags from being embedded (see [no_hreflang_langs](#no_hreflang_langs)).

For instance, if you want to avoid index for English pages, add `en` as below.
`<meta name="robots" content="noindex">` tag will be inserted inside `head` tag
for English pages.

`wovn.ini`

```ini
no_index_langs[] = en
```

`wovn.json`

```json
{
  "no_index_langs": ["en"]
}
```

#### `encoding`

This parameter tells WOVN.php which encoding you use for you files. WOVN.php
supports 8 encodings: `UTF-8`, `EUC-JP`, `SJIS`, `eucJP-win`, `SJIS-win`, `JIS`,
`ISO-2022-JP` and `ASCII`. If you do not set the encoding, WOVN.php will detect
it automatically. However, encoding detection might take time, so we recommend
you to set the encoding for better performances.

For instance, if your website files are encoded in UTF-8, you should configure
WOVN.php as below.

`wovn.ini`

```
encoding = UTF-8
```

`wovn.json`

```json
{
	"encoding": "UTF-8"
}
```

#### `api_timeout`

This parameter tells WOVN.php the maximum amount of time that can be spent on
localizing content with our API. Indeed, we centralize most of our localization
logic on separate servers at WOVN.io and WOVN.php delegates most of the job to
them. Setting up the `api_timeout` will tell WOVN.php how long to wait for an
answer from our API. If the API is too long to respond, the original content
will be served. By default, the `api_timeout` is set to 1 second.

For instance, if you want to increase the default timeout up to 2 seconds, you
should configure `wovn.ini` as below.

`wovn.ini`

```ini
api_timeout = 2
```

`wovn.json`

```json
{
	"api_timeout": 2
}
```

#### `api_timeout_search_engine_bots`
A variation of `api_timeout`, integer, configures the translation API request timeout in seconds if WOVN.php has detected a request
to be coming from a search engine bot. Currently, bots from Google, Yahoo, Bing, Yandex, DuckDuckGo and Baidu are supported.

This setting defaults to `5`.

`wovn.json`

```json
{
  "api_timeout_search_engine_bots": 5
}
```

`wovn.ini`

```ini
api_timeout_search_engine_bots = 5
```

#### `disable_api_request_for_default_lang`

This parameter tells WOVN.php whether or not it should use our localization API
when content is requested in original language. By default, the
`disable_api_request_for_default_lang` option is set to `0` (false). It means
that WOVN.php will use our localization API even if the content does not have to
be translated. When this setting is set to `1`, you may notice more server
resource being used. This is because WOVN.php has to do some HTML parsing that
our localization API usually does (for instance, to insert `hreflang`
information). However, it will save web page loading time since it does not send
a request to our API. If you experience no resource issues, we recommand you to
deactivate API requests for original language as below.

`wovn.ini`

```ini
disable_api_request_for_default_lang = 1
```

`wovn.json`

```json
{
	"disable_api_request_for_default_lang": true
}
```

#### `use_proxy`

This parameter tells WOVN.php whether or not your content is served through a
proxy. By default, this setting is set to `1` (true). If your content is served
through a proxy, WOVN.php needs to know it when gathering information based on
requested URL.

`wovn.ini`

```ini
use_proxy = 1
```
`wovn.json`

```json
{
	"use_proxy": true
}
```

When `use_proxy` is active, WOVN.php will attempt to use URL protocol and host
from HTTP Headers `X-Forwarded-Proto` and `X-Forwarded-Host`. These are standard
fields for proxy forwarding.

Furthermore, WOVN.php will look for HTTP Header `X-Forwarded-Request-Uri`. This
may be manually set in order for WOVN.php to see the original client requested
URI (i.e "/japan/tokyo.html"). If using mod\_proxy and the ProxyPass directive,
for example, this HTTP Header may be set with the RequestHeader directive as
follows
```apache
ProxyPass        /japan http://my.subdomain.com
ProxyPassReverse /japan http://my.subdomain.com
RequestHeader    setifempty X-Forwarded-Request-Uri "expr=%{REQUEST_URI}"
```

#### `override_content_length`
This parameter tell WOVN.php whether or not it should update the response header
"Content-Length". For performance optimization, `override_content_length` is set
to `0` (false) by default. If you need to maintain the response header
"Content-Length" update, you should set `override_content_length` to `1` (true).

`wovn.ini`

```ini
override_content_length = 1
```

`wovn.json`

```json
{
  "override_content_length": true
}
```



#### `check_amp`

This parameter tells WOVN.php not to process your content if it is
an AMP (Accelerated Mobile Pages) compliant page.The default is disabled.
If you enable this parameter, WOVN.php will not change the content.
Therefore, we do not add any WOVN script tags.

`wovn.ini`

```ini
check_amp = 1
```

`wovn.json`

```json
{
  "check_amp": true
}
```



#### `site_prefix_path`

This parameter tells WOVN.php to only process requests under the specified path.
When translating URLs, the language identifier will be inserted after this prefix path.
This parameter is valid only when `url_pattern_name` is `path`.

For example, only `http://www.mysite.com/dir/*` is processed when `sitePrefixPath = dir` is set.
When `http://www.mysite.com/dir/index.html` is translated to English, language identifier will be added after specified path like `http://www.mysite.com/dir/en/index.html`.
URL which is not matched is not processed and snippet will not be inserted.

`wovn.ini`

```
site_prefix_path = dir1/dir2
```

`wovn.json`

```json
{
  "site_prefix_path": "dir1/dir2"
}
```

#### `insert_hreflangs`

This parameter tells WOVN.php to insert link tag with hreflang.
If setting is on, the tag like `<link rel="alternate" hreflang="en" href="https://my-website.com/en/">` will be inserted for published languages.

If setting is off, WOVN.php doesn't add any change to link tag with hreflang.

`wovn.ini`

```
insert_hreflangs = 1
```

`wovn.json`

```json
{
  "insert_hreflangs": true
}
```

#### `enable_wovn_diagnostics`

This parameter tells WOVN.php if it should turn on the included WOVN.php diagnostics tool. The default value is `false`. Please do not set this parameter unless you were told to do so.

If you set this parameter to `true`, you must also set the `wovn_diagnostics_username` and `wovn_diagnostics_password` parameters.

For more details, please refer to the Wovn Diagnostics Tool section.

`wovn.ini`

```ini
enable_wovn_diagnostics = true
```

`wovn.json`

```json
{
  "enable_wovn_diagnostics": true
}
```

#### `wovn_diagnostics_username`

This parameter is required when you set `enable_wovn_diagnostics` to `true`. This will be the username WOVN will use to gain access to the WOVN.php diagnostics tools.

`wovn.ini`

```ini
wovn_diagnostics_username = wovn_diagnostics_username
```

`wovn.json`

```json
{
  "wovn_diagnostics_username": "wovn_diagnostics_username"
}
```

#### `wovn_diagnostics_password`

This parameter is required when you set `enable_wovn_diagnostics` to `true`. This will be the password WOVN will use to gain access to the WOVN.php diagnostics tools.

`wovn.ini`

```ini
wovn_diagnostics_password = wovn_diagnostics_password
```

`wovn.json`

```json
{
  "wovn_diagnostics_password": "wovn_diagnostics_password"
}
```

#### `use_cookie_lang`
When set to true, WOVN.php will attempt to redirect a request to the default language to the language set in the `wovn_selected_lang` cookie. No redirect will happen if no such cookie is set.

`wovn.ini`

```ini
use_cookie_lang = true
```

`wovn.json`

```json
{
  "use_cookie_lang": true
}
```

#### `compress_api_requests`
When set to true, WOVN.php will attempt to compress requests made to the translation API if possible. Set to true by default.

`wovn.ini`

```ini
compress_api_requests = true
```

`wovn.json`

```json
{
  "compress_api_requests": true
}
```

#### `logging`

Configures WOVN.php's internal logging. When this section is included in `wovn.json`, WOVN.php's internal logging is enabled. WOVN.php's internal logging uses `error_log()` to log its messages.

`destination`: Optional, can only have the value of `file`. If this is set, WOVN.php's logs will be written into a file defined by `path`. If this not set, logs will be written to PHP's default handling location of `error_log`.

`path`: Required if `destination` is set. Configures which file WOVN.php's logs will be written to. Must be a fully qualified path.

`max_line_length`: Optional, defaults to `1024`. Lines longer than this setting will be truncated.

`wovn.json`

```json
{
  "logging": {
    "destination": "file",
    "path": "/var/logs/error_log.log",
    "max_line_length": 5124
  }
}
```

`wovn.ini`

```ini
logging[destination] = "file"
logging[path] = "/var/logs/error_log.log"
logging[max_line_length] = 5124
```

#### `translate_canonical_tag`
Configures if WOVN.php should automatically translate existing canonical tag in the HTML. When set to `true`, WOVN.php
will translate the canonical URL with the current language code according to your `url_pattern_name` setting. 
This setting defaults to `true`.

Example:
`<link rel="canonical" href="http://site.com/page.html">` may be translated to 
`<link rel="canonical" href="http://site.com/en/page.html">` if you are using `path` URL pattern.

`wovn.json`

```json
{
  "translate_canonical_tag": true
}
```

`wovn.ini`

```ini
translate_canonical_tag = true
```

#### `outbound_proxy_host / outbound_proxy_port`
Configures if WOVN.php should connect to our API using a proxy server.
`outbound_proxy_host` should be set to the proxy server host. `outbound_proxy_port` should be set to the proxy server port number.

`wovn.json`

```json
{
  "outbound_proxy_host": "site.com",
  "outbound_proxy_port": "8080",
}
```

`wovn.ini`

```ini
outbound_proxy_host = site.com
outbound_proxy_port = 8080
```

#### `hreflang_x_default_lang`
Configures the language code used to generate link tag with `hreflang="x-default"` attribute.

The language code used must be in `supported_langs`. If the language code is invalid, link tag will not be inserted.

If a link tag with `hreflang="x-default"` attribute exists, this setting does nothing.

```html
<link rel="alternate" hreflang="x-default" href="https://my-website.com/">
```

`wovn.json`

```json
{
  "hreflang_x_default_lang": "ja"
}
```

`wovn.ini`

```ini
hreflang_x_default_lang = ja
```

## 4. Environment Variable

### `WOVN_TARGET_LANG`

This environment variable sets the language code of the translation target as
retrieved from the HTTP request.
The user can get the target language code from this environment variable and
arbitrarily change the behavior of their program.

For example.
```
if ($_ENV['WOVN_TARGET_LANG'] == 'fr') {
    ... some kind of your code ...
}
```

### `WOVN_CONFIG`

This environment variable allows the user to change the default `wovn.ini` or `wovn.json` path to
an arbitrary configuration file path.
For example, you can make changes as follows

Users can set `$_SERVER['WOVN_CONFIG']` before loading `wovn_interceptor.php`.
```
$_SERVER['WOVN_CONFIG'] = ... your config path ...;

require_once('/path/to/WOVN.php/src/wovn_interceptor.php');
```

Or, users can use `.htaccess` to set as follows.
```
SetEnv WOVN_CONFIG /path/to/wovn.ini
```

**Note on configuration file path:** The path to the configuration file must be an absolute path.

## 5. Bug Report

To help us finding a solution to your issue we will require some information.
First we need to know on which web pages you encounter the issue, as well as
steps to reproduce. If possible, we would also need test accounts if your
issues occur on web pages behind authorization
(we recommend you to use a staging server for that matter).

If your problems are happening on your server side (widget `<script>` tag not
inserted, language not detected, redirections not correctly handled, etc), we
usually need more information. To help us find a solution to your issue as soon
as possible, we would need to know information like the followings.

| Info                 | Description                                                           |
|----------------------|-----------------------------------------------------------------------|
| PHP version          | must be 5.3 or higher                                                 |
| WOVN.php version     | You can check with `src/version.php`                                  |
| Structure            | Snapshots of your website's directories and files structure           |
| wovn.ini             | Your `wovn.ini`                                                       |
| wovn_index.php       | Your `wovn_index.php`, if it is used                                  |
| index.php            | Your `index.php`                                                      |
| Server type          | Nginx / Apache / both                                                 |
| Server config        | Your Nginx configuration file / Your all `.htaccess` of Apache        |
| Log                  | Error log when an error occurs                                        |
| Request restriction  | Request to `wovn.global.ssl.fastly.net` with 443 port must be allowed |
| Using SSI            | Whether you are using SSI(Server Side Includes)                       |

### Wovn Diagnostics Tool

WOVN.php ships with a diagnostics tool that automatically gathers information for debugging purposes. This tool is shippped disabled by default.

To enable the Wovn Diagnostics Tool, please do the following tasks:

1. Add `enable_wovn_diagnostics` parameter in your `wovn.ini` configuration file, and set it to `true`.
2. Add `wovn_diagnostics_username`  in your `wovn.ini` configuration file, and set it to a username of your choice. The diagnostics tool cannot be used if a username is not set.
3. Add `wovn_diagnostics_password`  in your `wovn.ini` configuration file, and set it to a password of your choice. The diagnostics tool cannot be used if a password is not set.

The configuration will take effect immediately.

Please only enable the diagnostics tool when it is necessary to do so.
