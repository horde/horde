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
        $imp_contents = $this->getConfigParam('imp_contents');
        $machine = $original = $status = null;
        $mime_id = $this->_mimepart->getMimeId();
        $ret = array();

        switch ($this->_mimepart->getType()) {
        case 'message/delivery-status':
            $machine = $imp_contents->getMimePart($mime_id);
            break;

        case 'multipart/report':
            /* RFC 3464 [2]: There are three parts to a delivery status
             * multipart/report message:
             *   (1) Human readable message
             *   (2) Machine parsable body part (message/delivery-status)
             *   (3) Returned message (optional) */
            $iterator = $this->_mimepart->partIterator(false);
            $iterator->rewind();

            if (!($curr = $iterator->current())) {
                break;
            }

            $part1_id = $curr->getMimeId();
            $id_ob = new Horde_Mime_Id($part1_id);

            /* Technical details. */
            $id_ob->id = $id_ob->idArithmetic($id_ob::ID_NEXT);
            $ret[$id_ob->id] = null;
            $machine = $imp_contents->getMimePart($id_ob->id);

            /* Returned message. */
            $original = $imp_contents->getMimePart(
                $id_ob->idArithmetic($id_ob::ID_NEXT)
            );

            if ($original) {
                foreach ($this->_mimepart->partIterator() as $val) {
                    $ret[$val->getMimeId()] = null;
                }

                /* Allow the human readable part to be displayed
                 * separately. */
                unset($ret[$part1_id]);
            }
            break;
        }

        if (!$machine) {
            return array($mime_id => null);
        }

        $parse = Horde_Mime_Headers::parseHeaders(
            /* Remove extra line endings. */
            preg_replace(
                '/\n{2,}/',
                "\n",
                strtr($machine->getContents(), "\r", "\n")
            )
        );

        /* Information on the message status is found in the 'Action'
         * field located in part #2 (RFC 3464 [2.3.3]). */
        if (isset($parse['Action'])) {
            switch (trim($parse['Action']->value_single)) {
            case 'failed':
            case 'delayed':
                $msg_link = _("View details of the returned message.");
                $status_action = IMP_Mime_Status::ERROR;
                $status_msg = _("ERROR: Your message could not be delivered.");
                break;

            case 'delivered':
            case 'expanded':
            case 'relayed':
                $msg_link = _("View details of the delivered message.");
                $status_action = IMP_Mime_Status::SUCCESS;
                $status_msg = _("Your message was successfully delivered.");
                break;
            }

            if (isset($msg_link)) {
                $status = new IMP_Mime_Status($this->_mimepart, $status_msg);
                $status->action($status_action);

                if (isset($parse['Final-Recipient'])) {
                    list(,$recip) = explode(
                        ';',
                        $parse['Final-Recipient']->value_single
                    );
                    $recip_ob = new Horde_Mail_Rfc822_List($recip);

                    if (count($recip_ob)) {
                        $status->addText(sprintf(
                            _("Recipient: %s"),
                            $recip_ob[0]
                        ));
                    }
                }

                /* Display a link to the returned message, if it exists. */
                if ($original) {
                    $status->addText(
                        $imp_contents->linkViewJS(
                            $original,
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
            }
        }

        $ret[$mime_id] = array_filter(array(
            'data' => '',
            'status' => $status ?: null,
            'type' => 'text/html; charset=' . $this->getConfigParam('charset'),
            'wrap' => 'mimePartWrap'
        ));

        return $ret;
    }

}
