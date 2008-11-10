<?php
/**
 * The IMP_Fetchmail_imap driver implements the IMAP_Fetchmail class for use
 * with IMAP/POP3 servers.
 *
 * $Horde: imp/lib/Fetchmail/imap.php,v 1.24 2008/11/09 07:16:01 slusarz Exp $
 *
 * Copyright 2002-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Nuno Loureiro <nuno@co.sapo.pt>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package IMP
 */
class IMP_Fetchmail_imap extends IMP_Fetchmail
{
    /**
     * The Horde_Imap_Client object.
     *
     * @var Horde_Imap_Client
     */
    protected $_ob = null;

    /**
     * Returns a description of the driver.
     *
     * @see IMP_Fetchmail::description()
     */
    static public function description()
    {
        return _("IMAP/POP3 Mail Servers");
    }

    /**
     * Return a list of protocols supported by this driver.
     *
     * @see IMP_Fetchmail::getProtocolList()
     */
    public function getProtocolList()
    {
        $output = array();
        foreach ($this->_protocolList() as $key => $val) {
            $output[$key] = $val['name'];
        }
        return $output;
    }

    /**
     * Returns the list of IMAP/POP3 protocols that this driver supports, and
     * associated configuration options.
     * This needs to be in a separate function because PHP will not allow
     * gettext strings to appear in member variables.
     *
     * @return array  The protocol configuration list.
     */
    protected function _protocolList()
    {
        return array(
            'pop3' => array(
                'name' => _("POP3"),
                'string' => 'pop3',
                'port' => 110,
                'base' => 'POP3'
            ),
            'pop3sslvalid' => array(
                'name' => _("POP3 over SSL"),
                'string' => 'pop3',
                'port' => 995,
                'base' => 'POP3'
            ),
            'imap' => array(
                'name' => _("IMAP"),
                'string' => 'imap',
                'port' => 143,
                'base' => 'IMAP'
            ),
            'imapsslvalid' => array(
                'name' => _("IMAP over SSL"),
                'string' => 'imap',
                'port' => 993,
                'base' => 'IMAP'
            )
        );
    }

    /**
     * Checks if the remote mailbox exists.
     *
     * @return boolean  Does the remote mailbox exist?
     */
    protected function _remoteMboxExists($mbox)
    {
        if (strcasecmp($mbox, 'INBOX') === 0) {
            /* INBOX always exists and is a special case. */
            return true;
        }

        try {
            $res = $this->_ob->listMailboxes($mbox, array('flat' => true));
            return (bool)count($res);
        } catch (Horde_Imap_Client_Exception $e) {
            $GLOBALS['imp_imap']->logException($e);
            return false;
        }
    }

    /**
     * Attempts to connect to the mail server
     *
     * @return mixed  Returns true on success or PEAR_Error on failure.
     */
    protected function _connect()
    {
        if (!is_null($this->_ob)) {
            return true;
        }

        $protocols = $this->_protocolList();

        /* Create the server string now. */
        $imap_config = array(
            'hostspec' => $this->_params['server'],
            'password' => $this->_params['password'],
            'port' => $protocols[$this->_params['protocol']]['port'],
            'username' => $this->_params['username']
            // TODO: secure
        );

        try {
            $this->_ob = Horde_Imap_Client::getInstance(($protocol == 'imap') ? 'Socket' : 'Cclient-pop3', $imap_config);
            return true;
        } catch (Horde_Imap_Client_Exception $e) {
            $GLOBALS['imp_imap']->logException($e);
            return PEAR::raiseError(_("Cannot connect to the remote mail server: ") . $e->getMessage());
        }
    }

    /**
     * Gets the mail using the data in this object.
     *
     * @see IMP_Fetchmail::getMail()
     */
    public function getMail()
    {
        $flags = $to_store = array();
        $numMsgs = 0;

        $stream = $this->_connect();
        if (is_a($stream, 'PEAR_Error')) {
            return $stream;
        }

        /* Check to see if remote mailbox exists. */
        $mbox = $this->_params['rmailbox'];
        if (!$mbox || !$this->_remoteMboxExists($mbox)) {
            return PEAR::raiseError(_("Invalid Remote Mailbox"));
        }

        $query = new Horde_Imap_Client_Search_Query();
        $query->flag('\\deleted', false);
        if ($this->_params['onlynew']) {
            $query->flag('\\seen', false);
        }

        try {
            $search_res = $GLOBALS['imp_imap']->ob->search($mbox, $query);
            if (empty($search_res['match'])) {
                return 0;
            }
            $fetch_res = $GLOBALS['imp_imap']->ob->fetch($mbox, array(
                Horde_Imap_Client::FETCH_ENVELOPE => true,
                Horde_Imap_Client::FETCH_SIZE => true

            ), array('ids' => $search_res['match']));
        } catch (Horde_Imap_Client_Exception $e) {
            $GLOBALS['imp_imap']->logException($e);
            return 0;
        }

        reset($fetch_res);
        while (list($id, $ob) = each($fetch_res)) {
            /* Check message size. */
            if (!$this->_checkMessageSize($ob['size'], $ob['envelope']['subject'], Horde_Mime_Address::addrArray2String($ob['envelope']['from']))) {
                continue;
            }

            try {
                $res = $GLOBALS['imp_imap']->ob->fetch($this->_mailbox, array(
                    Horde_Imap_Client::FETCH_HEADERTEXT => array(array('peek' => true)),
                    Horde_Imap_Client::FETCH_BODYTEXT => array(array('peek' => true))
                ), array('ids' => array($this->_index)));
                $mail_source = $this->_processMailMessage($res[$this->_index]['headertext'][0], $res[$this->_index]['bodytext'][0]);
            } catch (Horde_Imap_Client_Exception $e) {
                $GLOBALS['imp_imap']->logException($e);
                continue;
            }

            /* Append to the server. */
            if ($this->_addMessage($mail_source)) {
                ++$numMsgs;
                $to_store[] = $id;
            }
        }

        /* Remove the mail if 'del' is set. */
        if ($this->_params['del']) {
            $flags[] = '\\deleted';
        }

        /* Mark message seen if 'markseen' is set. */
        if ($this->_params['markseen']) {
            $flags[] = '\\seen';
        }

        if (!empty($flags)) {
            try {
                $imp_imap->ob->store($mbox, array('add' => $flags, 'ids' => $to_store));
                if ($this->_params['del']) {
                    $imp_imap->ob->expunge($mbox, array('ids' => $to_store));
                }
            } catch (Horde_Imap_Client_Exception $e) {
                $GLOBALS['imp_imap']->logException($e);
            }
        }

        return $numMsgs;
    }
}
