<?php
// $Id$


/**
* 
* This class implements a Text_Wiki_Parse to find all heading tokens and
* build a table of contents.  The [[toc]] tag gets replaced with a list
* of all the level-2 through level-6 headings.
*
* @author Paul M. Jones <pmjones@ciaweb.net>
*
* @package Text_Wiki
*
*/


class Text_Wiki_Parse_Toc extends Text_Wiki_Parse {
	
	
	/**
	* 
	* The regular expression used to parse the source text and find
	* matches conforming to this rule.  Used by the parse() method.
	* 
	* @access public
	* 
	* @var string
	* 
	* @see parse()
	* 
	*/
	
	var $regex = "/\[\[toc\]\]/m";
	
	
	/**
	* 
	* The collection of headings (text and levels).
	* 
	* @access public
	* 
	* @var array
	* 
	* @see _getEntries()
	* 
	*/
	
	var $entry = array();
	
	
	/**
	* 
	* Custom parsing (have to process heading entries first).
	* 
	* @access public
	* 
	* @see Text_Wiki::parse()
	* 
	*/
	
	function parse()
	{
		// have to get all the heading entries before we can parse properly.
		$this->_getEntries();
		
		// now parse the source text for TOC entries
		parent::parse();
	}
	
	
	/**
	* 
	* Generates a replacement for the matched text.
	*  
	* Token options are:
	* 
	* 'type' => ['list_start'|'list_end'|'item_start'|'item_end'|'target']
	*
	* 'level' => The heading level (1-6).
	*
	* 'count' => Which entry number this is in the list.
	* 
	* @access public
	*
	* @param array &$matches The array of matches from parse().
	*
	* @return string A token indicating the TOC collection point.
	*
	*/
	
	function process(&$matches)
	{
		$output = $this->wiki->addToken(
			$this->rule,
			array('type' => 'list_start')
		);
		
		foreach ($this->entry as $key => $val) {
		
			$options = array(
				'type' => 'item_start',
				'count' => $val['count'],
				'level' => $val['level']
			);
			
			$output .= $this->wiki->addToken($this->rule, $options);
			
			$output .= $val['text'];
			
			$output .= $this->wiki->addToken(
				$this->rule,
				array('type' => 'item_end')
			);
		}
		
		$output .= $this->wiki->addToken(
			$this->rule, array('type' => 'list_end')
		);
		
		return $output;
	}
	
	
	/**
	* 
	* Finds all headings in the text and saves them in $this->entry.
	* 
	* @access private
	*
	* @return void
	* 
	*/
	
	function _getEntries()
	{
		// the wiki delimiter
		$delim = $this->wiki->delim;
		
		// list of all TOC entries (h2, h3, etc)
		$this->entry = array();
		
		// the new source text with TOC entry tokens
		$newsrc = '';
		
		// when passing through the parsed source text, keep track of when
		// we are in a delimited section
		$in_delim = false;
		
		// when in a delimited section, capture the token key number
		$key = '';
		
		// TOC entry count
		$count = 0;
		
		// pass through the parsed source text character by character
		$k = strlen($this->wiki->source);
		for ($i = 0; $i < $k; $i++) {
			
			// the current character
			$char = $this->wiki->source{$i};
			
			// are alredy in a delimited section?
			if ($in_delim) {
			
				// yes; are we ending the section?
				if ($char == $delim) {
					
					// yes, get the replacement text for the delimited
					// token number and unset the flag.
					$key = (int)$key;
					$rule = $this->wiki->tokens[$key][0];
					$opts = $this->wiki->tokens[$key][1];
					$in_delim = false;
					
					// is the key a start heading token
					// of level 2 or deeper?
					if ($rule == 'Heading' &&
						$opts['type'] == 'start' &&
						$opts['level'] > 1) {
						
						// yes, add a TOC target link to the
						// tokens array...
						$token = $this->wiki->addToken(
							$this->rule, 
							array(
								'type' => 'target',
								'count' => $count,
								'level' => $opts['level']
							)
						);
						
						// ... and to the new source, before the
						// heading-start token.
						$newsrc .= $token . $delim . $key . $delim;
						
						// retain the toc item
						$this->entry[] = array (
							'count' => $count,
							'level' => $opts['level'],
							'text' => $opts['text']
						);
						
						// increase the count for the next entry
						$count++;
						
					} else {
						// not a heading-start of 2 or deeper.
						// re-add the delimited token number
						// as it was in the original source.
						$newsrc .= $delim . $key . $delim;
					}
					
				} else {
				
					// no, add to the delimited token key number
					$key .= $char;
					
				}
				
			} else {
				
				// not currently in a delimited section.
				// are we starting into a delimited section?
				if ($char == $delim) {
					// yes, reset the previous key and
					// set the flag.
					$key = '';
					$in_delim = true;
				} else {
					// no, add to the output as-is
					$newsrc .= $char;
				}
			}
		}
		
		// replace entire source text with changed source text
		$this->wiki->source = $newsrc;
	}
}
?>