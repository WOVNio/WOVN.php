<?php
/**
 * Modified from simplehtmldom
 */

/**
 * Website: http://sourceforge.net/projects/simplehtmldom/
 * Additional projects that may be used: http://sourceforge.net/projects/debugobject/
 * Acknowledge: Jose Solorzano (https://sourceforge.net/projects/php-html/)
 * Contributions by:
 *	 Yousuke Kumakura (Attribute filters)
 *	 Vadim Voituk (Negative indexes supports of "find" method)
 *	 Antcs (Constructor with automatically load contents either text or file/url)
 *
 * all affected sections have comments starting with "PaperG"
 *
 * Paperg - Added case insensitive testing of the value of the selector.
 * Paperg - Added tag_start for the starting index of tags - NOTE: This works but not accurately.
 *  This tag_start gets counted AFTER \r\n have been crushed out, and after the remove_noice calls so it will not reflect the REAL position of the tag in the source,
 *  it will almost always be smaller by some amount.
 *  We use this to determine how far into the file the tag in question is.  This "percentage will never be accurate as the $dom->size is the "real" number of bytes the dom was created from.
 *  but for most purposes, it's a really good estimation.
 * Paperg - Added the forceTagsClosed to the dom constructor.  Forcing tags closed is great for malformed html, but it CAN lead to parsing errors.
 * Allow the user to tell us how much they trust the html.
 * Paperg add the text and plaintext to the selectors for the find syntax.  plaintext implies text in the innertext of a node.  text implies that the tag is a text node.
 * This allows for us to find tags based on the text they contain.
 * Create find_ancestor_tag to see if a tag is - at any level - inside of another specific tag.
 * Paperg: added parse_charset so that we know about the character set of the source document.
 *  NOTE:  If the user's system has a routine called get_last_retrieve_url_contents_content_type availalbe, we will assume it's returning the content-type header from the
 *  last transfer or curl_exec, and we will parse that and use it in preference to any other method of charset detection.
 *
 * Found infinite loop in the case of broken html in restore_noise.  Rewrote to protect from that.
 * PaperG (John Schlick) Added get_display_size for "IMG" tags.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author S.C. Chen <me578022@gmail.com>
 * @author John Schlick
 * @author Rus Carroll
 * @version 1.5 ($Rev: 210 $)
 * @package PlaceLocalInclude
 * @subpackage simple_html_dom
 */

namespace Wovnio\ModifiedVendor;

/**
 * All of the Defines for the classes below.
 * @author S.C. Chen <me578022@gmail.com>
 */
define('HDOM_TYPE_ELEMENT', 1);
define('HDOM_TYPE_COMMENT', 2);
define('HDOM_TYPE_TEXT',	3);
define('HDOM_TYPE_ENDTAG',  4);
define('HDOM_TYPE_ROOT',	5);
define('HDOM_TYPE_UNKNOWN', 6);
define('HDOM_QUOTE_DOUBLE', 0);
define('HDOM_QUOTE_SINGLE', 1);
define('HDOM_QUOTE_NO',	 3);
// define('HDOM_INFO_QUOTE',   2);
// define('HDOM_INFO_SPACE',   3);
define('DEFAULT_TARGET_CHARSET', 'UTF-8');
define('DEFAULT_BR_TEXT', "\r\n");
define('DEFAULT_SPAN_TEXT', " ");
define('MAX_FILE_SIZE', 600000);

/**
 * simple html dom node
 * PaperG - added ability for "find" routine to lowercase the value of the selector.
 * PaperG - added $tag_start to track the start position of the tag in the total byte index
 *
 * @package PlaceLocalInclude
 */
class simple_html_dom_node
{
	public $nodetype = HDOM_TYPE_TEXT;
	public $tag = 'text';
	public $attribute = '';
	public $next_sibling = null;
	public $children_list_head = null;
	public $children_list_tail = null;

	public $dom_flat_list_next = null;

