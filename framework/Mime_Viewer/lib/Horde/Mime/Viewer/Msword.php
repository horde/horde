<?php
/**
 * The Horde_Mime_Viewer_Msword class renders out Microsoft Word documents
 * in HTML format by using the AbiWord package.
 *
 * Copyright 1999-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Anil Madhavapeddy <anil@recoil.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Mime_Viewer
 */
class Horde_Mime_Viewer_Msword extends Horde_Mime_Viewer_Base
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
     * Constructor.
     *
     * @param array $conf  Configuration for this driver:
     *   - location: (string) Location of the abiword binary.
     */
    public function __construct(Horde_Mime_Part $part, array $conf = array())
    {
        $this->_required = array_merge($this->_required, array(
            'location'
        ));

        parent::__construct($part, $conf);
    }

    /**
     * Return the full rendered version of the Horde_Mime_Part object.
     *
     * @return array  See parent::render().
     */
    protected function _render()
    {
        return $this->_convert('html', 'text/html');
    }

    /**
     * Return the converted msword document.
     *
     * @param string $type  The document type (abiword 'to' argument).
     * @param string $mime  The MIME type of the output.
     *
     * @return array  See render().
     */
    protected function _convert($type, $mime)
    {
        /* Check to make sure the viewer program exists. */
        if (!($location = $this->getConfigParam('location')) ||
            !file_exists($location)) {
            return array();
        }

        $tmp_in = $this->_getTempFile();
        $tmp_out = $this->_getTempFile();

        file_put_contents($tmp_in, $this->_mimepart->getContents());

        exec($location . ' --to=' . escapeshellcmd($type) . ' --to-name=' . escapeshellcmd($tmp_out) . ' ' . escapeshellcmd($tmp_in));

        if (file_exists($tmp_out)) {
            $data = file_get_contents($tmp_out);
        } else {
            $data = Horde_Mime_Viewer_Translation::t("Unable to translate this Word document");
            $mime = 'text/plain; charset=UTF-8';
        }

        return $this->_renderReturn($data, $mime);
    }

}
