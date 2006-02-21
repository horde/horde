<?php

/**
*
* Parses for text marked as a code example block.
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
* Parses for text marked as a code example block.
*
* This class implements a Text_Wiki_Parse to find sections marked as code
* examples.  Blocks are marked as the string <code> on a line by itself,
* followed by the inline code example, and terminated with the string
* </code> on a line by itself.  The code example is run through the
* native PHP highlight_string() function to colorize it, then surrounded
* with <pre>...</pre> tags when rendered as XHTML.
*
* @category Text
*
* @package Text_Wiki
*
* @author Paul M. Jones <pmjones@php.net>
*
*/

class Text_Wiki_Parse_Code extends Text_Wiki_Parse {

    /**
    *
    * The regular expression used to find source text matching this
    * rule.
    *
    * @access public
    *
    * @var string
    *
    */

/*    var $regex = '/^(\<code( .+)?\>)\n(.+)\n(\<\/code\>)(\s|$)/Umsi';*/
    var $regex = ';^<code(\s[^>]*)?>((?:(?R)|.)*?)\n</code>(\s|$);msi';

    /**
    *
    * Generates a token entry for the matched text.  Token options are:
    *
    * 'text' => The full matched text, not including the <code></code> tags.
    *
    * @access public
    *
    * @param array &$matches The array of matches from parse().
    *
    * @return A delimited token number to be used as a placeholder in
    * the source text.
    *
    */

    function process(&$matches)
    {
        // are there additional attribute arguments?
        $args = trim($matches[1]);

        if ($args == '') {
            $options = array(
                'text' => $matches[2],
                'attr' => array('type' => '')
            );
        } else {
            // get the attributes...
            $attr = $this->getAttrs($args);

            // ... and make sure we have a 'type'
            if (! isset($attr['type'])) {
                $attr['type'] = '';
            }

            //
            // Check to see if the preformatted area has the type of "parsed",
            // in which case we want to parse it not just present it as a plain
            // block of text.  This means we can have wiki links, etc, within
            // a plain text block.
            //
            if ($attr['type'] == "parsed") {
                //
                // Yes, parse this block.  Find the start and end of the block.
                //
                if ($args == '') {
                    $start = $this->wiki->addToken(
                        $this->rule,
                        array('type' => 'start')
                    );

                } else {
                    // get the attributes...
                    $attr = $this->getAttrs($args);
                    // ... and make sure we have a 'type'
                    if (! isset($attr['type'])) {
                        $attr['type'] = '';
                    }
                    $start = $this->wiki->addToken(
                        $this->rule,
                        array('type' => 'start',
                            'attr' => $attr
                        )
                    );
                }

                $end = $this->wiki->addToken(
                    $this->rule,
                    array('type' => 'end',
                        'attr' => $attr)
                );

                //
                // Tokenize the body of the block.
                //
                $text = str_replace("\t", "    ", $matches[3]);
                $body = explode("\n", $text);
                $newtext = "";
                $lines = count($body);

                //
                // Add a new line to the token array.
                //
                $newline = $this->wiki->addToken(
                    'Raw',
                    array( 'text' => "\n" )
                );

                for ($line = 0; $line < $lines; $line++) {
                    $newtext .= $body[$line];
                    if ($line < $lines-1) {
                        //
                        // Don't want a newline after the last line.
                        //
                        $newtext .= $newline;
                    }
                }

                return "\n" . $start . $newtext . $end . "\n";

            } else {

                // retain the options
                $options = array(
                    'text' => $matches[2],
                    'attr' => $attr
                );
            }
        }

        return $this->wiki->addToken($this->rule, $options) . $matches[3];
    }
}
?>
