<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | PHP version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available through the world-wide-web at                              |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Paul M. Jones <pmjones@ciaweb.net>                          |
// +----------------------------------------------------------------------+
//
// $Id$

require_once 'Text/Wiki/Rule.php';


/**
* 
* This is the "master" class for handling the management and convenience
* functions to transform Wiki-formatted text.
* 
* @author Paul M. Jones <pmjones@ciaweb.net>
* 
* @version 0.16 alpha
* 
*/

class Text_Wiki {
	
	
	/**
	* 
	* The array of rules to apply to the source text, in order.
	* 
	* @access public
	* 
	* @var array
	* 
	*/
	
	var $rules = array(
	
		'prefilter' => array(
			'file' => 'Text/Wiki/Rule/prefilter.php',
			'name' => 'Text_Wiki_Rule_prefilter',
			'flag' => true,
			'conf' => array()
		),
		
		'delimiter' => array(
			'file' => 'Text/Wiki/Rule/delimiter.php',
			'name' => 'Text_Wiki_Rule_delimiter',
			'flag' => true,
			'conf' => array()
		),
		
		'code' => array(
			'file' => 'Text/Wiki/Rule/code.php',
			'name' => 'Text_Wiki_Rule_code',
			'flag' => true,
			'conf' => array()
		),
		
		'phpcode' => array(
			'file' => 'Text/Wiki/Rule/phpcode.php',
			'name' => 'Text_Wiki_Rule_phpcode',
			'flag' => true,
			'conf' => array()
		),
		
		'html' => array(
			'file' => 'Text/Wiki/Rule/html.php',
			'name' => 'Text_Wiki_Rule_html',
			'flag' => false,
			'conf' => array()
		),
		
		'raw' => array(
			'file' => 'Text/Wiki/Rule/raw.php',
			'name' => 'Text_Wiki_Rule_raw',
			'flag' => true,
			'conf' => array()
		),
		
		'include' => array(
			'file' => 'Text/Wiki/Rule/include.php',
			'name' => 'Text_Wiki_Rule_include',
			'flag' => false,
			'conf' => array(
				'base' => '/path/to/scripts/'
			)
		),
		
		'heading' => array(
			'file' => 'Text/Wiki/Rule/heading.php',
			'name' => 'Text_Wiki_Rule_heading',
			'flag' => true,
			'conf' => array()
		),
		
		'horiz' => array(
			'file' => 'Text/Wiki/Rule/horiz.php',
			'name' => 'Text_Wiki_Rule_horiz',
			'flag' => true,
			'conf' => array()
		),
		
		'break' => array(
			'file' => 'Text/Wiki/Rule/break.php',
			'name' => 'Text_Wiki_Rule_break',
			'flag' => true,
			'conf' => array()
		),
		
		'blockquote' => array(
			'file' => 'Text/Wiki/Rule/blockquote.php',
			'name' => 'Text_Wiki_Rule_blockquote',
			'flag' => true,
			'conf' => array()
		),
		
		'list' => array(
			'file' => 'Text/Wiki/Rule/list.php',
			'name' => 'Text_Wiki_Rule_list',
			'flag' => true,
			'conf' => array()
		),
		
		'deflist' => array(
			'file' => 'Text/Wiki/Rule/deflist.php',
			'name' => 'Text_Wiki_Rule_deflist',
			'flag' => true,
			'conf' => array()
		),
		
		'table' => array(
			'file' => 'Text/Wiki/Rule/table.php',
			'name' => 'Text_Wiki_Rule_table',
			'flag' => true,
			'conf' => array(
				'border' => 1,
				'spacing' => 0,
				'padding' => 4
			)
		),
		
		'embed' => array(
			'file' => 'Text/Wiki/Rule/embed.php',
			'name' => 'Text_Wiki_Rule_embed',
			'flag' => false,
			'conf' => array(
				'base' => '/path/to/scripts/'
			)
		),
		
		'image' => array(
			'file' => 'Text/Wiki/Rule/image.php',
			'name' => 'Text_Wiki_Rule_image',
			'flag' => true,
			'conf' => array(
				'base' => '/path/to/images/'
			)
		),
		
		'phplookup' => array(
			'file' => 'Text/Wiki/Rule/phplookup.php',
			'name' => 'Text_Wiki_Rule_phplookup',
			'flag' => true,
			'conf' => array()
		),
		
		'toc' => array(
			'file' => 'Text/Wiki/Rule/toc.php',
			'name' => 'Text_Wiki_Rule_toc',
			'flag' => true,
			'conf' => array()
		),
		
		'newline' => array(
			'file' => 'Text/Wiki/Rule/newline.php',
			'name' => 'Text_Wiki_Rule_newline',
			'flag' => true,
			'conf' => array(
				'skip' => array(
					'code',
					'phpcode',
					'heading',
					'horiz',
					'deflist',
					'table',
					'list',
					'toc'
				)
			)
		),
		
		'center' => array(
			'file' => 'Text/Wiki/Rule/center.php',
			'name' => 'Text_Wiki_Rule_center',
			'flag' => true,
			'conf' => array()
		),
		
		'paragraph' => array(
			'file' => 'Text/Wiki/Rule/paragraph.php',
			'name' => 'Text_Wiki_Rule_paragraph',
			'flag' => true,
			'conf' => array(
				'skip' => array(
					'blockquote',
					'code',
					'phpcode',
					'heading',
					'horiz',
					'deflist',
					'table',
					'list',
					'toc'
				)
			)
		),
		
		'url' => array(
			'file' => 'Text/Wiki/Rule/url.php',
			'name' => 'Text_Wiki_Rule_url',
			'flag' => true,
			'conf' => array(
				'target' => '_blank',
				'images' => true
			)
		),
		
		'freelink' => array(
			'file' => 'Text/Wiki/Rule/freelink.php',
			'name' => 'Text_Wiki_Rule_freelink',
			'flag' => true,
			'conf' => array(
				'pages'	   => array(),
				'view_url' => 'http://example.com/index.php?page=%s',
				'new_url'  => 'http://example.com/new.php?page=%s',
				'new_text' => '?'
			)
		),
		
		'interwiki' => array(
			'file' => 'Text/Wiki/Rule/interwiki.php',
			'name' => 'Text_Wiki_Rule_interwiki',
			'flag' => true,
			'conf' => array(
				'sites' => array(
					'MeatBall' => 'http://www.usemod.com/cgi-bin/mb.pl?%s',
					'Advogato' => 'http://advogato.org/%s',
					'Wiki'	   => 'http://c2.com/cgi/wiki?%s'
				),
				'target' => '_blank'
			)
		),
		
		'wikilink' => array(
			'file' => 'Text/Wiki/Rule/wikilink.php',
			'name' => 'Text_Wiki_Rule_wikilink',
			'flag' => true,
			'conf' => array(
				'pages'	   => array(),
				'numbers'  => false,
				'view_url' => 'http://example.com/index.php?page=%s',
				'new_url'  => 'http://example.com/new.php?page=%s',
				'new_text' => '?'
			)
		),
		
		'colortext' => array(
			'file' => 'Text/Wiki/Rule/colortext.php',
			'name' => 'Text_Wiki_Rule_colortext',
			'flag' => true,
			'conf' => array()
		),
		
		'strong' => array(
			'file' => 'Text/Wiki/Rule/strong.php',
			'name' => 'Text_Wiki_Rule_strong',
			'flag' => true,
			'conf' => array()
		),
		
		'bold' => array(
			'file' => 'Text/Wiki/Rule/bold.php',
			'name' => 'Text_Wiki_Rule_bold',
			'flag' => true,
			'conf' => array()
		),
		
		'emphasis' => array(
			'file' => 'Text/Wiki/Rule/emphasis.php',
			'name' => 'Text_Wiki_Rule_emphasis',
			'flag' => true,
			'conf' => array()
		),
		
		'italic' => array(
			'file' => 'Text/Wiki/Rule/italic.php',
			'name' => 'Text_Wiki_Rule_italic',
			'flag' => true,
			'conf' => array()
		),
		
		'tt' => array(
			'file' => 'Text/Wiki/Rule/tt.php',
			'name' => 'Text_Wiki_Rule_tt',
			'flag' => true,
			'conf' => array()
		),
		
		'superscript' => array(
			'file' => 'Text/Wiki/Rule/superscript.php',
			'name' => 'Text_Wiki_Rule_superscript',
			'flag' => true,
			'conf' => array()
		),
		
		'revise' => array(
			'file' => 'Text/Wiki/Rule/revise.php',
			'name' => 'Text_Wiki_Rule_revise',
			'flag' => true,
			'conf' => array()
		),
		
		'tighten' => array(
			'file' => 'Text/Wiki/Rule/tighten.php',
			'name' => 'Text_Wiki_Rule_tighten',
			'flag' => true,
			'conf' => array()
		),
		
		'entities' => array(
			'file' => 'Text/Wiki/Rule/entities.php',
			'name' => 'Text_Wiki_Rule_entities',
			'flag' => true,
			'conf' => array()
		)
	);


