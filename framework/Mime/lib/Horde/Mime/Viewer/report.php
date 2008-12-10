<?php
/**
 * The Horde_Mime_Viewer_report class is a wrapper used to load the
 * appropriate Horde_Mime_Viewer for multipart/report data (RFC 3462).
 *
 * Copyright 2003-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Mime_Viewer
 */
class Horde_Mime_Viewer_report extends Horde_Mime_Viewer_Driver
{
    /**
     * Return the underlying MIME Viewer for this part.
     *
     * @return mixed  A Horde_Mime_Viewer object, or false if not found.
     */
    protected function _getViewer()
    {
        if (!($type = $this->_mimepart->getContentTypeParameter('report-type'))) {
            return false;
        }

        $viewer = Horde_Mime_Viewer::factory($this->_mimepart, 'message/' . String::lower($type));
        if ($viewer) {
            $viewer->setParams($this->_params);
        }
        return $viewer;
    }
}
