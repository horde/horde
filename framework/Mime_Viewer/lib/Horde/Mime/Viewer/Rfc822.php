<?php
/**
 * The Horde_Mime_Viewer_Rfc822 class renders out messages from the
 * message/rfc822 content type.
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Mime_Viewer
 */
class Horde_Mime_Viewer_Rfc822 extends Horde_Mime_Viewer_Base
{
    /**
     * This driver's display capabilities.
     *
     * @var array
     */
    protected $_capability = array(
        'full' => true,
        'info' => true,
        'inline' => false,
        'raw' => false
    );

    /**
     * Return the full rendered version of the Horde_Mime_Part object.
     *
     * @return array  See parent::render().
     */
    protected function _render()
    {
        return $this->_renderReturn(
            null,
            'text/plain; charset=' . $this->getConfigParam('charset')
        );
    }

    /**
     * Return the rendered information about the Horde_Mime_Part object.
     *
     * @return array  See parent::render().
     */
    protected function _renderInfo()
    {
        /* Get the text of the part.  Since we need to look for the end of
         * the headers by searching for the CRLFCRLF sequence, use
         * getCanonicalContents() to make sure we are getting the text with
         * CRLF's. */
        $text = $this->_mimepart->getContents(array('canonical' => true));
        if (empty($text)) {
            return array();
        }

        /* Search for the end of the header text (CRLFCRLF). */
        $text = substr($text, 0, strpos($text, "\r\n\r\n"));

        /* Get the list of headers now. */
        $headers = Horde_Mime_Headers::parseHeaders($text);

        $header_array = array(
            'date' => Horde_Mime_Viewer_Translation::t("Date"),
            'from' => Horde_Mime_Viewer_Translation::t("From"),
            'to' => Horde_Mime_Viewer_Translation::t("To"),
            'cc' => Horde_Mime_Viewer_Translation::t("Cc"),
            'bcc' => Horde_Mime_Viewer_Translation::t("Bcc"),
            'reply-to' => Horde_Mime_Viewer_Translation::t("Reply-To"),
            'subject' => Horde_Mime_Viewer_Translation::t("Subject")
        );
        $header_output = array();

        foreach ($header_array as $key => $val) {
            $hdr = $headers->getValue($key);
            if (!empty($hdr)) {
                $header_output[] = '<strong>' . $val . ':</strong> ' . htmlspecialchars($hdr);
            }
        }

        return $this->_renderReturn(
            (empty($header_output) ? '' : ('<div class="fixed mimeHeaders">' . $this->_textFilter(implode("<br />\n", $header_output), 'emails') . '</div>')),
            'text/html; charset=UTF-8'
        );
    }

}
