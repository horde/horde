<?php
/**
 * Displays message signatures marked by a '-- ' in the style of the CSS class
 * "signature".
 *
 * Copyright 2004-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Text_Filter
 */
class Horde_Text_Filter_Dimsignature extends Horde_Text_Filter_Base
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
        $parts = preg_split('/(\n--\s*(?:<br \/>)?\r?\n.*?)(?=<\/?(?:div|span)|$\s)/is', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        $text = '';

        while (count($parts)) {
            $text .= array_shift($parts);
            if (count($parts)) {
                $text .= '<span class="signature">' . array_shift($parts) . '</span>';
            }
        }

        return $text;
    }

}
