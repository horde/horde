<?php
/**
 * The Horde_Mime_Viewer_Wordperfect class renders out WordPerfect documents
 * in HTML format by using the libwpd package.
 *
 * libpwd website: http://libwpd.sourceforge.net/
 *
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Matt Selsky <selsky@columbia.edu>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Mime_Viewer
 */
class Horde_Mime_Viewer_Wordperfect extends Horde_Mime_Viewer_Base
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
     * 'location' - (string) Location of the wpd2html binary.
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

        $tmp_wpd = $this->_getTempFile();
        $tmp_output = $this->_getTempFile();

        file_put_contents($tmp_wpd, $this->_mimepart->getContents());

        exec($location . ' ' . $tmp_wpd . ' > ' . $tmp_output);

        $data = file_exists($tmp_output)
            ? file_get_contents($tmp_output)
            : _("Unable to translate this WordPerfect document");

        return $this->_renderReturn(
            $data,
            'text/html; charset=UTF-8'
        );
    }

}
