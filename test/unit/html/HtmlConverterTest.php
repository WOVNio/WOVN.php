<?php
namespace Wovnio\Wovnphp\Tests\Unit;

use \Wovnio\Html\HtmlConverter;
use Wovnio\Test\Helpers\TestUtils;
use \Wovnio\Html\HtmlReplaceMarker;
use \Wovnio\Test\Helpers\StoreAndHeadersFactory;
use \Wovnio\ModifiedVendor\SimpleHtmlDom;

class HtmlConverterTest extends \PHPUnit_Framework_TestCase
{
    public function testConvertAndRevertAtStackOverflow()
    {
        libxml_use_internal_errors(true);
        $html = file_get_contents('test/fixtures/real_html/stack_overflow.html');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default');
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        list($translated_html, $marker) = $converter->insertSnippetAndHreflangTags(false);

        $expected_html_text = file_get_contents('test/fixtures/real_html/stack_overflow_expected.html');
        $doc = new \DOMDocument("1.0", "ISO-8859-15");
        $doc->loadHTML(mb_convert_encoding($expected_html_text, 'HTML-ENTITIES', "utf-8"));
        $expected_html = $doc->saveHTML();

        $actual_html_text = $marker->revert($translated_html);
        $doc = new \DOMDocument("1.0", "ISO-8859-15");
        $doc->loadHTML(mb_convert_encoding($actual_html_text, 'HTML-ENTITIES', "utf-8"));
        $actual_html = $doc->saveHTML();

        $this->assertEquals($expected_html, $actual_html);
    }

    public function testConvertAndRevertAtYoutube()
    {
        libxml_use_internal_errors(true);
        $html = file_get_contents('test/fixtures/real_html/youtube.html');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default');
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        list($translated_html, $marker) = $converter->insertSnippetAndHreflangTags(false);

        $expected_html_text = file_get_contents('test/fixtures/real_html/youtube_expected.html');
        $doc = new \DOMDocument("1.0", "ISO-8859-15");
        $doc->loadHTML(mb_convert_encoding($expected_html_text, 'HTML-ENTITIES', "utf-8"));
        $expected_html = $doc->saveHTML();

        $actual_html_text = $marker->revert($translated_html);
        $doc = new \DOMDocument("1.0", "ISO-8859-15");
        $doc->loadHTML(mb_convert_encoding($actual_html_text, 'HTML-ENTITIES', "utf-8"));
        $actual_html = $doc->saveHTML();

        $this->assertEquals($expected_html, $actual_html);
    }

    public function testConvertAndRevertAtYelp()
    {
        libxml_use_internal_errors(true);
        $html = file_get_contents('test/fixtures/real_html/yelp.html');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default');
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        list($translated_html, $marker) = $converter->insertSnippetAndHreflangTags(false);

        $expected_html_text = file_get_contents('test/fixtures/real_html/yelp_expected.html');
        $doc = new \DOMDocument("1.0", "ISO-8859-15");
        $doc->loadHTML(mb_convert_encoding($expected_html_text, 'HTML-ENTITIES', "utf-8"));
        $expected_html = $doc->saveHTML();

        $actual_html_text = $marker->revert($translated_html);
        $doc = new \DOMDocument("1.0", "ISO-8859-15");
        $doc->loadHTML(mb_convert_encoding($actual_html_text, 'HTML-ENTITIES', "utf-8"));
        $actual_html = $doc->saveHTML();

        $this->assertEquals($expected_html, $actual_html);
    }

    public function testConvertAndRevertAtYahooJp()
    {
        libxml_use_internal_errors(true);
        $html = file_get_contents('test/fixtures/real_html/yahoo_jp.html');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default');
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        list($translated_html, $marker) = $converter->insertSnippetAndHreflangTags(false);

        $expected_html_text = file_get_contents('test/fixtures/real_html/yahoo_jp_expected.html');
        $doc = new \DOMDocument("1.0", "ISO-8859-15");
        $doc->loadHTML(mb_convert_encoding($expected_html_text, 'HTML-ENTITIES', "utf-8"));
        $expected_html = $doc->saveHTML();

        $actual_html_text = $marker->revert($translated_html);
        $doc = new \DOMDocument("1.0", "ISO-8859-15");
        $doc->loadHTML(mb_convert_encoding($actual_html_text, 'HTML-ENTITIES', "utf-8"));
        $actual_html = $doc->saveHTML();

        $this->assertEquals($expected_html, $actual_html);
    }

