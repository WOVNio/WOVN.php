<?php
namespace Wovnio\Wovnphp;

/**
 * The lang class contains the langs supported by wovn in this form: \n
 * code [ name -> Original name, code -> code, en -> English name]\n
 */
class Lang {
  static $lang = array(
    'ar' => array('name' => 'العربية',           'code' => 'ar',     'en' => 'Arabic'),
    'bg' => array('name' => 'Български',         'code' => 'bg',     'en' => 'Bulgarian'),
    'zh-CHS' => array('name' => '简体中文',      'code' => 'zh-CHS', 'en' => 'Simp Chinese'),
    'zh-CHT' => array('name' => '繁體中文',      'code' => 'zh-CHT', 'en' => 'Trad Chinese'),
    'da' => array('name' => 'Dansk',             'code' => 'da',     'en' => 'Danish'),
    'nl' => array('name' => 'Nederlands',        'code' => 'nl',     'en' => 'Dutch'),
    'en' => array('name' => 'English',           'code' => 'en',     'en' => 'English'),
    'fi' => array('name' => 'Suomi',             'code' => 'fi',     'en' => 'Finnish'),
    'fr' => array('name' => 'Français',          'code' => 'fr',     'en' => 'French'),
    'de' => array('name' => 'Deutsch',           'code' => 'de',     'en' => 'German'),
    'el' => array('name' => 'Ελληνικά',          'code' => 'el',     'en' => 'Greek'),
    'he' => array('name' => 'עברית',             'code' => 'he',     'en' => 'Hebrew'),
    'id' => array('name' => 'Bahasa Indonesia',  'code' => 'id',     'en' => 'Indonesian'),
    'it' => array('name' => 'Italiano',          'code' => 'it',     'en' => 'Italian'),
    'ja' => array('name' => '日本語',            'code' => 'ja',     'en' => 'Japanese'),
    'ko' => array('name' => '한국어',            'code' => 'ko',     'en' => 'Korean'),
    'ms' => array('name' => 'Bahasa Melayu',     'code' => 'ms',     'en' => 'Malay'),
    'my' => array('name' => 'ဗမာစာ',              'code' => 'my',     'en' => 'Burmese'),
    'ne' => array('name' => 'नेपाली भाषा',             'code' => 'ne',     'en' => 'Nepali'),
    'no' => array('name' => 'Norsk',             'code' => 'no',     'en' => 'Norwegian'),
    'pl' => array('name' => 'Polski',            'code' => 'pl',     'en' => 'Polish'),
    'pt' => array('name' => 'Português',         'code' => 'pt',     'en' => 'Portuguese'),
    'ru' => array('name' => 'Русский',           'code' => 'ru',     'en' => 'Russian'),
    'es' => array('name' => 'Español',           'code' => 'es',     'en' => 'Spanish'),
    'sv' => array('name' => 'Svensk',            'code' => 'sv',     'en' => 'Swedish'),
    'th' => array('name' => 'ภาษาไทย',           'code' => 'th',     'en' => 'Thai'),
    'hi' => array('name' => 'हिन्दी',               'code' => 'hi',     'en' => 'Hindi'),
    'tr' => array('name' => 'Türkçe',            'code' => 'tr',     'en' => 'Turkish'),
    'uk' => array('name' => 'Українська',        'code' => 'uk',     'en' => 'Ukrainian'),
    'vi' => array('name' => 'Tiếng Việt',        'code' => 'vi',     'en' => 'Vietnamese'),
    );

    /**
     * Get a formatted code from a given one. Formatting provides propper
     * capitalization as expected by Lang component.
     *
     * @param String $lang_code Code to format.
     * @return String The format code.
     */
    public static function formatLangCode($lang_code=null) {
      if ($lang_code === null) {
        return null;
      }

      if (isset(Lang::$lang[$lang_code])) {
        return $lang_code;
      }

      foreach(Lang::$lang as $lang) {
        if (strtolower($lang_code) === strtolower($lang['code'])) {
          return $lang['code'];
        }
      }

      return null;
    }

    /**
     * Get the code of the lang given either a code, an original name or an English name\n
     *
     * @param String $lang_name Code, Original Name or English name of the lang
     * @return String The code of the lang
     */
    public static function getCode($lang_name=null) {
      if ($lang_name === null) {
        return null;
      }
      if (isset(Lang::$lang[$lang_name])) {
        return $lang_name;
      }
      foreach(Lang::$lang as $lang) {
        if (strtolower($lang_name) === strtolower($lang['name']) || strtolower($lang_name) === strtolower($lang['en']) || strtolower($lang_name) === strtolower($lang['code'])) {
          return $lang['code'];
        }
      }
      return null;
    }

    /**
     * Get the English names supported by WOVN++\n
     *
     * @return array The English names
     */
    public static function getEnglishNamesArray() {
      $englishNamesArray = array();
      foreach(Lang::$lang as $lang) {
         array_push($englishNamesArray, $lang['en']);
      }
      return $englishNamesArray;
    }

    /**
     * Provides the ISO639-1 code for a given lang code.
     * Source: https://support.google.com/webmasters/answer/189077?hl=en
     *
     * @param String $lang_code Code of the language.
     *
     * @return String The ISO639-1 code of the language.
     */
    public static function iso639_1Normalization($lang_code) {
      return str_replace('zh-CHT', 'zh-Hant', str_replace('zh-CHS', 'zh-Hans', $lang_code));
    }
}

