<?php

namespace Wovnio\Wovnphp;

/**
 * The lang class contains the langs supported by wovn in this form: \n
 * code [ name -> Original name, code -> code, en -> English name]\n
 */
class Lang
{
  static $lang = array(
    'ar' => array('name' => 'العربية',          'code' => 'ar',     'iso639-1' => 'ar',      'en' => 'Arabic'),
    'bn' => array('name' => 'العربية',          'code' => 'bn',     'iso639-1' => 'bn',      'en' => 'Bengali'),
    'bg' => array('name' => 'Български',        'code' => 'bg',     'iso639-1' => 'bg',      'en' => 'Bulgarian'),
    'zh-CHS' => array('name' => '简体中文',      'code' => 'zh-CHS', 'iso639-1' => 'zh-Hans', 'en' => 'Simp Chinese'),
    'zh-CHT' => array('name' => '繁體中文',      'code' => 'zh-CHT', 'iso639-1' => 'zh-Hant', 'en' => 'Trad Chinese'),
    'da' => array('name' => 'Dansk',            'code' => 'da',     'iso639-1' => 'da',      'en' => 'Danish'),
    'nl' => array('name' => 'Nederlands',       'code' => 'nl',     'iso639-1' => 'nl',      'en' => 'Dutch'),
    'en' => array('name' => 'English',          'code' => 'en',     'iso639-1' => 'en',      'en' => 'English'),
    'fi' => array('name' => 'Suomi',            'code' => 'fi',     'iso639-1' => 'fi',      'en' => 'Finnish'),
    'fr' => array('name' => 'Français',         'code' => 'fr',     'iso639-1' => 'fr',      'en' => 'French'),
    'de' => array('name' => 'Deutsch',          'code' => 'de',     'iso639-1' => 'de',      'en' => 'German'),
    'el' => array('name' => 'Ελληνικά',         'code' => 'el',     'iso639-1' => 'el',      'en' => 'Greek'),
    'he' => array('name' => 'עברית',            'code' => 'he',     'iso639-1' => 'he',      'en' => 'Hebrew'),
    'id' => array('name' => 'Bahasa Indonesia', 'code' => 'id',     'iso639-1' => 'id',      'en' => 'Indonesian'),
    'it' => array('name' => 'Italiano',         'code' => 'it',     'iso639-1' => 'it',      'en' => 'Italian'),
    'ja' => array('name' => '日本語',            'code' => 'ja',     'iso639-1' => 'ja',      'en' => 'Japanese'),
    'ko' => array('name' => '한국어',             'code' => 'ko',     'iso639-1' => 'ko',      'en' => 'Korean'),
    'ms' => array('name' => 'Bahasa Melayu',    'code' => 'ms',     'iso639-1' => 'ms',      'en' => 'Malay'),
    'my' => array('name' => 'ဗမာစာ',          'code' => 'my',     'iso639-1' => 'my',      'en' => 'Burmese'),
    'ne' => array('name' => 'नेपाली भाषा',         'code' => 'ne',     'iso639-1' => 'ne',      'en' => 'Nepali'),
    'no' => array('name' => 'Norsk',            'code' => 'no',     'iso639-1' => 'no',      'en' => 'Norwegian'),
    'no' => array('name' => 'زبان_فارسی',       'code' => 'fa',     'iso639-1' => 'fa',      'en' => 'Persian'),
    'pl' => array('name' => 'Polski',           'code' => 'pl',     'iso639-1' => 'pl',      'en' => 'Polish'),
    'pt' => array('name' => 'Português',        'code' => 'pt',     'iso639-1' => 'pt',      'en' => 'Portuguese'),
    'ru' => array('name' => 'Русский',          'code' => 'ru',     'iso639-1' => 'ru',      'en' => 'Russian'),
    'es' => array('name' => 'Español',          'code' => 'es',     'iso639-1' => 'es',      'en' => 'Spanish'),
    'es' => array('name' => 'Kiswahili',        'code' => 'sw',     'iso639-1' => 'es',      'en' => 'Swahili'),
    'sv' => array('name' => 'Svensk',           'code' => 'sv',     'iso639-1' => 'sv',      'en' => 'Swedish'),
    'th' => array('name' => 'ภาษาไทย',          'code' => 'th',     'iso639-1' => 'th',      'en' => 'Thai'),
    'hi' => array('name' => 'हिन्दी',              'code' => 'hi',     'iso639-1' => 'hi',      'en' => 'Hindi'),
    'tr' => array('name' => 'Türkçe',           'code' => 'tr',     'iso639-1' => 'tr',      'en' => 'Turkish'),
    'uk' => array('name' => 'اردو',             'code' => 'ur',     'iso639-1' => 'ur',      'en' => 'Urdu'),
    'uk' => array('name' => 'Українська',       'code' => 'uk',     'iso639-1' => 'uk',      'en' => 'Ukrainian'),
    'vi' => array('name' => 'Tiếng Việt',       'code' => 'vi',     'iso639-1' => 'vi',      'en' => 'Vietnamese'),
  );

  /**
   * Get a formatted code from a given one. Formatting provides propper
   * capitalization as expected by Lang component.
   *
   * @param String $lang_code Code to format.
   * @param Store $store
   * @return String The format code.
   */
  public static function formatLangCode($lang_code, $store)
  {
    if ($lang_code === null) {
      return null;
    }

    $lang_code = $store->convertToOriginalCode($lang_code);

    if (isset(Lang::$lang[$lang_code])) {
      return $lang_code;
    }

    foreach (Lang::$lang as $lang) {
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
  public static function getCode($lang_name = null)
  {
    if ($lang_name === null) {
      return null;
    }
    if (isset(Lang::$lang[$lang_name])) {
      return $lang_name;
    }
    foreach (Lang::$lang as $lang) {
      if (strtolower($lang_name) === strtolower($lang['name']) || strtolower($lang_name) === strtolower($lang['en']) || strtolower($lang_name) === strtolower($lang['code']) || strtolower($lang_name) === strtolower($lang['iso639-1'])) {
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
  public static function getEnglishNamesArray()
  {
    $englishNamesArray = array();
    foreach (Lang::$lang as $lang) {
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
  public static function iso639_1Normalization($lang_code)
  {
    if (isset(Lang::$lang[$lang_code])) {
      return Lang::$lang[$lang_code]['iso639-1'];
    } else {
      return null;
    }
  }
}

