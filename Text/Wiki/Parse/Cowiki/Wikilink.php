<?php

/**
* 
* Parse for links to wiki pages.
* 
* @category Text
* 
* @package Text_Wiki
* 
* @author Paul M. Jones <pmjones@php.net>
* 
* @license LGPL
* 
* @version $Id$
* 
*/

/**
* 
* Parse for links to wiki pages.
*
* Wiki page names are typically in StudlyCapsStyle made of
* WordsSmashedTogether.
*
* You can also create described links to pages in this style:
* [WikiPageName nice text link to use for display]
*
* The token options for this rule are:
*
* 'page' => the wiki page name.
* 
* 'text' => the displayed link text.
* 
* 'anchor' => a named anchor on the target wiki page.
* 
* @category Text
* 
* @package Text_Wiki
* 
* @author Paul M. Jones <pmjones@php.net>
* 
*/

class Text_Wiki_Parse_Wikilink extends Text_Wiki_Parse {
    
    var $conf = array (
                       'ext_chars' => false,
                       'utf-8' => false
    );
    
    /**
    * 
    * Constructor.
    * 
    * We override the Text_Wiki_Parse constructor so we can
    * explicitly comment each part of the $regex property.
    * 
    * @access public
    * 
    * @param object &$obj The calling "parent" Text_Wiki object.
    * 
    */
    
    function Text_Wiki_Parse_Wikilink(&$obj)
    {
        parent::Text_Wiki_Parse($obj);
        if ($this->getConf('utf-8')) {
			$upper = 'A-Z\p{Lu}';
			$lower = 'a-z0-9\p{Ll}';
			$either = 'A-Za-z0-9\p{L}';
        } else if ($this->getConf('ext_chars')) {
        	// use an extended character set; this should
        	// allow for umlauts and so on.  taken from the
        	// Tavi project defaults.php file.
			$upper = 'A-Z\xc0-\xde';
			$lower = 'a-z0-9\xdf-\xfe';
			$either = 'A-Za-z0-9\xc0-\xfe';
		} else {
			// the default character set, should be fine
			// for most purposes.
			$upper = "A-Z";
			$lower = "a-z0-9";
			$either = "A-Za-z0-9";
		}
		
        // build the regular expression for finding WikiPage names.
        $this->regex =
            "(!?" .            // START WikiPage pattern (1)
            "[$upper]" .       // 1 upper
            "[$either]*" .     // 0+ alpha or digit
            "[$lower]+" .      // 1+ lower or digit
            "[$upper]" .       // 1 upper
            "[$either]*" .     // 0+ or more alpha or digit
            ")" .              // END WikiPage pattern (/1)
            "((\#" .           // START Anchor pattern (2)(3)
            "[$either]" .      // 1 alpha
            "(" .              // start sub pattern (4)
            "[-_$either:.]*" . // 0+ dash, alpha, digit, underscore, colon, dot
            "[-_$either]" .    // 1 dash, alpha, digit, or underscore
            ")?)?)";           // end subpatterns (/4)(/3)(/2)
    }
    
    
    /**
    * 
    * First parses for described links, then for standalone links.
    * 
    * @access public
    * 
    * @return void
    * 
    */
    
    function parse()
    {
        if ($this->getConf('utf-8')) {
			$either = 'A-Za-z0-9\p{L}';
        } else if ($this->getConf('ext_chars')) {
			$either = "A-Za-z0-9\xc0-\xfe";
		} else {
			$either = "A-Za-z0-9";
		}
		
        // described wiki links
        $tmp_regex = '/\(\(' . /*$this->regex*/ '(['.$either.'\s:\.]*?)((\#['.$either.'\s:\.](['.$either.'\s:\.]*?)?)?)' . '(\)\((.+?))?\)\)/'.($this->getConf('utf-8') ? 'u' : '');
        $this->wiki->source = preg_replace_callback(
            $tmp_regex,
            array(&$this, 'processDescr'),
            $this->wiki->source
        );
        
        if ($this->getConf('camel_case')) {
            // standalone wiki links
            $tmp_regex = '/(^|[^$either\-_])(\)\))?' . $this->regex . '(\(\()?/'.($this->getConf('utf-8') ? 'u' : '');
            $this->wiki->source = preg_replace_callback(
                                                        $tmp_regex,
                                                        array(&$this, 'process'),
                                                        $this->wiki->source
                                                        );
        }
    }
    
    
    /**
    * 
    * Generate a replacement for described links.
    * 
    * @access public
    *
    * @param array &$matches The array of matches from parse().
    *
    * @return A delimited token to be used as a placeholder in
    * the source text, plus any text priot to the match.
    *
    */
    
    function processDescr(&$matches)
    {
        // set the options
        $options = array(
            'page'   => $matches[1],
            'text'   => isset($matches[6]) && strlen($matches[6]) ? $matches[6] : $matches[1],
            'anchor' => isset($matches[3]) && strlen($matches[3]) ? $matches[3] : ''
        );
        if ($options['text'] == $options['page']) {
            $options['text'] = '';
        }
        
        // create and return the replacement token and preceding text
        return $this->wiki->addToken($this->rule,
                                     array_merge(array('type' => 'start'), $options)).
            $options['text'].
            $this->wiki->addToken($this->rule,
                                  array_merge(array('type' => 'end'), $options));
                                        
    }
    
    
    /**
    * 
    * Generate a replacement for standalone links.
    * 
    * 
    * @access public
    *
    * @param array &$matches The array of matches from parse().
    *
    * @return A delimited token to be used as a placeholder in
    * the source text, plus any text prior to the match.
    *
    */
    
    function process(&$matches)
    {
        // when prefixed with !, it's explicitly not a wiki link.
        // return everything as it was.
        /*if ($matches[3]{0} == '!') {
            return $matches[1] . substr($matches[3], 1) . $matches[4] . $matches[7];
        }*/
        if (!isset($matches[4])) {
            $matches[4] = '';
        }
        if ($matches[2] == '))' && $matches[7] == '((') {
            return $matches[1] . $matches[3] . $matches[4];
        }
        
        // set the options
        $options = array(
            'page' => $matches[3],
            'text' => $matches[3] . $matches[4],
            'anchor' => $matches[4]
        );
        if ($options['text'] == $options['page']) {
            $options['text'] = '';
        }

        // create and return the replacement token and preceding text
        return $matches[1].
            $matches[2].
            $this->wiki->addToken($this->rule, array_merge(array('type' => 'start'), $options)).
            $options['text'].
            $this->wiki->addToken($this->rule, array_merge(array('type' => 'end'), $options)).
            $matches[7];
    }
}
?>