<?php
/**
 * The IMP_Horde_Mime_Viewer_Mdn class handles multipart/report messages that
 * that refer to message disposition notification (MDN) messages (RFC 3798).
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Horde_Mime_Viewer_Mdn extends Horde_Mime_Viewer_Driver
{
    /**
     * This driver's display capabilities.
     *
     * @var array
     */
    protected $_capability = array(
        'full' => false,
        'info' => true,
        'inline' => true,
        'raw' => false
    );

    /**
     * Metadata for the current viewer/data.
     *
     * @var array
     */
    protected $_metadata = array(
        'compressed' => false,
        'embedded' => false,
        'forceinline' => true
    );

    /**
     * Return the rendered inline version of the Horde_Mime_Part object.
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
     */
    protected function _renderInline()
    {
        /* If this is a straight message/disposition-notification part, just
         * output the text. */
        if ($this->_mimepart->getType() == 'message/disposition-notification') {
            return $this->_params['contents']->renderMIMEPart($this->_mimepart->getMIMEId(), IMP_Contents::RENDER_FULL, array('type' => 'text/plain', 'params' => $this->_params));
        }

        return $this->_renderInfo();
    }

    /**
     * Return the rendered information about the Horde_Mime_Part object.
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
     */
    protected function _renderInfo()
    {
        $mdn_id = $this->_mimepart->getMimeId();
        $parts = array_keys($this->_mimepart->contentTypeMap());

        $status = array(
            array(
                'icon' => Horde::img('info_icon.png', _("Info")),
                'text' => array(_("A message you have sent has resulted in a return notification from the recipient."))
            )
        );

        /* RFC 3798 [3]: There are three parts to a delivery status
         * multipart/report message:
         *   (1) Human readable message
         *   (2) Machine parsable body part (message/disposition-notification)
         *   (3) Original message (optional) */

        /* Print the human readable message. */
        reset($parts);
        $curr_id = $first_id = next($parts);
        $first_part = $this->_params['contents']->renderMIMEPart($curr_id, IMP_Contents::RENDER_INLINE_AUTO, array('params' => $this->_params));

        /* Display a link to more detailed message. */
        $curr_id = Horde_Mime::mimeIdArithmetic($curr_id, 'next');
        $part = $this->_params['contents']->getMIMEPart($curr_id);
        if ($part) {
            $status[0]['text'][] = sprintf(_("Additional information can be viewed %s."), $this->_params['contents']->linkViewJS($part, 'view_attach', _("HERE"), array('jstext' => _("Additional information details"), 'params' => array('mode' => IMP_Contents::RENDER_INLINE))));
        }

        /* Display a link to the sent message. Try to download the text of
           the message/rfc822 part first, if it exists. */
        $curr_id = Horde_Mime::mimeIdArithmetic($curr_id, 'next');
        $part = $this->_params['contents']->getMIMEPart($curr_id);
        if ($part) {
            $status[0]['text'][] = sprintf(_("The text of the sent message can be viewed %s."), $this->_params['contents']->linkViewJS($part, 'view_attach', _("HERE"), array('jstext' => _("The text of the sent message"))));
        }

        if (empty($first_part)) {
            $data = '';
        } else {
            $status[0]['text'][] = _("The mail server generated the following informational message:");
            $status = array_merge($status, $first_part[$first_id]['status']);
            $data = $first_part[$first_id]['data'];
        }

        $ret = array_combine($parts, array_fill(0, count($parts), null));
        $ret[$mdn_id] = array(
            'data' => $data,
            'status' => $status,
            'type' => 'text/html; charset=' . $GLOBALS['registry']->getCharset(),
            'wrap' => 'mimePartWrap'
        );

        return $ret;
    }

}
