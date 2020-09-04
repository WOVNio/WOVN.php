<?php


namespace Wovnio\Wovnphp\Core;

class WovnLang
{
    private $name;
    private $code;
    private $englishName;
    private $alias;
    private $active;

    /**
     * WovnLang constructor.
     * @param $code string The language code, IETF language tag format.
     * @param $name string The name of the language, in its local language.
     * @param $englishName string The name of the language, in English.
     * @param mixed $alias The alias of this language, defaults to null.
     * @param bool $active If the language is a valid choice in the current project.
     */
    public function __construct($code, $name, $englishName, $alias = null, $active = false)
    {
        $this->name = $name;
        $this->code = $code;
        $this->englishName = $englishName;
        $this->alias = $alias;
        $this->active = $active;
    }

    /**
     * Returns the name of the language, in its local language.
     *
     * @return string The name of the language.
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * Returns the wovn language code of the language.
     *
     * @return string The language code.
     */
    public function code()
    {
        return $this->code;
    }

    /**
     * Returns the language code used to display in a webpage.
     *
     * Always use this function over code() when using the code in a webpage.
     *
     * @return string The language code.
     */
    public function displayCode()
    {
        // Provides the ISO639-1 code for a given lang code.
        // Source: https://support.google.com/webmasters/answer/189077?hl=en
        $wovnCode = array('zh-CHT', 'zh-CHS');
        $iso6391 = array('zh-Hant', 'zh-Hans');
        return str_replace($wovnCode, $iso6391, $this->code);
    }

    /**
     * Returns the English name of the language.
     *
     * @return string The English name of the language.
     */
    public function englishName()
    {
        return $this->englishName;
    }

    /**
     * Returns the alias of the language, null when unset.
     *
     * @return string|null The alias of the language.
     */
    public function alias()
    {
        return $this->alias;
    }

    /**
     * Sets the alias of the language.
     *
     * @param $alias string The alias of the language.
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;
    }

    /**
     * Marks the language as in-use in the project.
     */
    public function enable()
    {
        $this->active = true;
    }

    /**
     * Returns if the language is in-use in the project.
     *
     * @return bool
     */
    public function isValidLang()
    {
        return $this->active;
    }
}