	public $parent = null;
	// The "info" array - see HDOM_INFO_... for what each element contains.
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
		$this->parent = null;
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
			// If it's a br tag...  don't return the HDOM_INNER_INFO that we may or may not have added.
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
			case HDOM_TYPE_TEXT: return $this->dom->restore_noise($this->info_text);
			case HDOM_TYPE_COMMENT: return '';
			case HDOM_TYPE_UNKNOWN: return '';
		}
		if (strcasecmp($this->tag, 'script')===0) return '';
		if (strcasecmp($this->tag, 'style')===0) return '';

		$ret = '';
		// In rare cases, (always node type 1 or HDOM_TYPE_ELEMENT - observed for some span tags, and some p tags) $this->nodes is set to NULL.
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
		switch ($name)
		{
			case 'outertext': return true;
			case 'innertext': return true;
			case 'plaintext': return true;
		}
		//no value attr: nowrap, checked selected...
		// return (array_key_exists($name, $this->attr)) ? true : isset($this->attr[$name]);
		return false;
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
	// function hasAttribute($name) {return $this->__isset($name);}
	function removeAttribute($name) {$this->__set($name, null);}
	function getElementById($id) {return $this->find("#$id", 0);}
	function getElementsById($id, $idx=null) {return $this->find("#$id", $idx);}
	function getElementByTagName($name) {return $this->find($name, 0);}
	function getElementsByTagName($name, $idx=null) {return $this->find($name, $idx);}
	function parentNode() {return $this->parent();}
	function nodeName() {return $this->tag;}
	function appendChild($node) {$node->parent($this); return $node;}

}

/**
 * simple html dom parser
 * Paperg - in the find routine: allow us to specify that we want case insensitive testing of the value of the selector.
 * Paperg - change $size from protected to public so we can easily access it
 * Paperg - added ForceTagsClosed in the constructor which tells us whether we trust the html or not.  Default is to NOT trust it.
 *
 * @package PlaceLocalInclude
 */
class simple_html_dom
{
	public $root = null;
	public $callback = null;
	public $lowercase = false;
	// Used to keep track of how large the text was when we started.
	public $original_size;
	public $size;
	protected $pos;
	protected $char;
	protected $cursor;
	protected $parent;
	protected $noise = array();
	public $dom_flat_node_list_tail = null;

	public $token_blank = " \t\r\n";
	protected $token_equal = ' =/>';
	protected $token_slash = " />\r\n\t";
	protected $token_attr = ' >';
	// Note that this is referenced by a child node, and so it needs to be public for that node to see this information.
	public $_charset = '';
	public $_target_charset = '';
	protected $default_br_text = "";
	public $default_span_text = "";

	// use isset instead of in_array, performance boost about 30%...
	protected $self_closing_tags = array('img'=>1, 'br'=>1, 'input'=>1, 'meta'=>1, 'link'=>1, 'hr'=>1, 'base'=>1, 'embed'=>1, 'spacer'=>1);
	protected $block_tags = array('root'=>1, 'body'=>1, 'form'=>1, 'div'=>1, 'span'=>1, 'table'=>1);
	// Known sourceforge issue #2977341
	// B tags that are not closed cause us to return everything to the end of the document.
	protected $optional_closing_tags = array(
		'tr'=>array('tr'=>1, 'td'=>1, 'th'=>1),
		'th'=>array('th'=>1),
		'td'=>array('td'=>1),
		'li'=>array('li'=>1),
		'dt'=>array('dt'=>1, 'dd'=>1),
		'dd'=>array('dd'=>1, 'dt'=>1),
		'dl'=>array('dd'=>1, 'dt'=>1),
		'p'=>array('p'=>1),
		'nobr'=>array('nobr'=>1),
		'b'=>array('b'=>1),
		'option'=>array('option'=>1),
	);

