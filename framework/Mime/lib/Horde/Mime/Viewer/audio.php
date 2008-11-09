<?php
/**
 * The Horde_MIME_Viewer_audio class sends audio parts to the browser for
 * handling by the browser, a plugin, or a helper application.
 *
 * Copyright 2004-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_MIME_Viewer
 */
class Horde_MIME_Viewer_audio extends Horde_MIME_Viewer_Driver
{
    /**
     * Return the content-type.
     *
     * @return string  The content-type of the output.
     */
    public function getType()
    {
        return $this->mime_part->getType();
    }
}
