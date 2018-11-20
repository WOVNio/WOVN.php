<?php
namespace Wovnio\ModifiedVendor;

class SimpleHtmlDom {
    const HDOM_TYPE_ELEMENT = 1;
    const HDOM_TYPE_COMMENT = 2;
    const HDOM_TYPE_TEXT = 3;
    const HDOM_TYPE_ENDTAG =  4;
    const HDOM_TYPE_ROOT = 5;
    const HDOM_TYPE_UNKNOWN = 6;
    const HDOM_QUOTE_DOUBLE = 0;
    const HDOM_QUOTE_SINGLE = 1;
    const HDOM_QUOTE_NO = 3;
    const DEFAULT_TARGET_CHARSET = 'UTF-8';
    const DEFAULT_BR_TEXT = "\r\n";
    const DEFAULT_SPAN_TEXT = " ";

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
    public static function file_get_html($url, $charset, $use_include_path = false, $context=null, $offset = -1, $maxLen=-1, $lowercase = true, $forceTagsClosed=true, $target_charset = SimpleHtmlDom::DEFAULT_TARGET_CHARSET, $stripRN=true, $defaultBRText=SimpleHtmlDom::DEFAULT_BR_TEXT, $defaultSpanText=SimpleHtmlDom::DEFAULT_SPAN_TEXT)
    {
        // We DO force the tags to be terminated.
        $dom = new SimpleHtmlDom(null, $lowercase, $forceTagsClosed, $target_charset, $stripRN, $defaultBRText, $defaultSpanText);
        // For sourceforge users: uncomment the next line and comment the retreive_url_contents line 2 lines down if it is not already done.
        $contents = file_get_contents($url, $use_include_path, $context, $offset);
        // Paperg - use our own mechanism for getting the contents as we want to control the timeout.
        //$contents = retrieve_url_contents($url);
        if (empty($contents))
        {
            return false;
        }
        // The second parameter can force the selectors to all be lowercase.
        $dom->load($contents, $lowercase, $stripRN);
        return $dom;
    }

    // get html dom from string
    public static function str_get_html($str, $charset, $lowercase=true, $forceTagsClosed=true, $target_charset = SimpleHtmlDom::DEFAULT_TARGET_CHARSET, $stripRN=true, $defaultBRText=SimpleHtmlDom::DEFAULT_BR_TEXT, $defaultSpanText=SimpleHtmlDom::DEFAULT_SPAN_TEXT)
    {
        $dom = new SimpleHtmlDom(null, $lowercase, $forceTagsClosed, $target_charset, $stripRN, $defaultBRText, $defaultSpanText);
        if (empty($str))
        {
            $dom->clear();
            return false;
        }
        $dom->load($str, $charset, $lowercase, $stripRN);
        return $dom;
    }

    function __construct($str=null, $charset=null, $lowercase=true, $forceTagsClosed=true, $target_charset=SimpleHtmlDom::DEFAULT_TARGET_CHARSET, $stripRN=true, $defaultBRText=SimpleHtmlDom::DEFAULT_BR_TEXT, $defaultSpanText=SimpleHtmlDom::DEFAULT_SPAN_TEXT)
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
    function load($str, $charset, $lowercase=true, $stripRN=true, $defaultBRText=SimpleHtmlDom::DEFAULT_BR_TEXT, $defaultSpanText=SimpleHtmlDom::DEFAULT_SPAN_TEXT)
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
    protected function prepare($str, $lowercase=true, $stripRN=true, $defaultBRText=SimpleHtmlDom::DEFAULT_BR_TEXT, $defaultSpanText=SimpleHtmlDom::DEFAULT_SPAN_TEXT)
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
        $this->root = new SimpleHtmlDomNode($this);
        $this->root->tag = 'root';
        $this->root->info_begin = -1;
        $this->root->nodetype = SimpleHtmlDom::HDOM_TYPE_ROOT;
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
        $node = new SimpleHtmlDomNode($this);
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
            // This represents the change in the SimpleHtmlDom trunk from revision 180 to 181.
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

        $node = new SimpleHtmlDomNode($this);
        $node->info_begin = $this->cursor;
        $node->node_begin = $node;
        ++$this->cursor;
        $tag = $this->copy_until($doc, $this->token_slash);
        // $node->tag_start = $begin_tag_pos;

        // doctype, cdata & comments...
        if (isset($tag[0]) && $tag[0]==='!') {
            $node->info_text = '<' . $tag . $this->copy_until_char($doc, '>');

            if (isset($tag[2]) && $tag[1]==='-' && $tag[2]==='-') {
                $node->nodetype = SimpleHtmlDom::HDOM_TYPE_COMMENT;
                $node->tag = 'comment';
            } else {
                $node->nodetype = SimpleHtmlDom::HDOM_TYPE_UNKNOWN;
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
        $node->nodetype = SimpleHtmlDom::HDOM_TYPE_ELEMENT;
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
                $node->nodetype = SimpleHtmlDom::HDOM_TYPE_TEXT;
                $node->info_end = 0;
                $node->info_text = '<'.$tag . $space[0] . $name;
                $node->tag = 'text';
                $this->link_nodes($node, false);
                return true;
            }

            // handle mismatch '<'
            if ($doc[$this->pos-1]=='<') {
                $node->nodetype = SimpleHtmlDom::HDOM_TYPE_TEXT;
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
        $node = new SimpleHtmlDomNode($this);
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
