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
     * Stores the Horde_Mime_Viewer of the specified protocol.
     *
     * @var Horde_Mime_Viewer
     */
    protected $_viewer;

    /**
     * Render the multipart/report data.
     *
     * @param array $params  An array of parameters needed.
     *
     * @return string  The rendered data.
     */
    public function render($params = array())
    {
        /* Get the appropriate Horde_Mime_Viewer for the protocol specified. */
        if (!($this->_resolveViewer())) {
            return;
        }

        /* Render using the loaded Horde_Mime_Viewer object. */
        return $this->_viewer->render($params);
    }

    /**
     * Returns the content-type of the Viewer used to view the part.
     *
     * @return string  A content-type string.
     */
    public function getType()
    {
        /* Get the appropriate Horde_Mime_Viewer for the protocol specified. */
        if (!($this->_resolveViewer())) {
            return 'application/octet-stream';
        } else {
            return $this->_viewer->getType();
        }
    }

    /**
     * Load a Horde_Mime_Viewer according to the report-type parameter stored
     * in the MIME_Part to render. If unsuccessful, try to load a generic
     * multipart Horde_Mime_Viewer.
     *
     * @return boolean  True on success, false on failure.
     */
    protected function _resolveViewer()
    {
        $type = $viewer = null;

        if (empty($this->_viewer)) {
            if (($type = $this->mime_part->getContentTypeParameter('report-type'))) {
                $viewer = &Horde_Mime_Viewer::factory($this->mime_part, 'message/' . String::lower($type));
                $type = $this->mime_part->getPrimaryType();
            } else {
                /* If report-type is missing, the message is an improper
                 * multipart/report message.  Attempt to fall back to a
                 * multipart/mixed viewer instead. */
                $type = 'multipart';
            }

            if (empty($viewer) ||
                (String::lower(get_class($viewer)) == 'mime_viewer_default')) {
                if (!($viewer = &Horde_Mime_Viewer::factory($this->mime_part, $type . '/*'))) {
                    return false;
                }
            }
            $this->_viewer = $viewer;
        }

        return true;
    }
}
