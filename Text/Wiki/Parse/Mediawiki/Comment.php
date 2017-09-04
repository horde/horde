<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * Mediawiki: Parses for Comments.
 *
 * PHP versions 4 and 5
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Brian J. Sipos <bjs5075@rit.edu>
 * @author     Moritz Venn <ritzmo@php.net>
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    CVS: $Id$
 * @link       http://pear.php.net/package/Text_Wiki
 */

/**
 * Parses for Comments in the Source text.
 *
 * This class implements a Text_Wiki_Parse to remove editor comments in the
 * source text.
 *
 * @category   Text
 * @package    Text_Wiki
 * @author Brian J. Sipos <bjs5075@rit.edu>
 * @author     Moritz Venn <ritzmo@php.net>
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/Text_Wiki
 * @see        Text_Wiki_Parse::Text_Wiki_Parse()
 */
class Text_Wiki_Parse_Comment extends Text_Wiki_Parse {

    /**
    * The regular expression used to parse the source text and find
    * matches conforming to this rule.  Used by the parse() method.
    *
    * @access public
    * @var string
    * @see parse()
    */
    var $regex = '/<\!--(.*?)-->/Us';


    /**
    * Generates a replacement token for the matched text.
    *
    * @access public
    * @param array &$matches The array of matches from parse().
    * @return string A delimited token to be used as a placeholder in
    * the source text.
    */
    function process(&$matches)
    {
        return '';
    }
}

?>