    public function testinsertSnippetAndHreflangTags()
    {
        $html = '<html><body><a>hello</a></body></html>';
        $settings = array(
            'supported_langs' => array('en', 'vi'),
            'lang_param_name' => 'wovn'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        list($translated_html) = $converter->insertSnippetAndHreflangTags(false);

        $expected_html = "<html><body><link rel=\"alternate\" hreflang=\"en\" href=\"http://my-site.com/\"><link rel=\"alternate\" hreflang=\"vi\" href=\"http://my-site.com/?wovn=vi\"><script src=\"//j.wovn.io/1\" data-wovnio=\"key=123456&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=query&amp;langCodeAliases=[]&amp;langParamName=wovn\" data-wovnio-info=\"version=WOVN.php_VERSION\" async></script><a>hello</a></body></html>";
        $this->assertEquals($expected_html, $translated_html);
    }

    public function testInsertSnippetAndHreflangTagsWithCustomAlias()
    {
        $html = '<html><body><a>hello</a></body></html>';
        $settings = array(
            'supported_langs' => array('fr'),
            'default_lang' => 'en',
            'custom_lang_aliases' => array('en' => 'custom_en'),
            'lang_param_name' => 'wovn',
            'url_pattern_name' => 'path'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        list($translated_html) = $converter->insertSnippetAndHreflangTags(false);

        $expected_html = "<html><body><link rel=\"alternate\" hreflang=\"fr\" href=\"http://my-site.com/fr/\"><script src=\"//j.wovn.io/1\" data-wovnio=\"key=123456&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=path&amp;langCodeAliases={&quot;en&quot;:&quot;custom_en&quot;}&amp;langParamName=wovn\" data-wovnio-info=\"version=WOVN.php_VERSION\" async></script><a>hello</a></body></html>";
        $this->assertEquals($expected_html, $translated_html);
    }

    public function testInsertSnippetAndHreflangTagsWithCustomDomainLangs()
    {
        $html = '<html><body><a>hello</a></body></html>';
        $settings = array(
            'supported_langs' => array('fr'),
            'default_lang' => 'en',
            'custom_domain_langs' => array('testsite.com' => 'en', 'testsite.com/fr' => 'fr'),
            'url_pattern_name' => 'custom_domain'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, array('HTTP_HOST' => 'testsite.com'));
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        list($translated_html) = $converter->insertSnippetAndHreflangTags(false);

        $expected_html = '<html>'.
        '<body>'.
        '<link rel="alternate" hreflang="fr" href="http://testsite.com/fr/">'.
        '<script src="//j.wovn.io/1"'.
        ' data-wovnio="key=123456&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=custom_domain&amp;langCodeAliases=[]&amp;langParamName=wovn&amp;customDomainLangs={&quot;testsite.com&quot;:&quot;en&quot;,&quot;testsite.com\/fr&quot;:&quot;fr&quot;}"'.
        ' data-wovnio-info="version=WOVN.php_VERSION"'.
        ' async></script>'.
        '<a>hello</a>'.
        '</body>'.
        '</html>';
        $this->assertEquals($expected_html, $translated_html);
    }

    public function testBuildHrefLangPath()
    {
        $html = '<html><body><a>hello</a></body></html>';
        $settings = array(
            'supported_langs' => array('ja'),
            'default_lang' => 'en',
            'lang_param_name' => 'wovn',
            'url_pattern_name' => 'path'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        $expected_href = 'http://my-site.com/ja/';
        $generated_href = TestUtils::invokeMethod($converter, 'buildHrefLang', array('ja'));
        $this->assertEquals($expected_href, $generated_href);
    }

    public function testBuildHrefLangPathDefaultLangAliasSwapDefaultLang()
    {
        $html = '<html><body><a>hello</a></body></html>';
        $settings = array(
            'supported_langs' => array('ja'),
            'default_lang' => 'en',
            'custom_lang_aliases' => array('en' => 'custom_en'),
            'lang_param_name' => 'wovn',
            'url_pattern_name' => 'path'
        );

        $envs = array(
            'REQUEST_URI' => '/pages.html'
        );

        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $envs);
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        $expected_href = 'http://my-site.com/custom_en/pages.html';
        $generated_href = TestUtils::invokeMethod($converter, 'buildHrefLang', array('en'));
        $this->assertEquals($expected_href, $generated_href);
    }

    public function testBuildHrefLangPathDefaultLangAliasSwapDefaultLang2()
    {
        $html = '<html><body><a>hello</a></body></html>';
        $settings = array(
            'supported_langs' => array('ja'),
            'default_lang' => 'en',
            'custom_lang_aliases' => array('en' => 'custom_en'),
            'lang_param_name' => 'wovn',
            'url_pattern_name' => 'path'
        );

        $envs = array(
            'REQUEST_URI' => '/custom_en/pages.html'
        );

        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $envs);
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        $expected_href = 'http://my-site.com/custom_en/pages.html';
        $generated_href = TestUtils::invokeMethod($converter, 'buildHrefLang', array('en'));
        $this->assertEquals($expected_href, $generated_href);
    }

    public function testBuildHrefLangPathDefaultLangAliasSwapDefaultLang3()
    {
        $html = '<html><body><a>hello</a></body></html>';
        $settings = array(
            'supported_langs' => array('ja'),
            'default_lang' => 'en',
            'custom_lang_aliases' => array('en' => 'custom_en'),
            'lang_param_name' => 'wovn',
            'url_pattern_name' => 'path'
        );

        $envs = array(
            'REQUEST_URI' => '/news/blog/pages.html'
        );

        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $envs);
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        $expected_href = 'http://my-site.com/custom_en/news/blog/pages.html';
        $generated_href = TestUtils::invokeMethod($converter, 'buildHrefLang', array('en'));
        $this->assertEquals($expected_href, $generated_href);
    }

