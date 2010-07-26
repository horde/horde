<?php
/**
 * The Horde_Mime_Viewer_Deb class renders out lists of files in Debian
 * packages by using the dpkg tool to query the package.
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
class Horde_Mime_Viewer_Deb extends Horde_Mime_Viewer_Base
{
    /**
     * This driver's display capabilities.
     *
     * @var array
     */
    protected $_capability = array(
        'full' => true,
        'info' => true,
        'inline' => false,
        'raw' => false
    );

    /**
     * Metadata for the current viewer/data.
     *
     * @var array
     */
    protected $_metadata = array(
        'compressed' => true,
        'embedded' => false,
        'forceinline' => false
    );

    /**
     * Return the full rendered version of the Horde_Mime_Part object.
     *
     * @return array  See parent::render().
     */
    protected function _render()
    {
        return $this->_renderFullReturn($this->_renderInfo());
    }

    /**
     * Return the rendered information about the Horde_Mime_Part object.
     *
     * @return array  See parent::render().
     */
    protected function _renderInfo()
    {
        /* Check to make sure the viewer program exists. */
        if (!isset($this->_conf['location']) ||
            !file_exists($this->_conf['location'])) {
            return array();
        }

        $data = '';

        $tmpin = Horde::getTempFile('horde_deb');
        file_put_contents($tmp_deb, $this->_mimepart->getContents());

        $fh = popen($this->_conf['location'] . " -f $tmpin 2>&1", 'r');
        while ($rc = fgets($fh, 8192)) {
            $data .= $rc;
        }
        pclose($fh);

        return $this->_renderReturn(
            '<pre>' . htmlspecialchars($data) . '</pre>',
            'text/html; charset=' . $GLOBALS['registry']->getCharset()
        );
    }

}