	// get html dom from file
	// $maxlen is defined in the code as PHP_STREAM_COPY_ALL which is defined as -1.
	public static function file_get_html($url, $charset, $use_include_path = false, $context=null, $offset = -1, $maxLen=-1, $lowercase = true, $forceTagsClosed=true, $target_charset = DEFAULT_TARGET_CHARSET, $stripRN=true, $defaultBRText=DEFAULT_BR_TEXT, $defaultSpanText=DEFAULT_SPAN_TEXT)
	{
		// We DO force the tags to be terminated.
		$dom = new simple_html_dom(null, $lowercase, $forceTagsClosed, $target_charset, $stripRN, $defaultBRText, $defaultSpanText);
		// For sourceforge users: uncomment the next line and comment the retreive_url_contents line 2 lines down if it is not already done.
		$contents = file_get_contents($url, $use_include_path, $context, $offset);
		// Paperg - use our own mechanism for getting the contents as we want to control the timeout.
		//$contents = retrieve_url_contents($url);
		if (empty($contents) || strlen($contents) > MAX_FILE_SIZE)
		{
			return false;
		}
		// The second parameter can force the selectors to all be lowercase.
		$dom->load($contents, $lowercase, $stripRN);
		return $dom;
	}

	// get html dom from string
	public static function str_get_html($str, $charset, $lowercase=true, $forceTagsClosed=true, $target_charset = DEFAULT_TARGET_CHARSET, $stripRN=true, $defaultBRText=DEFAULT_BR_TEXT, $defaultSpanText=DEFAULT_SPAN_TEXT)
	{
		$dom = new simple_html_dom(null, $lowercase, $forceTagsClosed, $target_charset, $stripRN, $defaultBRText, $defaultSpanText);
		if (empty($str) || strlen($str) > MAX_FILE_SIZE)
		{
			$dom->clear();
			return false;
		}
		$dom->load($str, $charset, $lowercase, $stripRN);
		return $dom;
	}

	function __construct($str=null, $charset=null, $lowercase=true, $forceTagsClosed=true, $target_charset=DEFAULT_TARGET_CHARSET, $stripRN=true, $defaultBRText=DEFAULT_BR_TEXT, $defaultSpanText=DEFAULT_SPAN_TEXT)
	{
		if ($str)
		{
			if (preg_match("/^http:\/\//i",$str) || is_file($str))
			{
				$this->load_file($str);
			}
			else
			{
				$this->load($str, $charset, $lowercase, $stripRN, $defaultBRText, $defaultSpanText);
			}
		}
		// Forcing tags to be closed implies that we don't trust the html, but it can lead to parsing errors if we SHOULD trust the html.
		if (!$forceTagsClosed) {
			$this->optional_closing_array=array();
		}
		$this->_target_charset = $target_charset;
	}

	function __destruct()
	{
		$this->clear();
	}

	// load html from string
	function load($str, $charset, $lowercase=true, $stripRN=true, $defaultBRText=DEFAULT_BR_TEXT, $defaultSpanText=DEFAULT_SPAN_TEXT)
	{
		//before use the doc...  strip out the \r \n's if we are told to.
		if ($stripRN) {
			$str = str_replace("\r", " ", $str);
			$str = str_replace("\n", " ", $str);
		}

		// prepare
		$this->prepare($str, $lowercase, $stripRN, $defaultBRText, $defaultSpanText);
		// strip out cdata
		$this->remove_noise($str, "'<!\[CDATA\[(.*?)\]\]>'is", true);
		// strip out comments
		$this->remove_noise($str, "'<!--(.*?)-->'is");
		// Per sourceforge http://sourceforge.net/tracker/?func=detail&aid=2949097&group_id=218559&atid=1044037
		// Script tags removal now preceeds style tag removal.
		// strip out <script> tags
		$this->remove_noise($str, "'<\s*script[^>]*[^/]>(.*?)<\s*/\s*script\s*>'is");
		$this->remove_noise($str, "'<\s*script\s*>(.*?)<\s*/\s*script\s*>'is");
		// strip out <style> tags
		$this->remove_noise($str, "'<\s*style[^>]*[^/]>(.*?)<\s*/\s*style\s*>'is");
		$this->remove_noise($str, "'<\s*style\s*>(.*?)<\s*/\s*style\s*>'is");
		// strip out preformatted tags
		$this->remove_noise($str, "'<\s*(?:code)[^>]*>(.*?)<\s*/\s*(?:code)\s*>'is");
		// strip out server side scripts
		$this->remove_noise($str, "'(<\?)(.*?)(\?>)'s", true);
		// strip smarty scripts
		$this->remove_noise($str, "'(\{\w)(.*?)(\})'s", true);

		// parsing
		while ($this->parse($str));
		// end
		$this->root->info_end = $this->cursor;
		if ($charset) {
			$this->_charset = $charset;
		} else {
			$this->parse_charset();
		}

		// make load function chainable
		return $this;

	}