	/**
	* 
	* The delimiter that surrounds a token number embedded in the source
	* wiki text.
	* 
	* @access public
	* 
	* @var string
	* 
	*/
	
	var $delim = "\xFF"; 
	
	
	/**
	* 
	* An array of tokens generated by rules as the source text is
	* parsed.
	* 
	* As Text_Wiki applies rule classes to the source text, it will
	* replace portions of the text with a delimited token number.  This
	* is the array of those tokens, representing the replaced text and
	* any options set by the parser for that replaced text.
	* 
	* The tokens array is seqential; each element is itself a sequential
	* array where element 0 is the name of the rule that generated the
	* token, and element 1 is an associative array where the key is an
	* option name and the value is an option value.
	* 
	* @access private
	* 
	* @var string
	* 
	*/
	
	var $_tokens = array();
	
	
	/**
	* 
	* The source text to which rules will be applied.  This text will be
	* transformed in-place, which means that it will change as the rules
	* are applied.
	* 
	* @access private
	* 
	* @var string
	* 
	*/
	
	var $_source = '';
	
	
	/**
	* 
	* Text_Wiki creates one instance of every rule that is applied to
	* the source text; this array holds those instances.  The array key
	* is the rule name, and the array value is an instance of the rule
	* class.
	* 
	* @access private
	* 
	* @var string
	* 
	*/
	
