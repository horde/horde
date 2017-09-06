<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker */

// {{{ Header

/**
 * BBCode: Parses for subscript text.
 *
 * This class implements a Text_Wiki_Rule to find source text marked for
 * strong emphasis (subscript) text as defined by text surrounded by
 * [sub] ... [/sub] On parsing, the text itself is left in place, but
 * the starting and ending tags are replaced with tokens.
 *
 * PHP versions 4 and 5
 *
 * @category Text
 * @package Text_Wiki
 * @author Firman Wandayandi <firman@php.net>
 * @copyright 2005 bertrand Gugger
 * @license http://www.gnu.org/copyleft/lgpl.html
 *          GNU Lesser General Public License, version 2.1
 * @version CVS: $Id$
 */

// }}}
// {{{ Class: Text_Wiki_Parse_Subscript

/**
 * Subscript text rule parser class for BBCode.
 *
 * @category Text
 * @package Text_Wiki
 * @author Firman Wandayandi <firman@php.net>
 * @copyright 2005 bertrand Gugger
 * @license http://www.gnu.org/copyleft/lgpl.html
 *          GNU Lesser General Public License, version 2.1
 * @version Release: @package_version@
 */
class Text_Wiki_Parse_Subscript extends Text_Wiki_Parse
{
    // {{{ Properties

    /**
     * The regular expression used to parse the source text and find
     * matches conforming to this rule. Used by the parse() method.
     *
     * @access public
     * @var string
     * @see parse()
     */
    var $regex = "#\[sub](.*?)\[/sub]#i";

    // }}}
    // {{{ process()

    /**
     * Generates a replacement for the matched text.  Token options are:
     * - 'type' => ['start'|'end'] The starting or ending point of the
     * emphasized text.  The text itself is left in the source.
     *
     * @param array &$matches The array of matches from parse().
     * @return A pair of delimited tokens to be used as a placeholder in
     * the source text surrounding the text to be emphasized.
     * @access public
     */
    function process(&$matches)
    {
        $start = $this->wiki->addToken($this->rule, array('type' => 'start'));
        $end = $this->wiki->addToken($this->rule, array('type' => 'end'));
        return $start . $matches[1] . $end;
    }

    // }}}
}

// }}}

/*
 * Local variables:
 * mode: php
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */
