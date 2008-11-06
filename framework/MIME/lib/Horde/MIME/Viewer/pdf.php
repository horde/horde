<?php
/**
 * The Horde_MIME_Viewer_pdf class simply outputs the PDF file with the
 * content-type 'application/pdf' enabling web browsers with a PDF viewer
 * plugin to view the PDF file inside the browser.
 *
 * Copyright 2003-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_MIME_Viewer
 */
class Horde_MIME_Viewer_pdf extends Horde_MIME_Viewer_Driver
{
    /**
     * Return the content-type.
     *
     * @return string  The content-type of the output.
     */
    public function getType()
    {
        return 'application/pdf';
    }
}
