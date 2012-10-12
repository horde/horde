<?php
/**
 * The Horde_Mime_Viewer_Deb class renders out lists of files in Debian
 * packages by using the dpkg tool to query the package.
 *
 * Copyright 1999-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Anil Madhavapeddy <anil@recoil.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
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
     * 'location' - (string) Location of the dpkg binary [REQUIRED].
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
        $tmp_deb = $this->_getTempFile();

        file_put_contents($tmp_deb, $this->_mimepart->getContents());

        $fh = popen($location . ' -f ' . $tmp_deb . ' 2>&1', 'r');
        while ($rc = fgets($fh, 8192)) {
            $data .= $rc;
        }
        pclose($fh);

        $monospace = $this->getConfigParam('monospace');

        return $this->_renderReturn(
            '<span ' .
            ($monospace ? 'class="' . $monospace . '">' : 'style="font-family:monospace">') .
            htmlspecialchars($data) . '</span>',
            'text/html; charset=UTF-8'
        );
    }

}
