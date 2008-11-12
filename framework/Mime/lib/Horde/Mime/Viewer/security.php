<?php
/**
 * The Horde_Mime_Viewer_security class is a wrapper used to load the
 * appropriate Horde_Mime_Viewer for secure multipart messages (defined by RFC
 * 1847). This class handles multipart/signed and multipart/encrypted data.
 *
 * Copyright 2002-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Mime_Viewer
 */
class Horde_Mime_Viewer_security extends Horde_Mime_Viewer_Driver
{
    /**
     * Stores the Horde_Mime_Viewer of the specified security protocol.
     *
     * @var Horde_Mime_Viewer
     */
    protected $_viewer;

    /**
     * The $mime_part class variable has the information to render
     * out, encapsulated in a Horde_Mime_Part object.
     *
     * @param $params mixed  The parameters (if any) to pass to the underlying
     *                       Horde_Mime_Viewer.
     *
     * @return string  Rendering of the content.
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
     * Load a Horde_Mime_Viewer according to the protocol parameter stored
     * in the Horde_Mime_Part to render. If unsuccessful, try to load a generic
     * multipart Horde_Mime_Viewer.
     *
     * @return boolean  True on success, false on failure.
     */
    protected function _resolveViewer()
    {
        $viewer = null;

        if (empty($this->_viewer)) {
            $protocol = $this->mime_part->getContentTypeParameter('protocol');
            if (empty($protocol)) {
                return false;
            }
            $viewer = Horde_Mime_Viewer::factory($this->mime_part, $protocol);
            if (empty($viewer) ||
                (String::lower(get_class($viewer)) == 'mime_viewer_default')) {
                $viewer = Horde_Mime_Viewer::factory($this->mime_part, $this->mime_part->getPrimaryType() . '/*');
                if (empty($viewer)) {
                    return false;
                }
            }
            $this->_viewer = $viewer;
        }

        return true;
    }
}
