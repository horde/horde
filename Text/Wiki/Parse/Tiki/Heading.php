<?php

/**
* 
* Parses for heading text.
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
* Parses for heading text.
* 
* This class implements a Text_Wiki_Parse to find source text marked to
* be a heading element, as defined by text on a line by itself prefixed
* with a number of plus signs (+). The heading text itself is left in
* the source, but is prefixed and suffixed with delimited tokens marking
* the start and end of the heading.
*
* @category Text
* 
* @package Text_Wiki
* 
* @author Paul M. Jones <pmjones@php.net>
* 
*/

class Text_Wiki_Parse_Heading extends Text_Wiki_Parse {
    
    
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

    //TODO: add support for expandable / contractable sections (-+ after !)    
    var $regex = '/(^|\n)(!{1,6})([-+]?)([^\n]*)(.*?)(?=\n!|$)/s';
    
    var $conf = array(
        'id_prefix' => 'toc'
    );
    
    /**
    * 
    * Generates a replacement for the matched text.  Token options are:
    * 
    * 'type' => ['start'|'end'] The starting or ending point of the
    * heading text.  The text itself is left in the source.
    * 
    * @access public
    *
    * @param array &$matches The array of matches from parse().
    *
    * @return string A pair of delimited tokens to be used as a
    * placeholder in the source text surrounding the heading text.
    *
    */
    
    function process(&$matches)
    {
        // keep a running count for header IDs.  we use this later
        // when constructing TOC entries, etc.
        static $id;
        if (!isset($id)) {
            $id = 0;
        } else {
            ++$id;
        }

        $prefix = htmlspecialchars($this->getConf('id_prefix'));
        $collapse = $matches[3] ? ($matches[3] == '-') : null;
        return $matches[1].
            $this->wiki->addToken(
                                  $this->rule, 
                                  array(
                                        'type' => 'start',
                                        'level' => strlen($matches[2]),
                                        'text' => $matches[4],
                                        'id' => $prefix.$id,
                                        'collapse' => $collapse,
                                        )
                                  ).
            $matches[4].
            $this->wiki->addToken(
                                  $this->rule, 
                                  array(
                                        'type' => 'end',
                                        'text' => $matches[4],
                                        'level' => strlen($matches[2]),
                                        'collapse' => $collapse,
                                        'id' => $prefix.$id,
                                        )
                                  ).
            $this->wiki->addToken($this->rule,
                                  array(
                                        'type' =>'startContent',
                                        'id' => $prefix.$id,
                                        'level' => strlen($matches[2]),
                                        'collapse' => $collapse,
                                        'text' => $matches[4],
                                        )
                                  ).
            $matches[5].
            $this->wiki->addToken($this->rule,
                                  array(
                                        'type' => 'endContent',
                                        'collapse' => $collapse,
                                        'level' => strlen($matches[2]),
                                        'id' => $prefix.$id,
                                        'text' => $matches[4],
                                        )
                                  );
    }
}
?>