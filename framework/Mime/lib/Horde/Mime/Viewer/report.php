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
     * Can this driver render the the data?
     *
     * @param string $mode  The mode.  Either 'full', 'inline', or 'info'.
     *
     * @return boolean  True if the driver can render the data for the given
     *                  view.
     */
    public function canRender($mode)
    {
        $viewer = $this->_getViewer();
        return $viewer ? $viewer->canRender($mode) : false;
    }

    /**
     * Does this MIME part possibly contain embedded MIME parts?
     *
     * @return boolean  True if this driver supports parsing embedded MIME
     *                  parts.
     */
    public function embeddedMimeParts()
    {
        $viewer = $this->_getViewer();
        return $viewer ? $viewer->embeddedMimeParts() : false;
    }

    /**
     * Return the full rendered version of the Horde_Mime_Part object.
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
     */
    protected function _render()
    {
        return $this->_toHTML(false);
    }

    /**
     * Return the rendered inline version of the Horde_Mime_Part object.
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
     */
    protected function _renderInline()
    {
        return $this->_toHTML(true);
    }

    /**
     * Return an HTML rendered version of the part.
     *
     * @param boolean  Viewing inline?
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
     */
    protected function _toHTML($inline)
    {
        $viewer = $this->_getViewer();
        return $viewer
            ? $viewer->render($inline ? 'inline' : 'full')
            : array();
    }

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
        $viewer->setParams($this->_params);
        return $viewer;
    }
}
