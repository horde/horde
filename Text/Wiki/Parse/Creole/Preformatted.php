<?php

/**
 *
 * Parses for preformatted text.
 *
 * @category Text
 *
 * @package Text_Wiki
 *
 * @author Tomaiuolo Michele <tomamic@yahoo.it>
 *
 * @license LGPL
 *
 * @version $Id$
 *
 */

class Text_Wiki_Parse_Preformatted extends Text_Wiki_Parse {


    /**
     *
     * The regular expression used to parse the source text and find
     * matches conforming to this rule. Used by the parse() method.
     *
     * @access public
     *
     * @var string
     *
     * @see parse()
     *
     */

    var $regex = '/\n{{{\n(.*)\n}}}\n/Us';

    /**
     *
     * Generates a replacement for the matched text. Token options are:
     *
     * 'text' => The preformatted text.
     *
     * @access public
     *
     * @param array &$matches The array of matches from parse().
     *
     * @return string A token to be used as a placeholder
     * in the source text for the preformatted text.
     *
     */

    function process(&$matches)
    {
        $token = $this->wiki->addToken(
            $this->rule,
            array('text' => htmlentities($matches[1]))
        );
        return "\n\n" . $token . "\n\n";
    }
}
?>
