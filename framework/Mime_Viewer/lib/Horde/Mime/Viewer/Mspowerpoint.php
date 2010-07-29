<?php
/**
 * The Horde_Mime_Viewer_Mspowerpoint class renders out Microsoft Powerpoint
 * documents in HTML format by using the xlHtml package.
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Anil Madhavapeddy <anil@recoil.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Mime_Viewer
 */
class Horde_Mime_Viewer_Mspowerpoint extends Horde_Mime_Viewer_Base
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
     * @return array  See parent::render().
     */
    protected function _render()
    {
        /* Check to make sure the viewer program exists. */
        if (!isset($this->_conf['location']) ||
            !file_exists($this->_conf['location'])) {
            return array();
        }

        $data = '';

        $tmp_ppt = Horde::getTempFile('horde_mspowerpoint');
        file_put_contents($tmp_ppt, $this->_mimepart->getContents());

        $fh = popen($this->_conf['location'] . " $tmp_ppt 2>&1", 'r');
        while (($rc = fgets($fh, 8192))) {
            $data .= $rc;
        }
        pclose($fh);

        return $this->_renderReturn(
            $data,
            'text/html; charset=' . $GLOBALS['registry']->getCharset()
        );
    }

}
