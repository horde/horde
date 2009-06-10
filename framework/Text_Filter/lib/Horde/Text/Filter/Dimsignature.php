<?php
/**
 * Displays message signatures marked by a '-- ' in the style of the CSS class
 * "signature".
 *
 * Copyright 2004-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Horde_Text
 */
class Horde_Text_Filter_Dimsignature extends Horde_Text_Filter
{
    /**
     * Executes any code necessary after applying the filter patterns.
     *
     * @param string $text  The text after the filtering.
     *
     * @return string  The modified text.
     */
    public function postProcess($text)
    {
        $parts = preg_split('|(\n--\s*(?:<br />)?\r?\n)|', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        $num_parts = count($parts);
        if ($num_parts > 2) {
            return implode('', array_slice($parts, 0, -2))
                . '<span class="signature">' . $parts[$num_parts - 2]
                . $parts[$num_parts - 1] . '</span>';
        }

        return $text;
    }

}