	// load html from file
	function load_file()
	{
		$args = func_get_args();
		$this->load(call_user_func_array('file_get_contents', $args), true);
		// Throw an error if we can't properly load the dom.
		if (($error=error_get_last())!==null) {
			$this->clear();
			return false;
		}
	}

	// set callback function
	function set_callback($function_name)
	{
		$this->callback = $function_name;
	}

	// remove callback function
	function remove_callback()
	{
		$this->callback = null;
	}

	// save dom as string
	function save($filepath='')
	{
		$ret = $this->root->innertext();
		if ($filepath!=='') file_put_contents($filepath, $ret, LOCK_EX);
		return $ret;
	}

	// find dom node by css selector
	// Paperg - allow us to specify that we want case insensitive testing of the value of the selector.
	function find($selector, $idx=null, $lowercase=false)
	{
		return $this->root->find($selector, $idx, $lowercase);
	}

	function iterateAll($callback) {
		$current_node = $this->root->dom_flat_list_next;
		while($current_node) {
			$callback($current_node);
			$current_node = $current_node->dom_flat_list_next;
		}
	}

	// clean up memory due to php5 circular references memory leak...
	function clear()
	{
		if (!is_null($this->root)) {
			$current_node = $this->root->dom_flat_list_next;
			while($current_node) {
				$current_node->clear();
				$current_node = $current_node->dom_flat_list_next;
			}
		}

		// This add next line is documented in the sourceforge repository. 2977248 as a fix for ongoing memory leaks that occur even with the use of clear.
		if (isset($this->parent)) {$this->parent->clear(); unset($this->parent);}
		if (isset($this->root)) {$this->root->clear(); unset($this->root);}
		unset($this->noise);
	}

	// prepare HTML data and init everything
	protected function prepare($str, $lowercase=true, $stripRN=true, $defaultBRText=DEFAULT_BR_TEXT, $defaultSpanText=DEFAULT_SPAN_TEXT)
	{
		$this->clear();

		// set the length of content before we do anything to it.
		$this->size = strlen($str);
		// Save the original size of the html that we got in.  It might be useful to someone.
		$this->original_size = $this->size;

		$this->pos = 0;
		$this->cursor = 1;
		$this->noise = array();
		$this->lowercase = $lowercase;
		$this->default_br_text = $defaultBRText;
		$this->default_span_text = $defaultSpanText;
		$this->root = new simple_html_dom_node($this);
		$this->root->tag = 'root';
		$this->root->info_begin = -1;
		$this->root->nodetype = HDOM_TYPE_ROOT;
		$this->parent = $this->root;
		if ($this->size>0) $this->char = $str[0];
	}

	// parse html content
	protected function parse($doc)
	{
		if (($s = $this->copy_until_char($doc, '<'))==='')
		{
			return $this->read_tag($doc);
		}

		// text
		$node = new simple_html_dom_node($this);
		++$this->cursor;
		$node->info_text = $s;
		$this->link_nodes($node, false);
		return true;
	}

