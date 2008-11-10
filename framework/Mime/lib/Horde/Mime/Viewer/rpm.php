<?php
/**
 * The Horde_Mime_Viewer_rpm class renders out lists of files in RPM
 * packages by using the rpm tool to query the package.
 *
 * Copyright 1999-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @package Horde_Mime_Viewer
 */
class Horde_Mime_Viewer_rpm extends Horde_Mime_Viewer_Driver
{
    /**
     * Render out the RPM contents.
     *
     * @param array $params  Any parameters the Viewer may need.
     *
     * @return string  The rendered contents.
     */
    public function render($params = array())
    {
        /* Check to make sure the program actually exists. */
        if (!file_exists($GLOBALS['mime_drivers']['horde']['rpm']['location'])) {
            return '<pre>' . sprintf(_("The program used to view this data type (%s) was not found on the system."), $GLOBALS['mime_drivers']['horde']['rpm']['location']) . '</pre>';
        }

        $data = '';
        $tmp_rpm = Horde::getTempFile('horde_rpm');

        $fh = fopen($tmp_rpm, 'w');
        fwrite($fh, $this->mime_part->getContents());
        fclose($fh);

        $fh = popen($GLOBALS['mime_drivers']['horde']['rpm']['location'] . " -qip $tmp_rpm 2>&1", 'r');
        while (($rc = fgets($fh, 8192))) {
            $data .= $rc;
        }
        pclose($fh);

        return '<pre>' . htmlentities($data) . '</pre>';
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
