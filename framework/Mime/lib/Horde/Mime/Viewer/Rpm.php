<?php
/**
 * The Horde_Mime_Viewer_Rpm class renders out lists of files in RPM
 * packages by using the rpm tool to query the package.
 *
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @package Horde_Mime_Viewer
 */
class Horde_Mime_Viewer_Rpm extends Horde_Mime_Viewer_Driver
{
    /**
     * Can this driver render various views?
     *
     * @var boolean
     */
    protected $_capability = array(
        'embedded' => false,
        'forceinline' => true,
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

        $data = '';
        $tmp_rpm = Horde::getTempFile('horde_rpm');

        file_put_contents($tmp_rpm, $this->_mimepart->getContents());

        $fh = popen($this->_conf['location'] . " -qip $tmp_rpm 2>&1", 'r');
        while (($rc = fgets($fh, 8192))) {
            $data .= $rc;
        }
        pclose($fh);

        return array(
            $this->_mimepart->getMimeId() => array(
                'data' => '<html><body><pre>' . htmlentities($data) . '</pre></body></html>',
                'status' => array(),
                'type' => 'text/html; charset=' . Horde_Nls::getCharset()
            )
        );
    }
}