	// PAPERG - dkchou - added this to try to identify the character set of the page we have just parsed so we know better how to spit it out later.
	// NOTE:  IF you provide a routine called get_last_retrieve_url_contents_content_type which returns the CURLINFO_CONTENT_TYPE from the last curl_exec
	// (or the content_type header from the last transfer), we will parse THAT, and if a charset is specified, we will use it over any other mechanism.
	protected function parse_charset()
	{
		$charset = null;

		if (function_exists('get_last_retrieve_url_contents_content_type'))
		{
			$contentTypeHeader = get_last_retrieve_url_contents_content_type();
			$success = preg_match('/charset=(.+)/', $contentTypeHeader, $matches);
			if ($success)
			{
				$charset = $matches[1];
			}

		}

		if (empty($charset))
		{
			$el = $this->root->find('meta[http-equiv=Content-Type]',0, true);
			if (!empty($el))
			{
				$fullvalue = $el->content;
				if (!empty($fullvalue))
				{
					$success = preg_match('/charset=(.+)/i', $fullvalue, $matches);
					if ($success)
					{
						$charset = $matches[1];
					}
					else
					{
						// If there is a meta tag, and they don't specify the character set, research says that it's typically ISO-8859-1
						$charset = 'ISO-8859-1';
					}
				}
			}
		}

		// If we couldn't find a charset above, then lets try to detect one based on the text we got...
		if (empty($charset))
		{
			// Use this in case mb_detect_charset isn't installed/loaded on this machine.
			$charset = false;
			if (function_exists('mb_detect_encoding'))
			{
				// Have php try to detect the encoding from the text given to us.
				$charset = mb_detect_encoding($this->root->plaintext . "ascii", $encoding_list = array( "UTF-8", "CP1252" ) );
			}

			// and if this doesn't work...  then we need to just wrongheadedly assume it's UTF-8 so that we can move on - cause this will usually give us most of what we need...
			if ($charset === false)
			{
				$charset = 'UTF-8';
			}
		}

		// Since CP1252 is a superset, if we get one of it's subsets, we want it instead.
		if ((strtolower($charset) == strtolower('ISO-8859-1')) || (strtolower($charset) == strtolower('Latin1')) || (strtolower($charset) == strtolower('Latin-1')))
		{
			$charset = 'CP1252';
		}

		return $this->_charset = $charset;
	}

