# WOVN.php Instructions

For English users: [English](README.en.md)

## Table of Contents

1. [必要条件](#1-必要条件)
2. [導入方法](#2-導入方法)
3. [設定](#3-設定)
4. [環境変数](#4-環境変数)
5. [バグ報告](#5-バグ報告)

## 1. 必要条件

WOVN.phpにはPHP 5.3以上が必要です。WOVN.phpにはサードパーティの依存関係はありません。

設定によっては、Apacheモジュール `mod_rewrite` をインストールしたり有効化したりする必要があるかもしれません ( [2.3.2.項](#232-静的ウェブサイト) や [3.2.項](#32-任意パラメータ) を参照してください)。
WOVN.phpはApache 2とNginxで動作確認済みです。両方のインストール方法を提供しています。

## 2. 導入方法

### 2.1. WOVN.php ダウンロード

WOVN.phpをインストールするには、当社のGithubリポジトリからWOVN.phpを手動でダウンロードする必要があります。
WOVN.php のルートディレクトリは、ウェブサイトのディレクトリのルートに配置する必要があります。

このドキュメントの残りの部分では、ウェブサイトのルートディレクトリを `/website/root/directory` とみなします。

```
$ cd /website/root/directory
$ wget https://github.com/WOVNio/WOVN.php/archive/master.zip -O WOVN.php.zip
$ unzip WOVN.php.zip; mv WOVN.php-master WOVN.php
```

**更新時の注意:**
WOVN.phpを更新する必要がある場合は、`WOVN.php`ディレクトリのすべての内容を新しいバージョンの内容に置き換えるだけです。

### 2.2. 基本的な設定

WOVN.ioプロジェクトでWOVN.phpを動作させるためには、設定ファイルを記入する必要があります。  
`.ini` 設定ファイルか `.json` 設定ファイルのどちらかを選択してください。

WOVN.ioプロジェクトでWOVN.phpを動作させるためには、設定ファイルを作成する必要があります。

設定ファイルは `wovn.ini` という名前で、ウェブサイトのディレクトリのルートに置かなければなりません。
サンプルファイルの`wovn.php/wovn.ini.sample`をコピーして利用して始めることができます。

```
$ cp WOVN.php/wovn.ini.sample wovn.ini
```

**注意**:

バージョン1.3.0以降、 `custom_domain` のURLパターンを利用したい場合は、設定ファイルをJSON形式で作成する必要があります。  
設定ファイルは、 `wovn.json` という名前で、ウェブサイトのディレクトリのルートに置かなければなりません。  
サンプルファイルは `wovn.php/wovn.json.sample` から起動することができます。Additionally, you need to set a `mod_env` Apache internal environment variable called `WOVN_CONFIG` for WOVN.php to start using the JSON config file. For example, you can set this variable by adding `SetEnv WOVN_CONFIG` to your `.htaccess` file.

```
$ cp WOVN.php/wovn.json.sample wovn.json
```


このセクションでは、開始するための基本的な設定を説明します。
WOVN.phpの設定の詳細については、[セクション3.](#3-設定)を参照してください。

開始するには、少なくともWOVN.ioのプロジェクトトークン、ウェブサイトのオリジナル言語、そして、ウェブサイトがWOVN.ioによって翻訳可能な言語を知っている必要があります。

プロジェクトトークンを取得するには、プロジェクトのダッシュボードにアクセスし、 "INTEGRATION METHODS" をクリックし、"PHP Library "のインストール方法を選択します。

以下は、トークンが "TOKEN"、翻訳元言語が英語(`en`)、翻訳先言語が日本語(`ja`)とフランス語(`fr`)のプロジェクトの `wovn.ini` の例です。

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


このステップの最後に、ウェブサイトのファイル構造は以下のようになります。

`wovn.ini`

```
+ /website/root/directory
  + WOVN.php
  - wovn_index.php
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


### 2.3. WOVN.phpの有効化

WOVN.phpでウェブサイトを翻訳するためには、コンテンツインターセプトを有効にする必要があります。

ウェブページの生成方法に応じて、2つの有効化方法があります。

ウェブページがPHPファイルで生成されている場合は、動的ウェブサイトの指示に従ってください。
ウェブページが純粋なHTMLである場合は、静的なウェブサイトの手順に従ってください。

#### 2.3.1. 動的ウェブサイト

WebページがPHPファイルで生成される場合、コンテンツを生成する各PHPファイル内にWOVN.phpインターセプトスクリプトが必要になります。
以下のコードを使用してください。PHPファイルの先頭に挿入する必要があります。

```
require_once('/website/root/directory/WOVN.php/src/wovn_interceptor.php');
```

#### 2.3.2. 静的ウェブサイト

Webページが純粋なHTMLである場合、HTMLページを提供し翻訳するために使用する `wovn_index.php` ファイルを作成する必要があります。
私たちは、私たちが提供するサンプルから始めることをお勧めします。

```
$ cp WOVN.php/wovn_index_sample.php wovn_index.php
```

**SSIユーザの方への注意:**
サンプルの `wovn_index.php` を使用している場合は、コード内の `# SSI USER` の指示に従ってください。

`wovn_index.php` を設定したら、HTMLページへのリクエストがすべて `wovn_index.php` にリダイレクトされるようにウェブサイトを設定する必要があります。
Apacheサーバを使っている場合は、[Apacheの説明](#apacheで-wovn_indexphp-にリダイレクト)に従ってください。
Nginx (Apacheなし) を使用している場合は、[Nginxの説明](#nginxで-wovn_indexphp-にリダイレクト) に従ってください。

#### Apacheで `wovn_index.php` にリダイレクト

リクエストを `wovn_index.php` にリダイレクトするには、`.htaccess` の設定に `mod_rewrite` Apache モジュールを使用することをお勧めします。
`mod_rewrite` モジュールのインストールと有効化については、[公式のドキュメント](https://httpd.apache.org/docs/2.4/)に従ってください( `mod_rewrite` がインストール済みで有効化されていない場合もあります)。

以下に `.htaccess` の設定を示します。

```
<IfModule mod_rewrite.c>
  RewriteEngine On

  # パスパターンの場合、言語コードを削除
  # RewriteRule ^/?(?:ar|eu|bn|bg|ca|zh-CHS|zh-CHT|da|nl|en|fi|fr|gl|de|el|he|hu|id|it|ja|ko|lv|ms|my|ne|no|fa|pl|pt|ru|es|sw|sv|tl|th|hi|tr|uk|vi)($|/.*$) $1 [L]

  # .cgi ファイルは対象外
  RewriteCond %{THE_REQUEST} \.cgi
  RewriteRule .? - [L]

  # 静的なコンテンツのみを対象: html と htm urls
  # 警告: この行を削除しないでください、他のコンテンツがロードされる可能性があります。
  RewriteCond %{REQUEST_URI} /$ [OR]
  RewriteCond %{REQUEST_URI} \.(html|htm|shtml|php|php3|phtml)
  # 静的なページを処理するために wovn_index.php を使用します。
  RewriteRule .? wovn_index.php [L]
</IfModule>
```

あるいは、`WOVN.php` ディレクトリから `htaccess_sample` というファイルをコピーすることもできます。

```
$ cp WOVN.php/htaccess_sample .htaccess
```

#### Nginxで `wovn_index.php` にリダイレクト

`wovn_index.php` にリダイレクトするには、Nginx の設定ファイル (`/etc/nginx/conf.d/site.conf`) を更新する必要があります。

以下は、設定ファイルに追加するコードの抜粋です。

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

## 3. 設定

WOVN.phpの設定は `wovn.ini` ファイルから行うことができます。以下では、設定できるすべてのパラメータについて説明します。

### 3.1. 必須パラメーター

以下は、WOVN.phpを動作させるために設定しなければならないすべてのパラメータのリストです。

| Parameter         | Description                                           | Example                                              |
|-------------------|-------------------------------------------------------|------------------------------------------------------|
| `project_token`   | WOVN.ioプロジェクトトークン                              | `project_token = TOKEN`                              |
| `default_lang`    | ウェブサイトの翻訳元言語                                  | `default_lang = en`                                  |
| `supported_langs` | ウェブサイトの翻訳元言語と<br>WOVN.ioで翻訳可能な言語        | `supported_langs[] = ja`<br>`supported_langs[] = fr` |
| `url_pattern_name`| URLに言語コードを挿入するパターン                          | `url_pattern_name = query`                           |

#### `url_pattern_name`

このパラメータは、ウェブページのURLがどのように言語情報を含むように変更されるかを定義します。

WOVN.php は 3 つのパターンをサポートしています。

| Option                           | Description            | URL Examples                                                             |
|----------------------------------|------------------------|--------------------------------------------------------------------------|
|`url_pattern_name = query`        |クエリに言語コードを挿入    | [Original] `https://my-website.com/index.php`<br>[Japanese] `https://my-website.com/index.php?wovn=ja`<br>[French] `https://my-website.com/index.php?wovn=fr` |
|`url_pattern_name = path`         |パスの先頭に言語コードを挿入 | [Original] `https://my-website.com/index.php`<br>[Japanese] `https://my-website.com/ja/index.php`<br>[French] `https://my-website.com/fr/index.php`           |
|`url_pattern_name = subdomain`    |ドメインに言語コードを挿入  | [Original] `https://my-website.com/index.php`<br>[Japanese] `https://ja.my-website.com/index.php`<br>[French] `https://fr.my-website.com/index.php`            |
|`url_pattern_name = custom_domain`|ドメインとパスを設定       | [Original] `https://my-website.com/index.php`<br>[Japanese] `https://ja.my-website.com/index.php`<br>[French] `https://my-website.com/fr/index.php`           |

**パスパターンをお使いの方への設定方法:**  
PHPスクリプトで処理される前に、URLから言語コードを取り除くために、サーバーの設定を変更する必要があります。

Apacheユーザの場合は、`.htaccess`の先頭に以下のルールを追加してください。
Apacheモジュール `mod_rewrite` を有効化する必要があります。
`mod_rewrite` モジュールのインストールと有効化については、[公式のドキュメント](https://httpd.apache.org/docs/2.4/)に従ってください
(場合によっては、`mod_rewrite` が既にインストールされているのに有効化されていないこともあります)。

```
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteRule ^/?(?:ar|eu|bn|bg|ca|zh-CHS|zh-CHT|da|nl|en|fi|fr|gl|de|el|he|hu|id|it|ja|ko|lv|ms|my|ne|no|fa|pl|pt|ru|es|sw|sv|tl|th|hi|tr|uk|vi)($|/.*$) $1 [L]
</IfModule>
```

Nginx ユーザーは、Nginx の設定ファイル (`/etc/nginx/conf.d/site.conf`) を更新する必要があります。
以下は、設定ファイルに追加する必要があるコードの抜粋です。

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

**custom_domainパターンの設定方法:**  

この設定では、サポートされている各言語に対応するドメインとパスを定義できます。  
バージョン1.3.0以降、 `custom_domain` のURLパターンを利用したい場合は、設定ファイルをJSON形式で作成する必要があります。

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

このパラメータは、カスタムドメイン言語パターンの場合（`url_pattern_name = custom_domain` が設定されている場合）のみ有効です。  
カスタムドメイン言語パターン使用時は必須パラメータです。  
`supported_langs` で設定した全ての言語と元言語に、必ず `custom_domain_langs` を設定してください。

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

上記の例では、 `www.site.co.jp/english/*` にマッチするリクエストは英語のリクエストとして扱われます。
それ以外の `www.site.co.jp/*` にマッチするリクエストは日本語のリクエストとして扱われます。
また、 `fr.site.co.jp/*` にマッチするリクエストはフランス語のリクエストとして扱われます。
例えば、`http://www.site.co.jp/about.html` の日本語ページは、`http://www.site.com/english/about.html` という英語ページのURLを持つことになります。

必ず `url_pattern_name = custom_domain`と`custom_domain_langs` は一緒に使用してください。

`supported_langs` で宣言された各言語に `custom_domain_langs` を与えなければなりません。

オリジナル言語のために宣言されたパスは、実際のウェブサーバーの構造と一致していなければなりません。
この設定を使用して、オリジナル言語のリクエストパスを変更することはできません。

### 3.2. 任意パラメータ

このセクションでは、WOVN.phpで使用できるオプションを詳しく説明します。
それらのいくつかはあなたのウェブサイトの構造に依存していますが、他のものはより高度であり、パフォーマンスの最適化のために使用する必要があります。

| Parameter                            | 設定が有効になる url_pattern_name | Description                       |
|--------------------------------------|--------------------------------|-----------------------------------|
| [lang_param_name](#lang_param_name)  | query                          | クエリパラメータ名を設定              |
| [custom_lang_aliases](#custom_lang_aliases)| query, path, subdomain   | 言語コードをWOVN既定から変更          |
| [ignore_paths](#ignore_paths)        | all                            | ライブラリ適用対象外URLをパスで設定    |
| [ignore_regex](#ignore_regex)        | all                            | ライブラリ適用対象外URLを正規表現で設定 |
| [ignore_class](#ignore_class)        | all                            | ライブラリ翻訳対象外とするHTMLのclassを設定 |
| [no_index_langs](#no_index_langs)    | all                            | 検索インデックスを防ぎ、指定した言語はSEO最適化タグを埋め込みません    |
| [no_hreflang_langs](#no_hreflang_langs)    | all                            | SEO最適化タグの埋め込みをしない言語を設定    |
| [encoding](#encoding)                | all                            | HTMLの文字エンコーディングを指定       |
| [api_timeout](#api_timeout)          | all                            | ライブラリの翻訳処理にかかる上限時間を設定 |
| [disable_api_request_for_default_lang](#disable_api_request_for_default_lang)| all | 元言語アクセス時の翻訳サーバーへのアクセスの要否を設定 |
| [use_proxy](#use_proxy)              | all                            | Proxyの使用有無を設定                |
| [override_content_length](#override_content_length)| all              | 翻訳後のContent-Lengthの更新要否を設定 |
| [check_amp](#check_amp)              | all                            | AMPページを翻訳対象にするかどうかを設定 |
| [site_prefix_path](#site_prefix_path)| path                           | 言語コードの挿入位置を変更            |
| [custom_domain_langs](#custom_domain_langs)| custom_domain            | サポートされている言語のドメインとパスを定義 |
| [insert_hreflangs](#custom_domain_langs)| all                         | hreflang属性を持つlinkタグの挿入要否を設定 |
| [outbound_proxy_host](#outbound_proxy_host--outbound_proxy_port)               | all                       | HTTP proxy server host used to connect to WOVN API 
| [outbound_proxy_port](#outbound_proxy_host--outbound_proxy_port)               | all                       | HTTP proxy server port used to connect to WOVN API

#### `lang_param_name`

このパラメータは `url_pattern_name = query` の場合のみ有効です。
ページの言語を宣言するためのクエリパラメータ名を設定することができます。
この設定のデフォルト値は `lang_param_name = wovn` です。

翻訳された英語ページのURLは、 URLの形式が以下のなります。

```
https://my-website.com/index.php?wovn=en
```

代わりに以下のような値を設定すると

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

上記のURLの例では、次のような形式になります。

```
https://my-website.com/index.php?language=en
```

#### `custom_lang_aliases`

このパラメータでは、WOVN.phpで使用する言語コードを再定義することができます。
例えば、"ja "の代わりに "japanese "を、"fr "の代わりに "french "を使いたい場合は、以下のように設定して下さい。

`wovn.ini`

```
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

**パスURLパターンをお使いの方へのご注意:**

それに応じて `.htaccess` または Nginx の設定を更新する必要があります。
上の例では、`|ja|` と `|fr|` は、式 `ar|eu|bn|bg|ca|zh-CHS|zh-CHT|da|nl|en|fi|fr|gl|de|el|he|hu|id|it|ja|ko|lv|ms|my|ne|no|fa|pl|pt|ru|es|sw|sv|tl|th|hi|tr|uk|vi` の中でそれぞれ `|japanese|` と `|french|` に変更する必要があります。

#### `ignore_paths`

このパラメータは、WOVN.php が指定したディレクトリ内のコンテンツを翻訳しないようにします。
指定されたディレクトリは、URLパスの先頭にのみマッチします。

例えば、ウェブサイトの `admin` ディレクトリを翻訳したくない場合は、以下のように WOVN.php を設定します。

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

この設定では、WOVN.php は以下の URL を無視します。

```Text
https://my-wesite.com/admin
https://my-wesite.com/admin/
https://my-website.com/admin/plugin.html
```

しかし、次のURLは翻訳します。

```Text
https://my-website.com/index.html
https://my-website.com/user/admin
https://my-website.com/adminpage
```

#### `ignore_regex`

このパラメータは `ignore_paths` と似ています ( [上記](#ignore_paths) を参照）。

例えば、検索ページを翻訳しないようにしたい場合は、以下のように `wovn.ini` を設定します。
WOVN.phpは `https://my-website.com/search/index.php` を翻訳するが、`https://my-website.com/search/01/` や `https://my-website.com/search/02/` は翻訳しません。

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

このパラメータは、翻訳時に無視すべき HTML フラグメントを WOVN.php に指定します。
`ignore_class` で与えられるクラスは HTML 要素のクラスです。無視されるクラスを持つすべてのHTML要素はWOVN.phpによって翻訳されません。

例えば、クラス `ignore` と `no-translate` のすべての HTML 要素を無視したい場合は、以下のように WOVN.php を設定する必要があります。

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

SEO最適化タグの埋め込みをしない言語を設定

```html
<link rel="alternate" hreflang="en" href="https://my-website.com/en/">
```

`wovn.ini`

```ini
no_hreflang_langs[] = en
```

#### `no_index_langs`

このパラメータは、ウェブクローラーによるインデックスを避けるために、どの言語のHTMLを `noindex` に設定すべきかをWOVN.phpに指示します。

例えば、英語ページのインデックスを避けたい場合は、以下のように `en` を追加します。
`<meta name="robots" content="noindex">` タグは英語ページの場合、`head` タグの中に挿入されます。

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

このパラメータは、WOVN.php にファイルに使用するエンコーディングを指定します。

WOVN.php は 8 つのエンコーディングをサポートしています。
`UTF-8`, `EUC-JP`, `SJIS`, `eucJP-win`, `SJIS-win`, `JIS`, `ISO-2022-JP` および `ASCII` です。

エンコーディングを設定しなければ、WOVN.phpが自動的にエンコーディングを検出してくれます。
ただし、エンコーディングの検出に時間がかかる場合がありますので、より良いパフォーマンスを得るためにはエンコーディングを設定することをお勧めします。

例えば、WebサイトのファイルがUTF-8でエンコードされている場合は、以下のようにWOVN.phpを設定する必要があります。

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

このパラメータは、WOVN.php に API を使用してコンテンツの翻訳に費やせる最大時間を指定します。
実際、私たちは翻訳ロジックのほとんどをWOVN.ioの別のサーバーに集中させており、WOVN.phpはほとんどの作業をそれらのサーバーに委譲しています。
`api_timeout` を設定することで、WOVN.phpにAPIからの応答を待つ時間を伝えます。

APIからの応答に時間がかかりすぎると、翻訳元のコンテンツが提供されます。
デフォルトでは、`api_timeout` は1秒に設定されています。

例えば、デフォルトのタイムアウトを2秒まで増やしたい場合は、以下のように `wovn.ini` を設定します。

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

#### `disable_api_request_for_default_lang`

このパラメータは、WOVN.php が元の言語でコンテンツをリクエストした際に翻訳APIを使用するかどうかを指定します。

デフォルトでは、`disable_api_request_for_default_lang` オプションは `0` (false) に設定されています。
これは、コンテンツが翻訳されていなくても WOVN.php が 翻訳API を使用することを意味します。

この設定を `1` にすると、サーバーリソースの使用量が増えることに気づくかもしれません。
これは、WOVN.phpで翻訳APIが通常行うHTMLのパースを行わなければならないからです(例えば、`hreflang`情報を挿入するなど)。
しかし、WOVN.phpはAPIにリクエストを送信しないので、ウェブページの読み込み時間を短縮することができます。

リソースに問題がなければ、以下のように翻訳元言語のAPIリクエストを停止することをお勧めします。

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

このパラメータは、WOVN.php にコンテンツをプロキシ経由で提供するかどうかを指定します。
デフォルトでは、この設定は `1` (true) に設定されています。

プロキシを使ってコンテンツを提供している場合、WOVN.phpはリクエストされたURLに基づいて情報を収集する際にそれを知る必要があります。

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

`use_proxy` がアクティブな場合、WOVN.php は HTTP ヘッダ `X-Forwarded-Proto` と `X-Forwarded-Host` から URL プロトコルとホストを利用しようとします。
これらはプロキシ転送の標準フィールドです。

さらに、WOVN.phpはHTTPヘッダ`X-Forwarded-Request-Uri`を探します。
これは、WOVN.php がクライアントからリクエストされた元の URI (すなわち "/japan/tokyo.html") を参照するために手動で設定することができます。

mod\_proxy と ProxyPass ディレクティブを使用している場合、 例えば、この HTTP ヘッダは RequestHeader ディレクティブで以下のように設定することができます。

```apache
ProxyPass        /japan http://my.subdomain.com
ProxyPassReverse /japan http://my.subdomain.com
RequestHeader    setifempty X-Forwarded-Request-Uri "expr=%{REQUEST_URI}"
```

#### `override_content_length`

このパラメータはWOVN.phpにレスポンスヘッダ「Content-Length」を更新するかどうかを指定します。
パフォーマンスを最適化するために、デフォルトでは `override_content_length` が `0` (false) に設定されています。

レスポンスヘッダ「Content-Length」の更新を維持する必要がある場合は、`override_content_length` を `1` (true) に設定してください。

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
このパラメータは、AMP (Accelerated Mobile Pages) 準拠のページであれば WOVN.php がコンテンツを処理しないようにします。
このパラメータを有効にすると、WOVN.phpはコンテンツの変更をしません。
そのため、WOVNスクリプトのタグを追加することはありません。

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
このパラメータは、特定のパス以下のみWOVNで処理するように指定します。
`url_pattern_name`が`path`の場合のみ有効です。

例えば `site_prefix_path = dir` を設定した場合、`http://www.mysite.com/dir/*`のみ処理します。
`http://www.mysite.com/dir/index.html`を英語にした場合、`http://www.mysite.com/dir/en/index.html`のように、指定したディレクトリ以下に言語コードが追加されます。
該当しないパスの場合は、WOVNは処理せず、スクリプトも追加されません。

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
このパラメータはhreflang属性を持つlinkタグを挿入するかどうかを指定します。  
例えば設定が有効の場合、`<link rel="alternate" hreflang="en" href="https://my-website.com/en/">`のように、公開されている言語のタグを挿入します。

設定が無効の場合はタグは挿入せず、元からあるhreflang属性を持ったタグに変更は加えません。

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

## 4. 環境変数

### `WOVN_TARGET_LANG`

この環境変数は、HTTPリクエストから取得した翻訳対象の言語コードを設定します。
ユーザーは、この環境変数からターゲット言語のコードを取得し、プログラムの動作を任意に変更することができます。

例えば、以下のように
```
if ($_ENV['WOVN_TARGET_LANG'] == 'fr') {
    ... some kind of your code ...
}
```

### `WOVN_CONFIG`

この環境変数は、ユーザがデフォルトの `wovn.ini` のパスを任意の設定ファイルのパスに変更することを可能にします。
例えば、以下のように変更することができます。

ユーザは `wovn_interceptor.php` を読み込む前に `$_SERVER['WOVN_CONFIG']` を設定することができます。
```
$_SERVER['WOVN_CONFIG'] = ... your config path ...;

require_once('/path/to/WOVN.php/src/wovn_interceptor.php');
```

あるいは、`.htaccess`で以下のように設定することもできる。
```
SetEnv WOVN_CONFIG /path/to/wovn.ini
```

**設定ファイルのパスに関する注意事項：** 設定ファイルのパスは絶対パスでなければなりません。

## 5. バグ報告

お客様の問題を解決するためには、いくつかの情報が必要です。
まず、どのウェブページで問題が発生したのか、再現するための手順を知る必要があります。
問題が公開されていないウェブページで発生している場合は、ログイン情報も必要になります。
（検証にはステージングサーバを使用することをお勧めします）。

問題がサーバ側で発生している場合 (ウィジェット `<script>` タグが挿入されていない、言語が検出されない、リダイレクトが正しく処理されていないなど)、導入方法に問題がある場合が多く、お客様にサーバーの情報を提供して頂く必要があります。

できるだけ早く問題を解決するため、以下のような情報が必要です。

| 情報                  | 説明                                                                          |
|----------------------|-------------------------------------------------------------------------------|
| PHP バージョン         | 5.3以上であること                                                               |
| WOVN.php バージョン    | `src/version.php` で確認することができます。                                      |
| 構成                  | ウェブサイトのディレクトリとファイル構造                                            |
| wovn.ini             | 使用中の `wovn.ini`                                                            |
| wovn_index.php       | 使用中の `wovn_index.php`                                                      |
| index.php            | 使用中の `index.php`                                                           |
| サーバ種別             | Nginx / Apache / 両方                                                        |
| サーバ設定             | Nginxの設定ファイル / Apacheの`.htaccess`の設定ファイル（複数あれば全て）            |
| ログ                 | エラー発生時のエラーログ                                                          |
| リクエスト制限         | 443 ポートの `wovn.global.ssl.fastly.net` へのリクエストを許可する必要があります。    |
| SSIの使用             | SSI(Server Side Includes)を使用しているかどうか                                  |
