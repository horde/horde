<?php
/**
 * The Horde_MIME_Viewer_rtf class renders out Rich Text Format documents in
 * HTML format by using the UnRTF package
 * (http://www.gnu.org/software/unrtf/unrtf.html).
 *
 * Copyright 2007 Duck <duck@obala.net>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Duck <duck@obala.net>
 * @package Horde_MIME_Viewer
 */
class Horde_MIME_Viewer_rtf extends Horde_MIME_Viewer_Driver
{
    /**
     * Render out the current data using UnRTF.
     *
     * @param array $params  Any parameters the viewer may need.
     *
     * @return string  The rendered contents.
     */
    public function render($params = array())
    {
        /* Check to make sure the program actually exists. */
        if (!file_exists($GLOBALS['mime_drivers']['horde']['rtf']['location'])) {
            return '<pre>' . sprintf(_("The program used to view this data type (%s) was not found on the system."), $GLOBALS['mime_drivers']['horde']['rtf']['location']) . '</pre>';
        }

        $tmp_rtf = Horde::getTempFile('rtf');
        $tmp_output = Horde::getTempFile('rtf');
        $args = " $tmp_rtf > $tmp_output";

        $fh = fopen($tmp_rtf, 'w');
        fwrite($fh, $this->mime_part->getContents());
        fclose($fh);

        exec($GLOBALS['mime_drivers']['horde']['rtf']['location'] . $args);

        if (!file_exists($tmp_output)) {
            return _("Unable to translate this RTF document");
        }

        return file_get_contents($tmp_output);
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
