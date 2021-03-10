<?php
namespace Wovnio\ModifiedVendor;

class SimpleHtmlDomNode {
    public $nodetype = SimpleHtmlDom::HDOM_TYPE_TEXT;
    public $tag = 'text';
    public $attribute = '';
    public $next_sibling = null;
    public $children_list_head = null;
    public $children_list_tail = null;

    public $dom_flat_list_next = null;

    public $parent = null;
    // The "info" array - see SimpleHtmlDom::HDOM_INFO_... for what each element contains.
    private $dom = null;

    public $node_begin = null;
    public $info_begin = null;
    public $info_end = null;
    public $info_end_space = null;
    public $info_text = null;
    public $info_inner = null;
    public $info_outer = null;

    function __construct($dom)
    {
        $this->dom = $dom;
        if (is_null($dom->dom_flat_node_list_tail)) {
            $dom->dom_flat_node_list_tail = $this;
        } else {
            $dom->dom_flat_node_list_tail->dom_flat_list_next = $this;
            $dom->dom_flat_node_list_tail = $this;
        }
    }

    function __destruct()
    {
        $this->clear();
    }

    function __toString()
    {
        return $this->outertext();
    }

    // clean up memory due to php5 circular references memory leak...
    function clear()
    {
        $this->dom = null;
        // $this->parent = null;
        $this->node_begin = null;
    }

    public function addChild($node) {
        if (is_null($this->children_list_head)) {
            $this->children_list_head = $node;
            $this->children_list_tail = $node;
        } else {
            $this->children_list_tail->next_sibling = $node;
            $this->children_list_tail = $node;
        }
    }

    // returns the parent of node
    // If a node is passed in, it will reset the parent of the current node to that one.
    function parent($parent=null)
    {
        // I am SURE that this doesn't work properly.
        // It fails to unset the current node from it's current parents nodes or children list first.
        if ($parent !== null)
        {
            $this->parent = $parent;
            $this->parent->addChild($this);
        }

        return $this->parent;
    }

    // function to locate a specific ancestor tag in the path to the root.
    function find_ancestor_tag($tag)
    {
        // Start by including ourselves in the comparison.
        $returnDom = $this;

        while (!is_null($returnDom))
        {
            if ($returnDom->tag == $tag)
            {
                break;
            }
            $returnDom = $returnDom->parent;
        }
        return $returnDom;
    }

    // get dom node's inner html
    function innertext()
    {
        if (!is_null($this->info_inner)) return $this->info_inner;
        if (!is_null($this->info_text)) return $this->dom->restore_noise($this->info_text);

        $ret = '';

        $current_node = $this->children_list_head;
        while($current_node) {
            $ret .= $current_node->outertext();
            $current_node = $current_node->next_sibling;
        }
        return $ret;
    }

    // get dom node's outer text (with tag)
    function outertext()
    {
        if ($this->tag==='root') return $this->innertext();

        // trigger callback
        if ($this->dom && $this->dom->callback!==null)
        {
            call_user_func_array($this->dom->callback, array($this));
        }

        if (!is_null($this->info_outer)) return $this->info_outer;
        if (!is_null($this->info_text)) return $this->dom->restore_noise($this->info_text);

        // render begin tag
        if (!is_null($this->node_begin))
        {
            $ret = $this->makeup();
        } else {
            $ret = "";
        }

        // render inner text
        if (!is_null($this->info_inner))
        {
            // If it's a br tag...  don't return the SimpleHtmlDom::HDOM_INNER_INFO that we may or may not have added.
            if ($this->tag != "br")
            {
                $ret .= $this->info_inner;
            }
        } else {
            if ($this->children_list_head)
            {
                $current_node = $this->children_list_head;
                while($current_node) {
                    $ret .= $this->convert_text($current_node->outertext());
                    $current_node = $current_node->next_sibling;
                }
            }
        }

        // render end tag
        if (!is_null($this->info_end) && $this->info_end!=0)
            $ret .= '</'.$this->tag.'>';
        return $ret;
    }

