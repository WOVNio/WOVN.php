<?php
require_once 'vendor/autoload.php';

// TODO: create autoload instead of loading every files
// TODO: not all files are loaded yet

require_once 'src/version.php';
require_once 'src/wovnio/wovnphp/Headers.php';
require_once 'src/wovnio/wovnphp/Lang.php';
require_once 'src/wovnio/wovnphp/Logger.php';
require_once 'src/wovnio/wovnphp/Store.php';
require_once 'src/wovnio/wovnphp/Url.php';
require_once 'src/wovnio/wovnphp/Utils.php';

require_once 'src/wovnio/html/HtmlConverter.php';
require_once 'src/wovnio/html/HtmlReplaceMarker.php';

require_once 'src/wovnio/modified_vendor/SimpleHtmlDom.php';
require_once 'src/wovnio/modified_vendor/SimpleHtmlDomNode.php';

require_once 'test/helpers/StoreAndHeadersFactory.php';
require_once 'test/helpers/Utils.php';

// disable error logging
\Wovnio\Wovnphp\Logger::get()->setQuiet(true);