	// read tag info
	protected function read_tag($doc)
	{
		if ($this->char!=='<')
		{
			$this->root->info_end = $this->cursor;
			return false;
		}
		$begin_tag_pos = $this->pos;
		$this->char = (++$this->pos<$this->size) ? $doc[$this->pos] : null; // next

		// end tag
		if ($this->char==='/')
		{
			$this->char = (++$this->pos<$this->size) ? $doc[$this->pos] : null; // next
			// This represents the change in the simple_html_dom trunk from revision 180 to 181.
			// $this->skip($this->token_blank_t);
			$this->skip($doc, $this->token_blank);
			$tag = $this->copy_until_char($doc, '>');

			// skip attributes in end tag
			if (($pos = strpos($tag, ' '))!==false)
				$tag = substr($tag, 0, $pos);

			$parent_lower = strtolower($this->parent->tag);
			$tag_lower = strtolower($tag);

			if ($parent_lower!==$tag_lower)
			{
				if (isset($this->optional_closing_tags[$parent_lower]) && isset($this->block_tags[$tag_lower]))
				{
					$this->parent->info_end = 0;
					$org_parent = $this->parent;

					while (($this->parent->parent) && strtolower($this->parent->tag)!==$tag_lower)
						$this->parent = $this->parent->parent;

					if (strtolower($this->parent->tag)!==$tag_lower) {
						$this->parent = $org_parent; // restore origonal parent
						if ($this->parent->parent) $this->parent = $this->parent->parent;
						$this->parent->info_end = $this->cursor;
						return $this->as_text_node($doc, $tag);
					}
				}
				else if (($this->parent->parent) && isset($this->block_tags[$tag_lower]))
				{
					$this->parent->info_end = 0;
					$org_parent = $this->parent;

					while (($this->parent->parent) && strtolower($this->parent->tag)!==$tag_lower)
						$this->parent = $this->parent->parent;

					if (strtolower($this->parent->tag)!==$tag_lower)
					{
						$this->parent = $org_parent; // restore origonal parent
						$this->parent->info_end = $this->cursor;
						return $this->as_text_node($doc, $tag);
					}
				}
				else if (($this->parent->parent) && strtolower($this->parent->parent->tag)===$tag_lower)
				{
					$this->parent->info_end = 0;
					$this->parent = $this->parent->parent;
				}
				else
					return $this->as_text_node($doc, $tag);
			}

			$this->parent->info_end = $this->cursor;
			if ($this->parent->parent) $this->parent = $this->parent->parent;

			$this->char = (++$this->pos<$this->size) ? $doc[$this->pos] : null; // next
			return true;
		}

		$node = new simple_html_dom_node($this);
		$node->info_begin = $this->cursor;
		$node->node_begin = $node;
		++$this->cursor;
		$tag = $this->copy_until($doc, $this->token_slash);
		// $node->tag_start = $begin_tag_pos;

		// doctype, cdata & comments...
		if (isset($tag[0]) && $tag[0]==='!') {
			$node->info_text = '<' . $tag . $this->copy_until_char($doc, '>');

			if (isset($tag[2]) && $tag[1]==='-' && $tag[2]==='-') {
				$node->nodetype = HDOM_TYPE_COMMENT;
				$node->tag = 'comment';
			} else {
				$node->nodetype = HDOM_TYPE_UNKNOWN;
				$node->tag = 'unknown';
			}
			if ($this->char==='>') $node->info_text.='>';
			$this->link_nodes($node, true);
			$this->char = (++$this->pos<$this->size) ? $doc[$this->pos] : null; // next
			return true;
		}

		// text
		if ($pos=strpos($tag, '<')!==false) {
			$tag = '<' . substr($tag, 0, -1);
			$node->info_text = $tag;
			$this->link_nodes($node, false);
			$this->char = $doc[--$this->pos]; // prev
			return true;
		}

		if (!preg_match("/^[\w-:]+$/", $tag)) {
			$node->info_text = '<' . $tag . $this->copy_until($doc, '<>');
			if ($this->char==='<') {
				$this->link_nodes($node, false);
				return true;
			}

			if ($this->char==='>') $node->info_text.='>';
			$this->link_nodes($node, false);
			$this->char = (++$this->pos<$this->size) ? $doc[$this->pos] : null; // next
			return true;
		}

		// begin tag
		$node->nodetype = HDOM_TYPE_ELEMENT;
		$tag_lower = strtolower($tag);
		$node->tag = ($this->lowercase) ? $tag_lower : $tag;

		// handle optional closing tags
		if (isset($this->optional_closing_tags[$tag_lower]) )
		{
			while (isset($this->optional_closing_tags[$tag_lower][strtolower($this->parent->tag)]))
			{
				$this->parent->info_end = 0;
				$this->parent = $this->parent->parent;
			}
			$node->parent = $this->parent;
		}

		$start_pos = $this->pos;
		$guard = 0; // prevent infinity loop
		$space = array($this->copy_skip($doc, $this->token_blank), '', '');

		// attributes
		do
		{
			if ($this->char!==null && $space[0]==='')
			{
				break;
			}
			$name = $this->copy_until($doc, $this->token_equal);
			if ($guard===$this->pos)
			{
				$this->char = (++$this->pos<$this->size) ? $doc[$this->pos] : null; // next
				continue;
			}
			$guard = $this->pos;

			// handle endless '<'
			if ($this->pos>=$this->size-1 && $this->char!=='>') {
				$node->nodetype = HDOM_TYPE_TEXT;
				$node->info_end = 0;
				$node->info_text = '<'.$tag . $space[0] . $name;
				$node->tag = 'text';
				$this->link_nodes($node, false);
				return true;
			}

			// handle mismatch '<'
			if ($doc[$this->pos-1]=='<') {
				$node->nodetype = HDOM_TYPE_TEXT;
				$node->tag = 'text';
				$node->attribute = '';
				$node->info_end = 0;
				$node->info_text = substr($doc, $begin_tag_pos, $this->pos-$begin_tag_pos-1);
				$this->pos -= 2;
				$this->char = (++$this->pos<$this->size) ? $doc[$this->pos] : null; // next
				$this->link_nodes($node, false);
				return true;
			}

			if ($name!=='/' && $name!=='') {
				$space[1] = $this->copy_skip($doc, $this->token_blank);
				$name = $this->restore_noise($name);
				if ($this->lowercase) $name = strtolower($name);
				if ($this->char==='=') {
					$this->char = (++$this->pos<$this->size) ? $doc[$this->pos] : null; // next
					// $this->parse_attr($node, $name, $space);
				}
				else {
					//no value attr: nowrap, checked selected...
					if ($this->char!='>') $this->char = $doc[--$this->pos]; // prev
				}
				$space = array($this->copy_skip($doc, $this->token_blank), '', '');
			}
			else
				break;
		} while ($this->char!=='>' && $this->char!=='/');

		$this->link_nodes($node, true);
		$node->info_end_space = $space[0];

		$tag_closing_diff = strlen($node->info_end_space) + 1;
		// check self closing
		if ($this->copy_until_char_escape($doc, '>')==='/')
		{
			$node->info_end_space .= '/';
			$node->info_end = 0;
			$tag_closing_diff += 1;
		}
		else
		{
			// reset parent
			if (!isset($this->self_closing_tags[strtolower($node->tag)])) $this->parent = $node;
		}
		$this->char = (++$this->pos<$this->size) ? $doc[$this->pos] : null; // next
		$node->attribute = substr($doc, $start_pos, $this->pos - $start_pos - $tag_closing_diff);

		// If it's a BR tag, we need to set it's text to the default text.
		// This way when we see it in plaintext, we can generate formatting that the user wants.
		// since a br tag never has sub nodes, this works well.
		if ($node->tag == "br")
		{
			$node->info_inner = $this->default_br_text;
		}

		return true;
	}

