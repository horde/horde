<?php
/**
 * The IMP_Horde_Mime_Viewer_appledouble class handles multipart/appledouble
 * messages conforming to RFC 1740.
 *
 * Copyright 2003-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Mime
 */
class IMP_Horde_Mime_Viewer_appledouble extends Horde_Mime_Viewer_Driver
{
    /**
     * Force viewing of a part inline, regardless of the Content-Disposition
     * of the MIME Part.
     *
     * @var boolean
     */
    protected $_forceinline = true;

    /**
     * Render out the currently set contents.
     *
     * @param array $params  An array with a reference to a MIME_Contents
     *                       object.
     *
     * @return string  The rendered text in HTML.
     */
    public function render($params)
    {
        $contents = &$params[0];
        $text = '';

        /* RFC 1740 [4]: There are two parts to an appledouble message:
             (1) application/applefile
             (2) Data embedded in the Mac file */

        /* Display the macintosh download link. */
        $part = $contents->getDecodedMIMEPart($this->mime_part->getRelativeMIMEId(1));
        if ($part) {
            $status = array(
                _("This message contains a Macintosh file."),
                sprintf(_("The Macintosh resource fork can be downloaded %s."), $contents->linkViewJS($part, 'download_attach', _("HERE"), _("The Macintosh resource fork"))),
                _("The contents of the Macintosh file are below.")
            );
            $text .= $this->formatStatusMsg($status, Horde::img('apple.png', _("Macintosh File")), false);
        }

        /* Display the content of the file. */
        $part = $contents->getDecodedMIMEPart($this->mime_part->getRelativeMIMEId(2));
        if ($part) {
            $mime_message = Horde_Mime_Message::convertMIMEPart($part);
            $mc = new IMP_Contents($mime_message, array('download' => 'download_attach', 'view' => 'view_attach'), array(&$contents));
            $mc->buildMessage();
            $text .= '<table cellspacing="0">' . $mc->getMessage(true) . '</table>';
        }

        return $text;
    }

    /**
     * Return the content-type.
     *
     * @return string  The content-type of the message.
     */
    public function getType()
    {
        return 'text/html; charset=' . NLS::getCharset();
    }
}
