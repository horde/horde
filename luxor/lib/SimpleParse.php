<?php
/**
 * $Horde: luxor/lib/SimpleParse.php,v 1.10 2005/06/13 03:20:27 selsky Exp $
 *
 * @author  Jan Schneider <jan@horde.org>
 * @since   Luxor 0.1
 * @package Luxor
 */
class Luxor_SimpleParse {

    /** File handle. */
    var $_fileh;

    /** Current linenumber. */
    var $_line = 0;

    /** Fragments in queue. */
    var $_frags = array();

    /** Array of body type ids. */
    var $_bodyid = array();

    /** Fragment closing delimiters. */
    var $_term = array();

    /** Fragmentation regexp. */
    var $_split = '';

    /** Fragment opening regexp. */
    var $_open = '';

    /** Tab width. */
    var $_tabwidth = 8;

    /**
     * Constructor for the source code parser.
     *
     * @param ressource $file     The file handler of the file to parse.
     *
     * @param int       $tabhint  (Unused?)
     *
     * @param array     $blksep   An array containing block seperators for
     *                            this file's type.
     *                            Essentially a 'spec' entry from the
     *                            $languages array.
     */
    function Luxor_SimpleParse(&$fileh, $tabhint, $blksep)
    {
        $this->_fileh = $fileh;

        /* Get possible block opening and closing delimiters and their meaning. */
        $open_a = array();
        while ($splice = array_splice($blksep, 0, 3)) {
            $this->_bodyid[] = $splice[0];
            $open_a[]        = $splice[1];
            $this->_term[]   = $splice[2];
        }

        /* Build regexps for opening and delimiters and fragment splitting. */
        foreach ($open_a as $open_s) {
            $this->_open  .= "($open_s)|";
            $this->_split .= "$open_s|";
        }
        $this->_open = substr($this->_open, 0, -1);
        $this->_open = str_replace('/', '\\/', $this->_open);

        foreach ($this->_term as $term) {
            if (empty($term)) {
                continue;
            }
            $this->_split .= "$term|";
        }
        $this->_split = substr($this->_split, 0, -1);
        $this->_split = str_replace('/', '\\/', $this->_split);
    }

    /**
     * Returns the content and type of the next code fragment.
     */
    function nextFrag()
    {
        $btype = null;
        $frag  = null;
        $line  = '';

        while (true) {
            // read one more line if we have processed
            // all of the previously read line
            if (!count($this->_frags)) {
                $line = fgets($this->_fileh);
                $this->_line++;

                if ($this->_line <= 2 &&
                    preg_match('/^.*-[*]-.*?[ \t;]tab-width:[ \t]*([0-9]+).*-[*]-/',
                               $line, $match)) {
                    $this->_tabwidth = $match[1];
                }

                // Optimize for common case.
                if (!empty($line)) {
                    $line = preg_replace('/^(\t+)/e', "str_repeat(' ', $this->_tabwidth * strlen('\\1'))", $line);
                    if (preg_match('/([^\t]*)\t/e', $line, $match)) {
                        $tabs = str_repeat(' ', $this->_tabwidth - (strlen($match[1]) % $this->_tabwidth));
                        $line = preg_replace('/([^\t]*)\t/', '\1' . $tabs, $line);
                    }

                    // split the line into fragments
                    $this->_frags = preg_split('/(' . $this->_split . ')/', $line, -1, PREG_SPLIT_DELIM_CAPTURE);
                }
            }

            if (!count($this->_frags)) {
                break;
            }

            // skip empty fragments
            if (empty($this->_frags[0])) {
                array_shift($this->_frags);
                continue;
            }

            if (!empty($frag)) {
                // Check if we are inside a fragment
                if (!is_null($btype)) {
                    $next = array_shift($this->_frags);

                    // Some ugly special casing for escaped quotes.
                    if (substr($frag, -1, 1) == '\\' && substr($frag, -2, 2) != '\\\\' &&
                            (substr($next, 0, 1) == '"' || substr($next, 0, 1) == "'")) {
                        $frag .= $next;
                        $next = substr($next, 1);
                    } else {
                        // Add to the fragment
                        $frag .= $next;
                    }

                    // We are done if this was the terminator
                    if (preg_match('/^' . str_replace('/', '\\/', $this->_term[$btype]) . '$/', $next)) {
                        // Return what we have
                        break;
                    }
                } else {
                    // Is the start of a frag?
                    if (preg_match('/^' . $this->_open . '$/', $this->_frags[0])) {
                        // Return what we have
                        break;
                    } else {
                        // Add to the fragment and keep looking
                        $frag .= array_shift($this->_frags);
                    }
                }
            } else {
                // Find the blocktype of the current block
                $frag = array_shift($this->_frags);
                if (preg_match_all('/^' . $this->_open . '$/', $frag, $match)) {
                    array_shift($match);
                    foreach ($match as $id => $matched) {
                        if ($matched[0] == $frag) {
                            $btype = $id;
                            break;
                        }
                    }
                    if (is_null($btype)) {
                        //return the fragment as unknown.
                        break;
                    }
                }
            }
        }

        // Clear text block type
        if (!is_null($btype)) {
            $btype = $this->_bodyid[$btype];
        }
        return array($btype, $frag);
    }

}
