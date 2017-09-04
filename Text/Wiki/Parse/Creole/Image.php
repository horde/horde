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
     * Constructor.  Overrides the Text_Wiki_Parse constructor so that we
     * can set the $regex property dynamically (we need to include the
     * Text_Wiki $delim character).
     *
     * @param object &$obj The calling "parent" Text_Wiki object.
     *
     * @param string $name The token name to use for this rule.
     *
     */

    function Text_Wiki_Parse_Image(&$obj)
    {
        parent::Text_Wiki_Parse($obj);
        $this->regex = '/{{([^' . $this->wiki->delim . ']*)(\|([^' . $this->wiki->delim . ']*))?}}/U';
    }


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
		$src = ltrim($src, '/');
        $alt = isset($matches[3]) ? trim($matches[3]) : $src;

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