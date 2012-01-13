<?php
/**
 * The IMP_Mime_Viewer_Status class handles multipart/report messages
 * that refer to mail system administrative messages (RFC 3464).
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Mime_Viewer_Status extends Horde_Mime_Viewer_Base
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

        /* RFC 3464 [2]: There are three parts to a delivery status
         * multipart/report message:
         *   (1) Human readable message
         *   (2) Machine parsable body part (message/delivery-status)
         *   (3) Returned message (optional)
         *
         * Information on the message status is found in the 'Action' field
         * located in part #2 (RFC 3464 [2.3.3]). It can be either 'failed',
         * 'delayed', 'delivered', 'relayed', or 'expanded'. */

        if (count($parts) < 2) {
            return array();
        }

        reset($parts);
        $part1_id = next($parts);
        $part2_id = Horde_Mime::mimeIdArithmetic($part1_id, 'next');
        $part3_id = Horde_Mime::mimeIdArithmetic($part2_id, 'next');

        /* Get the action first - it appears in the second part. */
        $action = null;
        $part2 = $this->getConfigParam('imp_contents')->getMIMEPart($part2_id);

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
            $status = new IMP_Mime_Status(array(
                _("ERROR: Your message could not be delivered."),
                sprintf(_("Technical error details can be viewed %s."), $this->getConfigParam('imp_contents')->linkViewJS($part2, 'view_attach', _("HERE"), array('jstext' => _("Technical details"), 'params' => array('ctype' => 'text/plain', 'mode' => IMP_Contents::RENDER_FULL))))
            ));
            $status->action(IMP_Mime_Status::ERROR);
            $msg_link = _("The text of the returned message can be viewed %s.");
            $msg_link_status = _("The text of the returned message");
            break;

        case 'delivered':
        case 'expanded':
        case 'relayed':
            $status = new IMP_Mime_Status(array(
                _("Your message was successfully delivered."),
                sprintf(_("Technical message details can be viewed %s."), $this->getConfigParam('imp_contents')->linkViewJS($part2, 'view_attach', _("HERE"), array('jstext' => _("Technical details"), 'params' => array('ctype' => 'text/x-simple', 'mode' => IMP_Contents::RENDER_FULL))))
            ));
            $status->action(IMP_Mime_Status::SUCCESS);
            $msg_link = _("The text of the message can be viewed %s.");
            $msg_link_status = _("The text of the message");
            break;
        }

        /* Display a link to the returned message, if it exists. */
        $part3 = $this->getConfigParam('imp_contents')->getMIMEPart($part3_id);
        if ($part3) {
            $status->addText(sprintf($msg_link, $this->getConfigParam('imp_contents')->linkViewJS($part3, 'view_attach', _("HERE"), array('jstext' => $msg_link_status, 'params' => array('ctype' => 'message/rfc822')))));
        }

        $ret = array_fill_keys(array_diff($parts, array($part1_id)), null);

        $ret[$this->_mimepart->getMimeId()] = array(
            'data' => '',
            'status' => $status,
            'type' => 'text/html; charset=' . $this->getConfigParam('charset'),
            'wrap' => 'mimePartWrap'
        );

        return $ret;
    }

}