    // get dom node's plain text
    function text()
    {
        if (!is_null($this->info_inner)) return $this->info_inner;
        switch ($this->nodetype)
        {
            case SimpleHtmlDom::HDOM_TYPE_TEXT: return $this->dom->restore_noise($this->info_text);
            case SimpleHtmlDom::HDOM_TYPE_COMMENT: return '';
            case SimpleHtmlDom::HDOM_TYPE_UNKNOWN: return '';
        }
        if (strcasecmp($this->tag, 'script')===0) return '';
        if (strcasecmp($this->tag, 'style')===0) return '';

        $ret = '';
        // In rare cases, (always node type 1 or SimpleHtmlDom::HDOM_TYPE_ELEMENT - observed for some span tags, and some p tags) $this->nodes is set to NULL.
        // NOTE: This indicates that there is a problem where it's set to NULL without a clear happening.
        // WHY is this happening?
        if (!is_null($this->children_list_head))
        {
            $current_node = $this->children_list_head;
            while($current_node) {
                $ret .= $this->convert_text($current_node ->text());
                $current_node = $current_node->next_sibling;
            }

            // If this node is a span... add a space at the end of it so multiple spans don't run into each other.  This is plaintext after all.
            if ($this->tag == "span")
            {
                $ret .= $this->dom->default_span_text;
            }


        }
        return $ret;
    }

    function xmltext()
    {
        $ret = $this->innertext();
        $ret = str_ireplace('<![CDATA[', '', $ret);
        $ret = str_replace(']]>', '', $ret);
        return $ret;
    }

    // build node's text with tag
    function makeup()
    {
        // text, comment, unknown
        if (!is_null($this->info_text)) return $this->dom->restore_noise($this->info_text);

        $ret = '<'.$this->tag;
        $ret .= $this->attribute;
        $ret = $this->dom->restore_noise($ret);
        return $ret . $this->info_end_space . '>';
    }

    protected function match($exp, $pattern, $value) {
        switch ($exp) {
            case '=':
                return ($value===$pattern);
            case '!=':
                return ($value!==$pattern);
            case '^=':
                return preg_match("/^".preg_quote($pattern,'/')."/", $value);
            case '$=':
                return preg_match("/".preg_quote($pattern,'/')."$/", $value);
            case '*=':
                if ($pattern[0]=='/') {
                    return preg_match($pattern, $value);
                }
                return preg_match("/".$pattern."/i", $value);
        }
        return false;
    }

    protected function parse_selector($selector_string) {
        // pattern of CSS selectors, modified from mootools
        // Paperg: Add the colon to the attrbute, so that it properly finds <tag attr:ibute="something" > like google does.
        // Note: if you try to look at this attribute, yo MUST use getAttribute since $dom->x:y will fail the php syntax check.
        // Notice the \[ starting the attbute?  and the @? following?  This implies that an attribute can begin with an @ sign that is not captured.
        // This implies that an html attribute specifier may start with an @ sign that is NOT captured by the expression.
        // farther study is required to determine of this should be documented or removed.
        //		$pattern = "/([\w-:\*]*)(?:\#([\w-]+)|\.([\w-]+))?(?:\[@?(!?[\w-]+)(?:([!*^$]?=)[\"']?(.*?)[\"']?)?\])?([\/, ]+)/is";
        $pattern = "/([\w-:\*]*)(?:\#([\w-]+)|\.([\w-]+))?(?:\[@?(!?[\w-:]+)(?:([!*^$]?=)[\"']?(.*?)[\"']?)?\])?([\/, ]+)/is";
        preg_match_all($pattern, trim($selector_string).' ', $matches, PREG_SET_ORDER);

        $selectors = array();
        $result = array();
        //print_r($matches);

        foreach ($matches as $m) {
            $m[0] = trim($m[0]);
            if ($m[0]==='' || $m[0]==='/' || $m[0]==='//') continue;
            // for browser generated xpath
            if ($m[1]==='tbody') continue;

            list($tag, $key, $val, $exp, $no_key) = array($m[1], null, null, '=', false);
            if (!empty($m[2])) {$key='id'; $val=$m[2];}
            if (!empty($m[3])) {$key='class'; $val=$m[3];}
            if (!empty($m[4])) {$key=$m[4];}
            if (!empty($m[5])) {$exp=$m[5];}
            if (!empty($m[6])) {$val=$m[6];}

            // convert to lowercase
            if ($this->dom->lowercase) {$tag=strtolower($tag); $key=strtolower($key);}
            //elements that do NOT have the specified attribute
            if (isset($key[0]) && $key[0]==='!') {$key=substr($key, 1); $no_key=true;}

            $result[] = array($tag, $key, $val, $exp, $no_key);
            if (trim($m[7])===',') {
                $selectors[] = $result;
                $result = array();
            }
        }
        if (count($result)>0)
            $selectors[] = $result;
        return $selectors;
    }

