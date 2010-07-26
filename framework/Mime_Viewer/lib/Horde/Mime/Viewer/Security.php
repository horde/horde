<?php
/**
 * The Horde_Mime_Viewer_Security class is a wrapper used to load the
 * appropriate Horde_Mime_Viewer for secure multipart messages (defined by RFC
 * 1847). This class handles multipart/signed and multipart/encrypted data.
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Mime_Viewer
 */
class Horde_Mime_Viewer_Security extends Horde_Mime_Viewer_Base
{
    /**
     * Return the underlying MIME Viewer for this part.
     *
     * @return mixed  A Horde_Mime_Viewer object, or false if not found.
     */
    protected function _getViewer()
    {
        if (!($protocol = $this->_mimepart->getContentTypeParameter('protocol'))) {
            return false;
        }

        $viewer = Horde_Mime_Viewer::factory($this->_mimepart, $protocol);
        if ($viewer) {
            $viewer->setParams($this->_params);
        }

        return $viewer;
    }

}
