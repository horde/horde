<?php
/**
 * The Horde_MIME_Viewer_msexcel class renders out Microsoft Excel
 * documents in HTML format by using the xlHtml package.
 *
 * Copyright 1999-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @package Horde_MIME_Viewer
 */
class Horde_MIME_Viewer_msexcel extends Horde_MIME_Viewer_Driver
{
    /**
     * Render out the currently data using xlhtml.
     *
     * @param array $params  Any params this Viewer may need.
     *
     * @return string  The rendered data.
     */
    public function render($params = array())
    {
        /* Check to make sure the program actually exists. */
        if (!file_exists($GLOBALS['mime_drivers']['horde']['msexcel']['location'])) {
            return '<pre>' . sprintf(_("The program used to view this data type (%s) was not found on the system."), $GLOBALS['mime_drivers']['horde']['msexcel']['location']) . '</pre>';
        }

        $data = '';
        $tmp_xls = Horde::getTempFile('horde_msexcel');

        $fh = fopen($tmp_xls, 'w');
        fwrite($fh, $this->mime_part->getContents());
        fclose($fh);

        $fh = popen($GLOBALS['mime_drivers']['horde']['msexcel']['location'] . " -nh $tmp_xls 2>&1", 'r');
        while (($rc = fgets($fh, 8192))) {
            $data .= $rc;
        }
        pclose($fh);

        return $data;
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
