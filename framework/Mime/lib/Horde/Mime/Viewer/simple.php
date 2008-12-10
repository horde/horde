<?php
/**
 * The Horde_Mime_Viewer_simple class renders out plain text without any
 * modifications.
 *
 * Copyright 2004-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Mime_Viewer
 */
class Horde_Mime_Viewer_simple extends Horde_Mime_Viewer_Driver
{
    /**
     * Can this driver render various views?
     *
     * @var boolean
     */
    protected $_capability = array(
        'embedded' => false,
        'forceinline' => false,
        'full' => true,
        'info' => false,
        'inline' => true
    );

    /**
     * Return the full rendered version of the Horde_Mime_Part object.
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
     */
    protected function _render()
    {
        return array(
            $this->_mimepart->getMimeId() => array(
                'data' => $this->_mimepart->getContents(),
                'status' => array(),
                'type' => 'text/plain; charset=' . $this->_mimepart->getCharset()
            )
        );
    }

    /**
     * Return the rendered inline version of the Horde_Mime_Part object.
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
     */
    protected function _renderInline()
    {
        return array(
            $this->_mimepart->getMimeId() => array(
                'data' => String::convertCharset($this->_mimepart->getContents(), $this->_mimepart->getCharset()),
                'status' => array(),
                'type' => 'text/plain; charset=' . NLS::getCharset()
            )
        );
    }
}
