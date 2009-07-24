<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * Mediawiki: Parses for heading text.
 *
 * PHP versions 4 and 5
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Paul M. Jones <pmjones@php.net>
 * @author     Moritz Venn <ritzmo@php.net>
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    CVS: $Id$
 * @link       http://pear.php.net/package/Text_Wiki
 */

/**
 * Parses for heading text.
 * 
 * This class implements a Text_Wiki_Parse to find source text marked to
 * be a heading element, as defined by text on a line by itself prefixed
 * with a number of plus signs (+). The heading text itself is left in
 * the source, but is prefixed and suffixed with delimited tokens marking
 * the start and end of the heading.
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Justin Patrin <papercrane@reversefold.com>
 * @author     Paul M. Jones <pmjones@php.net>
 * @author     Moritz Venn <ritzmo@php.net>
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/Text_Wiki
 * @see        Text_Wiki_Parse::Text_Wiki_Parse()
 */
class Text_Wiki_Parse_Heading extends Text_Wiki_Parse {
    
    /**
    * The regular expression used to parse the source text and find
    * matches conforming to this rule.  Used by the parse() method.
    * 
    * @access public
    * @var string
    * @see parse()
    */
    var $regex = '/^(={1,6})(.*?)\1(?=\s|$)/m';
    
    var $conf = array(
        'id_prefix' => 'toc'
    );
    
    /**
    * Generates a replacement for the matched text.  Token options are:
    * 'type' => ['start'|'end'] The starting or ending point of the
    * heading text.  The text itself is left in the source.
    * 
    * @access public
    * @param array &$matches The array of matches from parse().
    * @return string A pair of delimited tokens to be used as a
    * placeholder in the source text surrounding the heading text.
    */
    function process(&$matches)
    {
        // keep a running count for header IDs.  we use this later
        // when constructing TOC entries, etc.
        static $id;
        if (! isset($id)) {
            $id = 0;
        }
        
        $prefix = htmlspecialchars($this->getConf('id_prefix'));
        
        $start = $this->wiki->addToken(
            $this->rule, 
            array(
                'type' => 'start',
                'level' => strlen($matches[1]),
                'text' => trim($matches[2]),
                'id' => $prefix . $id ++
            )
        );
        
        $end = $this->wiki->addToken(
            $this->rule, 
            array(
                'type' => 'end',
                'level' => strlen($matches[1])
            )
        );
        
        return $start . trim($matches[2]) . $end . "\n";
    }
}
?>
