<?php
/**
 * The Horde_Mime_Viewer_Images class allows images to be displayed.
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
class Horde_Mime_Viewer_Images extends Horde_Mime_Viewer_Base
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
     * @param array $conf                 Configuration.
     */
    public function __construct(Horde_Mime_Part $part, array $conf = array())
    {
        parent::__construct($part, $conf);

        /* TODO: Are there other image types that are compressed? */
        $this->_metadata['compressed'] = in_array($this->_getType(), array('image/gif', 'image/jpeg', 'image/png'));
    }

    /**
     * Return the full rendered version of the Horde_Mime_Part object.
     *
     * @return array  See parent::render().
     */
    protected function _render()
    {
        return $this->_renderReturn(null, $this->_getType());
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
        case 'image/jpg':
            /* image/jpg == image/jpeg. */
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
