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
     * Return the MIME type of the rendered content.
     *
     * @return string  MIME-type of the output content.
     */
    public function getType()
    {
        return 'text/plain';
    }
}
