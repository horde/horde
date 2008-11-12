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
     * Can this driver render various views?
     *
     * @var boolean
     */
    protected $_capability = array(
        'embedded' => false,
        'full' => true,
        'info' => false,
        'inline' => true,
    );

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
     * @param boolean
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
     */
    protected function _toHTML($inline)
    {
        if (!($type = $this->_mimepart->getContentTypeParameter('report-type'))) {
            return array();
        }

        $viewer = Horde_Mime_Viewer::factory('message/' . String::lower($type));
        if (!$viewer) {
            return array();
        }
        $viewer->setMIMEPart($this->_mimepart);
        $viewer->setParams($this->_params);

        /* Render using the loaded Horde_Mime_Viewer object. */
        return $viewer->render($inline ? 'inline' : 'full');
    }
}