    function __get($name)
    {
        $val = $this->get_attr_val($this->attribute, $name);

        if ($val) {
            return $this->convert_text($val);
        }

        switch ($name)
        {
            case 'outertext': return $this->outertext();
            case 'innertext': return $this->innertext();
            case 'plaintext': return $this->text();
            case 'xmltext': return $this->xmltext();
            // default: return array_key_exists($name, $this->attr);
            default: return false;
        }
    }

    // parse attributes
    protected function get_attr_val($attr_text, $name)
    {
        $text_len = strlen($attr_text);
        $name_pos = stripos($attr_text, $name);
        if ($name_pos === false) return false;
        if ($name_pos >= $text_len) return true;

        $blank_len = strspn($attr_text, $this->dom->token_blank, $name_pos + strlen($name));
        $val_start_pos = $name_pos + strlen($name) + $blank_len;
        if ($val_start_pos >= $text_len) return true;

        if ($attr_text[$val_start_pos] !== '=') return true;

        $val_start_pos += 1;
        $blank_len = strspn($attr_text, $this->dom->token_blank, $val_start_pos);
        $val_start_pos = $val_start_pos + $blank_len;

        switch ($attr_text[$val_start_pos]) {
            case '"':
                $val_end_pos = strpos($attr_text, '"', $val_start_pos + 1);
                $val = $this->dom->restore_noise(substr($attr_text, $val_start_pos + 1, $val_end_pos - $val_start_pos - 1));
                break;
            case '\'':
                $val_end_pos = strpos($attr_text, '\'', $val_start_pos + 1);
                $val = $this->dom->restore_noise(substr($attr_text, $val_start_pos + 1, $val_end_pos - $val_start_pos - 1));
                break;
            default:
                $len = strcspn($attr_text, $this->dom->token_blank, $val_start_pos);
                $val = $this->dom->restore_noise(substr($attr_text, $val_start_pos, $len));
        }

        // PaperG: Attributes should not have \r or \n in them, that counts as html whitespace.
        $val = str_replace("\r", "", $val);
        $val = str_replace("\n", "", $val);
        // PaperG: If this is a "class" selector, lets get rid of the preceeding and trailing space since some people leave it in the multi class case.
        if ($name == "class") {
            $val = trim($val);
        }

        return $val;
    }

    function __set($name, $value)
    {
        switch ($name)
        {
            case 'outertext':
                $this->info_outer = $value;
                break;
            case 'innertext':
                if (!is_null($this->info_text)) {
                    $this->info_text = $value;
                    return;
                }
                $this->info_inner = $value;
                break;
            default:
                $this->replaceAttributeValue($name, $value);
        }
    }

    function replaceAttributeValue($name, $value) {
        $attr_text = $this->attribute;
        $text_len = strlen($attr_text);
        $name_pos = stripos($attr_text, $name);
        if ($name_pos === false) {
            $this->attribute .= " $name=\"$value\"";
            return;
        }

        if ($name_pos >= $text_len) {
            $this->attribute .= "=\"$value\"";
            return;
        };

        $blank_len = strspn($attr_text, $this->dom->token_blank, $name_pos + strlen($name));
        $val_start_pos = $name_pos + strlen($name) + $blank_len;
        if ($val_start_pos >= $text_len) {
            $this->attribute = substr_replace($attr_text, "=\"$value\"", $name_pos + strlen($name), 0);
            return;
        }

        if ($attr_text[$val_start_pos] !== '=') {
            $this->attribute = substr_replace($attr_text, "=\"$value\"", $name_pos + strlen($name), 0);
            return;
        }

        $val_start_pos += 1;
        $blank_len = strspn($attr_text, $this->dom->token_blank, $val_start_pos);
        $val_start_pos = $val_start_pos + $blank_len;

        switch ($attr_text[$val_start_pos]) {
            case '"':
                $val_end_pos = strpos($attr_text, '"', $val_start_pos + 1);
                $this->attribute = substr_replace($attr_text, $value, $val_start_pos + 1, $val_end_pos - $val_start_pos - 1);
                break;
            case '\'':
                $val_end_pos = strpos($attr_text, '\'', $val_start_pos + 1);
                $this->attribute = substr_replace($attr_text, $value, $val_start_pos + 1, $val_end_pos - $val_start_pos - 1);
                break;
            default:
                $len = strcspn($attr_text, $this->dom->token_blank, $val_start_pos + 1);
                $this->attribute = substr_replace($attr_text, $value, $val_start_pos, $len + 1);
        }
    }

