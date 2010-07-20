<?php
/**
 * Highlights quoted messages with different colors for the different quoting
 * levels.
 *
 * CSS class names called "quoted1" ... "quoted{$cssLevels}" must be present.
 *
 * The text to be passed in must have already been passed through
 * htmlspecialchars().
 *
 * Parameters:
 * <pre>
 * 'citeblock'  -- Display cite blocks?
 *                 DEFAULT: true
 * 'cssLevels'  -- Number of defined CSS class names.
 *                 DEFAULT: 5
 * 'hideBlocks' -- Hide large quoted text blocks by default?
 *                 DEFAULT: false
 * </pre>
 *
 * Copyright 2004-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Text_Filter
 */
class Horde_Text_Filter_Highlightquotes extends Horde_Text_Filter_Base
{
    /**
     * Filter parameters.
     *
     * @var array
     */
    protected $_params = array(
        'citeblock' => true,
        'cssLevels' => 5,
        'hideBlocks' => false
    );

    /**
     * The number of quoted lines to exceed to trigger large block
     * processing.
     *
     * @var integer
     */
    protected $_qlimit = 8;

    /**
     * Executes any code necessaray before applying the filter patterns.
     *
     * @param string $text  The text before the filtering.
     *
     * @return string  The modified text.
     */
    public function preProcess($text)
    {
        /* Tack a newline onto the beginning of the string so that we
         * correctly highlight when the first character in the string is a
         * quote character. */
        return "\n$text";
    }

    /**
     * Returns a hash with replace patterns.
     *
     * @return array  Patterns hash.
     */
    public function getPatterns()
    {
        /* Remove extra spaces before quoted text as the CSS formatting will
         * automatically add a bit of space for us. */
        return ($this->_params['citeblock'])
            ? array('regexp' => array("/<br \/>\s*\n\s*<br \/>\s*\n\s*((&gt;\s?)+)/m" => "<br />\n\\1"))
            : array();
    }

    /**
     * Executes any code necessaray after applying the filter patterns.
     *
     * @param string $text  The text after the filtering.
     *
     * @return string  The modified text.
     */
    public function postProcess($text)
    {
        /* Use cite blocks to display the different quoting levels? */
        $cb = $this->_params['citeblock'];

        /* Cite level before parsing the current line. */
        $qlevel = 0;

        /* Other loop variables. */
        $text_out = '';
        $lines = array();
        $tmp = array('level' => 0, 'lines' => array());
        $qcount = 0;

        /* Parse text line by line. */
        foreach (explode("\n", $text) as $line) {
            /* Cite level of current line. */
            $clevel = 0;
            $matches = array();

            /* Do we have a citation line? */
            if (preg_match('/^\s*((&gt;\s?)+)/m', $line, $matches)) {
                /* Count number of > characters => cite level */
                $clevel = count(preg_split('/&gt;\s?/', $matches[1])) - 1;
            }

            if ($cb && isset($matches[1])) {
                /* Strip all > characters. */
                $line = substr($line, Horde_String::length($matches[1]));
            }

            /* Is this cite level lower than the current level? */
            if ($clevel < $qlevel) {
                $lines[] = $tmp;
                if ($clevel == 0) {
                    $text_out .= $this->_process($lines, $qcount);
                    $lines = array();
                    $qcount = 0;
                }
                $tmp = array('level' => $clevel, 'lines' => array());

            /* Is this cite level higher than the current level? */
            } elseif ($clevel > $qlevel) {
                $lines[] = $tmp;
                $tmp = array('level' => $clevel, 'lines' => array());
            }

            $tmp['lines'][] = $line;
            $qlevel = $clevel;

            if ($qlevel) {
                ++$qcount;
            }
        }

        $lines[] = $tmp;
        $text_out .= $this->_process($lines, $qcount);

        /* Remove the leading newline we added above, if it's still there. */
        return ($text_out[0] == "\n")
            ? substr($text_out, 1)
            : $text_out;
    }

    /**
     * Process a batch of lines at the same quoted level.
     *
     * @param array $lines     Lines.
     * @param integer $qcount  Number of lines in quoted level.
     *
     * @return string  The rendered lines.
     */
    protected function _process($lines, $qcount)
    {
        $curr = reset($lines);
        $out = implode("\n", $this->_removeBr($curr['lines']));

        if ($qcount > $this->_qlimit) {
            $out .= $this->_beginLargeBlock($lines, $qcount);
        }

        $level = 0;

        next($lines);
        while (list(,$curr) = each($lines)) {
            if ($level > $curr['level']) {
                for ($i = $level; $i > $curr['level']; --$i) {
                    $out .= $this->_params['citeblock'] ? '</div>' : '</font>';
                }
            } else {
                for ($i = $level; $i < $curr['level']; ++$i) {
                    /* Add quote block start tags for each cite level. */
                    $out .= ($this->_params['citeblock'] ? '<div class="citation ' : '<font class="') .
                        'quoted' . (($i % $this->_params['cssLevels']) + 1) . '"' .
                        ((($i == 0) && ($qcount > $this->_qlimit) && $this->_params['hideBlocks']) ? ' style="display:none"' : '') .
                        '>';
                }
            }

            $out .= implode("\n", $this->_removeBr($curr['lines']));
            $level = $curr['level'];
        }

        for ($i = $level; $i > 0; --$i) {
            $out .= $this->_params['citeblock'] ? '</div>' : '</font>';
        }

        if ($qcount > $this->_qlimit) {
            $out .= $this->_endLargeBlock($lines, $qcount);
        }

        return $out;
    }

    /**
     * Add HTML code at the beginning of a large block of quoted lines.
     *
     * @param array $lines     Lines.
     * @param integer $qcount  Number of lines in quoted level.
     *
     * @return string  HTML code.
     */
    protected function _beginLargeBlock($lines, $qcount)
    {
        return '';
    }

    /**
     * Add HTML code at the end of a large block of quoted lines.
     *
     * @param array $lines     Lines.
     * @param integer $qcount  Number of lines in quoted level.
     *
     * @return string  HTML code.
     */
    protected function _endLargeBlock($lines, $qcount)
    {
        return '';
    }

    /**
     * Remove leading and trailing BR tags.
     *
     * @param array $lines  An array of text.
     *
     * @return array  The array with bare BR tags removed at the beginning and
     *                end.
     */
    protected function _removeBr($lines)
    {
        /* Remove leading/trailing line breaks. Spacing between quote blocks
         * will be handled by div CSS. */
        if (!$this->_params['citeblock']) {
            return $lines;
        }

        foreach (array_keys($lines) as $i) {
            if (!preg_match("/^\s*<br\s*\/>\s*$/i", $lines[$i])) {
                break;
            }
            unset($lines[$i]);
        }

        foreach (array_reverse(array_keys($lines)) as $i) {
            if (!preg_match("/^\s*<br\s*\/>\s*$/i", $lines[$i])) {
                break;
            }
            unset($lines[$i]);
        }

        return $lines;
    }

}
