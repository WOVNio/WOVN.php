# WOVN.php Instructions

## Table of Contents
1. [Requirements](requirements)
2. [Installation](installation)
3. [Configuration](configuration)
4. [Bug Report](bug-report)
5. [Troubleshooting](frequently-asked-questions)

## 1. Requirements
WOVN.php requires PHP 5.3 or higher. WOVN.php has no third-party dependencies.
Depending on your configuration, you might have to install and/or activate the
PHP module `mod_rewrite` (see [Section 3.](configuration)).

WOVN.php has been tested with Apache 2 and Nginx. We provide installation
instructions for both. If you use other technologies, we encourage you to
[contact us](mailto:support@wovn.io) for support.

## 2. Installation
### 2.1. Downloading WOVN.php
To install WOVN.php, you have to manually download WOVN.php from our Github
repository. The root directory of WOVN.php must be place  at the root of your
website's directory.
```
$ cd /website/root/dirctory
$ wget https://github.com/WOVNio/WOVN.php/archive/master.zip -O WOVN.php.zip
$ unzip WOVN.php.zip; mv WOVN.php-master WOVN.php
```

**Note on updates:** When you need to update WOVN.php, you can simply replace
all the content of `WOVN.php` directory by the content of the new version.

### 2.2. Basic configuration
In order for WOVN.php to work with you WOVN.io project, you need to fill a
configuration file. The configuration file must be named `wovn.ini` and be
placed at the root of your website's directory. You start from the sample file
at `WOVN.php/wovn.ini.sample`.
```
$ cp WOVN.php/wovn.ini wovn.ini
```

In this section, we give you the basic configuration you should use to get
started. You can find complete details of WOVN.php configuration at
[Section 3.](configuration). To get started, you need to know at least your
WOVN.io project token, the original language of your website and the languages
your website can be translated into by WOVN.io. To obtain your project token,
you can visit your project dashboard, click on "INTEGRATION METHODS" and then
select the "PHP Library" installation method.

Bellow is an example configuration for a project with token "TOKEN", original
language English (`en`) and translated languages Japanese (`ja`) and French
(`fr`).
```
project_token = TOKEN
url_pattern_name = query
default_lang = en
supported_langs[] = ja
supported_langs[] = fr
```

At the end of this stage, the file structure of you website should look like below.
```
+ /website/root/directory
  + WOVN.php
  - wovn.ini
  [...]
```

### 2.3. Activate WOVN.php
#### 2.3.1. For dynamic websites
#### 2.3.2. For static websites
##### With Apache
##### With Nginx

## 3. Configuration

## 4. Troubleshooting

## 5. Frequently Asked Questions
