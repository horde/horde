<?php

/**
 *
 * Trim lines in the source text and compress 3 or more newlines to
 * 2 newlines.
 *
 * @category Text
 *
 * @package Text_Wiki
 *
 * @author Paul M. Jones <pmjones@php.net>
 * @author Michele Tomaiuolo <tomamic@yahoo.it>
 *
 */

class Text_Wiki_Parse_Trim extends Text_Wiki_Parse {


    /**
     *
     * Simple parsing method.
     *
     * @access public
     *
     */

    function parse()
    {
        // trim lines
        $find = "/ *\n */";
        $replace = "\n";
        $this->wiki->source = preg_replace($find, $replace,
            $this->wiki->source);

        // make ordinal numbers superscripted
        $this->wiki->source = preg_replace("/(?<![\w])([\d]+)([^\W\d_]+)/", "$1^^$2^^",
            $this->wiki->source);

        // finally, compress all instances of 3 or more newlines
        // down to two newlines.
        $find = "/\n{3,}/m";
        $replace = "\n\n";
        $this->wiki->source = preg_replace($find, $replace,
            $this->wiki->source);
    }

}
?>