	var $_rule_obj = array();
	
	
	/**
	* 
	* Constructor.  Loads the rule objects.
	* 
	* @access public
	* 
	* @param array $rules The set of rules to load for this object.
	*	 
	*/
	
	function Text_Wiki($rules = null)
	{
		// set up the list of rules
		if (is_array($rules)) {
			$this->rules = $rules;
		}
	}
	
	
	/**
	* 
	* Inserts a rule into to the rule set.
	* 
	* @access public
	* 
	* @param string $key The key name for the rule.  Should be different from
	* all other keys in the rule set.
	* 
	* @param string $val The rule values; should be an associative array with 
	* the keys 'file', 'name', 'flag', and 'conf'.
	* 
	* @param string $tgt The rule after which to insert this new rule.  By
	* default (null) the rule is inserted at the end; if set to '', inserts
	* at the beginning.
	* 
	* @return void
	* 
	*/
	
	function insertRule($key, $val, $tgt = null)
	{
		// does the rule key to be inserted already exist?
		if (isset($this->rules[$key])) {
			// yes, return
			return false;
		}
		
		// the target name is not null, not '', but does not exist. this
		// means we're trying to insert after a target key, but the
		// target key isn't there.
		if (! is_null($tgt) && $tgt != '' && ! isset($this->rules[$tgt])) {
			return false;
		}
		
		// if $tgt is null, insert at the end.  We know this is at the
		// end (instead of resetting an existing rule) becuase we exited
		// at the top of this method if the rule was already in place.
		if (is_null($tgt)) {
			$this->rules[$key] = $val;
			return true;
		}
		
		// save a copy of the current rules, then reset the rule set
		// so we can insert in the proper place later.
		$tmp = $this->rules;
		$this->rules = array();
		
		// where to insert the rule?
		if ($tgt == '') {
			// insert at the beginning
			$this->rules[$key] = $val;
			foreach ($tmp as $k => $v) {
				$this->rules[$k] = $v;
			}
			return true;
		} else {
			// insert after the named rule
			foreach ($tmp as $k => $v) {
				$this->rules[$k] = $v;
				if ($k == $tgt) {
					$this->rules[$key] = $val;
				}
			}
		}
		return true;
	}
	
	
	/**
	* 
	* Delete (remove or unset) a rule from the $rules property.
	* 
	* @access public
	* 
	* @param string $rule The name of the rule to remove.
	* 
	* @return void
	*	 
	*/
	
