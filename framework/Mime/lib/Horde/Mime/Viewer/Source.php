<?php
/**
 * The Horde_Mime_Viewer_Source class is a class for any viewer that wants
 * to provide line numbers to extend.
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Mime
 */
class Horde_Mime_Viewer_Source extends Horde_Mime_Viewer_Driver
{
    /**
     * Add line numbers to a block of code.
     *
     * @param string $code  The code to number.
     *
     * @return string  The code with line numbers added.
     */
    protected function _lineNumber($code, $linebreak = "\n")
    {
        $html = array('<table class="lineNumbered" cellspacing="0"><tr><th>');
        for ($l = 1,  $lines = substr_count($code, $linebreak) + 1; $l <= $lines; ++$l) {
            $html[] = sprintf('<a id="l%s" href="#l%s">%s</a><br />', $l, $l, $l);
        }
        return implode("\n", $html) . '</th><td><div>' . $code . '</div></td></tr></table>';
    }

}
