<?php
require_once 'version.php';
require_once 'wovnio/wovnphp/Headers.php';
require_once 'wovnio/wovnphp/Lang.php';
require_once 'wovnio/wovnphp/Logger.php';
require_once 'wovnio/wovnphp/Store.php';
require_once 'wovnio/wovnphp/Utils.php';
require_once 'wovnio/wovnphp/API.php';
require_once 'wovnio/wovnphp/Url.php';
require_once 'wovnio/wovnphp/Diagnostics.php';
require_once 'wovnio/wovnphp/RequestOptions.php';
require_once 'wovnio/html/HtmlConverter.php';
require_once 'wovnio/html/HtmlReplaceMarker.php';
require_once 'wovnio/modified_vendor/SimpleHtmlDom.php';
require_once 'wovnio/modified_vendor/SimpleHtmlDomNode.php';
require_once 'wovn_helper.php';
require_once 'wovnio/wovnphp/CookieLang.php';


use Wovnio\Wovnphp\Logger;
use Wovnio\Wovnphp\Utils;
use Wovnio\Wovnphp\API;
use Wovnio\Wovnphp\Diagnostics;
use Wovnio\Wovnphp\RequestOptions;
use \Wovnio\Wovnphp\CookieLang;

// GET STORE AND HEADERS
list($store, $headers) = Utils::getStoreAndHeaders($_SERVER, $_COOKIE);

if (!$store->isValid()) {
    Logger::get()->critical('WOVN Invalid configuration');
    return false;
}

$queryString = parse_url($_SERVER['REQUEST_URI'])['query'];
$debugModeEnable = $store->settings['debug_mode'];
$requestOptions = new RequestOptions($queryString, $debugModeEnable);

if ($requestOptions->getDisableMode()) {
    return true;
}

// Make it available for user application
$_ENV['WOVN_TARGET_LANG'] = $headers->requestLang();

Logger::get()->info('WOVN.php version ' . WOVN_PHP_VERSION . ' has received a request for '
    . $_SERVER['REQUEST_URI'] . ' in ' . $_ENV['WOVN_TARGET_LANG'] . '.');
Logger::get()->info('Request received. ' . json_encode($_SERVER));

$headers->requestOut();

$uri = $headers->getDocumentURI();
if (!Utils::isIgnoredPath($uri, $store)) {
    $diagnostics = null;
    $benchmarkStart = 0;
    if (Utils::wovnDiagnosticsEnabled($store, $headers)) {
        Logger::get()->info('Wovn Diagnostics is turned on.');
        $benchmarkStart = microtime(true) * 1000;
        $diagnostics = new Diagnostics($store);
    }
    // use the callback of ob_start to modify the content and return
    ob_start(function ($buffer) use ($headers, $store, $diagnostics, $benchmarkStart, $requestOptions) {
        if ($headers->shouldRedirect()) {
            // this carries an implied HTTP 302
            header("Location: " . $headers->computeRedirectUrl());
            Logger::get()->info('Request ended, redirect: ' . $headers->computeRedirectUrl());
            exit();
        }
        $headers->responseOut();

        if (empty($buffer) || !Utils::isHtml($buffer)) {
            return $buffer;
        }

        if ($store->settings['check_amp'] && Utils::isAmp($buffer)) {
            return $buffer;
        }

        $translatedBuffer = API::translate($store, $headers, $buffer, $requestOptions->getCacheDisableMode());

        if (Utils::wovnDiagnosticsEnabled($store, $headers)) {
            $benchmarkEnd = microtime(true) * 1000;
            $diagnostics->logPerformance($benchmarkStart, $benchmarkEnd);
            Logger::get()->info('Request ended, swapping time (ms): ' . ($benchmarkEnd - $benchmarkStart));
            $diagnostics->logOriginalPage($buffer);
            $diagnostics->logSwappedPage($translatedBuffer);
            return $diagnostics->renderResults();
        } else {
            Logger::get()->info('Request ended.');
            if ($translatedBuffer !== null && !empty($translatedBuffer)) {
                Utils::changeHeaders($translatedBuffer, $store);
                return $translatedBuffer;
            }
            return $buffer;
        }
    });
}
