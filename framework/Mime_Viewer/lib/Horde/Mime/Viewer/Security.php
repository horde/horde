<?php
/**
 * The Horde_Mime_Viewer_Security class is a wrapper used to load the
 * appropriate Horde_Mime_Viewer for secure multipart messages (defined by RFC
 * 1847). This class handles multipart/signed and multipart/encrypted data.
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Mime_Viewer
 */
class Horde_Mime_Viewer_Security extends Horde_Mime_Viewer_Base
{
    /**
     * Constructor.
     *
     * @param Horde_Mime_Part $mime_part  The object with the data to be
     *                                    rendered.
     * @param array $conf                 Configuration:
     * <pre>
     * 'viewer_callback' - (callback) A callback to a factory that will
     *                     return the appropriate viewer for the embedded
     *                     MIME type. Is passed three parameters: the
     *                     current driver object, the MIME part object, and
     *                     the MIME type to use.
     * </pre>
     */
    public function __construct(Horde_Mime_Part $part, array $conf = array())
    {
        parent::__construct($part, $conf);
    }

    /**
     * Return the underlying MIME Viewer for this part.
     *
     * @return mixed  A Horde_Mime_Viewer object, or false if not found.
     */
    protected function _getViewer()
    {
        if (($callback = $this->getConfigParam('viewer_callback')) &&
            ($protocol = $this->_mimepart->getContentTypeParameter('protocol'))) {
            return call_user_func($callback, $this, $this->_mimepart, $protocol);
        }

        return false;
    }

}
