<?php
/**
 * Copyright 2002-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2002-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Renderer for multipart/report data referring to mail system administrative
 * messages (RFC 3464).
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2002-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
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
        /* RFC 3464 [2]: There are three parts to a delivery status
         * multipart/report message:
         *   (1) Human readable message
         *   (2) Machine parsable body part (message/delivery-status)
         *   (3) Returned message (optional)
         *
         * Information on the message status is found in the 'Action' field
         * located in part #2 (RFC 3464 [2.3.3]). It can be either 'failed',
         * 'delayed', 'delivered', 'relayed', or 'expanded'. */

        if (count($this->_mimepart) < 2) {
            return array();
        }

        $iterator = $this->_mimepart->partIterator(false);
        $iterator->rewind();
        $part1_id = $iterator->current()->getMimeId();

        $id_ob = new Horde_Mime_Id($part1_id);
        $part2_id = $id_ob->id = $id_ob->idArithmetic($id_ob::ID_NEXT);
        $part3_id = $id_ob->idArithmetic($id_ob::ID_NEXT);

        /* Get the action first - it appears in the second part. */
        $action = null;
        $imp_contents = $this->getConfigParam('imp_contents');
        $part2 = $imp_contents->getMimePart($part2_id);

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
            $status = new IMP_Mime_Status(
                $this->_mimepart,
                _("ERROR: Your message could not be delivered.")
            );
            $status->action(IMP_Mime_Status::ERROR);
            $msg_link = _("View details of the returned message.");
            break;

        case 'delivered':
        case 'expanded':
        case 'relayed':
            $status = new IMP_Mime_Status(
                $this->_mimepart,
                _("Your message was successfully delivered.")
            );
            $status->action(IMP_Mime_Status::SUCCESS);
            $msg_link = _("View details of the delivered message.");
            break;

        default:
            return array();
        }

        /* Display a link to the returned message, if it exists. */
        if ($part3 = $imp_contents->getMimePart($part3_id)) {
            $status->addText(
                $imp_contents->linkViewJS(
                    $part3,
                    'view_attach',
                    $msg_link,
                    array(
                        'params' => array(
                            'ctype' => 'message/rfc822'
                        )
                    )
                )
            );
        }

        $ret = array();
        foreach ($iterator as $val) {
            $ret[$val->getMimeId()] = null;
        }

        $ret[$this->_mimepart->getMimeId()] = array(
            'data' => '',
            'status' => $status,
            'type' => 'text/html; charset=' . $this->getConfigParam('charset'),
            'wrap' => 'mimePartWrap'
        );

        return $ret;
    }

}
