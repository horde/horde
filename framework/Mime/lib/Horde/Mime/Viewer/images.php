<?php
/**
 * The Horde_Mime_Viewer_images class allows images to be displayed.
 *
 * Copyright 2002-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Mime_Viewer
 */
class Horde_Mime_Viewer_images extends Horde_Mime_Viewer_Driver
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
        'inline' => false
    );

    /**
     * Return the full rendered version of the Horde_Mime_Part object.
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
     */
    protected function _render()
    {
        return array(
            'data' => $this->_mimepart->getContents(),
            'type' => $this->_getType()
        );
    }

    /**
     * Return the content-type to use for the image.
     *
     * @return string  The content-type of the image.
     */
    protected function _getType()
    {
        $type = $this->_mimepart->getType();

        switch ($type) {
        case 'image/pjpeg':
            /* image/jpeg and image/pjpeg *appear* to be the same entity, but
             * Mozilla (for one) don't seem to want to accept the latter. */
            return 'image/jpeg';

        case 'image/x-png':
            /* image/x-png == image/png. */
            return 'image/png';

        default:
            return $type;
        }
    }
}
