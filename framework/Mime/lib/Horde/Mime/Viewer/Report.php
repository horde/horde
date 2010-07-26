<?php
/**
 * The Horde_Mime_Viewer_Report class is a wrapper used to load the
 * appropriate Horde_Mime_Viewer for multipart/report data (RFC 3462).
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Mime
 */
class Horde_Mime_Viewer_Report extends Horde_Mime_Viewer_Driver
{
    /**
     * Return the underlying MIME Viewer for this part.
     *
     * @return mixed  A Horde_Mime_Viewer object, or false if not found.
     */
    protected function _getViewer()
    {
        if (!($type = $this->_mimepart->getContentTypeParameter('report-type'))) {
            /* This is a broken RFC 3462 message, since 'report-type' is
             * mandatory. Try to determine the report-type by looking at the
             * sub-type of the second body part. */
            $parts = $this->_mimepart->getParts();
            if (!isset($parts[1])) {
                return false;
            }
            $type = $parts[1]->getSubType();
        }

        $viewer = Horde_Mime_Viewer::factory($this->_mimepart, 'message/' . Horde_String::lower($type));
        if ($viewer) {
            $viewer->setParams($this->_params);
        }

        return $viewer;
    }
}