	function deleteRule($key)
	{
		unset($this->rules[$key]);
	}
	
	
	/**
	* 
	* Sets the value of a rule's configuration keys.
	* 
	* @access public
	* 
	* @param string $rule The name of the rule for which to set
	* configuration keys.
	* 
	* @param array|string $arg1 If an array, sets the entire 'conf' key
	* for the rule; if a string, specifies which 'conf' subkey to set.
	* 
	* @param mixed $arg2 If $arg1 is a string, the 'conf' subkey
	* specified by $arg1 is set to this value.
	* 
	* @return void
	*	 
	*/
	
	function setRuleConf($rule, $arg1, $arg2 = null)
	{
		if (! isset($this->rules[$rule])) {
			return;
		}
		
		if (! isset($this->rules[$rule]['conf'])) {
			$this->rules[$rule]['conf'] = array();
		}
		
		if (is_array($arg1)) {
			$this->rules[$rule]['conf'] = $arg1;
		} else {
			$this->rules[$rule]['conf'][$arg1] = $arg2;
		}
	}
	
	
	/**
	* 
	* Sets the value of a rule's configuration keys.
	* 
	* @access public
	* 
	* @param string $rule The name of the rule from which to get
	* configuration keys.
	* 
	* @param string $key Which 'conf' subkey to retrieve.  If null,
	* gets the entire 'conf' key for the rule.
	* 
	* @return void
	*	 
	*/
	
	function getRuleConf($rule, $key = null)
	{
		if (! isset($this->rules[$rule])) {
			return null;
		}
		
		if (! isset($this->rules[$rule]['conf'])) {
			$this->rules[$rule]['conf'] = array();
		}
		
		if (is_null($key)) {
			return $this->rules[$rule]['conf'];
		}
		
		if (! isset($this->rules[$rule]['conf'][$key])) {
			return null;
		} else {
			return $this->rules[$rule]['conf'][$key];
		}
		
	}
	
	
	/**
	* 
	* Enables a rule so that it is applied when parsing.
	* 
	* @access public
	* 
	* @param string $rule The name of the rule to enable.
	* 
	* @return void
	*	 
	*/
	
	function enableRule($rule)
	{
		if (isset($this->rules[$rule])) {
			$this->rules[$rule]['flag'] = true;
		}
	}
	
	
	/**
	* 
	* Disables a rule so that it is not applied when parsing.
	* 
	* @access public
	* 
	* @param string $rule The name of the rule to disable.
	* 
	* @return void
	*	 
	*/
	
	function disableRule($rule)
	{
		if (isset($this->rules[$rule])) {
			$this->rules[$rule]['flag'] = false;
		}
	}
	
	
	/**
	* 
	* Parses and renders the text passed to it, and returns the results.
	* 
	* First, the method parses the source text, applying rules to the
	* text as it goes.  These rules will modify the source text
	* in-place, replacing some text with delimited tokens (and
	* populating the $this->_tokens array as it goes).
	* 
	* Next, the method renders the in-place tokens into the requested
	* output format.
	* 
	* Finally, the method returns the transformed text.  Note that the
	* source text is transformed in place; once it is transformed, it is
	* no longer the same as the original source text.
	* 
	* @access public
	* 
	* @param string $text The source text to which wiki rules should be
	* applied, both for parsing and for rendering.
	* 
	* @param string $format The target output format, typically 'xhtml'.
	*  If a rule does not support a given format, the output from that
	* rule is rule-specific.
	* 
	* @return string The transformed wiki text.
	* 
	*/
	
