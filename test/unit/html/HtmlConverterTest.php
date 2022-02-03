<?php
namespace Wovnio\Wovnphp\Tests\Unit;

use \Wovnio\Html\HtmlConverter;
use Wovnio\Test\Helpers\TestUtils;
use \Wovnio\Html\HtmlReplaceMarker;
use \Wovnio\Test\Helpers\StoreAndHeadersFactory;
use \Wovnio\ModifiedVendor\SimpleHtmlDom;

use PHPUnit\Framework\TestCase;

class HtmlConverterTest extends TestCase
{
    public function testInsertSnippetAndLangTagsWithSampleWebsites()
    {
        libxml_use_internal_errors(true);

        $websites = array(
            'real_html/stack_overflow',
            'real_html/youtube',
            'real_html/yelp',
            'real_html/yahoo_jp'
        );

        foreach ($websites as $website_name) {
            // insert snippet and hreflang
            $original_html = file_get_contents("test/fixtures/{$website_name}.html");
            list($store, $headers) = StoreAndHeadersFactory::fromFixture('default');
            $converter = new HtmlConverter('UTF-8', $store->settings['project_token'], $store, $headers);
            $translated_html = $converter->insertSnippetAndLangTags($original_html, false);

            $actual_html = $this->convertEncordingAndCorrectHtml($converter->revertMarkers($translated_html));
            $expected_html = $this->convertEncordingAndCorrectHtml(file_get_contents("test/fixtures/{$website_name}_expected.html"));

            $this->assertEquals($expected_html, $actual_html);
        }
    }

