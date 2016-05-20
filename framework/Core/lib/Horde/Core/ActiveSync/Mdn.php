<?php
/**
 *
 */
class Horde_Core_ActiveSync_Mdn
{
    /**
     * @var Horde_Core_ActiveSync_Connector
     */
    protected $_connector;

    /**
     * @var string
     */
    protected $_mailbox;

    /**
     * @var integer
     */
    protected $_uid;

    /**
     * @var Horde_ActiveSync_Imap_Adapter
     */
    protected $_imap;

    /**
     * @var Horde_ActiveSync_Imap_Message
     */
    protected $_msg;

    /**
     * Const'r
     *
     * @param  string                          $mailbox
     * @param  integer                         $uid
     * @param  Horde_ActiveSync_Imap_Adapter   $imap
     * @param  Horde_Core_ActiveSync_Connector $connector
     */
    public function __construct(
        $mailbox, $uid, Horde_ActiveSync_Imap_Adapter $imap,
        Horde_Core_ActiveSync_Connector $connector)
    {
        $this->_imap = $imap;
        $this->_mailbox = $mailbox;
        $this->_uid = $uid;
        $this->_connector = $connector;
    }

    public function __call($method, $args)
    {
        switch ($method) {
        case 'headers':
            if (empty($this->_msg)) {
                return false;
            }
            return $this->_msg->getHeaders();
        case 'mailbox':
            return $this->_mailbox;
        case 'uid':
            return $this->_uid;
        }
    }

    /**
     * Check if we should and are able to send an MDN.
     *
     * @boolean
     */
    public function mdnCheck()
    {
        if (!$this->_sysCheck() || !$this->_msgCheck()) {
            return false;
        }

        return $this->_connector->mdnSend($this);
    }

    /**
     * Check to see if we are able to send an unconfirmed MDN based on the
     * message data.
     *
     * @return boolean  True if able to send, otherwise false.
     */
    protected function _msgCheck()
    {
        $msgs = $this->_imap->getImapMessage($this->_mailbox, $this->_uid, array('headers' => true));
        if (!count($msgs)) {
            return false;
        }
        $imap_msg = array_pop($msgs);
        $mdn = new Horde_Mime_Mdn($imap_msg->getHeaders());
        if ($mdn->getMdnReturnAddr() && !$mdn->userConfirmationNeeded()) {
            $this->_msg = $imap_msg;
            return true;
        }

        return false;
    }

    /**
     * Check system prefs and/or hooks to determine if we are allowed to send.
     *
     * @return boolean  True if able to send otherwise false.
     */
    protected function _sysCheck()
    {
        // Check existence of API method needed.
        if (!$this->_connector->horde_hasMethod('mdnSend', 'mail')) {
            return false;
        }

        // @todo Other checks?

        return true;
    }

}