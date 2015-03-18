<?php
/**
 * Copyright 2003-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2003-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Handler for multipart/report messages that refer to message disposition
 * notification (MDN) messages (RFC 3798).
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2003-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Mime_Viewer_Mdn extends Horde_Mime_Viewer_Base
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
        $original = null;
        $ret = array();

        switch ($this->_mimepart->getType()) {
        case 'message/disposition-notification':
            /* Outlook can send a disposition-notification without the
             * RFC-required multipart/report wrapper. */
            $machine = $imp_contents->getMimePart(
                $this->_mimepart->getMimeId()
            );
            break;

        case 'multipart/report':
            /* RFC 3798 [3]: There are three parts to a delivery status
             * multipart/report message:
             *   (1) Human readable message
             *   (2) Machine parsable body part
             *       [message/disposition-notification]
             *   (3) Original message (optional) */
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

            /* Original sent message. */
            $original = $imp_contents->getMimePart(
                $id_ob->idArithmetic($id_ob::ID_NEXT)
            );

            if ($original) {
                foreach ($part->partIterator() as $val) {
                    $ret[$val->getMimeId()] = null;
                }
            }
            break;

        default:
            return array($this->_mimepart->getMimeId() => null);
        }

        $mdn_status = array(
            _("A message you have sent has resulted in a return notification from the recipient.")
        );

        if ($machine) {
            $parse = Horde_Mime_Headers::parseHeaders($machine->getContents());

            if (isset($parse['Final-Recipient'])) {
                list(,$recip) = explode(
                    ';',
                    $parse['Final-Recipient']->value_single
                );

                if ($recip) {
                    $mdn_status[] = sprintf(
                        _("Recipient: %s"),
                        trim($recip)
                    );
                }
            }

            if (isset($parse['Disposition'])) {
                list($modes, $type) = explode(
                    ';',
                    $parse['Disposition']->value_single
                );
                list($action, $sent) = explode('/', $modes);

                switch (trim(Horde_String::lower($type))) {
                case 'displayed':
                    $mdn_status[] = _("The message has been displayed to the recipient.");
                    break;

                case 'deleted':
                    $mdn_status[] = _("The message has been deleted by the recipient; it is unknown whether they viewed the message.");
                    break;
                }

                switch (trim(Horde_String::lower($action))) {
                case 'manual-action':
                    // NOOP
                    break;

                case 'automatic-action':
                    // NOOP
                    break;
                }

                switch (trim(Horde_String::lower($sent))) {
                case 'mdn-sent-manually':
                    $mdn_status[] = _("This notification was explicitly sent by the recipient.");
                    break;

                case 'mdn-sent-automatically':
                    // NOOP
                    break;
                }
            }
        }

        $status = new IMP_Mime_Status($this->_mimepart, $mdn_status);
        $status->icon('info_icon.png', _("Info"));

        if ($original) {
            $status->addText(
                $imp_contents->linkViewJS(
                    $original,
                    'view_attach',
                    _("View the text of the original sent message."),
                    array(
                        'params' => array(
                            'ctype' => 'message/rfc822',
                            'mode' => IMP_Contents::RENDER_FULL
                        )
                    )
                )
            );
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
