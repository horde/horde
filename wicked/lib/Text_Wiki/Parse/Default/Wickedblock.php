<?php
/**
 * $Horde: wicked/lib/Text_Wiki/Parse/Default/Wickedblock.php,v 1.1 2007/05/23 21:43:30 ben Exp $
 *
 * This parser parses Wicked blocks, which add Horde_Blocks to the
 * page.  Basic syntax is [[block block-app/block-name block-args]].
 *
 * @package Wicked
 */
class Text_Wiki_Parse_Wickedblock extends Text_Wiki_Parse {

    /**
     * The regular expression used to find blocks.
     *
     * @access public
     *
     * @var string
     */
    var $regex = "/\[\[block (.*)?\]\]/sU";

    /**
     * Generates a token entry for the matched text. Token options are:
     *
     * 'src'  => The image source, typically a relative path name.
     * 'opts' => Any macro options following the source.
     *
     * @access public
     *
     * @param array &$matches  The array of matches from parse().
     *
     * @return  A delimited token number to be used as a placeholder in
     *          the source text.
     */
    function process(&$matches)
    {
    }

}