	// link node's parent
	protected function link_nodes(&$node, $is_child)
	{
		$node->parent = $this->parent;
		$this->parent->addChild($node);
		if ($is_child)
		{
			// $this->parent->children[] = $node;
		}
	}

	// as a text node
	protected function as_text_node($doc, $tag)
	{
		$node = new simple_html_dom_node($this);
		++$this->cursor;
		$node->info_text = '</' . $tag . '>';
		$this->link_nodes($node, false);
		$this->char = (++$this->pos<$this->size) ? $doc[$this->pos] : null; // next
		return true;
	}

	protected function skip($doc, $chars)
	{
		$this->pos += strspn($doc, $chars, $this->pos);
		$this->char = ($this->pos<$this->size) ? $doc[$this->pos] : null; // next
	}

	protected function copy_skip($doc, $chars)
	{
		$pos = $this->pos;
		$len = strspn($doc, $chars, $pos);
		$this->pos += $len;
		$this->char = ($this->pos<$this->size) ? $doc[$this->pos] : null; // next
		if ($len===0) return '';
		return substr($doc, $pos, $len);
	}

	protected function copy_until($doc, $chars)
	{
		$pos = $this->pos;
		$len = strcspn($doc, $chars, $pos);
		$this->pos += $len;
		$this->char = ($this->pos<$this->size) ? $doc[$this->pos] : null; // next
		return substr($doc, $pos, $len);
	}

	protected function copy_until_char($doc, $char)
	{
		if ($this->char===null) return '';

		if (($pos = strpos($doc, $char, $this->pos))===false) {
			$ret = substr($doc, $this->pos, $this->size-$this->pos);
			$this->char = null;
			$this->pos = $this->size;
			return $ret;
		}

		if ($pos===$this->pos) return '';
		$pos_old = $this->pos;
		$this->char = $doc[$pos];
		$this->pos = $pos;
		return substr($doc, $pos_old, $pos-$pos_old);
	}

	protected function copy_until_char_escape($doc, $char)
	{
		if ($this->char===null) return '';

		$start = $this->pos;
		while (1)
		{
			if (($pos = strpos($doc, $char, $start))===false)
			{
				$ret = substr($doc, $this->pos, $this->size-$this->pos);
				$this->char = null;
				$this->pos = $this->size;
				return $ret;
			}

			if ($pos===$this->pos) return '';

			if ($doc[$pos-1]==='\\') {
				$start = $pos+1;
				continue;
			}

			$pos_old = $this->pos;
			$this->char = $doc[$pos];
			$this->pos = $pos;
			return substr($doc, $pos_old, $pos-$pos_old);
		}
	}

	// remove noise from html content
	// save the noise in the $this->noise array.
	protected function remove_noise(&$str, $pattern, $remove_tag=false)
	{
		$count = preg_match_all($pattern, $str, $matches, PREG_SET_ORDER|PREG_OFFSET_CAPTURE);

		for ($i=$count-1; $i>-1; --$i)
		{
			$key = '___noise___'.sprintf('% 5d', count($this->noise)+1000);
			$idx = ($remove_tag) ? 0 : 1;
			$this->noise[$key] = $matches[$i][$idx][0];
			$str = substr_replace($str, $key, $matches[$i][$idx][1], strlen($matches[$i][$idx][0]));
		}

		// reset the length of content
		$this->size = strlen($str);
		if ($this->size>0)
		{
			$this->char = $str[0];
		}
	}

	// restore noise to html content
  function restore_noise($text)
	{
		while (($pos=strpos($text, '___noise___'))!==false)
		{
			// Sometimes there is a broken piece of markup, and we don't GET the pos+11 etc... token which indicates a problem outside of us...
			if (strlen($text) > $pos+15)
			{
				$key = '___noise___'.$text[$pos+11].$text[$pos+12].$text[$pos+13].$text[$pos+14].$text[$pos+15];
				if (isset($this->noise[$key]))
				{
					$text = substr($text, 0, $pos).$this->noise[$key].substr($text, $pos+16);
				}
				else
				{
					// do this to prevent an infinite loop.
					$text = substr($text, 0, $pos).'UNDEFINED NOISE FOR KEY: '.$key . substr($text, $pos+16);
				}
			}
			else
			{
				// There is no valid key being given back to us... We must get rid of the ___noise___ or we will have a problem.
				$text = substr($text, 0, $pos).'NO NUMERIC NOISE KEY' . substr($text, $pos+11);
			}
		}
		return $text;
	}

	// Sometimes we NEED one of the noise elements.
	function search_noise($text)
	{
		foreach($this->noise as $noiseElement)
		{
			if (strpos($noiseElement, $text)!==false)
			{
				return $noiseElement;
			}
		}
	}
	function __toString()
	{
		return $this->root->innertext();
	}

	function __get($name)
	{
		switch ($name)
		{
			case 'outertext':
				return $this->root->innertext();
			case 'innertext':
				return $this->root->innertext();
			case 'plaintext':
				return $this->root->text();
			case 'charset':
				return $this->_charset;
			case 'target_charset':
				return $this->_target_charset;
		}
	}

	// camel naming conventions
	function getElementById($id) {return $this->find("#$id", 0);}
	function getElementsById($id, $idx=null) {return $this->find("#$id", $idx);}
	function getElementByTagName($name) {return $this->find($name, 0);}
	function getElementsByTagName($name, $idx=-1) {return $this->find($name, $idx);}
	function loadFile() {$args = func_get_args();$this->load_file($args);}
}

?>
