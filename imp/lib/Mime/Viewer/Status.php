<?php
/**
 * The IMP_Horde_Mime_Viewer_Status class handles multipart/report messages
 * that refer to mail system administrative messages (RFC 3464).
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Horde_Mime_Viewer_Status extends Horde_Mime_Viewer_Base
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
     * @return array  See parent::render().
     */
    protected function _renderInline()
    {
        /* If this is a straight message/disposition-notification part, just
         * output the text. */
        if ($this->_mimepart->getType() == 'message/delivery-status') {
            return $this->_params['contents']->renderMIMEPart($this->_mimepart->getMIMEId(), IMP_Contents::RENDER_FULL, array('type' => 'text/plain', 'params' => $this->_params));
        }

        return $this->_renderInfo();
    }

    /**
     * Return the rendered information about the Horde_Mime_Part object.
     *
     * @return array  See parent::render().
     */
    protected function _renderInfo()
    {
        $parts = array_keys($this->_mimepart->contentTypeMap());

        reset($parts);
        $part1_id = next($parts);
        $part2_id = Horde_Mime::mimeIdArithmetic($part1_id, 'next');
        $part3_id = Horde_Mime::mimeIdArithmetic($part2_id, 'next');

        /* RFC 3464 [2]: There are three parts to a delivery status
         * multipart/report message:
         *   (1) Human readable message
         *   (2) Machine parsable body part (message/delivery-status)
         *   (3) Returned message (optional)
         *
         * Information on the message status is found in the 'Action' field
         * located in part #2 (RFC 3464 [2.3.3]). It can be either 'failed',
         * 'delayed', 'delivered', 'relayed', or 'expanded'. */

        /* Get the action first - it appears in the second part. */
        $action = null;
        $part2 = $this->_params['contents']->getMIMEPart($part2_id);

        foreach (explode("\n", $part2->getContents()) as $line) {
            if (stristr($line, 'Action:') !== false) {
                $action = strtolower(trim(substr($line, strpos($line, ':') + 1)));
                if (strpos($action, ' ') !== false) {
                    $action = substr($action, 0, strpos($action, ' '));
                }
                break;
            }
        }

        if (is_null($action)) {
            return array();
        }

        /* Get the correct text strings for the action type. */
        switch ($action) {
        case 'failed':
        case 'delayed':
            $status = array(
                array(
                    'icon' => Horde::img('alerts/error.png', _("Error")),
                    'text' => array(
                        _("ERROR: Your message could not be delivered."),
                        sprintf(_("Additional error message details can be viewed %s."), $this->_params['contents']->linkViewJS($part2, 'view_attach', _("HERE"), array('jstext' => _("Additional message details"), 'params' => array('mode' => IMP_Contents::RENDER_INLINE))))
                    )
                )
            );
            $msg_link = _("The text of the returned message can be viewed %s.");
            $msg_link_status = _("The text of the returned message");
            break;

        case 'delivered':
        case 'expanded':
        case 'relayed':
            $status = array(
                array(
                    'icon' => Horde::img('alerts/success.png', _("Success")),
                    'text' => array(
                        _("Your message was successfully delivered."),
                        sprintf(_("Additional message details can be viewed %s."), $this->_params['contents']->linkViewJS($part2, 'view_attach', _("HERE"), array('jstext' => _("Additional message details"), 'params' => array('mode' => IMP_Contents::RENDER_INLINE))))
                    )
                )
            );
            $msg_link = _("The text of the message can be viewed %s.");
            $msg_link_status = _("The text of the message");
            break;
        }

        /* Print the human readable message. */
        $first_part = $this->_params['contents']->renderMIMEPart($part1_id, IMP_Contents::RENDER_INLINE_AUTO, array('params' => $this->_params));

        /* Display a link to the returned message, if it exists. */
        $part3 = $this->_params['contents']->getMIMEPart($part3_id);
        if ($part3) {
            $status[0]['text'][] = sprintf($msg_link, $this->_params['contents']->linkViewJS($part3, 'view_attach', _("HERE"), array('jstext' => $msg_link_status, 'ctype' => 'message/rfc822')));
        }

        if (empty($first_part)) {
            $data = '';
        } else {
            $status[0]['text'][] = _("The mail server generated the following informational message:");
            $status = array_merge($status, $first_part[$part1_id]['status']);
            $data = $first_part[$part1_id]['data'];
        }

        $ret = array_combine($parts, array_fill(0, count($parts), null));

        $ret[$this->_mimepart->getMimeId()] = array(
            'data' => $data,
            'status' => $status,
            'type' => 'text/html; charset=' . $GLOBALS['registry']->getCharset(),
            'wrap' => 'mimePartWrap'
        );

        return $ret;
    }

}
