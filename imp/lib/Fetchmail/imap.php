<?php
/**
 * The IMP_Fetchmail_imap driver implements the IMAP_Fetchmail class for use
 * with IMAP/POP3 servers.
 *
 * Copyright 2002-2009 The Horde Project (http://www.horde.org/)
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
                'base' => 'POP3',
                'secure' => false
            ),
            'pop3tls' => array(
                'name' => _("POP3 over TLS"),
                'string' => 'pop3',
                'port' => 110,
                'base' => 'POP3',
                'secure' => 'tls'
            ),
            'pop3ssl' => array(
                'name' => _("POP3 over SSL"),
                'string' => 'pop3',
                'port' => 995,
                'base' => 'POP3',
                'secure' => 'ssl'
            ),
            'imap' => array(
                'name' => _("IMAP"),
                'string' => 'imap',
                'port' => 143,
                'base' => 'IMAP',
                'secure' => false
            ),
            'imaptls' => array(
                'name' => _("IMAP"),
                'string' => 'imap over TLS',
                'port' => 143,
                'base' => 'IMAP',
                'secure' => 'tls'
            ),
            'imapsslvalid' => array(
                'name' => _("IMAP over SSL"),
                'string' => 'imap',
                'port' => 993,
                'base' => 'IMAP',
                'secure' => 'ssl'
            )
        );
    }

    /**
     * Attempts to connect to the mail server
     *
     * @throws Horde_Exception
     */
    protected function _connect()
    {
        if (!is_null($this->_ob)) {
            return;
        }

        $protocols = $this->_protocolList();

        /* Create the server string now. */
        $imap_config = array(
            'hostspec' => $this->_params['server'],
            'password' => $this->_params['password'],
            'port' => $protocols[$this->_params['protocol']]['port'],
            'username' => $this->_params['username'],
            'secure' => $protocols[$this->_params['protocol']]['secure']
        );

        try {
            $this->_ob = Horde_Imap_Client::getInstance(($protocols[$this->_params['protocol']]['string'] == 'imap') ? 'Socket' : 'Socket_Pop3', $imap_config);
        } catch (Horde_Imap_Client_Exception $e) {
            throw new Horde_Exception(_("Cannot connect to the remote mail server: ") . $e->getMessage());
        }
    }

    /**
     * Gets the mail using the data in this object.
     *
     * @see IMP_Fetchmail::getMail()
     * @throws Horde_Exception
     */
    public function getMail()
    {
        $to_store = array();
        $numMsgs = 0;

        $stream = $this->_connect();

        /* Check to see if remote mailbox exists. */
        $mbox = $this->_params['rmailbox'];

        /* INBOX always exists and is a special case. */
        if ($mbox && strcasecmp($mbox, 'INBOX') !== 0) {
            try {
                $res = $this->_ob->listMailboxes($mbox, array('flat' => true));
                if (!count($res)) {
                    $mbox = false;
                }
            } catch (Horde_Imap_Client_Exception $e) {
                $mbox = false;
            }
        }

        if (!$mbox) {
            throw new Horde_Exception(_("Invalid Remote Mailbox"));
        }

        $query = new Horde_Imap_Client_Search_Query();
        $query->flag('\\deleted', false);
        if ($this->_params['onlynew']) {
            $query->flag('\\seen', false);
        }

        try {
            $search_res = $this->_ob->search($mbox, $query);
            if (empty($search_res['match'])) {
                return 0;
            }
            $fetch_res = $this->_ob->fetch($mbox, array(
                Horde_Imap_Client::FETCH_ENVELOPE => true,
                Horde_Imap_Client::FETCH_SIZE => true

            ), array('ids' => $search_res['match']));
        } catch (Horde_Imap_Client_Exception $e) {
            return 0;
        }

        /* Mark message seen if 'markseen' is set. */
        $peek = !$this->_params['markseen'];

        reset($fetch_res);
        while (list($id, $ob) = each($fetch_res)) {
            /* Check message size. */
            if (!$this->_checkMessageSize($ob['size'], $ob['envelope']['subject'], Horde_Mime_Address::addrArray2String($ob['envelope']['from']))) {
                continue;
            }

            try {
                $res = $this->_ob->fetch($mbox, array(
                    Horde_Imap_Client::FETCH_HEADERTEXT => array(array('peek' => $peek)),
                    Horde_Imap_Client::FETCH_BODYTEXT => array(array('peek' => true))
                ), array('ids' => array($id)));
            } catch (Horde_Imap_Client_Exception $e) {
                continue;
            }

            /* Append to the server. */
            if ($this->_addMessage($res[$id]['headertext'][0], $res[$id]['bodytext'][0])) {
                ++$numMsgs;
                $to_store[] = $id;
            }
        }

        /* Remove the mail if 'del' is set. */
        if ($numMsgs && $this->_params['del']) {
            try {
                $imp_imap->ob->store($mbox, array('add' => array('\\deleted'), 'ids' => $to_store));
                $imp_imap->ob->expunge($mbox, array('ids' => $to_store));
            } catch (Horde_Imap_Client_Exception $e) {}
        }

        return $numMsgs;
    }

}