    public function testInsertSnippetAndLangTags()
    {
        $html_cases = array(
            array(
                'Common case',

                '<html><head></head><body><a>hello</a></body></html>',

                '<html lang="en">' .
                '<head>' .
                '<link rel="alternate" hreflang="en" href="http://my-site.com/">' .
                '<link rel="alternate" hreflang="vi" href="http://my-site.com/?wovn=vi">' .
                '<script src="//j.wovn.io/1" data-wovnio="key=123456&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=query&amp;langCodeAliases=[]&amp;langParamName=wovn" data-wovnio-info="version=WOVN.php_VERSION" async></script>' .
                '</head>' .
                '<body>' .
                '<a>hello</a>' .
                '</body>' .
                '</html>'
            ),
            array(
                'without head tag',

                '<html><body><a>hello</a></body></html>',

                '<html lang="en">' .
                '<body>' .
                '<link rel="alternate" hreflang="en" href="http://my-site.com/">' .
                '<link rel="alternate" hreflang="vi" href="http://my-site.com/?wovn=vi">' .
                '<script src="//j.wovn.io/1" data-wovnio="key=123456&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=query&amp;langCodeAliases=[]&amp;langParamName=wovn" data-wovnio-info="version=WOVN.php_VERSION" async></script>' .
                '<a>hello</a>' .
                '</body>' .
                '</html>'
            ),
            array(
                'without body tag',

                '<html><a>hello</a></html>',

                '<html lang="en">' .
                '<link rel="alternate" hreflang="en" href="http://my-site.com/">' .
                '<link rel="alternate" hreflang="vi" href="http://my-site.com/?wovn=vi">' .
                '<script src="//j.wovn.io/1" data-wovnio="key=123456&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=query&amp;langCodeAliases=[]&amp;langParamName=wovn" data-wovnio-info="version=WOVN.php_VERSION" async></script>' .
                '<a>hello</a>' .
                '</html>'
            ),
            array(
                'with existing hreflang',

                '<html>' .
                '<body>' .
                '<link rel="alternate" hreflang="en" href="http://my-site.com/?wovn=en" existing-hreflang-supported>' .
                '<link rel="alternate" hreflang="fr" href="http://my-site.com/?wovn=fr" existing-hreflang-not-supported>' .
                '<a>hello</a>' .
                '</body>' .
                '</html>',

                '<html lang="en">' .
                '<body>' .
                '<link rel="alternate" hreflang="en" href="http://my-site.com/">' .
                '<link rel="alternate" hreflang="vi" href="http://my-site.com/?wovn=vi">' .
                '<script src="//j.wovn.io/1" data-wovnio="key=123456&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=query&amp;langCodeAliases=[]&amp;langParamName=wovn" data-wovnio-info="version=WOVN.php_VERSION" async></script>' .
                '<link rel="alternate" hreflang="fr" href="http://my-site.com/?wovn=fr" existing-hreflang-not-supported>' .
                '<a>hello</a>' .
                '</body>' .
                '</html>'
            )
        );
        $settings = array(
            'supported_langs' => array('en', 'vi'),
            'lang_param_name' => 'wovn'
        );
        foreach ($html_cases as $case) {
            list($message, $original_html, $expected_html) = $case;
            list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);
            $converter = new HtmlConverter('UTF-8', $store->settings['project_token'], $store, $headers);
            $translated_html = $converter->insertSnippetAndLangTags($original_html, false);

            $this->assertEquals($expected_html, $translated_html, $message);
        }
    }

    public function testInsertSnippetAndLangTagsRemoveExistingSnippets()
    {
        $original_html = '<html><head>' .
        '<script src="https://example.com"></script>' .
        '<script src="https://wovn.global.ssl.fastly.net/widget/abcdef"></script>' .
        '<script src="https://j.dev-wovn.io:3000"></script>' .
        '<script src="//j.wovn.io/1" data-wovnio="key=NCmbvk&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=path&amp;version=0.0.0" data-wovnio-type="backend_without_api" async></script>' .
        '</head><body></body></html>';

        $expected_html = '<html lang="en"><head>' .
        '<link rel="alternate" hreflang="en" href="http://my-site.com/"><link rel="alternate" hreflang="vi" href="http://my-site.com/?wovn=vi">' .
        '<script src="//j.wovn.io/1" data-wovnio="key=123456&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=query&amp;langCodeAliases=[]&amp;langParamName=wovn" data-wovnio-info="version=WOVN.php_VERSION" async></script>' .
        '<script src="https://example.com"></script>' .
        '</head><body></body></html>';

        $settings = array(
            'supported_langs' => array('en', 'vi'),
            'lang_param_name' => 'wovn'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);
        $converter = new HtmlConverter('UTF-8', $store->settings['project_token'], $store, $headers);
        $translated_html = $converter->insertSnippetAndLangTags($original_html, false);

        $this->assertEquals($expected_html, $translated_html);
    }

    public function testInsertSnippetAndLangTagsWithInsertHreflangsFalse()
    {
        $html_cases = array(
            array(
                'common case',

                '<html><head></head><body><a>hello</a></body></html>',

                '<html lang="en">' .
                '<head>' .
                '<script src="//j.wovn.io/1" data-wovnio="key=123456&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=query&amp;langCodeAliases=[]&amp;langParamName=wovn" data-wovnio-info="version=WOVN.php_VERSION" async></script>' .
                '</head>' .
                '<body>' .
                '<a>hello</a>' .
                '</body>' .
                '</html>'
            ),
            array(
                'with existing hreflang',

                '<html>' .
                '<body>' .
                '<link rel="alternate" hreflang="en" href="http://my-site.com/?wovn=en" existing-hreflang-supported>' .
                '<link rel="alternate" hreflang="fr" href="http://my-site.com/?wovn=fr" existing-hreflang-not-supported>' .
                '<a>hello</a>' .
                '</body>' .
                '</html>',

                '<html lang="en">' .
                '<body>' .
                '<script src="//j.wovn.io/1" data-wovnio="key=123456&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=query&amp;langCodeAliases=[]&amp;langParamName=wovn" data-wovnio-info="version=WOVN.php_VERSION" async></script>' .
                '<link rel="alternate" hreflang="en" href="http://my-site.com/?wovn=en" existing-hreflang-supported>' .
                '<link rel="alternate" hreflang="fr" href="http://my-site.com/?wovn=fr" existing-hreflang-not-supported>' .
                '<a>hello</a>' .
                '</body>' .
                '</html>'
            )
        );
        $settings = array(
            'supported_langs' => array('en', 'vi'),
            'lang_param_name' => 'wovn',
            'insert_hreflangs' => false
        );
        foreach ($html_cases as $case) {
            list($message, $original_html, $expected_html) = $case;
            list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);
            $converter = new HtmlConverter('UTF-8', $store->settings['project_token'], $store, $headers);
            $translated_html = $converter->insertSnippetAndLangTags($original_html, false);

            $this->assertEquals($expected_html, $translated_html, $message);
        }
    }

    public function testInsertCanonicalTagWithInsertCanonicalTagFalse()
    {
        $html_cases = array(
            array(
                'common case',

                '<html><head></head><body><a>hello</a></body></html>',

                '<html lang="en">' .
                '<head>' .
                '<script src="//j.wovn.io/1" data-wovnio="key=123456&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=query&amp;langCodeAliases=[]&amp;langParamName=wovn" data-wovnio-info="version=WOVN.php_VERSION" async></script>' .
                '</head>' .
                '<body>' .
                '<a>hello</a>' .
                '</body>' .
                '</html>'
            ),
            array(
                'with existing canonical tag',

                '<html>' .
                '<body>' .
                '<link rel="canonical" href="http://my-site.com/" existing-canonical-supported>' .
                '<link rel="alternate" hreflang="en" href="http://my-site.com/?wovn=en" existing-hreflang-supported>' .
                '<link rel="alternate" hreflang="fr" href="http://my-site.com/?wovn=fr" existing-hreflang-not-supported>' .
                '<a>hello</a>' .
                '</body>' .
                '</html>',

                '<html lang="en">' .
                '<body>' .
                '<script src="//j.wovn.io/1" data-wovnio="key=123456&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=query&amp;langCodeAliases=[]&amp;langParamName=wovn" data-wovnio-info="version=WOVN.php_VERSION" async></script>' .
                '<link rel="canonical" href="http://my-site.com/" existing-canonical-supported>' .
                '<link rel="alternate" hreflang="en" href="http://my-site.com/?wovn=en" existing-hreflang-supported>' .
                '<link rel="alternate" hreflang="fr" href="http://my-site.com/?wovn=fr" existing-hreflang-not-supported>' .
                '<a>hello</a>' .
                '</body>' .
                '</html>'
            )
        );
        $settings = array(
            'supported_langs' => array('en', 'vi'),
            'lang_param_name' => 'wovn',
            'insert_hreflangs' => false,
            'translate_canonical_tag' => false
        );
        foreach ($html_cases as $case) {
            list($message, $original_html, $expected_html) = $case;
            list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);
            $converter = new HtmlConverter('UTF-8', $store->settings['project_token'], $store, $headers);
            $translated_html = $converter->insertSnippetAndLangTags($original_html, false);

            $this->assertEquals($expected_html, $translated_html, $message);
        }
    }

    public function testInsertCanonicalTagTranslateExistingTag()
    {
        $html_cases = array(
            array(
                'common case - should keep existing canonical tag',

                '<html><head><link rel="canonical" href="http://my-site.com/news/"></head><body><a>hello</a></body></html>',

                '<html lang="en">' .
                '<head>' .
                '<link rel="alternate" hreflang="en" href="http://my-site.com/news/"><link rel="alternate" hreflang="vi" href="http://my-site.com/vi/news/">' .
                '<script src="//j.wovn.io/1" data-wovnio="key=123456&amp;backend=true&amp;currentLang=vi&amp;defaultLang=en&amp;urlPattern=path&amp;langCodeAliases=[]&amp;langParamName=wovn" data-wovnio-info="version=WOVN.php_VERSION" async></script>' .
                '<link rel="canonical" href="http://my-site.com/vi/news/">' .
                '</head>' .
                '<body>' .
                '<a>hello</a>' .
                '</body>' .
                '</html>'
            )
        );
        $settings = array(
            'supported_langs' => array('en', 'vi'),
            'url_pattern_name' => 'path',
            'translate_canonical_tag' => true
        );
        $envs = array(
            'REQUEST_URI' => '/vi/news/'
        );
        foreach ($html_cases as $case) {
            list($message, $original_html, $expected_html) = $case;
            list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $envs);
            $converter = new HtmlConverter('UTF-8', $store->settings['project_token'], $store, $headers);
            $translated_html = $converter->insertSnippetAndLangTags($original_html, false);

            $this->assertEquals($expected_html, $translated_html, $message);
        }
    }

    public function testInsertCanonicalTagTranslateExistingTagIsDefaultLang()
    {
        $html_cases = array(
            array(
                'common case - should keep existing canonical tag',

                '<html><head><link rel="canonical" href="http://my-site.com/news/"></head><body><a>hello</a></body></html>',

                '<html lang="en">' .
                '<head>' .
                '<link rel="alternate" hreflang="en" href="http://my-site.com/news/"><link rel="alternate" hreflang="vi" href="http://my-site.com/vi/news/">' .
                '<script src="//j.wovn.io/1" data-wovnio="key=123456&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=path&amp;langCodeAliases=[]&amp;langParamName=wovn" data-wovnio-info="version=WOVN.php_VERSION" async></script>' .
                '<link rel="canonical" href="http://my-site.com/news/">' .
                '</head>' .
                '<body>' .
                '<a>hello</a>' .
                '</body>' .
                '</html>'
            )
        );
        $settings = array(
            'supported_langs' => array('en', 'vi'),
            'url_pattern_name' => 'path',
            'translate_canonical_tag' => true
        );
        $envs = array(
            'REQUEST_URI' => '/news/'
        );
        foreach ($html_cases as $case) {
            list($message, $original_html, $expected_html) = $case;
            list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $envs);
            $converter = new HtmlConverter('UTF-8', $store->settings['project_token'], $store, $headers);
            $translated_html = $converter->insertSnippetAndLangTags($original_html, false);

            $this->assertEquals($expected_html, $translated_html, $message);
        }
    }

    public function testInsertSnippetAndLangTagsWithCustomAlias()
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
        $converter = new HtmlConverter('UTF-8', $store->settings['project_token'], $store, $headers);
        $translated_html = $converter->insertSnippetAndLangTags($html, false);

        $expected_html = "<html lang=\"en\"><body><link rel=\"alternate\" hreflang=\"fr\" href=\"http://my-site.com/fr/\"><script src=\"//j.wovn.io/1\" data-wovnio=\"key=123456&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=path&amp;langCodeAliases={&quot;en&quot;:&quot;custom_en&quot;}&amp;langParamName=wovn\" data-wovnio-info=\"version=WOVN.php_VERSION\" async></script><a>hello</a></body></html>";
        $this->assertEquals($expected_html, $translated_html);
    }

    public function testInsertSnippetAndLangTagsWithCustomDomainLangs()
    {
        $html = '<html><body><a>hello</a></body></html>';
        $settings = array(
            'supported_langs' => array('fr'),
            'default_lang' => 'en',
            'custom_domain_langs' => array('en' => array('url' => 'testsite.com'), 'fr' => array('url' => 'testsite.com/fr')),
            'url_pattern_name' => 'custom_domain'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, array('HTTP_HOST' => 'testsite.com'));
        $converter = new HtmlConverter('UTF-8', $store->settings['project_token'], $store, $headers);
        $translated_html = $converter->insertSnippetAndLangTags($html, false);

        $expected_html = '<html lang="en">'.
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

    public function testInsertSnippetAndLangTagsWithFallback()
    {
        $html = '<html><body><a>hello</a></body></html>';
        $settings = array(
            'supported_langs' => array('en', 'vi'),
            'lang_param_name' => 'wovn'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);
        $converter = new HtmlConverter('UTF-8', $store->settings['project_token'], $store, $headers);
        $translated_html = $converter->insertSnippetAndLangTags($html, true);

        $expected_html = '<html lang="en">'.
        '<body>'.
        '<link rel="alternate" hreflang="en" href="http://my-site.com/">'.
        '<link rel="alternate" hreflang="vi" href="http://my-site.com/?wovn=vi">'.
        '<script src="//j.wovn.io/1"'.
        ' data-wovnio="key=123456&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=query&amp;langCodeAliases=[]&amp;langParamName=wovn"'.
        ' data-wovnio-info="version=WOVN.php_VERSION"'.
        ' data-wovnio-type="fallback_snippet"'.
        ' async></script>'.
        '<a>hello</a>'.
        '</body>'.
        '</html>';
        $this->assertEquals($expected_html, $translated_html);
    }

    public function testInsertHtmlLangAttribute()
    {
        $settings = array(
            'supported_langs' => array('en', 'vi'),
            'lang_param_name' => 'wovn',
            'insert_hreflangs' => false
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);
        $converter = new HtmlConverter('UTF-8', $store->settings['project_token'], $store, $headers);

        $this->assertEquals(strpos($converter->insertSnippetAndLangTags('<html><head></head><body><a>hello</a></body></html>', 'en'), '<html lang="en"') !== false, true, 'general case - insert lang attribute');
        $this->assertEquals(strpos($converter->insertSnippetAndLangTags('<html test="lang"><head></head><body><a>hello</a></body></html>', 'en'), '<html lang="en"') !== false, true, 'html with other attribute - insert lang attribute');
        $this->assertEquals(strpos($converter->insertSnippetAndLangTags('<html lang="ja"><head></head><body><a>hello</a></body></html>', 'en'), '<html lang="ja"') !== false, true, 'lang attribute exists - keep existing lang');
        $this->assertEquals(strpos($converter->insertSnippetAndLangTags("<html lang='ja'><head></head><body><a>hello</a></body></html>", 'en'), "<html lang='ja'") !== false, true, 'lang attribute exists with single quotes - keep existing lang');
        $this->assertEquals(strpos($converter->insertSnippetAndLangTags("<html lang=ja><head></head><body><a>hello</a></body></html>", 'en'), "<html lang=ja") !== false, true, 'lang attribute exists without quotes - keep existing lang');
        $this->assertEquals(strpos($converter->insertSnippetAndLangTags('<html lang="zh-CHS"><head></head><body><a>hello</a></body></html>', 'en'), '<html lang="zh-CHS"') !== false, true, 'lang code has dash - keep existing lang');
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
        $converter = new HtmlConverter('UTF-8', $store->settings['project_token'], $store, $headers);
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
        $converter = new HtmlConverter('UTF-8', $store->settings['project_token'], $store, $headers);
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
        $converter = new HtmlConverter('UTF-8', $store->settings['project_token'], $store, $headers);
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
        $converter = new HtmlConverter('UTF-8', $store->settings['project_token'], $store, $headers);
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
        $converter = new HtmlConverter('UTF-8', $store->settings['project_token'], $store, $headers);
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
        $converter = new HtmlConverter('UTF-8', $store->settings['project_token'], $store, $headers);
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
        $converter = new HtmlConverter('UTF-8', $store->settings['project_token'], $store, $headers);
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
        $converter = new HtmlConverter('UTF-8', $store->settings['project_token'], $store, $headers);
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
        $converter = new HtmlConverter('UTF-8', $store->settings['project_token'], $store, $headers);
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
        $converter = new HtmlConverter('UTF-8', $store->settings['project_token'], $store, $headers);
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
        $converter = new HtmlConverter('UTF-8', $store->settings['project_token'], $store, $headers);
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
        $this->assertEquals('en', $headers->requestLang());
        $this->assertEquals('custom_en.my-site.com', $headers->host);
        $converter = new HtmlConverter('UTF-8', $store->settings['project_token'], $store, $headers);
        $expected_href = 'http://ja.my-site.com/pages.html';
        $generated_href = TestUtils::invokeMethod($converter, 'buildHrefLang', array('ja'));
        $this->assertEquals($expected_href, $generated_href);
    }

    public function testConvertToAppropriateBodyForApi()
    {
        $html_cases = array(
            array(
                'common case',

                '<html><head></head><body><a>hello</a></body></html>',

                '<html><head></head><body><a>hello</a></body></html>',

                '<html><head></head><body><a>hello</a></body></html>'
            ),
            array(
                'with wovn-ignore to html',

                '<html wovn-ignore><body><a>hello</a></body></html>',

                '<html wovn-ignore><!-- __wovn-backend-ignored-key-0 --></html>',

                '<html wovn-ignore><body><a>hello</a></body></html>'
            ),
            array(
                'with wovn-ignore to elements',

                '<html><body>'.
                '<a wovn-ignore>hello 1</a>'.
                '<div wovn-ignore><span>hello 2</span></div>'.
                '<a data-wovn-ignore>hello 3</a>'.
                '<div data-wovn-ignore><span>hello 4</span></div>'.
                '</body></html>',

                '<html><body>'.
                '<a wovn-ignore><!-- __wovn-backend-ignored-key-0 --></a>'.
                '<div wovn-ignore><!-- __wovn-backend-ignored-key-1 --></div>'.
                '<a data-wovn-ignore><!-- __wovn-backend-ignored-key-2 --></a>'.
                '<div data-wovn-ignore><!-- __wovn-backend-ignored-key-3 --></div>'.
                '</body></html>',

                '<html><body>'.
                '<a wovn-ignore>hello 1</a>'.
                '<div wovn-ignore><span>hello 2</span></div>'.
                '<a data-wovn-ignore>hello 3</a>'.
                '<div data-wovn-ignore><span>hello 4</span></div>'.
                '</body></html>',
            ),
            array(
                'with ignore_class',

                '<html><body>'.
                '<span class="ignore-class">hello 1</span>'.
                '</body></html>',

                '<html><body>'.
                '<span class="ignore-class"><!-- __wovn-backend-ignored-key-0 --></span>'.
                '</body></html>',

                '<html><body>'.
                '<span class="ignore-class">hello 1</span>'.
                '</body></html>',
            ),
            array(
                'with form',

                '<html><body>'.
                '<form><input type="text" name="name" >hello 1</form>'.
                '<input type="hidden" value="hello 2">'.
                '</body></html>',

                '<html><body>'.
                '<form><!-- __wovn-backend-ignored-key-0 --></form>'.
                '<input type="hidden" value="__wovn-backend-ignored-key-1">'.
                '</body></html>',

                '<html><body>'.
                '<form><input type="text" name="name" >hello 1</form>'.
                '<input type="hidden" value="hello 2">'.
                '</body></html>',
            ),
            array(
                'with script',

                '<html><head>'.
                '<script type="text/javascript">document.write("this is test.");</script>'.
                '<script type="application/ld+json">{test: "test"}</script>'.
                '</head><body></body></html>',

                '<html><head>'.
                '<script type="text/javascript"><!-- __wovn-backend-ignored-key-0 --></script>'.
                '<script type="application/ld+json">{test: "test"}</script>'.
                '</head><body></body></html>',

                '<html><head>'.
                '<script type="text/javascript">document.write("this is test.");</script>'.
                '<script type="application/ld+json">{test: "test"}</script>'.
                '</head><body></body></html>',
            ),
            array(
                '<input> with no value',

                '<html><head></head><body><input type="hidden"></body></html>',

                '<html><head></head><body><input type="hidden"></body></html>',

                '<html><head></head><body><input type="hidden"></body></html>',
            ),
        );
        $settings = array(
            'supported_langs' => array('en', 'vi'),
            'lang_param_name' => 'wovn',
            'ignore_class' => array('ignore-class')
        );
        foreach ($html_cases as $case) {
            list($message, $original_html, $expected_converted_html, $expected_reverted_html) = $case;
            list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);
            $converter = new HtmlConverter('UTF-8', $store->settings['project_token'], $store, $headers);
            $converted_html = $converter->convertToAppropriateBodyForApi($original_html, false);

            $this->assertEquals($expected_converted_html, $converted_html, $message);
            $this->assertEquals($expected_reverted_html, $converter->revertMarkers($converted_html), $message);
        }
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
        $converter = new HtmlConverter('UTF-8', $store->settings['project_token'], $store, $headers);
        $converted_html = $converter->convertToAppropriateBodyForApi($html);

        $expected_html = '<html><body><p>' . $long_string . '</p></body></html>';
        $this->assertEquals($expected_html, $converted_html);
    }

    public function testConvertToAppropriateBodyForApiDoesNotFailForContentOverDefaultSimpleHtmlDomMaxSize()
    {
        $html = '';
        $settings = array(
            'supported_langs' => array('en', 'vi'),
            'lang_param_name' => 'wovn'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);
        $converter = new HtmlConverter('UTF-8', $store->settings['project_token'], $store, $headers);
        $converted_html = $converter->convertToAppropriateBodyForApi($html);

        $expected_html = "";
        $this->assertEquals($expected_html, $converted_html);
    }

    public function testConvertToAppropriateBodyForApiWithoutEncoding()
    {
        $html = mb_convert_encoding('<html>こんにちは</html>', 'SJIS');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default');
        $converter = new HtmlConverter(null, $store->settings['project_token'], $store, $headers);
        $converted_html = $converter->convertToAppropriateBodyForApi($html);

        $expected_html = "<html>こんにちは</html>";
        $expected_html = mb_convert_encoding($expected_html, 'SJIS');

        $this->assertEquals($expected_html, $converted_html);
    }

    public function testConvertToAppropriateBodyForApiWithSupportedEncoding()
    {
        foreach (HtmlConverter::$supportedEncodings as $encoding) {
            $html = mb_convert_encoding('<html>こんにちは</html>', $encoding);
            list($store, $headers) = StoreAndHeadersFactory::fromFixture('default');
            $converter = new HtmlConverter($encoding, $store->settings['project_token'], $store, $headers);
            $converted_html = $converter->convertToAppropriateBodyForApi($html);

            $expected_html = "<html>こんにちは</html>";
            $expected_html = mb_convert_encoding($expected_html, $encoding);
            $this->assertEquals($expected_html, $converted_html);
        }
    }

    public function testConvertToAppropriateBodyForApiWithCustomIgnoreClass()
    {
        $html = "<html><body><a class=\"random    \n\f\rignore\tvalid custom\">hello</a></body></html>";
        $settings = array(
            'ignore_class' => array('ignore'),
            'lang_param_name' => 'wovn'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);
        $converter = new HtmlConverter(null, $store->settings['project_token'], $store, $headers);
        list($translated_html, $marker) = $this->executeConvert($converter, $html, 'UTF-8', '_removeCustomIgnoreClass');
        $keys = $marker->keys();

        $this->assertEquals(1, count($keys));
        $this->assertEquals("<html><body><a class=\"random    \n\f\rignore\tvalid custom\">$keys[0]</a></body></html>", $translated_html);
    }

    public function testConvertToAppropriateBodyForApiWithMultipleWovnIgnore()
    {
        $html = '<html><body><a wovn-ignore>hello</a>ignore<div wovn-ignore>world</div></body></html>';
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default');
        $converter = new HtmlConverter('UTF-8', $store->settings['project_token'], $store, $headers);
        list($translated_html, $marker) = $this->executeConvert($converter, $html, 'UTF-8', '_removeWovnIgnore');
        $keys = $marker->keys();

        $this->assertEquals(2, count($keys));
        $this->assertEquals("<html><body><a wovn-ignore>$keys[0]</a>ignore<div wovn-ignore>$keys[1]</div></body></html>", $translated_html);
    }

    public function testConvertToAppropriateBodyForApiWithForm()
    {
        $html = '<html><body><form>hello<input type="button" value="click"></form>world</body></html>';
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default');
        $converter = new HtmlConverter('UTF-8', $store->settings['project_token'], $store, $headers);
        list($translated_html, $marker) = $this->executeConvert($converter, $html, 'UTF-8', '_removeForm');
        $keys = $marker->keys();

        $this->assertEquals(1, count($keys));
        $this->assertEquals("<html><body><form>$keys[0]</form>world</body></html>", $translated_html);
    }

    public function testConvertToAppropriateBodyForApiWithMultipleForm()
    {
        $html = '<html><body><form>hello<input type="button" value="click"></form>world<form>hello2<input type="button" value="click2"></form></body></html>';
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default');
        $converter = new HtmlConverter('UTF-8', $store->settings['project_token'], $store, $headers);
        list($translated_html, $marker) = $this->executeConvert($converter, $html, 'UTF-8', '_removeForm');
        $keys = $marker->keys();

        $this->assertEquals(2, count($keys));
        $this->assertEquals("<html><body><form>$keys[0]</form>world<form>$keys[1]</form></body></html>", $translated_html);
    }

    public function testConvertToAppropriateBodyForApiWithFormAndWovnIgnore()
    {
        $html = '<html><body><form wovn-ignore>hello<input type="button" value="click"></form>world</body></html>';
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default');
        $converter = new HtmlConverter('UTF-8', $store->settings['project_token'], $store, $headers);
        list($translated_html, $marker) = $this->executeConvert($converter, $html, 'UTF-8', '_removeForm');
        $keys = $marker->keys();

        $this->assertEquals(1, count($keys));
        $this->assertEquals("<html><body><form wovn-ignore>$keys[0]</form>world</body></html>", $translated_html);
    }

    public function testConvertToAppropriateBodyForApiWithHiddenInput()
    {
        $html = '<html><body><input type="hidden" value="aaaaa">world</body></html>';
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default');
        $converter = new HtmlConverter('UTF-8', $store->settings['project_token'], $store, $headers);
        list($translated_html, $marker) = $this->executeConvert($converter, $html, 'UTF-8', '_removeForm');
        $keys = $marker->keys();

        $this->assertEquals(1, count($keys));
        $this->assertEquals("<html><body><input type=\"hidden\" value=\"$keys[0]\">world</body></html>", $translated_html);
    }

    public function testConvertToAppropriateBodyForApiWithHiddenInputMultipleTimes()
    {
        $html = '<html><body><input type="hidden" value="aaaaa">world<input type="hidden" value="aaaaa"></body></html>';
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default');
        $converter = new HtmlConverter('UTF-8', $store->settings['project_token'], $store, $headers);
        list($translated_html, $marker) = $this->executeConvert($converter, $html, 'UTF-8', '_removeForm');
        $keys = $marker->keys();

        $this->assertEquals(2, count($keys));
        $this->assertEquals("<html><body><input type=\"hidden\" value=\"$keys[0]\">world<input type=\"hidden\" value=\"$keys[1]\"></body></html>", $translated_html);
    }

    public function testConvertToAppropriateBodyForApiWithScript()
    {
        $html = '<html><body><script>console.log("hello")</script>world</body></html>';
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default');
        $converter = new HtmlConverter('UTF-8', $store->settings['project_token'], $store, $headers);
        list($translated_html, $marker) = $this->executeConvert($converter, $html, 'UTF-8', '_removeScript');
        $keys = $marker->keys();

        $this->assertEquals(1, count($keys));
        $this->assertEquals("<html><body><script>$keys[0]</script>world</body></html>", $translated_html);
    }

    public function testConvertToAppropriateBodyForApiWithMultipleScript()
    {
        $html = '<html><head><script>console.log("hello")</script></head><body>world<script>console.log("hello2")</script></body></html>';
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default');
        $converter = new HtmlConverter('UTF-8', $store->settings['project_token'], $store, $headers);
        list($translated_html, $marker) = $this->executeConvert($converter, $html, 'UTF-8', '_removeScript');
        $keys = $marker->keys();

        $this->assertEquals(2, count($keys));
        $this->assertEquals("<html><head><script>$keys[0]</script></head><body>world<script>$keys[1]</script></body></html>", $translated_html);
    }

    public function testConvertToAppropriateBodyForApiWithComment()
    {
        $html = '<html><body>hello<!-- backend-wovn-ignore    -->ignored <!--/backend-wovn-ignore-->  world</body></html>';
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default');
        $converter = new HtmlConverter('UTF-8', $store->settings['project_token'], $store, $headers);
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
        $converter = new HtmlConverter('UTF-8', $store->settings['project_token'], $store, $headers);
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
        $converter = new HtmlConverter('UTF-8', $store->settings['project_token'], $store, $headers);
        $translated_html = $converter->insertSnippetAndLangTags($html, false);

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
        $converter = new HtmlConverter('UTF-8', $store->settings['project_token'], $store, $headers);
        $translated_html = $converter->insertSnippetAndLangTags($html, false);

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
        $converter = new HtmlConverter('UTF-8', $store->settings['project_token'], $store, $headers);
        $translated_html = $converter->insertSnippetAndLangTags($html, false);

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
        $converter = new HtmlConverter('UTF-8', $store->settings['project_token'], $store, $headers);
        $translated_html = $converter->insertSnippetAndLangTags($html, false);

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
        $converter = new HtmlConverter('UTF-8', $store->settings['project_token'], $store, $headers);
        $translated_html = $converter->insertSnippetAndLangTags($html, false);

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
        $converter = new HtmlConverter('UTF-8', $store->settings['project_token'], $store, $headers);
        $translated_html = $converter->insertSnippetAndLangTags($html, false);

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
        $converter = new HtmlConverter('UTF-8', $store->settings['project_token'], $store, $headers);
        $translated_html = $converter->insertSnippetAndLangTags($html, false);

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
        $converter = new HtmlConverter('UTF-8', $store->settings['project_token'], $store, $headers);
        $translated_html = $converter->insertSnippetAndLangTags($html, false);

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
        $converter = new HtmlConverter('UTF-8', $store->settings['project_token'], $store, $headers);
        $translated_html = $converter->insertSnippetAndLangTags($html, false);

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
        $converter = new HtmlConverter('UTF-8', $store->settings['project_token'], $store, $headers);
        $translated_html = $converter->insertSnippetAndLangTags($html, false);

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
        $converter = new HtmlConverter('UTF-8', $store->settings['project_token'], $store, $headers);
        $translated_html = $converter->insertSnippetAndLangTags($html, false);

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
        $converter = new HtmlConverter('UTF-8', $store->settings['project_token'], $store, $headers);
        $translated_html = $converter->insertSnippetAndLangTags($html, false);

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
        $converter = new HtmlConverter('UTF-8', $store->settings['project_token'], $store, $headers);
        $translated_html = $converter->insertSnippetAndLangTags($html, false);

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
        $converter = new HtmlConverter('UTF-8', $store->settings['project_token'], $store, $headers);
        $translated_html = $converter->insertSnippetAndLangTags($html, false);

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
        $converter = new HtmlConverter('UTF-8', $store->settings['project_token'], $store, $headers);
        $translated_html = $converter->insertSnippetAndLangTags($html, false);

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
        $converter = new HtmlConverter('UTF-8', $store->settings['project_token'], $store, $headers);
        $translated_html = $converter->insertSnippetAndLangTags($html, false);

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
        $converter = new HtmlConverter('UTF-8', $store->settings['project_token'], $store, $headers);
        $translated_html = $converter->insertSnippetAndLangTags($html, false);

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
        $converter = new HtmlConverter('UTF-8', $store->settings['project_token'], $store, $headers);
        $translated_html = $converter->insertSnippetAndLangTags($html, false);

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

    private function convertEncordingAndCorrectHtml($html_text, $encoding = 'utf-8')
    {
        $doc = new \DOMDocument("1.0", "ISO-8859-15");
        $doc->loadHTML(mb_convert_encoding($html_text, 'HTML-ENTITIES', $encoding));
        return $doc->saveHTML();
    }
}