    public function testBuildHrefLangPathDefaultLangAlias()
    {
        $html = '<html><body><a>hello</a></body></html>';
        $settings = array(
            'supported_langs' => array('ja'),
            'default_lang' => 'en',
            'custom_lang_aliases' => array('en' => 'custom_en'),
            'url_pattern_name' => 'path'
        );

        $envs = array(
            'REQUEST_URI' => '/custom_en/pages.html'
        );

        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $envs);
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        $expected_href = 'http://my-site.com/ja/pages.html';
        $generated_href = TestUtils::invokeMethod($converter, 'buildHrefLang', array('ja'));
        $this->assertEquals($expected_href, $generated_href);
    }

    public function testBuildHrefLangPathDefaultLangAlias2()
    {
        $html = '<html><body><a>hello</a></body></html>';
        $settings = array(
            'supported_langs' => array('ja'),
            'default_lang' => 'en',
            'custom_lang_aliases' => array('en' => 'custom_en'),
            'lang_param_name' => 'wovn',
            'url_pattern_name' => 'path'
        );

        $envs = array(
            'REQUEST_URI' => '/ja/pages.html'
        );

        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $envs);
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        $expected_href = 'http://my-site.com/custom_en/pages.html';
        $generated_href = TestUtils::invokeMethod($converter, 'buildHrefLang', array('en'));
        $this->assertEquals($expected_href, $generated_href);
    }

    public function testBuildHrefLangPathDefaultLangAlias3()
    {
        $html = '<html><body><a>hello</a></body></html>';
        $settings = array(
            'supported_langs' => array('ja'),
            'default_lang' => 'en',
            'custom_lang_aliases' => array('en' => 'custom_en'),
            'lang_param_name' => 'wovn',
            'url_pattern_name' => 'path'
        );

        $envs = array(
            'REQUEST_URI' => '/custom_en/blog/news/pages.html'
        );

        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $envs);
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        $expected_href = 'http://my-site.com/ja/blog/news/pages.html';
        $generated_href = TestUtils::invokeMethod($converter, 'buildHrefLang', array('ja'));
        $this->assertEquals($expected_href, $generated_href);
    }

    public function testBuildHrefLangQuery()
    {
        $html = '<html><body><a>hello</a></body></html>';
        $settings = array(
            'supported_langs' => array('ja'),
            'default_lang' => 'en',
            'lang_param_name' => 'wovn',
            'url_pattern_name' => 'query'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        $expected_href = 'http://my-site.com/?wovn=ja';
        $generated_href = TestUtils::invokeMethod($converter, 'buildHrefLang', array('ja'));
        $this->assertEquals($expected_href, $generated_href);
    }

    public function testBuildHrefLangQueryDefaultLangAlias()
    {
        $html = '<html><body><a>hello</a></body></html>';
        $settings = array(
            'supported_langs' => array('ja'),
            'default_lang' => 'en',
            'custom_lang_aliases' => array('en' => 'custom_en'),
            'lang_param_name' => 'wovn',
            'url_pattern_name' => 'query'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        $expected_href = 'http://my-site.com/?wovn=ja';
        $generated_href = TestUtils::invokeMethod($converter, 'buildHrefLang', array('ja'));
        $this->assertEquals($expected_href, $generated_href);
    }

    public function testBuildHrefLangQueryCustomLangParamName()
    {
        $html = '<html><body><a>hello</a></body></html>';
        $settings = array(
            'supported_langs' => array('ja'),
            'default_lang' => 'en',
            'url_pattern_name' => 'query',
            'lang_param_name' => 'lan'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        $expected_href = 'http://my-site.com/?lan=ja';
        $generated_href = TestUtils::invokeMethod($converter, 'buildHrefLang', array('ja'));
        $this->assertEquals($expected_href, $generated_href);
    }

    public function testBuildHrefLangSubdomain()
    {
        $html = '<html><body><a>hello</a></body></html>';
        $settings = array(
            'supported_langs' => array('ja'),
            'default_lang' => 'en',
            'lang_param_name' => 'wovn',
            'url_pattern_name' => 'subdomain'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        $expected_href = 'http://ja.my-site.com/';
        $generated_href = TestUtils::invokeMethod($converter, 'buildHrefLang', array('ja'));
        $this->assertEquals($expected_href, $generated_href);
    }

    public function testBuildHrefLangSubdomainDefaultLangAlias()
    {
        $html = '<html><body><a>hello</a></body></html>';
        $settings = array(
            'supported_langs' => array('ja'),
            'default_lang' => 'en',
            'custom_lang_aliases' => array('en' => 'custom_en'),
            'lang_param_name' => 'wovn',
            'url_pattern_name' => 'subdomain'
        );

        $envs = array(
            'REQUEST_URI' => '/pages.html',
            'HTTP_HOST' => 'custom_en.my-site.com'
        );

        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $envs);
        $this->assertEquals('/pages.html', $headers->pathname);
        $this->assertEquals('en', $headers->lang());
        $this->assertEquals('custom_en.my-site.com', $headers->host);
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        $expected_href = 'http://ja.my-site.com/pages.html';
        $generated_href = TestUtils::invokeMethod($converter, 'buildHrefLang', array('ja'));
        $this->assertEquals($expected_href, $generated_href);
    }

    public function testinsertSnippetAndHreflangTagsWithErrorMark()
    {
        $html = '<html><body><a>hello</a></body></html>';
        $settings = array(
            'supported_langs' => array('en', 'vi'),
            'lang_param_name' => 'wovn'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        list($translated_html) = $converter->insertSnippetAndHreflangTags(true);

        $expected_html = "<html><body><link rel=\"alternate\" hreflang=\"en\" href=\"http://my-site.com/\"><link rel=\"alternate\" hreflang=\"vi\" href=\"http://my-site.com/?wovn=vi\"><script src=\"//j.wovn.io/1\" data-wovnio=\"key=123456&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=query&amp;langCodeAliases=[]&amp;langParamName=wovn\" data-wovnio-info=\"version=WOVN.php_VERSION\" data-wovnio-type=\"fallback_snippet\" async></script><a>hello</a></body></html>";
        $this->assertEquals($expected_html, $translated_html);
    }

    public function testConvertToAppropriateBodyForApi()
    {
        $html = '<html><body><a>hello</a></body></html>';
        $settings = array(
            'supported_langs' => array('en', 'vi'),
            'lang_param_name' => 'wovn'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        list($translated_html) = $converter->convertToAppropriateBodyForApi();

        $expected_html = "<html><body><link rel=\"alternate\" hreflang=\"en\" href=\"http://my-site.com/\"><link rel=\"alternate\" hreflang=\"vi\" href=\"http://my-site.com/?wovn=vi\"><script src=\"//j.wovn.io/1\" data-wovnio=\"key=123456&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=query&amp;langCodeAliases=[]&amp;langParamName=wovn\" data-wovnio-info=\"version=WOVN.php_VERSION\" data-wovnio-type=\"fallback_snippet\" async></script><a>hello</a></body></html>";
        $this->assertEquals($expected_html, $translated_html);
    }

    public function testConvertToAppropriateBodyForApiDoesNotFailForEmptyContent()
    {
        $long_string = '';
        for ($i = 0; $i < 600000; $i++) {
            $long_string .= 'a';
        }
        $html = '<html><body><p>' . $long_string . '</p></body></html>';
        $settings = array(
            'supported_langs' => array('en', 'vi'),
            'lang_param_name' => 'wovn'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        list($translated_html) = $converter->convertToAppropriateBodyForApi();

        $expected_html = '<html><body><link rel="alternate" hreflang="en" href="http://my-site.com/"><link rel="alternate" hreflang="vi" href="http://my-site.com/?wovn=vi"><script src="//j.wovn.io/1" data-wovnio="key=123456&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=query&amp;langCodeAliases=[]&amp;langParamName=wovn" data-wovnio-info="version=WOVN.php_VERSION" data-wovnio-type="fallback_snippet" async></script><p>' . $long_string . '</p></body></html>';
        $this->assertEquals($expected_html, $translated_html);
    }

    public function testConvertToAppropriateBodyForApiDoesNotFailForContentOverDefaultSimpleHtmlDomMaxSize()
    {
        $html = '';
        $settings = array(
            'supported_langs' => array('en', 'vi'),
            'lang_param_name' => 'wovn'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        list($translated_html) = $converter->convertToAppropriateBodyForApi();

        $expected_html = "";
        $this->assertEquals($expected_html, $translated_html);
    }

    public function testInsertSnippetAndHreflangTagsWithEmptySupportedLangs()
    {
        $html = '<html><body><a>hello</a></body></html>';
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default');
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        list($translated_html) = $converter->convertToAppropriateBodyForApi();

        $expected_html = "<html><body><link rel=\"alternate\" hreflang=\"en\" href=\"http://my-site.com/\"><script src=\"//j.wovn.io/1\" data-wovnio=\"key=123456&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=query&amp;langCodeAliases=[]&amp;langParamName=wovn\" data-wovnio-info=\"version=WOVN.php_VERSION\" data-wovnio-type=\"fallback_snippet\" async></script><a>hello</a></body></html>";
        $this->assertEquals($expected_html, $translated_html);
    }

    public function testInsertSnippetAndHreflangTagsWithHead()
    {
        $html = '<html><head><title>TITLE</title></head><body><a>hello</a></body></html>';
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default');
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        list($translated_html) = $converter->convertToAppropriateBodyForApi();

        $expected_html = "<html><head><link rel=\"alternate\" hreflang=\"en\" href=\"http://my-site.com/\"><script src=\"//j.wovn.io/1\" data-wovnio=\"key=123456&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=query&amp;langCodeAliases=[]&amp;langParamName=wovn\" data-wovnio-info=\"version=WOVN.php_VERSION\" data-wovnio-type=\"fallback_snippet\" async></script><title>TITLE</title></head><body><a>hello</a></body></html>";
        $this->assertEquals($expected_html, $translated_html);
    }

    public function testInsertSnippetAndHreflangTagsWithoutBody()
    {
        $html = '<html>hello<a>world</a></html>';
        $settings = array(
            'supported_langs' => array(),
            'lang_param_name' => 'wovn'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        list($translated_html) = $converter->convertToAppropriateBodyForApi();

        $expected_html = "<html><script src=\"//j.wovn.io/1\" data-wovnio=\"key=123456&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=query&amp;langCodeAliases=[]&amp;langParamName=wovn\" data-wovnio-info=\"version=WOVN.php_VERSION\" data-wovnio-type=\"fallback_snippet\" async></script>hello<a>world</a></html>";
        $this->assertEquals($expected_html, $translated_html);
    }

    public function testInsertSnippetAndHreflangTagsOnDefaultLangWithQuerySupportedLangs()
    {
        $html = '<html>hello<a>world</a></html>';
        $settings = array(
            'default_lang' => 'en',
            'supported_langs' => array('en', 'ja', 'vi'),
            'url_pattern_name' => 'query',
            'lang_param_name' => 'wovn'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        list($translated_html) = $converter->convertToAppropriateBodyForApi();

        $expected_html = "<html><link rel=\"alternate\" hreflang=\"en\" href=\"http://my-site.com/\"><link rel=\"alternate\" hreflang=\"ja\" href=\"http://my-site.com/?wovn=ja\"><link rel=\"alternate\" hreflang=\"vi\" href=\"http://my-site.com/?wovn=vi\"><script src=\"//j.wovn.io/1\" data-wovnio=\"key=123456&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=query&amp;langCodeAliases=[]&amp;langParamName=wovn\" data-wovnio-info=\"version=WOVN.php_VERSION\" data-wovnio-type=\"fallback_snippet\" async></script>hello<a>world</a></html>";
        $this->assertEquals($expected_html, $translated_html);
    }

    public function testInsertSnippetAndHreflangTagsOnDefaultLangWithPathSupportedLangs()
    {
        $html = '<html>hello<a>world</a></html>';
        $settings = array(
            'default_lang' => 'en',
            'supported_langs' => array('en', 'ja', 'vi'),
            'url_pattern_name' => 'path',
            'lang_param_name' => 'wovn'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        list($translated_html) = $converter->convertToAppropriateBodyForApi();

        $expected_html = "<html><link rel=\"alternate\" hreflang=\"en\" href=\"http://my-site.com/\"><link rel=\"alternate\" hreflang=\"ja\" href=\"http://my-site.com/ja/\"><link rel=\"alternate\" hreflang=\"vi\" href=\"http://my-site.com/vi/\"><script src=\"//j.wovn.io/1\" data-wovnio=\"key=123456&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=path&amp;langCodeAliases=[]&amp;langParamName=wovn\" data-wovnio-info=\"version=WOVN.php_VERSION\" data-wovnio-type=\"fallback_snippet\" async></script>hello<a>world</a></html>";
        $this->assertEquals($expected_html, $translated_html);
    }

    public function testInsertSnippetAndHreflangTagsOnDefaultLangWithSubdomainSupportedLangs()
    {
        $html = '<html>hello<a>world</a></html>';
        $settings = array(
            'default_lang' => 'en',
            'supported_langs' => array('en', 'ja', 'vi'),
            'url_pattern_name' => 'subdomain',
            'lang_param_name' => 'wovn'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        list($translated_html) = $converter->convertToAppropriateBodyForApi();

        $expected_html = "<html><link rel=\"alternate\" hreflang=\"en\" href=\"http://my-site.com/\"><link rel=\"alternate\" hreflang=\"ja\" href=\"http://ja.my-site.com/\"><link rel=\"alternate\" hreflang=\"vi\" href=\"http://vi.my-site.com/\"><script src=\"//j.wovn.io/1\" data-wovnio=\"key=123456&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=subdomain&amp;langCodeAliases=[]&amp;langParamName=wovn\" data-wovnio-info=\"version=WOVN.php_VERSION\" data-wovnio-type=\"fallback_snippet\" async></script>hello<a>world</a></html>";
        $this->assertEquals($expected_html, $translated_html);
    }

    public function testConvertToAppropriateBodyForApiWithEmptySupportedLangs()
    {
        $html = '<html><body><a>hello</a></body></html>';
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default');
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        list($translated_html) = $converter->convertToAppropriateBodyForApi();

        $expected_html = "<html><body><link rel=\"alternate\" hreflang=\"en\" href=\"http://my-site.com/\"><script src=\"//j.wovn.io/1\" data-wovnio=\"key=123456&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=query&amp;langCodeAliases=[]&amp;langParamName=wovn\" data-wovnio-info=\"version=WOVN.php_VERSION\" data-wovnio-type=\"fallback_snippet\" async></script><a>hello</a></body></html>";
        $this->assertEquals($expected_html, $translated_html);
    }

    public function testConvertToAppropriateBodyForApiWithHead()
    {
        $html = '<html><head><title>TITLE</title></head><body><a>hello</a></body></html>';
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default');
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        list($translated_html) = $converter->convertToAppropriateBodyForApi();

        $expected_html = "<html><head><link rel=\"alternate\" hreflang=\"en\" href=\"http://my-site.com/\"><script src=\"//j.wovn.io/1\" data-wovnio=\"key=123456&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=query&amp;langCodeAliases=[]&amp;langParamName=wovn\" data-wovnio-info=\"version=WOVN.php_VERSION\" data-wovnio-type=\"fallback_snippet\" async></script><title>TITLE</title></head><body><a>hello</a></body></html>";
        $this->assertEquals($expected_html, $translated_html);
    }

    public function testConvertToAppropriateBodyForApiWithoutBody()
    {
        $html = '<html>hello<a>world</a></html>';
        $settings = array(
            'supported_langs' => array(),
            'lang_param_name' => 'wovn'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        list($translated_html) = $converter->convertToAppropriateBodyForApi();

        $expected_html = "<html><script src=\"//j.wovn.io/1\" data-wovnio=\"key=123456&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=query&amp;langCodeAliases=[]&amp;langParamName=wovn\" data-wovnio-info=\"version=WOVN.php_VERSION\" data-wovnio-type=\"fallback_snippet\" async></script>hello<a>world</a></html>";
        $this->assertEquals($expected_html, $translated_html);
    }

    public function testConvertToAppropriateBodyForApiWithoutEncoding()
    {
        $html = mb_convert_encoding('<html>こんにちは</html>', 'SJIS');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default');
        $converter = new HtmlConverter($html, null, $store->settings['project_token'], $store, $headers);
        list($translated_html) = $converter->convertToAppropriateBodyForApi();

        $expected_html = "<html><link rel=\"alternate\" hreflang=\"en\" href=\"http://my-site.com/\"><script src=\"//j.wovn.io/1\" data-wovnio=\"key=123456&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=query&amp;langCodeAliases=[]&amp;langParamName=wovn\" data-wovnio-info=\"version=WOVN.php_VERSION\" data-wovnio-type=\"fallback_snippet\" async></script>こんにちは</html>";
        $expected_html = mb_convert_encoding($expected_html, 'SJIS');

        $this->assertEquals($expected_html, $translated_html);
    }

    public function testConvertToAppropriateBodyForApiWithSupportedEncoding()
    {
        foreach (HtmlConverter::$supportedEncodings as $encoding) {
            $html = mb_convert_encoding('<html>こんにちは</html>', $encoding);
            list($store, $headers) = StoreAndHeadersFactory::fromFixture('default');
            $converter = new HtmlConverter($html, $encoding, $store->settings['project_token'], $store, $headers);
            list($translated_html) = $converter->convertToAppropriateBodyForApi();

            $expected_html = "<html><link rel=\"alternate\" hreflang=\"en\" href=\"http://my-site.com/\"><script src=\"//j.wovn.io/1\" data-wovnio=\"key=123456&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=query&amp;langCodeAliases=[]&amp;langParamName=wovn\" data-wovnio-info=\"version=WOVN.php_VERSION\" data-wovnio-type=\"fallback_snippet\" async></script>こんにちは</html>";
            $expected_html = mb_convert_encoding($expected_html, $encoding);
            $this->assertEquals($expected_html, $translated_html);
        }
    }

    public function testConvertToAppropriateBodyForApiWithWovnIgnore()
    {
        $html = '<html><body><a wovn-ignore>hello</a></body></html>';
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default');
        $converter = new HtmlConverter($html, null, $store->settings['project_token'], $store, $headers);
        list($translated_html, $marker) = $this->executeConvert($converter, $html, 'UTF-8', '_removeWovnIgnore');
        $keys = $marker->keys();

        $this->assertEquals(1, count($keys));
        $this->assertEquals("<html><body><a wovn-ignore>$keys[0]</a></body></html>", $translated_html);
    }

    public function testConvertToAppropriateBodyForApiWithDataWovnIgnore()
    {
        $html = '<html><body><a data-wovn-ignore>hello</a></body></html>';
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default');
        $converter = new HtmlConverter($html, null, $store->settings['project_token'], $store, $headers);
        list($translated_html, $marker) = $this->executeConvert($converter, $html, 'UTF-8', '_removeWovnIgnore');
        $keys = $marker->keys();

        $this->assertEquals(1, count($keys));
        $this->assertEquals('<!-- __wovn-backend-ignored-key-0 -->', $keys[0]);
        $this->assertEquals("<html><body><a data-wovn-ignore>$keys[0]</a></body></html>", $translated_html);
    }

    public function testConvertToAppropriateBodyForApiWithCustomIgnoreClass()
    {
        $html = "<html><body><a class=\"random    \n\f\rignore\tvalid custom\">hello</a></body></html>";
        $settings = array(
            'ignore_class' => array('ignore'),
            'lang_param_name' => 'wovn'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);
        $converter = new HtmlConverter($html, null, $store->settings['project_token'], $store, $headers);
        list($translated_html, $marker) = $this->executeConvert($converter, $html, 'UTF-8', '_removeCustomIgnoreClass');
        $keys = $marker->keys();

        $this->assertEquals(1, count($keys));
        $this->assertEquals("<html><body><a class=\"random    \n\f\rignore\tvalid custom\">$keys[0]</a></body></html>", $translated_html);
    }

    public function testConvertToAppropriateBodyForApiWithMultipleWovnIgnore()
    {
        $html = '<html><body><a wovn-ignore>hello</a>ignore<div wovn-ignore>world</div></body></html>';
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default');
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        list($translated_html, $marker) = $this->executeConvert($converter, $html, 'UTF-8', '_removeWovnIgnore');
        $keys = $marker->keys();

        $this->assertEquals(2, count($keys));
        $this->assertEquals("<html><body><a wovn-ignore>$keys[0]</a>ignore<div wovn-ignore>$keys[1]</div></body></html>", $translated_html);
    }

    public function testConvertToAppropriateBodyForApiWithForm()
    {
        $html = '<html><body><form>hello<input type="button" value="click"></form>world</body></html>';
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default');
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        list($translated_html, $marker) = $this->executeConvert($converter, $html, 'UTF-8', '_removeForm');
        $keys = $marker->keys();

        $this->assertEquals(1, count($keys));
        $this->assertEquals("<html><body><form>$keys[0]</form>world</body></html>", $translated_html);
    }

    public function testConvertToAppropriateBodyForApiWithMultipleForm()
    {
        $html = '<html><body><form>hello<input type="button" value="click"></form>world<form>hello2<input type="button" value="click2"></form></body></html>';
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default');
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        list($translated_html, $marker) = $this->executeConvert($converter, $html, 'UTF-8', '_removeForm');
        $keys = $marker->keys();

        $this->assertEquals(2, count($keys));
        $this->assertEquals("<html><body><form>$keys[0]</form>world<form>$keys[1]</form></body></html>", $translated_html);
    }

    public function testConvertToAppropriateBodyForApiWithFormAndWovnIgnore()
    {
        $html = '<html><body><form wovn-ignore>hello<input type="button" value="click"></form>world</body></html>';
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default');
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        list($translated_html, $marker) = $this->executeConvert($converter, $html, 'UTF-8', '_removeForm');
        $keys = $marker->keys();

        $this->assertEquals(1, count($keys));
        $this->assertEquals("<html><body><form wovn-ignore>$keys[0]</form>world</body></html>", $translated_html);
    }

    public function testConvertToAppropriateBodyForApiWithHiddenInput()
    {
        $html = '<html><body><input type="hidden" value="aaaaa">world</body></html>';
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default');
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        list($translated_html, $marker) = $this->executeConvert($converter, $html, 'UTF-8', '_removeForm');
        $keys = $marker->keys();

        $this->assertEquals(1, count($keys));
        $this->assertEquals("<html><body><input type=\"hidden\" value=\"$keys[0]\">world</body></html>", $translated_html);
    }

    public function testConvertToAppropriateBodyForApiWithHiddenInputMultipleTimes()
    {
        $html = '<html><body><input type="hidden" value="aaaaa">world<input type="hidden" value="aaaaa"></body></html>';
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default');
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        list($translated_html, $marker) = $this->executeConvert($converter, $html, 'UTF-8', '_removeForm');
        $keys = $marker->keys();

        $this->assertEquals(2, count($keys));
        $this->assertEquals("<html><body><input type=\"hidden\" value=\"$keys[0]\">world<input type=\"hidden\" value=\"$keys[1]\"></body></html>", $translated_html);
    }

    public function testConvertToAppropriateBodyForApiWithScript()
    {
        $html = '<html><body><script>console.log("hello")</script>world</body></html>';
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default');
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        list($translated_html, $marker) = $this->executeConvert($converter, $html, 'UTF-8', '_removeScript');
        $keys = $marker->keys();

        $this->assertEquals(1, count($keys));
        $this->assertEquals("<html><body><script>$keys[0]</script>world</body></html>", $translated_html);
    }

    public function testConvertToAppropriateBodyForApiWithMultipleScript()
    {
        $html = '<html><head><script>console.log("hello")</script></head><body>world<script>console.log("hello2")</script></body></html>';
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default');
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        list($translated_html, $marker) = $this->executeConvert($converter, $html, 'UTF-8', '_removeScript');
        $keys = $marker->keys();

        $this->assertEquals(2, count($keys));
        $this->assertEquals("<html><head><script>$keys[0]</script></head><body>world<script>$keys[1]</script></body></html>", $translated_html);
    }

    public function testConvertToAppropriateBodyForApiWithComment()
    {
        $html = '<html><body>hello<!-- backend-wovn-ignore    -->ignored <!--/backend-wovn-ignore-->  world</body></html>';
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default');
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        list($translated_html, $marker) = $this->executeRemoveBackendWovnIgnoreComment($converter, $html);
        $keys = $marker->keys();

        $this->assertEquals(1, count($keys));
        $this->assertEquals("<html><body>hello<!-- backend-wovn-ignore    -->$keys[0]<!--/backend-wovn-ignore-->  world</body></html>", $translated_html);
    }

    public function testConvertToAppropriateBodyForApiWithMultipleComment()
    {
        $html = "<html><body>hello<!-- backend-wovn-ignore    -->ignored <!--/backend-wovn-ignore-->  world
            line break
            <!-- backend-wovn-ignore    -->

ignored2

<!--/backend-wovn-ignore-->
bye
</body></html>";
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default');
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        list($translated_html, $marker) = $this->executeRemoveBackendWovnIgnoreComment($converter, $html);
        $keys = $marker->keys();

        $this->assertEquals(2, count($keys));

        $expected_html = "<html><body>hello<!-- backend-wovn-ignore    -->$keys[0]<!--/backend-wovn-ignore-->  world
            line break
            <!-- backend-wovn-ignore    -->$keys[1]<!--/backend-wovn-ignore-->
bye
</body></html>";
        $this->assertEquals($expected_html, $translated_html);
    }

    public function testInsertHreflang()
    {
        libxml_use_internal_errors(true);
        $html = file_get_contents('test/fixtures/real_html/stack_overflow_hreflang.html');
        $settings = array(
            'default_lang' => 'ja',
            'supported_langs' => array('en', 'vi'),
            'disable_api_request_for_default_lang' => true,
            'url_pattern_name' => 'path',
            'lang_param_name' => 'wovn'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        list($translated_html) = $converter->insertSnippetAndHreflangTags(false);

        $expected_html_text = file_get_contents('test/fixtures/real_html/stack_overflow_hreflang_expected.html');

        $this->assertEquals($expected_html_text, $translated_html);
    }

    public function testInsertHreflangWithCustomLangCodes()
    {
        libxml_use_internal_errors(true);
        $html = file_get_contents('test/fixtures/basic_html/insert_hreflang_with_custom_lang_codes.html');
        $settings = array(
            'default_lang' => 'en',
            'supported_langs' => array('en', 'vi', 'zh-CHS'),
            'disable_api_request_for_default_lang' => true,
            'url_pattern_name' => 'path',
            'custom_lang_aliases' => array('zh-CHS' => 'custom_simple'),
            'lang_param_name' => 'wovn'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        list($translated_html) = $converter->insertSnippetAndHreflangTags(false);

        $expected_html_text = file_get_contents('test/fixtures/basic_html/insert_hreflang_with_custom_lang_codes_expected.html');

        $this->assertEquals($expected_html_text, $translated_html);
    }

    public function testInsertHreflangWithDefaultLangAliasWithPathPattern()
    {
        libxml_use_internal_errors(true);
        $html = file_get_contents('test/fixtures/basic_html/insert_hreflang_with_default_lang_alias.html');
        $settings = array(
            'default_lang' => 'en',
            'supported_langs' => array('en', 'vi'),
            'disable_api_request_for_default_lang' => true,
            'url_pattern_name' => 'path',
            'custom_lang_aliases' => array('en' => 'english'),
            'lang_param_name' => 'wovn'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        list($translated_html) = $converter->insertSnippetAndHreflangTags(false);

        $expected_html_text = file_get_contents('test/fixtures/basic_html/insert_hreflang_with_default_lang_alias_expected.html');

        $this->assertEquals($expected_html_text, $translated_html);
    }

    public function testInsertHreflangWithDefaultLangAliasWithSubdomainPattern()
    {
        libxml_use_internal_errors(true);
        $html = file_get_contents('test/fixtures/basic_html/insert_hreflang_with_default_lang_alias.html');
        $settings = array(
            'default_lang' => 'en',
            'supported_langs' => array('en', 'vi'),
            'disable_api_request_for_default_lang' => true,
            'url_pattern_name' => 'subdomain',
            'custom_lang_aliases' => array('en' => 'english'),
            'lang_param_name' => 'wovn'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        list($translated_html) = $converter->insertSnippetAndHreflangTags(false);

        $expected_html_text = file_get_contents('test/fixtures/basic_html/insert_hreflang_with_default_lang_alias_expected_subdomain.html');

        $this->assertEquals($expected_html_text, $translated_html);
    }

    public function testInsertHreflangIntoHeadWithStyle()
    {
        libxml_use_internal_errors(true);
        $html = file_get_contents('test/fixtures/basic_html/insert_hreflang_head_style.html');
        $settings = array(
            'default_lang' => 'ja',
            'supported_langs' => array('en', 'vi'),
            'disable_api_request_for_default_lang' => true,
            'url_pattern_name' => 'path',
            'lang_param_name' => 'wovn'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        list($translated_html) = $converter->insertSnippetAndHreflangTags(false);

        $expected_html_text = file_get_contents('test/fixtures/basic_html/insert_hreflang_head_style_expected.html');

        $this->assertEquals($expected_html_text, $translated_html);
    }

    public function testInsertHreflangIntoBodyTag()
    {
        libxml_use_internal_errors(true);
        $html = file_get_contents('test/fixtures/basic_html/insert_hreflang_body.html');
        $settings = array(
            'default_lang' => 'ja',
            'supported_langs' => array('en', 'vi'),
            'disable_api_request_for_default_lang' => true,
            'url_pattern_name' => 'path',
            'lang_param_name' => 'wovn'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        list($translated_html) = $converter->insertSnippetAndHreflangTags(false);

        $expected_html_text = file_get_contents('test/fixtures/basic_html/insert_hreflang_body_expected.html');

        $this->assertEquals($expected_html_text, $translated_html);
    }

    public function testInsertSnippetForHtmlWithSnippetCode()
    {
        libxml_use_internal_errors(true);
        $html = file_get_contents('test/fixtures/basic_html/insert_snippet_when_already_exist.html');
        $settings = array(
            'default_lang' => 'ja',
            'supported_langs' => array('en', 'vi'),
            'disable_api_request_for_default_lang' => true,
            'url_pattern_name' => 'path',
            'lang_param_name' => 'wovn'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        list($translated_html) = $converter->insertSnippetAndHreflangTags(false);

        $expected_html_text = file_get_contents('test/fixtures/basic_html/insert_snippet_when_already_exist_expected.html');

        $this->assertEquals($expected_html_text, $translated_html);
    }

    public function testInsertHreflangIntoHtmlTag()
    {
        libxml_use_internal_errors(true);
        $html = file_get_contents('test/fixtures/basic_html/insert_hreflang_html.html');
        $settings = array(
            'default_lang' => 'ja',
            'supported_langs' => array('en', 'vi'),
            'disable_api_request_for_default_lang' => true,
            'url_pattern_name' => 'path',
            'lang_param_name' => 'wovn'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        list($translated_html) = $converter->insertSnippetAndHreflangTags(false);

        $expected_html_text = file_get_contents('test/fixtures/basic_html/insert_hreflang_html_expected.html');

        $this->assertEquals($expected_html_text, $translated_html);
    }

    public function testInsertHreflangShouldRemoveExistHreflangTags()
    {
        libxml_use_internal_errors(true);
        $html = file_get_contents('test/fixtures/basic_html/insert_with_exist_hreflang.html');
        $settings = array(
            'default_lang' => 'ja',
            'supported_langs' => array('en', 'vi', 'zh-CHT', 'zh-CHS'),
            'disable_api_request_for_default_lang' => true,
            'url_pattern_name' => 'path',
            'lang_param_name' => 'wovn'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        list($translated_html) = $converter->insertSnippetAndHreflangTags(false);

        $expected_html_text = file_get_contents('test/fixtures/basic_html/insert_with_exist_hreflang_expected.html');
        $this->assertEquals($expected_html_text, $translated_html);
    }

    public function testInsertHreflangHtmlEntities()
    {
        libxml_use_internal_errors(true);
        $html = file_get_contents('test/fixtures/real_html/stack_overflow_hreflang.html');
        $settings = array(
            'default_lang' => 'ja',
            'supported_langs' => array('en', 'vi'),
            'disable_api_request_for_default_lang' => true,
            'url_pattern_name' => 'path',
            'lang_param_name' => 'wovn'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        list($translated_html) = $converter->insertSnippetAndHreflangTags(false);

        $expected_html_text = file_get_contents('test/fixtures/real_html/stack_overflow_hreflang_html_entities_expected.html');

        $this->assertEquals($expected_html_text, $translated_html);
    }

    public function testInsertHreflangWithCustomLangAliasAndChinese()
    {
        libxml_use_internal_errors(true);
        $html = file_get_contents('test/fixtures/basic_html/insert_hreflang.html');
        $settings = array(
            'default_lang' => 'ja',
            'supported_langs' => array('en', 'vi', 'zh-CHT', 'zh-CHS'),
            'custom_lang_aliases' => array('en' => 'custom_en', 'zh-CHS' => 'custom_simple'),
            'url_pattern_name' => 'path',
            'lang_param_name' => 'wovn'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        list($translated_html) = $converter->insertSnippetAndHreflangTags(false);

        $expected_html_text = file_get_contents('test/fixtures/basic_html/insert_hreflang_expected.html');

        $this->assertEquals($expected_html_text, $translated_html);
    }

    public function testInsertHreflangWithCustomLangAlias()
    {
        libxml_use_internal_errors(true);
        $html = file_get_contents('test/fixtures/basic_html/insert_hreflang_lang_alias.html');
        $settings = array(
            'default_lang' => 'en',
            'supported_langs' => array('en', 'zh-CHT', 'zh-CHS'),
            'custom_lang_aliases' => array('zh-CHS' => 'cs', 'zh-CHT' => 'ct'),
            'url_pattern_name' => 'path',
            'lang_param_name' => 'wovn'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        list($translated_html) = $converter->insertSnippetAndHreflangTags(false);

        $expected_html_text = file_get_contents('test/fixtures/basic_html/insert_hreflang_expected_lang_alias.html');

        $this->assertEquals($expected_html_text, $translated_html);
    }

    public function testInsertHreflangWithLangParamName()
    {
        libxml_use_internal_errors(true);
        $html = file_get_contents('test/fixtures/basic_html/insert_hreflang_lang_alias.html');
        $settings = array(
            'default_lang' => 'en',
            'supported_langs' => array('en', 'fr', 'de'),
            'url_pattern_name' => 'query',
            'lang_param_name' => 'language'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        list($translated_html) = $converter->insertSnippetAndHreflangTags(false);

        $expected_html_text = file_get_contents('test/fixtures/basic_html/insert_hreflang_expected_lang_param_name.html');

        $this->assertEquals($expected_html_text, $translated_html);
    }

    public function testInsertHreflangWithDefaultCustomLangAlias()
    {
        libxml_use_internal_errors(true);
        $html = file_get_contents('test/fixtures/basic_html/insert_hreflang_default_lang_alias.html');
        $settings = array(
            'default_lang' => 'en',
            'supported_langs' => array('en', 'zh-CHT', 'zh-CHS'),
            'custom_lang_aliases' => array('en' => 'en', 'zh-CHS' => 'cs', 'zh-CHT' => 'ct'),
            'url_pattern_name' => 'path',
            'lang_param_name' => 'wovn'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        list($translated_html) = $converter->insertSnippetAndHreflangTags(false);

        $expected_html_text = file_get_contents('test/fixtures/basic_html/insert_hreflang_expected_default_lang_alias.html');

        $this->assertEquals($expected_html_text, $translated_html);
    }

    public function testInsertHreflangWithDefaultCustomLangAliasAndTrailingSlash()
    {
        libxml_use_internal_errors(true);
        $html = file_get_contents('test/fixtures/basic_html/insert_hreflang_default_lang_alias.html');
        $settings = array(
            'default_lang' => 'en',
            'supported_langs' => array('en', 'zh-CHT', 'zh-CHS'),
            'custom_lang_aliases' => array('en' => 'en', 'zh-CHS' => 'cs', 'zh-CHT' => 'ct'),
            'url_pattern_name' => 'path',
            'lang_param_name' => 'wovn'
        );
        $env = array('REQUEST_URI' => '/dir1/dir2/');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        list($translated_html) = $converter->insertSnippetAndHreflangTags(false);

        $expected_html_text = file_get_contents('test/fixtures/basic_html/insert_hreflang_expected_default_lang_alias_trailing_slash.html');

        $this->assertEquals($expected_html_text, $translated_html);
    }

    public function testInsertHreflangWithNoindexLangs()
    {
        libxml_use_internal_errors(true);
        $html = file_get_contents('test/fixtures/basic_html/insert_hreflang_default_lang_alias.html');
        $settings = array(
            'default_lang' => 'en',
            'supported_langs' => array('en', 'zh-CHT', 'zh-CHS'),
            'custom_lang_aliases' => array('en' => 'en', 'zh-CHS' => 'cs', 'zh-CHT' => 'ct'),
            'url_pattern_name' => 'path',
            'lang_param_name' => 'wovn',
            'no_index_langs' => array('en')
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        list($translated_html) = $converter->insertSnippetAndHreflangTags(false);

        $expected_html_text = file_get_contents('test/fixtures/basic_html/insert_hreflang_expected_noindex_langs.html');

        $this->assertEquals($expected_html_text, $translated_html);
    }

    public function testInsertHreflangWithMultiNoindexLangs()
    {
        libxml_use_internal_errors(true);
        $html = file_get_contents('test/fixtures/basic_html/insert_hreflang_default_lang_alias.html');
        $settings = array(
            'default_lang' => 'en',
            'supported_langs' => array('en', 'zh-CHT', 'zh-CHS'),
            'custom_lang_aliases' => array('en' => 'en', 'zh-CHS' => 'cs', 'zh-CHT' => 'ct'),
            'url_pattern_name' => 'path',
            'lang_param_name' => 'wovn',
            'no_index_langs' => array('en', 'cs', 'fr')
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        list($translated_html) = $converter->insertSnippetAndHreflangTags(false);

        $expected_html_text = file_get_contents('test/fixtures/basic_html/insert_hreflang_expected_multi_noindex_langs.html');
        $this->assertEquals($expected_html_text, $translated_html);
    }

    public function testInsertHreflangWithSitePrefixPath()
    {
        libxml_use_internal_errors(true);
        $html = file_get_contents('test/fixtures/basic_html/insert_hreflang_default_lang_alias.html');
        $settings = array(
            'default_lang' => 'en',
            'supported_langs' => array('en', 'zh-CHT', 'zh-CHS'),
            'url_pattern_name' => 'path',
            'lang_param_name' => 'wovn',
            'site_prefix_path' => '/dir1/dir2/'
        );
        $env = array('REQUEST_URI' => '/dir1/dir2/');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        list($translated_html) = $converter->insertSnippetAndHreflangTags(false);

        $expected_html_text = file_get_contents('test/fixtures/basic_html/insert_hreflang_expected_site_prefix_path.html');
        $this->assertEquals($expected_html_text, $translated_html);
    }

    public function testInsertHreflangWithSitePrefixPathAndCustomLangAliases()
    {
        libxml_use_internal_errors(true);
        $html = file_get_contents('test/fixtures/basic_html/insert_hreflang_default_lang_alias.html');
        $settings = array(
            'default_lang' => 'en',
            'supported_langs' => array('en', 'zh-CHT', 'zh-CHS'),
            'url_pattern_name' => 'path',
            'lang_param_name' => 'wovn',
            'custom_lang_aliases' => array('en' => 'en', 'zh-CHS' => 'custom_simple'),
            'site_prefix_path' => '/dir1/dir2/'
        );
        $env = array('REQUEST_URI' => '/dir1/dir2/');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);
        $converter = new HtmlConverter($html, 'UTF-8', $store->settings['project_token'], $store, $headers);
        list($translated_html) = $converter->insertSnippetAndHreflangTags(false);

        $expected_html_text = file_get_contents('test/fixtures/basic_html/insert_hreflang_expected_site_prefix_path_and_custom_lang_codes.html');
        $this->assertEquals($expected_html_text, $translated_html);
    }

    private function executeConvert($converter, $html, $charset, $name)
    {
        $dom = SimpleHtmlDom::str_get_html($html, $charset, false, false, $charset, false);
        $marker = new HtmlReplaceMarker();

        $method = new \ReflectionMethod($converter, $name);
        $method->setAccessible(true);

        $dom->iterateAll(function ($node) use ($method, $converter, $marker) {
            $method->invoke($converter, $node, $marker);
        });

        $converted_html = $dom->save();
        $dom->clear();
        unset($dom);

        return array($converted_html, $marker);
    }

    private function executeRemoveBackendWovnIgnoreComment($converter, $html)
    {
        $marker = new HtmlReplaceMarker();

        $method = new \ReflectionMethod($converter, 'removeBackendWovnIgnoreComment');
        $method->setAccessible(true);
        $converted_html = $method->invoke($converter, $html, $marker);

        return array($converted_html, $marker);
    }
}