	function transform($text, $format = 'Xhtml')
	{
		$this->parse($text);
		return $this->render($format);
	}
	
	
	/**
	* 
	* Sets the $_source text property, then parses it in place and
	* retains tokens in the $_tokens array property.
	* 
	* @access public
	* 
	* @param string $text The source text to which wiki rules should be
	* applied, both for parsing and for rendering.
	* 
	* @return void
	* 
	*/
	
	function parse($text)
	{
		// set the object property for the source text
		$this->_source = $text;
		
		// apply the parse() method of each requested rule to the source
		// text.
		foreach ($this->rules as $key => $val) {
			// if flag is not set to 'true' (active),
			// do not parse under this rule.  assume
			// that if a rule exists, but has no flag,
			// that it wants to be parsed with.
			if (! isset($val['flag']) || $val['flag'] == true) {
				$this->_loadRuleObject($key);
				$this->_rule_obj[$key]->parse();
			}
		}
	}
	
	
	/**
	* 
	* Renders tokens back into the source text, based on the requested format.
	* 
	* @access public
	* 
	* @param string $format The target output format, typically 'xhtml'.
	*  If a rule does not support a given format, the output from that
	* rule is rule-specific.
	* 
	* @return string The transformed wiki text.
	* 
	*/
	
	function render($format = 'Xhtml')
	{
		// the rendering method we're going to use from each rule
		$method = "render$format";
		
		// the eventual output text
		$output = '';
		
		// when passing through the parsed source text, keep track of when
		// we are in a delimited section
		$in_delim = false;
		
		// when in a delimited section, capture the token key number
		$key = '';
		
		// pass through the parsed source text character by character
		$k = strlen($this->_source);
		for ($i = 0; $i < $k; $i++) {
			
			// the current character
			$char = $this->_source{$i};
			
			// are alredy in a delimited section?
			if ($in_delim) {
			
				// yes; are we ending the section?
				if ($char == $this->delim) {
					
					// yes, get the replacement text for the delimited
					// token number and unset the flag.
					$key = (int)$key;
					$rule = $this->_tokens[$key][0];
					$opts = $this->_tokens[$key][1];
					$output .= $this->_rule_obj[$rule]->$method($opts);
					$in_delim = false;
					
				} else {
				
					// no, add to the dlimited token key number
					$key .= $char;
					
				}
				
			} else {
				
				// not currently in a delimited section.
				// are we starting into a delimited section?
				if ($char == $this->delim) {
					// yes, reset the previous key and
					// set the flag.
					$key = '';
					$in_delim = true;
				} else {
					// no, add to the output as-is
					$output .= $char;
				}
			}
		}
		
		// return the rendered source text
		return $output;
	}
	
	
	/**
	* 
	* Returns the parsed source text with delimited token placeholders.
	* 
	* @access public
	* 
	* @return string The parsed source text.
	* 
	*/
	
	function getSource()
	{
		return $this->_source;
	}
	
	
	/**
	* 
	* Returns tokens that have been parsed out of the source text.
	* 
	* @access public
	* 
	* @param array $rules If an array of rule names is passed, only return
	* tokens matching these rule names.  If no array is passed, return all
	* tokens.
	* 
	* @return array An array of tokens.
	* 
	*/
	
	function getTokens($rules = null)
	{
		if (is_null($rules)) {
			return $this->_tokens;
		} else {
			settype($rules, 'array');
			$result = array();
			foreach ($this->_tokens as $key => $val) {
				if (in_array($val[0], $rules)) {
					$result[] = $val;
				}
			}
			return $result;
		}
	}
	
	
	/**
	* 
	* Loads a rule class file and creates an instance of it.
	* 
	* @access public
	* 
	* @return void
	* 
	*/
	
	function _loadRuleObject($key)
	{
		$name = $this->rules[$key]['name'];
		if (! class_exists($name)) {
			include_once $this->rules[$key]['file'];
		}
		$this->_rule_obj[$key] =& new $name($this, $key);
	}
}

?>