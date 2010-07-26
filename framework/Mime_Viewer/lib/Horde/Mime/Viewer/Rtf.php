<?php
/**
 * The Horde_Mime_Viewer_Rtf class renders out Rich Text Format documents in
 * HTML format by using the UnRTF package.
 *
 * UnRTF package: http://www.gnu.org/software/unrtf/unrtf.html
 *
 * Copyright 2007 Duck <duck@obala.net>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Duck <duck@obala.net>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Mime_Viewer
 */
class Horde_Mime_Viewer_Rtf extends Horde_Mime_Viewer_Driver
{
    /**
     * This driver's display capabilities.
     *
     * @var array
     */
    protected $_capability = array(
        'full' => true,
        'info' => false,
        'inline' => false,
        'raw' => false
    );

    /**
     * Return the full rendered version of the Horde_Mime_Part object.
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
     */
    protected function _render()
    {
        /* Check to make sure the viewer program exists. */
        if (!isset($this->_conf['location']) ||
            !file_exists($this->_conf['location'])) {
            return array();
        }

        $tmp_rtf = Horde::getTempFile('rtf');
        $tmp_output = Horde::getTempFile('rtf');

        file_put_contents($tmp_rtf, $this->_mimepart->getContents());

        exec($this->_conf['location'] . " $tmp_rtf > $tmp_output");

        if (file_exists($tmp_output)) {
            $data = file_get_contents($tmp_output);
        } else {
            $data = _("Unable to translate this RTF document");
        }

        return array(
            $this->_mimepart->getMimeId() => array(
                'data' => $data,
                'status' => array(),
                'type' => 'text/html; charset=' . $GLOBALS['registry']->getCharset()
            )
        );
    }
}
