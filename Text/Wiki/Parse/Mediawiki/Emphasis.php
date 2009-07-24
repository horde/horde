<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * Mediawiki: Parses for emphazised text.
 *
 * Text_Wiki rule parser to find source text emphazised
 * as defined by text surrounded by repeated single quotes  ''...'' and more
 * Translated are ''emphasis'' , '''strong''' or '''''both''''' ...
 *
 * PHP versions 4 and 5
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Bertrand Gugger <bertrand@toggg.com>
 * @author     Paul M. Jones <pmjones@php.net>
 * @copyright  2005 bertrand Gugger
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    CVS: $Id$
 * @link       http://pear.php.net/package/Text_Wiki
 */

/**
 * Emphazised text rule parser class for Mediawiki. Makes Emphasis, Strong or both
 * This class implements a Text_Wiki_Parse to find source text marked for
 * emphasis, stronger and very as defined by text surrounded by 2,3 or 5 single-quotes.
 * On parsing, the text itself is left in place, but the starting and ending
 * instances of the single-quotes are replaced with tokens.
 *
 * This class does not follow the standard structure of other Text_Wiki_Parse class and 
 * is heavily based on the code from Mediawiki (Parser::doAllQuotes() and Parser::doQuotes())
 * that handles emphazised text
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Bertrand Gugger <bertrand@toggg.com>
 * @copyright  2005 bertrand Gugger
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/Text_Wiki
 * @see        Text_Wiki_Parse::Text_Wiki_Parse()
 */
class Text_Wiki_Parse_Emphasis extends Text_Wiki_Parse {

    /**
     * Split $this->wiki->source by line break and call $this->process()
     * for each line of the source text
     *
     * @access public
     * @return void
     */
    function parse() {
        $lines = explode("\n", $this->wiki->source);
        $this->wiki->source = '';
        foreach ($lines as $line) {
            $this->wiki->source .= $this->process($line) . "\n";
        }
        $this->wiki->source = substr($this->wiki->source, 0, -1);
    }

