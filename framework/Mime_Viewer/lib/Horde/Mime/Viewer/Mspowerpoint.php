<?php
/**
 * The Horde_Mime_Viewer_Mspowerpoint class renders out Microsoft Powerpoint
 * documents in HTML format by using the xlHtml package.
 *
 * Copyright 1999-2011 The Horde Project (http://www.horde.org/)
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
     * Constructor.
     *
     * @param Horde_Mime_Part $mime_part  The object with the data to be
     *                                    rendered.
     * @param array $conf                 Configuration:
     * <pre>
     * 'location' - (string) Location of the ppthtml binary.
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

        $data = '';

        $tmp_ppt = $this->_getTempFile();
        file_put_contents($tmp_ppt, $this->_mimepart->getContents());

        $fh = popen($location . ' ' . $tmp_ppt . ' 2>&1', 'r');
        while (($rc = fgets($fh, 8192))) {
            $data .= $rc;
        }
        pclose($fh);

        return $this->_renderReturn(
            $data,
            'text/html; charset=UTF-8'
        );
    }

}