    function __isset($name)
    {
        $val = $this->get_attr_val($this->attribute, $name);
        return $val !== false;
    }

  /*
  function __unset($name) {
    if (isset($this->attr[$name]))
      unset($this->attr[$name]);
  }
   */

    // PaperG - Function to convert the text from one character set to another if the two sets are not the same.
    function convert_text($text)
    {
        $converted_text = $text;

        $sourceCharset = "";
        $targetCharset = "";

        if ($this->dom)
        {
            $sourceCharset = strtoupper($this->dom->_charset);
            $targetCharset = strtoupper($this->dom->_target_charset);
        }

        if (!empty($sourceCharset) && !empty($targetCharset) && (strcasecmp($sourceCharset, $targetCharset) != 0))
        {
            // Check if the reported encoding could have been incorrect and the text is actually already UTF-8
            if ((strcasecmp($targetCharset, 'UTF-8') == 0) && ($this->is_utf8($text)))
            {
                $converted_text = $text;
            }
            else
            {
                $converted_text = iconv($sourceCharset, $targetCharset, $text);
            }
        }

        // Lets make sure that we don't have that silly BOM issue with any of the utf-8 text we output.
        if ($targetCharset == 'UTF-8')
        {
            if (substr($converted_text, 0, 3) == "\xef\xbb\xbf")
            {
                $converted_text = substr($converted_text, 3);
            }
            if (substr($converted_text, -3) == "\xef\xbb\xbf")
            {
                $converted_text = substr($converted_text, 0, -3);
            }
        }

        return $converted_text;
    }

    /**
     * Returns true if $string is valid UTF-8 and false otherwise.
     *
     * @param mixed $str String to be tested
     * @return boolean
     */
    static function is_utf8($str)
    {
        $c=0; $b=0;
        $bits=0;
        $len=strlen($str);
        for($i=0; $i<$len; $i++)
        {
            $c=ord($str[$i]);
            if($c > 128)
            {
                if(($c >= 254)) return false;
                elseif($c >= 252) $bits=6;
                elseif($c >= 248) $bits=5;
                elseif($c >= 240) $bits=4;
                elseif($c >= 224) $bits=3;
                elseif($c >= 192) $bits=2;
                else return false;
                if(($i+$bits) > $len) return false;
                while($bits > 1)
                {
                    $i++;
                    $b=ord($str[$i]);
                    if($b < 128 || $b > 191) return false;
                    $bits--;
                }
            }
        }
        return true;
    }
  /*
  function is_utf8($string)
  {
    //this is buggy
    return (utf8_encode(utf8_decode($string)) == $string);
  }
   */

    // camel naming conventions
    function getAttribute($name) {return $this->__get($name);}
    function setAttribute($name, $value) {$this->__set($name, $value);}
    function hasAttribute($name) {return $this->__isset($name);}
    function removeAttribute($name) {$this->__set($name, null);}
    function getElementById($id) {return $this->find("#$id", 0);}
    function getElementsById($id, $idx=null) {return $this->find("#$id", $idx);}
    function getElementByTagName($name) {return $this->find($name, 0);}
    function getElementsByTagName($name, $idx=null) {return $this->find($name, $idx);}
    function parentNode() {return $this->parent();}
    function nodeName() {return $this->tag;}
    function appendChild($node) {$node->parent($this); return $node;}
}