    /**
     * Generates a replacement for the matched text.  Token options are:
     * - 'type' => ['start'|'end'] The starting or ending point of the emphasized text.
     * Generated tokens are Emphasis (this rule), Strong or Emphasis / Strong
     * The text itself is left in the source but may content bested blocks
     *
     * This function is basically a copy of Parser::doQuotes() from the Mediawiki software.
     * The algorithm is the same but instead of replacing the syntax by HTML we replace by
     * tokens
     *
     * @param string $text a line from $this->wiki->source
     * @return string $output the source line with the wiki syntax replaced by tokens
     */
    function process($text) {
        $arr = preg_split("/(''+)/", $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        if (count($arr) == 1) {
            return $text;
        } else {
            # First, do some preliminary work. This may shift some apostrophes from
            # being mark-up to being text. It also counts the number of occurrences
            # of bold and italics mark-ups.
            $i = 0;
            $numbold = 0;
            $numitalics = 0;
            foreach ($arr as $r) {
                if (($i % 2) == 1) {
                    # If there are ever four apostrophes, assume the first is supposed to
                    # be text, and the remaining three constitute mark-up for bold text.
                    if (strlen($arr[$i]) == 4) {
                        $arr[$i-1] .= "'";
                        $arr[$i] = "'''";
                    }
                    # If there are more than 5 apostrophes in a row, assume they're all
                    # text except for the last 5.
                    else if (strlen( $arr[$i]) > 5 ) {
                        $arr[$i-1] .= str_repeat("'", strlen($arr[$i]) - 5);
                        $arr[$i] = "'''''";
                    }
                    # Count the number of occurrences of bold and italics mark-ups.
                    # We are not counting sequences of five apostrophes.
                    if (strlen($arr[$i]) == 2) 
                        $numitalics++;
                    else if (strlen($arr[$i]) == 3)
                        $numbold++;
                    else if (strlen($arr[$i]) == 5)
                        $numitalics++; $numbold++;
                }
                $i++;
            }

            # If there is an odd number of both bold and italics, it is likely
            # that one of the bold ones was meant to be an apostrophe followed
            # by italics. Which one we cannot know for certain, but it is more
            # likely to be one that has a single-letter word before it.
            if (($numbold % 2 == 1) && ($numitalics % 2 == 1)) {
                $i = 0;
                $firstsingleletterword = -1;
                $firstmultiletterword = -1;
                $firstspace = -1;
                foreach ($arr as $r) {
                    if (( $i % 2 == 1) and (strlen($r) == 3)) {
                        $x1 = substr($arr[$i-1], -1);
                        $x2 = substr($arr[$i-1], -2, 1);
                        if ($x1 === ' ') {
                            if ($firstspace == -1) $firstspace = $i;
                        } else if ($x2 === ' ') {
                            if ($firstsingleletterword == -1) $firstsingleletterword = $i;
                        } else {
                            if ($firstmultiletterword == -1) $firstmultiletterword = $i;
                        }
                    }
                    $i++;
                }

                # If there is a single-letter word, use it!
                if ($firstsingleletterword > -1) {
                    $arr[$firstsingleletterword] = "''";
                    $arr[$firstsingleletterword-1] .= "'";
                }
                # If not, but there's a multi-letter word, use that one.
                else if ($firstmultiletterword > -1) {
                    $arr[$firstmultiletterword] = "''";
                    $arr[$firstmultiletterword-1] .= "'";
                }
                # ... otherwise use the first one that has neither.
                # (notice that it is possible for all three to be -1 if, for example,
                # there is only one pentuple-apostrophe in the line)
                else if ($firstspace > -1) {
                    $arr[$firstspace] = "''";
                    $arr[$firstspace-1] .= "'";
                }
            }

            # Now let's actually convert our apostrophic mush to HTML!
            $output = '';
            $buffer = '';
            $state = '';
            $i = 0;
            foreach ($arr as $r) {
                if (($i % 2) == 0) {
                    if ($state === 'both')
                        $buffer .= $r;
                    else
                        $output .= $r;
                } else {
                    if (strlen($r) == 2) {
                        if ($state === 'i') { 
                            $output .= $this->wiki->addToken($this->rule, array('type' => 'end'));
                            $state = '';
                        } else if ($state === 'bi')    {
                            $output .= $this->wiki->addToken($this->rule, array('type' => 'end'));
                            $state = 'b';
                        } else if ($state === 'ib')    {
                            $output .= $this->wiki->addToken('Strong', array('type' => 'end'));
                            $output .= $this->wiki->addToken($this->rule, array('type' => 'end'));
                            $output .= $this->wiki->addToken('Strong', array('type' => 'start'));
                            $state = 'b';
                        } else if ($state === 'both') {
                            $output .= $this->wiki->addToken('Strong', array('type' => 'start'));
                            $output .= $this->wiki->addToken($this->rule, array('type' => 'start'));
                            $output .= $buffer;
                            $output .= $this->wiki->addToken($this->rule, array('type' => 'end'));
                            $state = 'b';
                        } else {
                            # $state can be 'b' or ''
                            $output .= $this->wiki->addToken($this->rule, array('type' => 'start'));
                            $state .= 'i';
                        }
                    } else if (strlen($r) == 3)    {
                        if ($state === 'b')    {
                            $output .= $this->wiki->addToken('Strong', array('type' => 'end'));
                            $state = '';
                        } else if ($state === 'bi')    {
                            $output .= $this->wiki->addToken($this->rule, array('type' => 'end'));
                            $output .= $this->wiki->addToken('Strong', array('type' => 'end'));
                            $output .= $this->wiki->addToken($this->rule, array('type' => 'start'));
                            $state = 'i';
                        } else if ($state === 'ib') {
                            $output .= $this->wiki->addToken('Strong', array('type' => 'end'));
                            $state = 'i';
                        } else if ($state === 'both') {
                            $output .= $this->wiki->addToken($this->rule, array('type' => 'start'));
                            $output .= $this->wiki->addToken('Strong', array('type' => 'start'));
                            $output .= $buffer;
                            $output .= $this->wiki->addToken('Strong', array('type' => 'end'));
                            $state = 'i';
                        } else {
                            # $state can be 'i' or ''
                            $output .= $this->wiki->addToken('Strong', array('type' => 'start'));
                            $state .= 'b';
                        }
                    } else if (strlen($r) == 5) {
                        if ($state === 'b') {
                            $output .= $this->wiki->addToken('Strong', array('type' => 'end'));
                            $output .= $this->wiki->addToken($this->rule, array('type' => 'start'));
                            $state = 'i';
                        } else if ($state === 'i') {
                            $output .= $this->wiki->addToken($this->rule, array('type' => 'end'));
                            $output .= $this->wiki->addToken('Strong', array('type' => 'start'));
                            $state = 'b';
                        } else if ($state === 'bi')    {
                            $output .= $this->wiki->addToken($this->rule, array('type' => 'end'));
                            $output .= $this->wiki->addToken('Strong', array('type' => 'end'));
                            $state = '';
                        } else if ($state === 'ib') {
                            $output .= $this->wiki->addToken('Strong', array('type' => 'end'));
                            $output .= $this->wiki->addToken($this->rule, array('type' => 'end'));
                            $state = '';
                        } else if ($state === 'both') {
                            $output .= $this->wiki->addToken($this->rule, array('type' => 'start'));
                            $output .= $this->wiki->addToken('Strong', array('type' => 'start'));
                            $output .= $buffer;
                            $output .= $this->wiki->addToken('Strong', array('type' => 'end'));
                            $output .= $this->wiki->addToken($this->rule, array('type' => 'end'));
                            $state = '';
                        } else {
                            # ($state == '')
                            $buffer = '';
                            $state = 'both';
                        }
                    }
                }
                $i++;
            }
            # Now close all remaining tags.  Notice that the order is important.
            if ($state === 'b' || $state === 'ib')
                $output .= $this->wiki->addToken('Strong', array('type' => 'end'));
            if ($state === 'i' || $state === 'bi' || $state === 'ib')
                $output .= $this->wiki->addToken($this->rule, array('type' => 'end'));
            if ($state === 'bi')
                $output .= $this->wiki->addToken('Strong', array('type' => 'end'));
            # There might be lonely ''''', so make sure we have a buffer
            if ($state === 'both' && $buffer) {
                $output .= $this->wiki->addToken('Strong', array('type' => 'start'));
                $output .= $this->wiki->addToken($this->rule, array('type' => 'start'));
                $output .= $buffer;
                $output .= $this->wiki->addToken($this->rule, array('type' => 'end'));
                $output .= $this->wiki->addToken('Strong', array('type' => 'end'));
            }
            return $output;
        }
    }

}
?>
