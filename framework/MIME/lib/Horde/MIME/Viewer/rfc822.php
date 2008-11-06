<?php
/**
 * The Horde_MIME_Viewer_rfc822 class renders out messages from the
 * message/rfc822 content type.
 *
 * Copyright 2002-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_MIME_Viewer
 */
class Horde_MIME_Viewer_rfc822 extends Horde_MIME_Viewer_Driver
{
    /**
     * Render out the currently set contents.
     *
     * @param array $params  An array with any parameters needed.
     *
     * @return string  The rendered text.
     */
    public function render($params = array())
    {
        if (!$this->mime_part) {
            return $this->formatStatusMsg(_("There was an error displaying this message part"));
        }

        $part = &Util::cloneObject($this->mime_part);
        $part->transferDecodeContents();
        $text = $part->getContents();

        return $text
            ? $text
            : $this->formatStatusMsg(_("There was an error displaying this message part"));
    }

    /**
     * Render out attachment information.
     *
     * @param array $params  An array with any parameters needed.
     *
     * @return string  The rendered text in HTML.
     */
    public function renderAttachmentInfo($params = array())
    {
        if (!$this->mime_part) {
            return '';
        }

        /* Get the text of the part.  Since we need to look for the end of
         * the headers by searching for the CRLFCRLF sequence, use
         * getCanonicalContents() to make sure we are getting the text with
         * CRLF's. */
        $text = $this->mime_part->getCanonicalContents();
        if (empty($text)) {
            return '';
        }

        /* Search for the end of the header text (CRLFCRLF). */
        $text = substr($text, 0, strpos($text, "\r\n\r\n"));

        /* Get the list of headers now. */
        require_once 'Horde/MIME/Headers.php';
        $headers = Horde_MIME_Headers::parseHeaders($text);

        $header_array = array(
            'date' => _("Date"),
            'from' => _("From"),
            'to' => _("To"),
            'cc' => _("Cc"),
            'bcc' => _("Bcc"),
            'reply-to' => _("Reply-To"),
            'subject' => _("Subject")
        );
        $header_output = array();

        foreach ($header_array as $key => $val) {
            $hdr = $headers->getValue($key);
            if (!empty($hdr)) {
                $header_output[] = '<strong>' . $val . ':</strong> ' . htmlspecialchars($hdr);
            }
        }

        require_once 'Horde/Text/Filter.php';
        return '<div class="mimeHeaders">' . Text_Filter::filter(implode("<br />\n", $header_output), 'emails') . '</div>';
    }

    /**
     * Return the MIME content type for the rendered data.
     *
     * @return string  The content type of the data.
     */
    public function getType()
    {
        return 'text/plain; charset=' . NLS::getCharset();
    }
}
