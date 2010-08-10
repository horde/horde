<?php
/**
 * The Horde_Mime_Viewer_Rpm class renders out lists of files in RPM
 * packages by using the rpm tool to query the package.
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
class Horde_Mime_Viewer_Rpm extends Horde_Mime_Viewer_Base
{
    /**
     * This driver's display capabilities.
     *
     * @var array
     */
    protected $_capability = array(
        'full' => false,
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
     * Constructor.
     *
     * @param Horde_Mime_Part $mime_part  The object with the data to be
     *                                    rendered.
     * @param array $conf                 Configuration:
     * <pre>
     * 'location' - (string) The location of the rpm binary [REQUIRED].
     * 'monospace' - (string) A class to use to display monospace text inline.
     *               DEFAULT: Uses style="font-family:monospace"
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
     * Return the rendered information about the Horde_Mime_Part object.
     *
     * @return array  See parent::render().
     */
    protected function _renderInfo()
    {
        /* Check to make sure the viewer program exists. */
        if (!($location = $this->getConfigParam('location')) ||
            !file_exists($location)) {
            return array();
        }

        $data = '';

        $tmp_rpm = $this->_getTempFile();
        file_put_contents($tmp_rpm, $this->_mimepart->getContents());

        $fh = popen($location . ' -qip ' . $tmp_rpm . ' 2>&1', 'r');
        while (($rc = fgets($fh, 8192))) {
            $data .= $rc;
        }
        pclose($fh);

        $monospace = $this->getConfigParam('monospace');

        return $this->_renderReturn(
            '<span ' .
            ($monospace ? 'class="' . $monospace . '">' : 'style="font-family:monospace">') .
            htmlspecialchars($data) . '</span>',
            'text/html; charset=' . $this->getConfigParam('charset')
        );
    }

}
