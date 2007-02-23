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
        $this->wiki->source = preg_replace($find, $replace, $this->wiki->source);

        // trim lines with only one dash
        $find = "/\n\-\n/";
        $replace = "\n\n";
        $this->wiki->source = preg_replace($find, $replace, $this->wiki->source);

        // finally, compress all instances of 3 or more newlines
        // down to two newlines.
        $find = "/\n{3,}/m";
        $replace = "\n\n";
        $this->wiki->source = preg_replace($find, $replace, $this->wiki->source);
            
        // make ordinal numbers superscripted
        $find = "/(?<![\w])([\d]+)([^\W\d_]+)/";
        $replace = "$1^^$2^^";
        $this->wiki->source = preg_replace($find, $replace, $this->wiki->source);

        // numbers in parentesis are footnotes and references
        /*$find = "/\(([\d]+)\)/";
        $replace = "[$1]";
        $this->wiki->source = preg_replace($find, $replace, $this->wiki->source);*/
    }

}
?>