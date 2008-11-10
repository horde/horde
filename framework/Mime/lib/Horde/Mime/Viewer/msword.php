<?php
/**
 * The Horde_Mime_Viewer_msword class renders out Microsoft Word documents
 * in HTML format by using the wvWare package.
 *
 * Copyright 1999-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @package Horde_Mime_Viewer
 */
class Horde_Mime_Viewer_msword extends Horde_Mime_Viewer_Driver
{
    /**
     * Render out the current data using wvWare.
     *
     * @param array $params  Any parameters the Viewer may need.
     *
     * @return string  The rendered contents.
     */
    public function render($params = array())
    {
        /* Check to make sure the program actually exists. */
        if (!file_exists($GLOBALS['mime_drivers']['horde']['msword']['location'])) {
            return '<pre>' . sprintf(_("The program used to view this data type (%s) was not found on the system."), $GLOBALS['mime_drivers']['horde']['msword']['location']) . '</pre>';
        }

        $data = '';
        $tmp_word   = Horde::getTempFile('msword');
        $tmp_output = Horde::getTempFile('msword');
        $tmp_dir    = Horde::getTempDir();
        $tmp_file   = str_replace($tmp_dir . '/', '', $tmp_output);

        if (OS_WINDOWS) {
            $args = ' -x ' . dirname($GLOBALS['mime_drivers']['horde']['msword']['location']) . "\\wvHtml.xml -d $tmp_dir -1 $tmp_word > $tmp_output";
        } else {
            $version = exec($GLOBALS['mime_drivers']['horde']['msword']['location'] . ' --version');
            if (version_compare($version, '0.7.0') >= 0) {
                $args = " --charset=" . NLS::getCharset() . " --targetdir=$tmp_dir $tmp_word $tmp_file";
            } else {
                $args = " $tmp_word $tmp_output";
            }
        }

        $fh = fopen($tmp_word, 'w');
        fwrite($fh, $this->mime_part->getContents());
        fclose($fh);

        exec($GLOBALS['mime_drivers']['horde']['msword']['location'] . $args);

        if (!file_exists($tmp_output)) {
            return _("Unable to translate this Word document");
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
