# WOVN.php Instructions

## Table of Contents

1. [必要条件](#1-必要条件)
2. [Getting Started](#2-getting-started)
3. [設定](#3-設定)
4. [バグ報告](#4-バグ報告)

## 1. 必要条件

WOVN.phpにはPHP 5.3以上が必要です。WOVN.phpにはサードパーティの依存関係はありません。

設定によっては、Apacheモジュール `mod_rewrite` をインストールしたり有効化したりする必要があるかもしれません ( [2.3.2.項](#232-静的ウェブサイト) や [3.2.項](#32-任意パラメータ) を参照してください)。

WOVN.phpはApache 2とNginxで動作確認済みです。両方のインストール方法を提供しています。

## 2. Getting Started

### 2.1. WOVN.php ダウンロード

WOVN.phpをインストールするには、当社のGithubリポジトリからWOVN.phpを手動でダウンロードする必要があります。WOVN.php のルートディレクトリは、ウェブサイトのディレクトリのルートに配置する必要があります。

このドキュメントの残りの部分では、ウェブサイトのルートディレクトリを `/website/root/directory` とみなします。

```
$ cd /website/root/directory
$ wget https://github.com/WOVNio/WOVN.php/archive/master.zip -O WOVN.php.zip
$ unzip WOVN.php.zip; mv WOVN.php-master WOVN.php
```

**更新時の注意:** WOVN.phpを更新する必要がある場合は、`WOVN.php`ディレクトリのすべての内容を新しいバージョンの内容に置き換えるだけです。

### 2.2. 基本的な設定

WOVN.ioプロジェクトでWOVN.phpを動作させるためには、設定ファイルを埋める必要があります。

設定ファイルは `wovn.ini` という名前で、ウェブサイトのディレクトリのルートに置かなければなりません。サンプルファイルは `wovn.php/wovn.ini.sample` から始めることができます。

```
$ cp WOVN.php/wovn.ini.sample wovn.ini
```

このセクションでは、開始するための基本的な設定を説明します。

WOVN.phpの設定の詳細については、[セクション3.](#3-設定)を参照してください。

開始するには、少なくともWOVN.ioのプロジェクトトークン、ウェブサイトのオリジナル言語、そして、ウェブサイトがWOVN.ioによって翻訳可能な言語を知っている必要があります。

プロジェクトトークンを取得するには、プロジェクトのダッシュボードにアクセスし、 "INTEGRATION METHODS" をクリックし、"PHP Library "のインストール方法を選択します。

以下は、トークンが "TOKEN"、翻訳元言語が英語(`en`)、翻訳先言語が日本語(`ja`)とフランス語(`fr`)のプロジェクトの `wovn.ini` の例です。

```
project_token = TOKEN
url_pattern_name = query
default_lang = en
supported_langs[] = ja
supported_langs[] = fr
```

このステップの最後に、ウェブサイトのファイル構造は以下のようになります。

```
+ /website/root/directory
  + WOVN.php
  - wovn.ini
  [...]
```

### 2.3. WOVN.phpの有効化

WOVN.phpでウェブサイトをローカライズするためには、コンテンツインターセプトを有効にする必要があります。

ウェブページの生成方法に応じて、2つの有効化方法があります。

ウェブページがPHPファイルで生成されている場合は、動的ウェブサイトの指示に従ってください。ウェブページが純粋なHTMLである場合は、静的なウェブサイトの手順に従ってください。

#### 2.3.1. 動的ウェブサイト

WebページがPHPファイルで生成される場合、コンテンツを生成する各PHPファイル内にWOVN.phpインターセプトスクリプトが必要になります。

以下のコードを使用してください。PHPファイルの先頭に挿入する必要があります。

```
require_once('/website/root/directory/WOVN.php/src/wovn_interceptor.php');
```

#### 2.3.2. 静的ウェブサイト

Webページが純粋なHTMLである場合、HTMLページを提供しローカライズするために使用する `wovn_index.php` ファイルを作成する必要があります。

私たちは、私たちが提供するサンプルから始めることをお勧めします。

```
$ cp WOVN.php/wovn_index_sample.php wovn_index.php
```

**SSIユーザの方への注意:**サンプルの `wovn_index.php` を使用している場合は、コード内の `# SSI USER` の指示に従ってください。

`wovn_index.php` を設定したら、HTMLページへのリクエストがすべて `wovn_index.php` にリダイレクトされるようにウェブサイトを設定する必要があります。

Apacheサーバを使っている場合は、[Apacheの説明](#Apacheで-wovn_index.php-にリダイレクト)に従ってください。

Nginx (Apacheなし) を使用している場合は、[Nginxの説明](#Nginxで-wovn_index.php-にリダイレクト) に従ってください。

#### Apacheで `wovn_index.php` にリダイレクト

リクエストを `wovn_index.php` にリダイレクトするには、`.htaccess` の設定に `mod_rewrite` Apache モジュールを使用することをお勧めします。

`mod_rewrite` モジュールのインストールと有効化については、[公式のドキュメント](https://httpd.apache.org/docs/2.4/)に従ってください( `mod_rewrite` がインストール済みで有効化されていない場合もあります)。

以下に `.htaccess` の設定を示します。

```
<IfModule mod_rewrite.c>
  RewriteEngine On

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

以下は、設定ファイルに追加するコードのハイライトです。

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

| Parameter         | Description                                                        | Example |
|-------------------|--------------------------------------------------------------------|-------- |
| `project_token`   | WOVN.ioプロジェクトトークン                                        | `project_token = TOKEN` |
| `default_lang`    | ウェブサイトの翻訳元言語                                           | `default_lang = en` |
| `supported_langs` | ウェブサイトの翻訳元言語と<br>WOVN.ioで翻訳可能な言語              | `supported_langs[] = ja`<br>`supported_langs[] = fr` |

### 3.2. 任意パラメータ

このセクションでは、WOVN.phpで使用できるオプションを詳しく説明します。

それらのいくつかはあなたのウェブサイトの構造に依存していますが、他のものはより高度であり、パフォーマンスの最適化のために使用する必要があります。

#### `url_pattern_name`

このパラメータは、ウェブページのURLがどのように言語情報を含むように変更されるかを定義します。WOVN.php は 3 つのパターンをサポートしています。

| Option                               | URL Examples                               | Example's language |
|--------------------------------------|--------------------------------------------|:------------------:|
| `url_pattern_name = query` (default) | `https://my-website.com/index.php`<br>`https://my-website.com/index.php?wovn=ja`<br>`https://my-website.com/index.php?wovn=fr`         | *Original*<br>Japanese<br>French         |
| `url_pattern_name = path`            | `https://my-website.com/index.php`<br>`https://my-website.com/ja/index.php`<br>`https://my-website.com/fr/index.php`         | *Original*<br>Japanese<br>French         |
| `url_pattern_name = subdomain`       | `https://my-website.com/index.php`<br>`https://ja.my-website.com/index.php`<br>`https://fr.my-website.com/index.php`         | *Original*<br>Japanese<br>French         |

**パスパターンをお使いの方への注意事項:**

PHPスクリプトで処理される前に、URLから言語コードを取り除くために、サーバーの設定を変更する必要があります。

Apacheユーザの場合は、`.htaccess`の先頭に以下のルールを追加することができます。

Apacheモジュール `mod_rewrite` を有効化する必要があります。`mod_rewrite` モジュールのインストールと有効化については、[公式のドキュメント](https://httpd.apache.org/docs/2.4/)に従ってください(場合によっては、`mod_rewrite` が既にインストールされているのに有効化されていないこともあります)。

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

#### `lang_param_name`

このパラメータは `url_pattern_name = query` の場合のみ有効です。

ページの言語を宣言するためのクエリパラメータ名を設定することができます。

この設定のデフォルト値は `lang_param_name = wovn` です。

翻訳された英語ページのURLは、 URLの形式が以下のなります。

```
https://my-website.com/index.php?wovn=en
```

代わりに以下のような値を設定すると

```
lang_param_name = language
```

上記のURLの例では、次のような形式になります。

```
https://my-website.com/index.php?language=en
```

#### `custom_lang_aliases`

このパラメータでは、WOVN.phpで使用する言語コードを再定義することができます。

例えば、"ja "の代わりに "japanese "を、"fr "の代わりに "french "を使いたい場合は、以下のように `wovn.ini` を設定します。

```
custom_lang_aliases[ja] = japanese
custom_lang_aliases[fr] = french
```

**パスURLパターンをお使いの方へのご注意:**

それに応じて `.htaccess` または Nginx の設定を更新する必要があります。

上の例では、`|ja|` と `|fr|` は、式 `ar|eu|bn|bg|ca|zh-CHS|zh-CHT|da|nl|en|fi|fr|gl|de|el|he|hu|id|it|ja|ko|lv|ms|my|ne|no|fa|pl|pt|ru|es|sw|sv|tl|th|hi|tr|uk|vi` の中でそれぞれ `|japanese|` と `|french|` に変更する必要があります。

#### `query`

このパラメータは、ページを一意にするクエリパラメータを WOVN.php に伝えます。

デフォルトでは、WOVN.ioはユニークなページを識別する際にクエリパラメータを無視します。

WOVN.io上で特定のクエリパラメータを持つページを作成した場合は、それらのクエリパラメータをWOVN.phpの設定に追加する必要があります。

例えば、WOVN.io上に
- `https://my-website.com/index.php`
- `https://my-website.com/index.php?login=1`
- `https://my-website.com/index.php?forgot_password=1`
の3つのページがある場合、以下のようにWOVN.phpを設定する必要があります。

```
query[] = login
query[] = forgot_password
```

#### `ignore_paths`

このパラメータは、WOVN.php が指定したディレクトリ内のコンテンツを翻訳しないようにします。

指定されたディレクトリは、URLパスの先頭にのみマッチします。

例えば、ウェブサイトの `admin` ディレクトリを翻訳したくない場合は、以下のように WOVN.php を設定します。

```Text
ignore_paths[] = /admin
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

例えば、検索ページをローカライズしないようにしたい場合は、以下のようにWOVN.phpを設定します。

WOVN.phpは `https://my-website.com/search/index.php` を翻訳するが、`https://my-website.com/search/01/` や `https://my-website.com/search/02/` はローカライズしない。

```
ignore_regex[] = /\/search\/\d\d\//
```

#### `ignore_class`

このパラメータは、翻訳時に無視すべき HTML フラグメントを WOVN.php に指定します。

`ignore_class` で与えられるクラスは HTML 要素のクラスです。無視されるクラスを持つすべてのHTML要素はWOVN.phpによって翻訳されません。

例えば、クラス `ignore` と `no-translate` のすべての HTML 要素を無視したい場合は、以下のように WOVN.php を設定する必要があります。

```
ignore_class[] = ignore
ignore_class[] = no-translate
```

#### `no_index_langs`

このパラメータは、ウェブクローラーによるインデックスを避けるために、どの言語のHTMLを `noindex` に設定すべきかをWOVN.phpに指示します。

例えば、英語ページのインデックスを避けたい場合は、以下のように `en` を追加します。

`<meta name="robots" content="noindex">` タグは英語ページの場合、`head` タグの中に挿入されます。

```
no_index_langs[] = en
```

#### `encoding`

このパラメータは、WOVN.php にファイルに使用するエンコーディングを指定します。

WOVN.php は 8 つのエンコーディングをサポートしています。 `UTF-8`, `EUC-JP`, `SJIS`, `eucJP-win`, `SJIS-win`, `JIS`, `ISO-2022-JP` および `ASCII` です。

エンコーディングを設定しなければ、WOVN.phpが自動的にエンコーディングを検出してくれます。

ただし、エンコーディングの検出に時間がかかる場合がありますので、より良いパフォーマンスを得るためにはエンコーディングを設定することをお勧めします。

例えば、WebサイトのファイルがUTF-8でエンコードされている場合は、以下のようにWOVN.phpを設定する必要があります。

```
encoding = UTF-8
```

#### `api_timeout`

このパラメータは、WOVN.php に API を使用してコンテンツの翻訳に費やせる最大時間を指定します。

実際、私たちは翻訳ロジックのほとんどをWOVN.ioの別のサーバーに集中させており、WOVN.phpはほとんどの作業をそれらのサーバーに委譲しています。

`api_timeout` を設定することで、WOVN.phpにAPIからの応答を待つ時間を伝えます。APIからの応答に時間がかかりすぎると、翻訳元のコンテンツが提供されます。

デフォルトでは、`api_timeout` は1秒に設定されています。

例えば、デフォルトのタイムアウトを2秒まで増やしたい場合は、以下のように `wovn.ini` を設定します。

```
api_timeout = 2
```

#### `disable_api_request_for_default_lang`

このパラメータは、WOVN.php が元の言語でコンテンツをリクエストした際に翻訳APIを使用するかどうかを指定します。

デフォルトでは、`disable_api_request_for_default_lang` オプションは `0` (false) に設定されています。

これは、コンテンツが翻訳されていなくても WOVN.php が 翻訳API を使用することを意味します。

この設定を `1` にすると、サーバーリソースの使用量が増えることに気づくかもしれません。

これは、WOVN.phpで翻訳APIが通常行うHTMLのパースを行わなければならないからです(例えば、`hreflang`情報を挿入するなど)。

しかし、WOVN.phpはAPIにリクエストを送信しないので、ウェブページの読み込み時間を短縮することができます。

```
disable_api_request_for_default_lang = 1
```

#### `use_proxy`

このパラメータは、WOVN.php にコンテンツをプロキシ経由で提供するかどうかを指定します。

デフォルトでは、この設定は `0` (false) に設定されています。

プロキシを使ってコンテンツを提供している場合、WOVN.phpはリクエストされたURLに基づいて情報を収集する際にそれを知る必要があります。

その場合は、`use_proxy` を `1` (true) に設定する必要があります。

```
use_proxy = 1
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

```
override_content_length = 1
```

## 4. バグ報告

お客様の問題を解決するためには、いくつかの情報が必要です。

まず、どのウェブページで問題が発生したのか、再現するための手順を知る必要があります。

可能であれば、問題が認証されていないウェブページで発生している場合は、テストアカウントも必要になります（そのためにはステージングサーバを使用することをお勧めします）。

問題がサーバ側で発生している場合 (ウィジェット `<script>` タグが挿入されていない、言語が検出されない、リダイレクトが正しく処理されていないなど)、通常はより多くの情報が必要です。

できるだけ早く問題を解決するためには、以下のような情報が必要です。

| 情報                 | 説明                                                                               |
|----------------------|------------------------------------------------------------------------------------|
| PHP バージョン       | 5.3以上であること                                                                  |
| WOVN.php バージョン  | `src/version.php` で確認することができます。                                       |
| 構成                 | ウェブサイトのディレクトリとファイル構造のスナップショット                         |
| wovn.ini             | あなたの `wovn.ini`                                                                |
| wovn_index.php       | 使用されている場合は、あたなの `wovn_index.php`                                    |
| index.php            | あなたの `index.php`                                                               |
| サーバ種別           | Nginx / Apache / both                                                              |
| サーバ設定           | Nginxの設定ファイル / Apacheの`.htaccess`の設定ファイル                            |
| ログ                 | エラー発生時のエラーログ                                                           |
| リクエスト制限       | 443 ポートの `wovn.global.ssl.fastly.net` へのリクエストを許可する必要があります。 |
| SSIの使用            | SSI(Server Side Includes)を使用しているかどうか                                    |
