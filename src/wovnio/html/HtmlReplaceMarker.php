<?php
namespace Wovnio\Html;

/**
 * Hold replacement information for
 *
 * 1. Replace to comment
 * 2. Revert to original content
 */
class HtmlReplaceMarker
{
    // The key must not be the same with common html.
    public static $keyPrefix = '__wovn-backend-ignored-key-';

    private $mappedValues;
    private $currentKeyNumber = 0;

    public function __construct()
    {
        $this->mappedValues = array();
    }

    /**
     * Add argument's value to mapping information
     *
     * @param string $value value to swap
     * @return string correspond key to the value
     */
    public function addValue($value)
    {
        $key = $this->generateKey();
        array_push($this->mappedValues, array($key, $value));
        return $key;
    }

    /**
     * Add argument's value to mapping information with comment style key
     *
     * @param string $value value to swap
     * @return string correspond key to the value
     */
    public function addCommentValue($value)
    {
        $key = '<!-- '.$this->generateKey().' -->';
        array_push($this->mappedValues, array($key, $value));
        return $key;
    }

    /**
     * Revert to original content
     *
     * @param string $markedHtml html which contains keys
     * @return string reverted html
     */
    public function revert($markedHtml)
    {
        // Replace inversely to handle duplicate replacement
        for ($i = count($this->mappedValues) - 1; $i >= 0; $i--) {
            $marked_value = $this->mappedValues[$i];
            $key = $marked_value[0];
            $value = $marked_value[1];
            $markedHtml = str_replace($key, $value, $markedHtml);
        }

        return $markedHtml;
    }

    /**
     * Get stored keys for debug/test
     *
     * @return array
     */
    public function keys()
    {
        $key_array = array();
        foreach ($this->mappedValues as $marked_value) {
            $key = $marked_value[0];
            array_push($key_array, $key);
        }
        return $key_array;
    }

    /**
     * Generate unique key
     * @return string
     */
    private function generateKey()
    {
        $new_key = self::$keyPrefix . $this->currentKeyNumber;

        $this->currentKeyNumber++;
        return $new_key;
    }
}
