<?php

/**
 *
 * Parse for images in the source text.
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


class Text_Wiki_Parse_Image extends Text_Wiki_Parse {

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

    var $regex = '/{{ *(.*)(\ *| *(.*))? *}}/U';


    /**
     *
     * Generates a replacement token for the matched text.
     *
     * @access public
     *
     * @param array &$matches The array of matches from parse().
     *
     * @return string A token marking the horizontal rule.
     *
     */

    function process(&$matches)
    {
        $src = trim($matches[1]);
        $alt = trim($matches[3]);
        if (! $alt) $alt = $src;

        return $this->wiki->addToken(
            $this->rule,
            array(
                'src' => $src,
                'attr' => array('alt' => $alt, 'title' => $alt)
            )
        );
    }

}
?>