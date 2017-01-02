<?php
/**
 * Horde_Core_ActiveSync_Mail_Draft::
 *
 * @copyright 2016-2017 Horde LLC (http://www.horde.org/)
 * @license http://www.horde.org/licenses/lgpl21 LGPL
 * @author  Michael J Rubinsky <mrubinsk@horde.org>
 * @package Core
 */
/**
 * Horde_Core_ActiveSync_Mail_Draft::
 *
 * Wraps functionality related to handling Draft email messages during sync.
 *
 * @copyright 2016-2017 Horde LLC (http://www.horde.org/)
 * @license http://www.horde.org/licenses/lgpl21 LGPL
 * @author  Michael J Rubinsky <mrubinsk@horde.org>
 * @package Core
 *
 * @todo  Move this, along with the parent class to the ActiveSync package and
 * inject any needed Core dependencies.
 */
class Horde_Core_ActiveSync_Mail_Draft extends Horde_Core_ActiveSync_Mail
{
    /**
     * Text part of Draft message.
     *
     * @var Horde_Mime_Part
     */
    protected $_textPart;

    /**
     * Existing Draft message.
     *
     * @var Horde_ActiveSync_Imap_Message
     */
    protected $_imapMessage;

    /**
     * Existing Draft message's UID.
     *
     * @var integer
     */
    protected $_draftUid;

    /**
     * Draft Email from client.
     *
     * @var Horde_ActiveSync_Message_Email
     */
    protected $_draftMessage;

    /**
     * Attachments to add.
     *
     * @var array An array of Horde_Mime_Part objects.
     */
    protected $_atcAdd = array();

    /**
     * Attachments to remove
     *
     * @var array An array of MIME part ids to remove.
     */
    protected $_atcDelete = array();

    /**
     * Append the current Draft message to the IMAP server.
     *
     * @return array  An array with the following keys:
     *     - uid: (integer)   The new draft message's IMAP UID.
     *     - atchash: (array) An attachment hash of newly added attachments.
     */
    public function append($folderid)
    {
        // Init
        $atc_map = array();
        $atc_hash = array();

        // Create the wrapper part.
        $base = new Horde_Mime_Part();
        $base->setType('multipart/mixed');

        // Check to see if we have any existing parts to add.
        if (!empty($this->_imapMessage)) {
            foreach ($this->_imapMessage->getStructure() as $part) {
                if ($part->isAttachment() &&
                    !in_array($part->getMimeId(), $this->_atcDelete)) {
                    $base->addPart(
                        $this->_imapMessage->getMimePart($part->getMimeId())
                    );
                }
            }
        }

        // Add body
        $base->addPart($this->_textPart);

        // Add Mime headers
        $base->addMimeHeaders(array(
            'headers' => $this->_headers)
        );

        foreach ($this->_atcAdd as $atc) {
            $base->addPart($atc);
            $atc_map[$atc->displayname] = $atc->clientid;
        }

        $stream = $base->toString(array(
            'stream' => true,
            'headers' => $this->_headers->toString()
        ));

        $new_uid = $this->_imap->appendMessage(
            $folderid,
            $stream,
            array('\draft', '\seen')
        );

        foreach ($base as $part) {
            if ($part->isAttachment() &&
                !empty($atc_map[$part->getName()])) {
                $atc_hash['add'][$atc_map[$part->getName()]] = $folderid . ':' . $stat['id'] . ':' . $part->getMimeId();
            }
        }

        // If we pulled down an existing Draft, delete it now since the
        // new one will replace it.
        if (!empty($this->_imapMessage)) {
            $this->_imap->deleteMessages(array($this->_draftUid), $folderid);
        }

        return array(
            'uid' => $new_uid,
            'atchash' => $atc_hash
        );
    }

    /**
     * Add the Draft message sent from the client.
     *
     * @param Horde_ActiveSync_Message_Mail $draft The draft message object.
     */
    public function setDraftMessage(Horde_ActiveSync_Message_Mail $draft)
    {
        // Save for later.
        $this->_draftMessage = $draft;

        // Create headers
        $this->_headers = new Horde_Mime_Headers();
        if ($draft->to) {
            $this->_headers->addHeader('To', $draft->to);
        }
        if ($draft->cc) {
            $this->_headers->addHeader('Cc', $draft->cc);
        }
        if ($draft->subject) {
            $this->_headers->addHeader('Subject', $draft->subject);
        }
        if ($draft->bcc) {
            $this->_headers->addHeader('Bcc', $draft->bcc);
        }
        if ($draft->importance) {
            $this->_headers->addHeader('importance', $draft->importance);
        }
        if ($from = $this->_getIdentityFromAddress()) {
            $this->_headers->removeHeader('From');
            $this->_headers->addHeader('From', $from);
        }
        if ($replyto = $this->_getReplyToAddress()) {
            $this->_headers->addHeader('Reply-To', $replyto);
        }
        $this->_headers->addHeaderOb(Horde_Mime_Headers_Date::create());
        $this->_headers->addHeaderOb(Horde_Mime_Headers_ContentId::create());
        $this->_headers->addHeader('X-IMP-Draft', 'Yes');

        // Get the text part and create a mime object for it.
        $this->_textPart = new Horde_Mime_Part();
        $this->_textPart->setContents($draft->airsyncbasebody->data);
        $this->_textPart->setType(
            $draft->airsyncbasebody->type == Horde_ActiveSync::BODYPREF_TYPE_HTML
                ? 'text/html'
                : 'text/plain'
        );

        // Attachments.
        $this->_handleAttachments();
    }

    protected function _handleAttachments()
    {
        // New attachments
        foreach ($this->_draftMessage->airsyncbaseattachments as $atc) {
            switch (get_class($atc)) {
            case 'Horde_ActiveSync_Message_AirSyncBaseAdd':
                $atc_mime = new Horde_Mime_Part();
                $atc_mime->setType($atc->contenttype);
                $atc_mime->setName($atc->displayname);
                $atc_mime->setContents($atc->content);
                $this->_atcAdd[] = $atc_mime;
                break;
            case 'Horde_ActiveSync_Message_AirSyncBaseDelete':
                list($mailbox, $uid, $part) = explode(':', $atc->filereference, 3);
                $this->_atcDelete[] = $part;
            }
        }
    }

    /**
     * Fetch an existing message from the Draft folder. This message will
     * be expunged after the new draft is appended to the IMAP server.
     *
     * @param string $folderid  The Draft folder's folderid.
     * @param integer $uid      The UID of the existing Draft message.
     *
     * @throws  Horde_ActiveSync_Exception
     */
    public function getExistingDraftMessage($folderid, $uid)
    {
        $imap_msg = $this->_imap->getImapMessage($folderid,$uid);
        if (empty($imap_msg[$uid])) {
             throw new Horde_ActiveSync_Exception(sprintf(
                'Unable to fetch %d from %s.',
                $uid, $folderid)
            );
        }
        $this->_imapMessage = $imap_msg[$uid];
        $this->_draftUid = $uid;
    }

}