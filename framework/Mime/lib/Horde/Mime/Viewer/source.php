<?php
/**
 * The Horde_Mime_Viewer_source class is a class for any viewer that wants
 * to provide line numbers to extend.
 *
 * Copyright 1999-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Horde_Mime_Viewer
 */
class Horde_Mime_Viewer_source extends Horde_Mime_Viewer_Driver
{
    /**
     * Add line numbers to a block of code.
     *
     * @param string $code  The code to number.
     */
    public function lineNumber($code, $linebreak = "\n")
    {
        $lines = substr_count($code, $linebreak) + 1;
        $html = '<table class="lineNumbered" cellspacing="0"><tr><th>';
        for ($l = 1; $l <= $lines; $l++) {
            $html .= sprintf('<a id="l%s" href="#l%s">%s</a><br />', $l, $l, $l) . "\n";
        }
        return $html . '</th><td><div>' . $code . '</div></td></tr></table>';
    }

    /**
     * Return the MIME content type of the rendered content.
     *
     * @return string  The content type of the output.
     */
    public function getType()
    {
        return 'text/html; charset=' . NLS::getCharset();
    }
}
