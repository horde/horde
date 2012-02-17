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
     * @param Horde_Mime_Part $mime_part  The object with the data to be
     *                                    rendered.
     * @param array $conf                 Configuration:
     * <pre>
     * 'location' - (string) Location of the abiword binary.
     * </pre>
     *
     * @throws InvalidArgumentException
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
        /* Check to make sure the viewer program exists. */
        if (!($location = $this->getConfigParam('location')) ||
            !file_exists($location)) {
            return array();
        }

        $tmp_in = $this->_getTempFile();
        $tmp_out = $this->_getTempFile();

        file_put_contents($tmp_in, $this->_mimepart->getContents());

        exec($location . ' --to=html --to-name=' . $tmp_out . ' ' . $tmp_in);

        if (file_exists($tmp_out)) {
            $data = file_get_contents($tmp_out);
            $type = 'text/html';
        } else {
            $data = Horde_Mime_Viewer_Translation::t("Unable to translate this Word document");
            $type = 'text/plain';
        }

        return $this->_renderReturn(
            $data,
            $type . '; charset=' . $this->getConfigParam('charset')
        );
    }

}
