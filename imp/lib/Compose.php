<?php
/**
 * The IMP_Compose:: class contains functions related to generating
 * outgoing mail messages.
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
class IMP_Compose
{
    /* The virtual path to use for VFS data. */
    const VFS_ATTACH_PATH = '.horde/imp/compose';

    /* The virtual path to save linked attachments. */
    const VFS_LINK_ATTACH_PATH = '.horde/imp/attachments';

    /* The virtual path to save drafts. */
    const VFS_DRAFTS_PATH = '.horde/imp/drafts';

    /**
     * The cached attachment data.
     *
     * @var array
     */
    protected $_cache = array();

    /**
     * Various metadata for this message.
     *
     * @var array
     */
    protected $_metadata = array();

    /**
     * The aggregate size of all attachments (in bytes).
     *
     * @var integer
     */
    protected $_size = 0;

    /**
     * Whether the user's PGP public key should be attached to outgoing
     * messages.
     *
     * @var boolean
     */
    protected $_pgpAttachPubkey = false;

    /**
     * Whether the user's vCard should be attached to outgoing messages.
     *
     * @var boolean
     */
    protected $_attachVCard = false;

    /**
     * Whether attachments should be linked.
     *
     * @var boolean
     */
    protected $_linkAttach = false;

    /**
     * The cache ID used to store object in session.
     *
     * @var string
     */
    protected $_cacheid;

    /**
     * Mark as modified for purposes of storing in the session.
     *
     * @var boolean
     */
    protected $_modified = false;

    /**
     * Constructor.
     *
     * @param string $cacheid  The cache ID string.
     */
    public function __construct($cacheid)
    {
        $this->_cacheid = $cacheid;
        $this->__wakeup();
    }

    /**
     * Code to run on unserialize().
     */
    public function __wakeup()
    {
        register_shutdown_function(array($this, 'shutdown'));
    }

    /**
     * Store a serialized version of ourself in the current session on
     * shutdown.
     */
    public function shutdown()
    {
        if ($this->_modified) {
            $this->_modified = false;
            $obs = $GLOBALS['injector']->getInstance('Horde_SessionObjects');
            $obs->overwrite($this->_cacheid, $this, false);
        }
    }

    /**
     * Destroys an IMP_Compose instance.
     *
     * @param string $action  The action performed to cause the end of this
     *                        instance.  Either 'cancel', 'save_draft', or
     *                        'send'.
     */
    public function destroy($action)
    {
        $uids = new IMP_Indices();

        switch ($action) {
        case 'save_draft':
            /* Don't delete any drafts. */
            break;

        case 'send':
            /* Delete the auto-draft and the original resumed draft. */
            $uids->add($this->getMetadata('draft_uid_resume'));
            // Fall-through

        case 'cancel':
            /* Delete the auto-draft, but save the original resume draft. */
            $uids->add($this->getMetadata('draft_uid'));
            break;
        }

        $GLOBALS['injector']->getInstance('IMP_Message')->delete($uids, array('nuke' => true));

        $this->deleteAllAttachments();
        $obs = $GLOBALS['injector']->getInstance('Horde_SessionObjects');
        $obs->prune($this->_cacheid);
    }

    /**
     * Gets metadata about the current object.
     *
     * @param string $name  The metadata name.
     *
     * @return mixed  The metadata value or null if it doesn't exist.
     */
    public function getMetadata($name)
    {
        return isset($this->_metadata[$name])
            ? $this->_metadata[$name]
            : null;
    }

    /**
     * Saves a message to the draft folder.
     *
     * @param array $header    List of message headers.
     * @param mixed $message   Either the message text (string) or a
     *                         Horde_Mime_Part object that contains the
     *                         text to send.
     * @param string $charset  The charset that was used for the headers.
     * @param boolean $html    Whether this is an HTML message.
     *
     * @return string  Notification text on success.
     * @throws IMP_Compose_Exception
     */
    public function saveDraft($headers, $message, $charset, $html)
    {
        $body = $this->_saveDraftMsg($headers, $message, $charset, $html, true);
        return $this->_saveDraftServer($body);
    }

    /**
     * Prepare the draft message.
     *
     * @param array $headers    List of message headers.
     * @param mixed $message    Either the message text (string) or a
     *                          Horde_Mime_Part object that contains the
     *                          text to send.
     * @param string $charset   The charset that was used for the headers.
     * @param boolean $html     Whether this is an HTML message.
     * @param boolean $session  Do we have an active session?
     *
     * @return string  The body text.
     * @throws IMP_Compose_Exception
     */
    protected function _saveDraftMsg($headers, $message, $charset, $html,
                                     $session)
    {
        /* Set up the base message now. */
        $mime = $this->_createMimeMessage(array(null), $message, $charset, array('html' => $html, 'nofinal' => true, 'noattach' => !$session));
        $base = $mime['msg'];
        $base->isBasePart(true);

        /* Initalize a header object for the draft. */
        $draft_headers = new Horde_Mime_Headers();

        $draft_headers->addHeader('Date', date('r'));
        if (!empty($headers['from'])) {
            $draft_headers->addHeader('From', $headers['from']);
        }
        foreach (array('to' => 'To', 'cc' => 'Cc', 'bcc' => 'Bcc') as $k => $v) {
            if (!empty($headers[$k])) {
                $addr = $headers[$k];
                if ($session) {
                    try {
                        Horde_Mime::encodeAddress(self::formatAddr($addr), $charset, $_SESSION['imp']['maildomain']);
                    } catch (Horde_Mime_Exception $e) {
                        throw new IMP_Compose_Exception(sprintf(_("Saving the draft failed. The %s header contains an invalid e-mail address: %s."), $k, $e->getMessage()), $e->getCode());
                    }
                }
                $draft_headers->addHeader($v, $addr);
            }
        }

        if (!empty($headers['subject'])) {
            $draft_headers->addHeader('Subject', $headers['subject']);
        }

        /* Need to add Message-ID so we can use it in the index search. */
        $draft_headers->addMessageIdHeader();

        /* Add necessary headers for replies. */
        $this->_addReferences($draft_headers);

        /* Add information necessary to log replies/forwards when finally
         * sent. */
        if ($this->getMetadata('reply_type')) {
            $imp_imap = $GLOBALS['injector']->getInstance('IMP_Imap')->getOb();
            try {
                $imap_url = $imp_imap->getUtils()->createUrl(array(
                    'type' => $_SESSION['imp']['protocol'],
                    'username' => $imp_imap->getParam('username'),
                    'hostspec' => $imp_imap->getParam('hostspec'),
                    'mailbox' => $this->getMetadata('mailbox'),
                    'uid' => $this->getMetadata('uid'),
                    'uidvalidity' => $imp_imap->checkUidvalidity($this->getMetadata('mailbox'))
                ));

                switch ($this->getMetadata('reply_type')) {
                case 'forward':
                    $draft_headers->addHeader('X-IMP-Draft-Forward', '<' . $imap_url . '>');
                    break;

                // 'reply', 'reply_all', 'reply_list'
                default:
                    $draft_headers->addHeader('X-IMP-Draft-Reply', '<' . $imap_url . '>');
                    $draft_headers->addHeader('X-IMP-Draft-Reply-Type', $this->getMetadata('reply_type'));
                    break;
                }
            } catch (Horde_Exception $e) {}
        }

        return $base->toString(array(
            'defserver' => $session ? $_SESSION['imp']['maildomain'] : null,
            'headers' => $draft_headers
        ));
    }

    /**
     * Save a draft message on the IMAP server.
     *
     * @param string $data  The text of the draft message.
     *
     * @return string  Status string.
     * @throws IMP_Compose_Exception
     */
    protected function _saveDraftServer($data)
    {
        $drafts_mbox = IMP::folderPref($GLOBALS['prefs']->getValue('drafts_folder'), true);
        if (empty($drafts_mbox)) {
            throw new IMP_Compose_Exception(_("Saving the draft failed. No draft folder specified."));
        }

        $imp_folder = $GLOBALS['injector']->getInstance('IMP_Folder');

        /* Check for access to drafts folder. */
        if (!$imp_folder->exists($drafts_mbox) &&
            !$imp_folder->create($drafts_mbox, $GLOBALS['prefs']->getValue('subscribe'))) {
            throw new IMP_Compose_Exception(_("Saving the draft failed. Could not create a drafts folder."));
        }

        $append_flags = array('\\draft');
        if (!$GLOBALS['prefs']->getValue('unseen_drafts')) {
            $append_flags[] = '\\seen';
        }

        /* RFC 3503 [3.4] states that when saving a draft, the client MUST
         * set the $MDNSent keyword. However, IMP doesn't write MDN headers
         * until send time so no need to set the flag here. */

        $drafts_mbox = IMP::folderPref($GLOBALS['prefs']->getValue('drafts_folder'), true);
        $old_uid = $this->getMetadata('draft_uid');

        /* Add the message to the mailbox. */
        try {
            $ids = $GLOBALS['injector']->getInstance('IMP_Imap')->getOb()->append($drafts_mbox, array(array('data' => $data, 'flags' => $append_flags)));

            if ($old_uid) {
                $GLOBALS['injector']->getInstance('IMP_Message')->delete($old_uid, array('nuke' => true));
            }

            $this->_metadata['draft_uid'] = new IMP_Indices($drafts_mbox, reset($ids));
            $this->_modified = true;
            return sprintf(_("The draft has been saved to the \"%s\" folder."), IMP::displayFolder($drafts_mbox));
        } catch (Horde_Imap_Client_Exception $e) {
            return _("The draft was not successfully saved.");
        }
    }

    /**
     * Resumes a previously saved draft message.
     *
     * @param IMP_Indices $indices  An indices object.
     *
     * @return mixed  An array with the following keys:
     * <pre>
     * 'msg' - (string) The message text.
     * 'mode' - (string) 'html' or 'text'.
     * 'header' - (array) A list of headers to add to the outgoing message.
     * 'identity' - (integer) The identity used to create the message.
     * </pre>
     * @throws IMP_Compose_Exception
     */
    public function resumeDraft($indices)
    {
        try {
            $contents = $GLOBALS['injector']->getInstance('IMP_Contents')->getOb($indices);
        } catch (IMP_Exception $e) {
            throw new IMP_Compose_Exception($e);
        }

        $msg_text = $this->_getMessageText($contents, array('type' => 'draft'));
        if (empty($msg_text)) {
            $message = '';
            $mode = 'text';
            $text_id = 0;
        } else {
            $message = $msg_text['text'];
            $mode = $msg_text['mode'];
            $text_id = $msg_text['id'];
        }

        $mime_message = $contents->getMIMEMessage();

        if ($mime_message->getType() != 'multipart/alternative') {
            $skip = (intval($text_id) == 1)
                ? array('skip' => array(1))
                : array();
            $this->attachFilesFromMessage($contents, (intval($text_id) === 1) ? array('notify' => true, 'skip' => $skip) : array());
        }

        $identity_id = null;
        $headers = $contents->getHeaderOb();
        if (($fromaddr = Horde_Mime_Address::bareAddress($headers->getValue('from')))) {
            $identity = $GLOBALS['injector']->getInstance('IMP_Identity');
            $identity_id = $identity->getMatchingIdentity($fromaddr);
        }

        $charset = $GLOBALS['registry']->getCharset();
        $header = array(
            'to' => Horde_Mime_Address::addrArray2String($headers->getOb('to'), array('charset' => $charset)),
            'cc' => Horde_Mime_Address::addrArray2String($headers->getOb('cc'), array('charset' => $charset)),
            'bcc' => Horde_Mime_Address::addrArray2String($headers->getOb('bcc'), array('charset' => $charset)),
            'subject' => $headers->getValue('subject')
        );

        if ($val = $headers->getValue('references')) {
            $this->_metadata['references'] = $val;

            if ($val = $headers->getValue('in-reply-to')) {
                $this->_metadata['in_reply_to'] = $val;
            }
        }

        if ($val = $headers->getValue('x-imp-draft-reply')) {
            if (!($reply_type = $headers->getValue('x-imp-draft-reply-type'))) {
                $reply_type = 'reply';
            }
        } elseif ($val = $headers->getValue('x-imp-draft-forward')) {
            $reply_type = 'forward';
        }

        if ($val) {
            $imp_imap = $GLOBALS['injector']->getInstance('IMP_Imap')->getOb();
            $imap_url = $imp_imap->getUtils()->parseUrl(rtrim(ltrim($val, '<'), '>'));

            try {
                if (($imap_url['type'] == $_SESSION['imp']['protocol']) &&
                    ($imap_url['username'] == $imp_imap->getParam('username')) &&
                    // Ignore hostspec and port, since these can change
                    // even though the server is the same. UIDVALIDITY should
                    // catch any true server/backend changes.
                    ($imp_imap->checkUidvalidity($imap_url['mailbox']) == $imap_url['uidvalidity']) &&
                    $GLOBALS['injector']->getInstance('IMP_Contents')->getOb(new IMP_Indices($imap_url['mailbox'], $imap_url['uid']))) {
                    $this->_metadata['mailbox'] = $imap_url['mailbox'];
                    $this->_metadata['reply_type'] = $reply_type;
                    $this->_metadata['uid'] = $imap_url['uid'];
                }
            } catch (Exception $e) {}
        }

        $this->_metadata['draft_uid_resume'] = $indices;
        $this->_modified = true;

        return array(
            'header' => $header,
            'identity' => $identity_id,
            'mode' => $mode,
            'msg' => $message
        );
    }

    /**
     * Builds and sends a MIME message.
     *
     * @param string $body     The message body.
     * @param array $header    List of message headers.
     * @param string $charset  The sending charset.
     * @param boolean $html    Whether this is an HTML message.
     * @param array $opts      An array of options w/the following keys:
     * <pre>
     * 'encrypt' => (integer) A flag whether to encrypt or sign the message.
     *              One of IMP::PGP_ENCRYPT, IMP::PGP_SIGNENC,
     *              IMP::SMIME_ENCRYPT, or IMP::SMIME_SIGNENC.
     * 'identity' => (IMP_Prefs_Identity) If set, checks for proper tie-to
     *               addresses.
     * 'priority' => (string) The message priority ('high', 'normal', 'low').
     * 'save_sent' = (bool) Save sent mail?
     * 'sent_folder' = (string) The sent-mail folder (UTF7-IMAP).
     * 'save_attachments' = (bool) Save attachments with the message?
     * 'readreceipt' => (bool) Add return receipt headers?
     * 'useragent' => (string) The User-Agent string to use.
     * </pre>
     *
     * @return boolean  Whether the sent message has been saved in the
     *                  sent-mail folder.
     * @throws Horde_Exception
     * @throws IMP_Compose_Exception
     * @throws IMP_Exception
     */
    public function buildAndSendMessage($body, $header, $charset, $html,
                                        $opts = array())
    {
        global $conf, $notification, $prefs, $registry;

        /* We need at least one recipient & RFC 2822 requires that no 8-bit
         * characters can be in the address fields. */
        $recip = $this->recipientList($header);
        $header = array_merge($header, $recip['header']);

        /* Check for correct identity usage. */
        if (!$this->getMetadata('identity_check') &&
            (count($recip['list']) === 1) &&
            isset($opts['identity'])) {
            $identity_search = $opts['identity']->getMatchingIdentity($recip['list'], false);
            if (!is_null($identity_search) &&
                ($opts['identity']->getDefault() != $identity_search)) {
                $this->_metadata['identity_check'] = true;
                $e = new IMP_Compose_Exception(_("Recipient address does not match the currently selected identity."));
                $e->tied_identity = $identity_search;
                throw $e;
            }
        }

        $barefrom = Horde_Mime_Address::bareAddress($header['from'], $_SESSION['imp']['maildomain']);
        $encrypt = empty($opts['encrypt']) ? 0 : $opts['encrypt'];
        $recipients = implode(', ', $recip['list']);

        /* Prepare the array of messages to send out.  May be more
         * than one if we are encrypting for multiple recipients or
         * are storing an encrypted message locally. */
        $send_msgs = array();
        $msg_options = array(
            'encrypt' => $encrypt,
            'html' => $html
        );

        /* Must encrypt & send the message one recipient at a time. */
        if ($prefs->getValue('use_smime') &&
            in_array($encrypt, array(IMP::SMIME_ENCRYPT, IMP::SMIME_SIGNENC))) {
            foreach ($recip['list'] as $val) {
                $send_msgs[] = $this->_createMimeMessage(array($val), $body, $charset, $msg_options);
            }

            /* Must target the encryption for the sender before saving message
             * in sent-mail. */
            $save_msg = $this->_createMimeMessage(array($header['from']), $body, $charset, $msg_options);
        } else {
            /* Can send in clear-text all at once, or PGP can encrypt
             * multiple addresses in the same message. */
            $msg_options['from'] = $barefrom;
            $send_msgs[] = $save_msg = $this->_createMimeMessage($recip['list'], $body, $charset, $msg_options);
        }

        /* Initalize a header object for the outgoing message. */
        $headers = new Horde_Mime_Headers();

        /* Add a Received header for the hop from browser to server. */
        $headers->addReceivedHeader(array(
            'dns' => $GLOBALS['injector']->getInstance('Net_DNS_Resolver'),
            'server' => $GLOBALS['conf']['server']['name']
        ));
        $headers->addMessageIdHeader();

        /* Add priority header, if requested. */
        if (!empty($opts['priority'])) {
            switch ($opts['priority']) {
            case 'high':
                $headers->addHeader('Importance', 'High');
                $headers->addHeader('X-Priority', '1 (Highest)');
                break;

            case 'low':
                $headers->addHeader('Importance', 'Low');
                $headers->addHeader('X-Priority', '5 (Lowest)');
                break;
            }
        }

        $headers->addHeader('Date', date('r'));

        /* Add Return Receipt Headers. */
        $mdn = null;
        if (!empty($opts['readreceipt']) &&
            $conf['compose']['allow_receipts']) {
            $mdn = new Horde_Mime_Mdn($headers);
            $mdn->addMdnRequestHeaders($barefrom);
        }

        $browser_charset = $GLOBALS['registry']->getCharset();

        $headers->addHeader('From', Horde_String::convertCharset($header['from'], $browser_charset, $charset));

        if (!empty($header['replyto']) &&
            ($header['replyto'] != $barefrom)) {
            $headers->addHeader('Reply-to', Horde_String::convertCharset($header['replyto'], $browser_charset, $charset));
        }
        if (!empty($header['to'])) {
            $headers->addHeader('To', Horde_String::convertCharset($header['to'], $browser_charset, $charset));
        } elseif (empty($header['to']) && empty($header['cc'])) {
            $headers->addHeader('To', 'undisclosed-recipients:;');
        }
        if (!empty($header['cc'])) {
            $headers->addHeader('Cc', Horde_String::convertCharset($header['cc'], $browser_charset, $charset));
        }
        $headers->addHeader('Subject', Horde_String::convertCharset($header['subject'], $browser_charset, $charset));

        /* Add necessary headers for replies. */
        $this->_addReferences($headers);

        /* Add the 'User-Agent' header. */
        if (empty($opts['useragent'])) {
            $headers->setUserAgent('Internet Messaging Program (IMP) ' . $GLOBALS['registry']->getVersion());
        } else {
            $headers->setUserAgent($opts['useragent']);
        }
        $headers->addUserAgentHeader();

        /* Tack on any site-specific headers. */
        try {
            $headers_result = Horde::loadConfiguration('header.php', '_header');
            if (is_array($headers_result)) {
                foreach ($headers_result as $key => $val) {
                    $headers->addHeader(trim($key), Horde_String::convertCharset(trim($val), $GLOBALS['registry']->getCharset(), $charset));
                }
            }
        } catch (Horde_Exception $e) {}

        $sentmail = $GLOBALS['injector']->getInstance('IMP_Sentmail');

        /* Send the messages out now. */
        if (!($reply_type = $this->getMetadata('reply_type'))) {
            $reply_type = 'new';
        }

        foreach ($send_msgs as $val) {
            try {
                $this->sendMessage($val['to'], $headers, $val['msg']);
            } catch (IMP_Compose_Exception $e) {
                /* Unsuccessful send. */
                Horde::logMessage($e, 'ERR');
                $sentmail->log($reply_type, $headers->getValue('message-id'), $val['recipients'], false);
                throw new IMP_Compose_Exception(sprintf(_("There was an error sending your message: %s"), $e->getMessage()));
            }

            /* Store history information. */
            $sentmail->log($reply_type, $headers->getValue('message-id'), $val['recipients'], true);
        }

        $sent_saved = true;

        if ($reply_type != 'new') {
            /* Log the reply. */
            if ($this->getMetadata('in_reply_to') &&
                !empty($conf['maillog']['use_maillog'])) {
                IMP_Maillog::log($reply_type, $this->getMetadata('in_reply_to'), $recipients);
            }

            $imp_message = $GLOBALS['injector']->getInstance('IMP_Message');
            $reply_uid = new IMP_Indices($this);

            switch ($reply_type) {
            case 'forward':
                /* Set the '$Forwarded' flag, if possible, in the mailbox.
                 * See RFC 5550 [5.9] */
                $imp_message->flag(array('$Forwarded'), $reply_uid);
                break;

            // 'reply', 'reply_all', 'reply_list'
            default:
                /* Make sure to set the IMAP reply flag and unset any
                 * 'flagged' flag. */
                $imp_message->flag(array('\\answered'), $reply_uid);
                $imp_message->flag(array('\\flagged'), $reply_uid, false);
                break;

            }
        }

        $entry = sprintf("%s Message sent to %s from %s", $_SERVER['REMOTE_ADDR'], $recipients, $GLOBALS['registry']->getAuth());
        Horde::logMessage($entry, 'INFO');

        /* Should we save this message in the sent mail folder? */
        if (!empty($opts['sent_folder']) &&
            ((!$prefs->isLocked('save_sent_mail') && !empty($opts['save_sent'])) ||
             ($prefs->isLocked('save_sent_mail') &&
              $prefs->getValue('save_sent_mail')))) {

            $mime_message = $save_msg['msg'];

            /* Keep Bcc: headers on saved messages. */
            if (!empty($header['bcc'])) {
                $headers->addHeader('Bcc', Horde_String::convertCharset($header['bcc'], $browser_charset, $charset));
            }

            /* Strip attachments if requested. */
            $save_attach = $prefs->getValue('save_attachments');
            if (($save_attach == 'never') ||
                ((strpos($save_attach, 'prompt') === 0) &&
                 empty($opts['save_attachments']))) {
                $mime_message->buildMimeIds();
                for ($i = 2;; ++$i) {
                    if (!($oldPart = $mime_message->getPart($i))) {
                        break;
                    }

                    $replace_part = new Horde_Mime_Part();
                    $replace_part->setType('text/plain');
                    $replace_part->setCharset($charset);
                    $replace_part->setContents('[' . _("Attachment stripped: Original attachment type") . ': "' . $oldPart->getType() . '", ' . _("name") . ': "' . $oldPart->getName(true) . '"]');
                    $mime_message->alterPart($i, $replace_part);
                }
            }

            /* Generate the message string. */
            $fcc = $mime_message->toString(array('defserver' => $_SESSION['imp']['maildomain'], 'headers' => $headers, 'stream' => true));

            $imp_folder = $GLOBALS['injector']->getInstance('IMP_Folder');

            if (!$imp_folder->exists($opts['sent_folder'])) {
                $imp_folder->create($opts['sent_folder'], $prefs->getValue('subscribe'));
            }

            $flags = array('\\seen');

            /* RFC 3503 [3.3] - set $MDNSent flag on sent message. */
            if ($mdn) {
                $flags[] = array('$MDNSent');
            }

            try {
                $GLOBALS['injector']->getInstance('IMP_Imap')->getOb()->append(Horde_String::convertCharset($opts['sent_folder'], $GLOBALS['registry']->getCharset(), 'UTF-8'), array(array('data' => $fcc, 'flags' => $flags)));
            } catch (Horde_Imap_Client_Exception $e) {
                $notification->push(sprintf(_("Message sent successfully, but not saved to %s"), IMP::displayFolder($opts['sent_folder'])));
                $sent_saved = false;
            }
        }

        /* Delete the attachment data. */
        $this->deleteAllAttachments();

        /* Save recipients to address book? */
        $this->_saveRecipients($recipients);

        /* Call post-sent hook. */
        try {
            Horde::callHook('post_sent', array($save_msg['msg'], $headers), 'imp');
        } catch (Horde_Exception_HookNotSet $e) {}

        return $sent_saved;
    }

    /**
     * Add necessary headers for replies.
     *
     * @param Horde_Mime_Headers $headers  The object holding this message's
     *                                     headers.
     */
    protected function _addReferences($headers)
    {
        if (strpos($this->getMetadata('reply_type'), 'reply') === 0) {
            if ($this->getMetadata('references')) {
                $headers->addHeader('References', implode(' ', preg_split('|\s+|', trim($this->getMetadata('references')))));
            }
            if ($this->getMetadata('in_reply_to')) {
                $headers->addHeader('In-Reply-To', $this->getMetadata('in_reply_to'));
            }
        }
    }

    /**
     * Sends a message.
     *
     * @param string $email                The e-mail list to send to.
     * @param Horde_Mime_Headers $headers  The object holding this message's
     *                                     headers.
     * @param Horde_Mime_Part $message     The Horde_Mime_Part object that
     *                                     contains the text to send.
     *
     * @throws IMP_Compose_Exception
     */
    public function sendMessage($email, $headers, $message)
    {
        $email = $this->_prepSendMessage($email, $headers, $message);

        try {
            $message->send($email, $headers, $GLOBALS['injector']->getInstance('IMP_Mail'));
        } catch (Horde_Mime_Exception $e) {
            throw new IMP_Compose_Exception($e);
        }
    }

    /**
     * Sanity checking/MIME formatting before sending a message.
     *
     * @param string $email                The e-mail list to send to.
     * @param Horde_Mime_Headers $headers  The object holding this message's
     *                                     headers.
     * @param Horde_Mime_Part $message     The Horde_Mime_Part object that
     *                                     contains the text to send.
     *
     * @return string  The encoded $email list.
     * @throws IMP_Compose_Exception
     */
    protected function _prepSendMessage($email, $headers = null,
                                        $message = null)
    {
        /* Validate the recipient addresses. */
        try {
            $alist = Horde_Mime_Address::parseAddressList($email, array(
                'defserver' => $_SESSION['imp']['maildomain'],
                'validate' => true
            ));
        } catch (Horde_Mime_Exception $e) {
            throw new IMP_Compose_Exception($e);
        }

        $timelimit = $GLOBALS['injector']->getInstance('Horde_Perms')->hasAppPermission('max_timelimit');
        if ($timelimit !== true) {
            $sentmail = $GLOBALS['injector']->getInstance('IMP_Sentmail');
            if (!is_subclass_of($sentmail, 'IMP_Sentmail_Driver')) {
                Horde::logMessage('The permission for the maximum number of recipients per time period has been enabled, but no backend for the sent-mail logging has been configured for IMP.', 'ERR');
                throw new IMP_Compose_Exception(_("The system is not properly configured. A detailed error description has been logged for the administrator."));
            }

            try {
                $recipients = $sentmail->numberOfRecipients($GLOBALS['conf']['sentmail']['params']['limit_period'], true);
            } catch (IMP_Exception $e) {
                $recipients = 0;
            }

            foreach ($alist as $address) {
                $recipients += isset($address['grounpname'])
                    ? count($address['addresses'])
                    : 1;
            }

            if ($recipients > $timelimit) {
                try {
                    $error = Horde::callHook('perms_denied', array('imp:max_timelimit'));
                } catch (Horde_Exception_HookNotSet $e) {
                    $error = @htmlspecialchars(sprintf(_("You are not allowed to send messages to more than %d recipients within %d hours."), $timelimit, $GLOBALS['conf']['sentmail']['params']['limit_period']), ENT_COMPAT, $GLOBALS['registry']->getCharset());
                }
                throw new IMP_Compose_Exception($error);
            }
        }

        /* Pass to hook to allow alteration of message details. */
        if (!is_null($message)) {
            try {
                Horde::callHook('pre_sent', array($message, $headers), 'imp');
            } catch (Horde_Exception_HookNotSet $e) {}
        }

        /* Properly encode the addresses we're sending to. */
        try {
            $email = Horde_Mime::encodeAddress($email, is_null($message) ? $GLOBALS['registry']->getCharset() : $message->getHeaderCharset(), $_SESSION['imp']['maildomain']);
        } catch (Horde_Mime_Exception $e) {
            throw new IMP_Compose_Exception($e);
        }

        return $email;
    }

    /**
     * Save the recipients done in a sendMessage().
     *
     * @param string $recipients  The list of recipients.
     */
    protected function _saveRecipients($recipients)
    {
        global $notification, $prefs, $registry;

        if (!$prefs->getValue('save_recipients') ||
            !$registry->hasMethod('contacts/import') ||
            !$registry->hasMethod('contacts/search')) {
            return;
        }

        $abook = $prefs->getValue('add_source');
        if (empty($abook)) {
            return;
        }

        try {
            $r_array = Horde_Mime::encodeAddress($recipients, $GLOBALS['registry']->getCharset(), $_SESSION['imp']['maildomain']);
            $r_array = Horde_Mime_Address::parseAddressList($r_array, array('validate' => true));
        } catch (Horde_Mime_Exception $e) {}

        if (empty($r_array)) {
            $notification->push(_("Could not save recipients."));
            return;
        }

        /* Filter out anyone that matches an email address already
         * in the address book. */
        $emails = array();
        foreach ($r_array as $recipient) {
            $emails[] = $recipient['mailbox'] . '@' . $recipient['host'];
        }

        try {
            $results = $registry->call('contacts/search', array($emails, array($abook), array($abook => array('email'))));
        } catch (Horde_Exception $e) {
            Horde::logMessage($e, 'ERR');
            $notification->push(_("Could not save recipients."));
            return;
        }

        foreach ($r_array as $recipient) {
            /* Skip email addresses that already exist in the add_source. */
            if (isset($results[$recipient['mailbox'] . '@' . $recipient['host']]) &&
                count($results[$recipient['mailbox'] . '@' . $recipient['host']])) {
                continue;
            }

            /* Remove surrounding quotes and make sure that $name is
             * non-empty. */
            $name = '';
            if (isset($recipient['personal'])) {
                $name = trim($recipient['personal']);
                if (preg_match('/^(["\']).*\1$/', $name)) {
                    $name = substr($name, 1, -1);
                }
            }
            if (empty($name)) {
                $name = $recipient['mailbox'];
            }
            $name = Horde_Mime::decode($name);

            try {
                $registry->call('contacts/import', array(array('name' => $name, 'email' => $recipient['mailbox'] . '@' . $recipient['host']), 'array', $abook));
                $notification->push(sprintf(_("Entry \"%s\" was successfully added to the address book"), $name), 'horde.success');
            } catch (Horde_Exception $e) {
                if ($e->getCode() == 'horde.error') {
                    $notification->push($e, $e->getCode());
                }
            }
        }
    }

    /**
     * Cleans up and returns the recipient list. Encodes all e-mail addresses
     * with IDN domains.
     *
     * @param array $hdr       An array of MIME headers.  Recipients will be
     *                         extracted from the 'to', 'cc', and 'bcc'
     *                         entries.
     * @param boolean $exceed  Test if user has exceeded the allowed
     *                         number of recipients?
     *
     * @return array  An array with the following entries:
     * <pre>
     * 'list' - An array of recipient addresses.
     * 'header' - An array containing the cleaned up 'to', 'cc', and 'bcc'
     *            header strings.
     * </pre>
     * @throws Horde_Exception
     * @throws IMP_Compose_Exception
     */
    public function recipientList($hdr, $exceed = true)
    {
        $addrlist = $header = array();

        foreach (array('to', 'cc', 'bcc') as $key) {
            if (!isset($hdr[$key])) {
                continue;
            }

            $arr = array_filter(array_map('trim', Horde_Mime_Address::explode($hdr[$key], ',;')));
            $tmp = array();

            foreach ($arr as $email) {
                if (empty($email)) {
                    continue;
                }

                try {
                    $obs = Horde_Mime_Address::parseAddressList($email);
                } catch (Horde_Mime_Exception $e) {
                    throw new IMP_Compose_Exception(sprintf(_("Invalid e-mail address: %s."), $email));
                }

                foreach ($obs as $ob) {
                    if (isset($ob['groupname'])) {
                        $group_addresses = array();
                        foreach ($ob['addresses'] as $ad) {
                            $ret = $this->_parseAddress($ad, $email);
                            $addrlist[] = $group_addresses[] = $ret;
                        }

                        $tmp[] = Horde_Mime_Address::writeGroupAddress($ob['groupname'], $group_addresses) . ' ';
                    } else {
                        $ret = $this->_parseAddress($ob, $email);
                        $addrlist[] = $ret;
                        $tmp[] = $ret . ', ';
                    }
                }
            }

            $header[$key] = rtrim(implode('', $tmp), ' ,');
        }

        if (empty($addrlist)) {
            throw new IMP_Compose_Exception(_("You must enter at least one recipient."));
        }

        /* Count recipients if necessary. We need to split email groups
         * because the group members count as separate recipients. */
        if ($exceed) {
            $max_recipients = $GLOBALS['injector']->getInstance('Horde_Perms')->hasAppPermission('max_recipients');
            if ($max_recipients !== true) {
                $num_recipients = 0;
                foreach ($addrlist as $recipient) {
                    $num_recipients += count(explode(',', $recipient));
                }
                if ($num_recipients > $max_recipients) {
                    try {
                        $message = Horde::callHook('perms_denied', array('imp:max_recipients'));
                    } catch (Horde_Exception_HookNotSet $e) {
                        $message = @htmlspecialchars(sprintf(_("You are not allowed to send messages to more than %d recipients."), $max_recipients), ENT_COMPAT, $GLOBALS['registry']->getCharset());
                    }
                    throw new IMP_Compose_Exception($message);
                }
            }
        }

        return array('list' => $addrlist, 'header' => $header);
    }

    /**
     * Helper function for recipientList().
     */
    protected function _parseAddress($ob, $email)
    {
        // Make sure we have a valid host.
        $host = trim($ob['host']);
        if (empty($host)) {
            $host = $_SESSION['imp']['maildomain'];
        }

        // Convert IDN hosts to ASCII.
        if (Horde_Util::extensionExists('idn')) {
            $old_error = error_reporting(0);
            $host = idn_to_ascii(Horde_String::convertCharset($host, $GLOBALS['registry']->getCharset(), 'UTF-8'));
            error_reporting($old_error);
        } elseif (Horde_Mime::is8bit($ob['mailbox'], $GLOBALS['registry']->getCharset())) {
            throw new IMP_Compose_Exception(sprintf(_("Invalid character in e-mail address: %s."), $email));
        }

        return Horde_Mime_Address::writeAddress($ob['mailbox'], $host, isset($ob['personal']) ? $ob['personal'] : '');
    }

    /**
     * Create the base Horde_Mime_Part for sending.
     *
     * @param array $to        The recipient list.
     * @param string $body     Message body.
     * @param string $charset  The charset of the message body.
     * @param array $options   Additional options:
     * <pre>
     * 'encrypt' - (integer) The encryption flag.
     * 'from' - (string) The outgoing from address - only needed for multiple
     *          PGP encryption.
     * 'html' - (boolean) Is this a HTML message?
     * 'nofinal' - (boolean) This is not a message which will be sent out.
     * 'noattach' - (boolean) Don't add attachment information.
     * </pre>
     *
     * @return array  An array with the following keys:
     * <pre>
     * 'msg' - (string) The MIME message.
     * 'recipients' - (array) The array of recipients.
     * 'to' - (string) The recipients list in string format.
     * </pre>
     * @throws Horde_Exception
     * @throws IMP_Compose_Exception
     */
    protected function _createMimeMessage($to, $body, $charset,
                                          $options = array())
    {
        $nls_charset = $GLOBALS['registry']->getCharset();
        $body = Horde_String::convertCharset($body, $nls_charset, $charset);

        if (!empty($options['html'])) {
            $body_html = $body;
            $body = $GLOBALS['injector']->getInstance('Horde_Text_Filter')->filter($body, 'Html2text', array('wrap' => false, 'charset' => $charset));
        }

        /* Get trailer message (if any). */
        $trailer = $trailer_file = null;
        if (empty($options['nofinal']) &&
            $GLOBALS['conf']['msg']['append_trailer']) {
            if (empty($GLOBALS['conf']['vhosts'])) {
                if (is_readable(IMP_BASE . '/config/trailer.txt')) {
                    $trailer_file = IMP_BASE . '/config/trailer.txt';
                }
            } elseif (is_readable(IMP_BASE . '/config/trailer-' . $GLOBALS['conf']['server']['name'] . '.txt')) {
                $trailer_file = IMP_BASE . '/config/trailer-' . $GLOBALS['conf']['server']['name'] . '.txt';
            }

            if (!empty($trailer_file)) {
                $trailer = $GLOBALS['injector']->getInstance('Horde_Text_Filter')->filter("\n" . file_get_contents($trailer_file), 'environment');
                try {
                    $trailer = Horde::callHook('trailer', array($trailer), 'imp');
                } catch (Horde_Exception_HookNotSet $e) {}

                $body .= $trailer;
                if (!empty($options['html'])) {
                    $body_html .= $this->text2html($trailer);
                }
            }
        }

        /* Set up the body part now. */
        $textBody = new Horde_Mime_Part();
        $textBody->setType('text/plain');
        $textBody->setCharset($charset);
        $textBody->setDisposition('inline');

        /* Send in flowed format. */
        $flowed = new Horde_Text_Flowed($body, $charset);
        $flowed->setDelSp(true);
        $textBody->setContentTypeParameter('format', 'flowed');
        $textBody->setContentTypeParameter('DelSp', 'Yes');
        $textBody->setContents($flowed->toFlowed());

        /* Determine whether or not to send a multipart/alternative
         * message with an HTML part. */
        if (!empty($options['html'])) {
            $htmlBody = new Horde_Mime_Part();
            $htmlBody->setType('text/html');
            $htmlBody->setCharset($charset);
            $htmlBody->setDisposition('inline');
            $htmlBody->setDescription(Horde_String::convertCharset(_("HTML Version"), $nls_charset, $charset));

            /* Add default font CSS information here. The data comes to us
             * with no HTML body tag - so simply wrap the data in a body
             * tag with the CSS information. */
            $styles = array();
            if ($font_family = $GLOBALS['prefs']->getValue('compose_html_font_family')) {
                $styles[] = 'font-family:' . $font_family;
            }
            if ($font_size = intval($GLOBALS['prefs']->getValue('compose_html_font_size'))) {
                $styles[] = 'font-size:' . $font_size . 'px';
            }

            if (!empty($styles)) {
                $body_html = '<body style="' . implode(';', $styles) . '">' .
                    $body_html .
                    '</body>';
            }

            $htmlBody->setContents($GLOBALS['injector']->getInstance('Horde_Text_Filter')->filter($body_html, 'cleanhtml', array('charset' => $charset)));

            $textBody->setDescription(Horde_String::convertCharset(_("Plaintext Version"), $nls_charset, $charset));

            $textpart = new Horde_Mime_Part();
            $textpart->setType('multipart/alternative');
            $textpart->addPart($textBody);
            $textpart->setHeaderCharset($charset);

            if (empty($options['nofinal'])) {
                try {
                    $htmlBody = $this->_convertToMultipartRelated($htmlBody);
                } catch (Horde_Exception $e) {}
            }

            $textpart->addPart($htmlBody);
        } else {
            $textpart = $textBody;
        }

        /* Add attachments now. */
        $attach_flag = true;
        if (empty($options['noattach']) && $this->numberOfAttachments()) {
            if (($this->_linkAttach &&
                 $GLOBALS['conf']['compose']['link_attachments']) ||
                !empty($GLOBALS['conf']['compose']['link_all_attachments'])) {
                $base = $this->linkAttachments($textpart);

                if ($this->_pgpAttachPubkey || $this->_attachVCard) {
                    $new_body = new Horde_Mime_Part();
                    $new_body->setType('multipart/mixed');
                    $new_body->addPart($base);
                    $base = $new_body;
                } else {
                    $attach_flag = false;
                }
            } else {
                $base = new Horde_Mime_Part();
                $base->setType('multipart/mixed');
                $base->addPart($textpart);
                foreach (array_keys($this->_cache) as $id) {
                    $base->addPart($this->buildAttachment($id));
                }
            }
        } elseif ($this->_pgpAttachPubkey || $this->_attachVCard) {
            $base = new Horde_Mime_Part();
            $base->setType('multipart/mixed');
            $base->addPart($textpart);
        } else {
            $base = $textpart;
            $attach_flag = false;
        }

        if ($attach_flag) {
            if ($this->_pgpAttachPubkey) {
                $imp_pgp = $GLOBALS['injector']->getInstance('IMP_Crypt_Pgp');
                $base->addPart($imp_pgp->publicKeyMIMEPart());
            }

            if ($this->_attachVCard) {
                $base->addPart($this->_attachVCard);
            }
        }

        /* Set up the base message now. */
        $encrypt = empty($options['encrypt']) ? 0 : $options['encrypt'];
        if ($GLOBALS['prefs']->getValue('use_pgp') &&
            !empty($GLOBALS['conf']['gnupg']['path']) &&
            in_array($encrypt, array(IMP::PGP_ENCRYPT, IMP::PGP_SIGN, IMP::PGP_SIGNENC, IMP::PGP_SYM_ENCRYPT, IMP::PGP_SYM_SIGNENC))) {
            $imp_pgp = $GLOBALS['injector']->getInstance('IMP_Crypt_Pgp');

            switch ($encrypt) {
            case IMP::PGP_SIGN:
            case IMP::PGP_SIGNENC:
            case IMP::PGP_SYM_SIGNENC:
                /* Check to see if we have the user's passphrase yet. */
                $passphrase = $imp_pgp->getPassphrase('personal');
                if (empty($passphrase)) {
                    $e = new IMP_Compose_Exception(_("PGP: Need passphrase for personal private key."));
                    $e->encrypt = 'pgp_passphrase_dialog';
                    throw $e;
                }
                break;

            case IMP::PGP_SYM_ENCRYPT:
            case IMP::PGP_SYM_SIGNENC:
                /* Check to see if we have the user's symmetric passphrase
                 * yet. */
                $symmetric_passphrase = $imp_pgp->getPassphrase('symmetric', 'imp_compose_' . $this->_cacheid);
                if (empty($symmetric_passphrase)) {
                    $e = new IMP_Compose_Exception(_("PGP: Need passphrase to encrypt your message with."));
                    $e->encrypt = 'pgp_symmetric_passphrase_dialog';
                    throw $e;
                }
                break;
            }

            /* Do the encryption/signing requested. */
            try {
                switch ($encrypt) {
                case IMP::PGP_SIGN:
                    $base = $imp_pgp->IMPsignMIMEPart($base);
                    break;

                case IMP::PGP_ENCRYPT:
                case IMP::PGP_SYM_ENCRYPT:
                    $to_list = empty($options['from'])
                        ? $to
                        : array_keys(array_flip(array_merge($to, array($options['from']))));
                    $base = $imp_pgp->IMPencryptMIMEPart($base, $to_list, ($encrypt == IMP::PGP_SYM_ENCRYPT) ? $symmetric_passphrase : null);
                    break;

                case IMP::PGP_SIGNENC:
                case IMP::PGP_SYM_SIGNENC:
                    $to_list = empty($options['from'])
                        ? $to
                        : array_keys(array_flip(array_merge($to, array($options['from']))));
                    $base = $imp_pgp->IMPsignAndEncryptMIMEPart($base, $to_list, ($encrypt == IMP::PGP_SYM_SIGNENC) ? $symmetric_passphrase : null);
                    break;
                }
            } catch (Horde_Exception $e) {
                throw new IMP_Compose_Exception(_("PGP Error: ") . $e->getMessage(), $e->getCode());
            }
        } elseif ($GLOBALS['prefs']->getValue('use_smime') &&
                  in_array($encrypt, array(IMP::SMIME_ENCRYPT, IMP::SMIME_SIGN, IMP::SMIME_SIGNENC))) {
            $imp_smime = $GLOBALS['injector']->getInstance('IMP_Crypt_Smime');

            /* Check to see if we have the user's passphrase yet. */
            if (in_array($encrypt, array(IMP::SMIME_SIGN, IMP::SMIME_SIGNENC))) {
                $passphrase = $imp_smime->getPassphrase();
                if ($passphrase === false) {
                    $e = new IMP_Compose_Exception(_("S/MIME Error: Need passphrase for personal private key."));
                    $e->encrypt = 'smime_passphrase_dialog';
                    throw $e;
                }
            }

            /* Do the encryption/signing requested. */
            try {
                switch ($encrypt) {
                case IMP::SMIME_SIGN:
                    $base = $imp_smime->IMPsignMIMEPart($base);
                    break;

                case IMP::SMIME_ENCRYPT:
                    $base = $imp_smime->IMPencryptMIMEPart($base, $to[0]);
                    break;

                case IMP::SMIME_SIGNENC:
                    $base = $imp_smime->IMPsignAndEncryptMIMEPart($base, $to[0]);
                    break;
                }
            } catch (Horde_Exception $e) {
                throw new IMP_Compose_Exception(_("S/MIME Error: ") . $e->getMessage(), $e->getCode());
            }
        }

        /* Flag this as the base part. */
        $base->isBasePart(true);

        return array(
            'msg' => $base,
            'recipients' => $to,
            'to' => implode(', ', $to)
        );
    }

    /**
     * Determines the reply text and headers for a message.
     *
     * @param string $type            The reply type (reply, reply_all,
     *                                reply_auto, reply_list, or *).
     * @param IMP_Contents $contents  An IMP_Contents object.
     * @param string $to              The recipient of the reply. Overrides
     *                                the automatically determined value.
     *
     * @return array  An array with the following keys:
     * <pre>
     * 'body'     - The text of the body part
     * 'encoding' - The guessed charset to use for the reply
     * 'format'   - The format of the body message
     * 'headers'  - The headers of the message to use for the reply
     * 'identity' - The identity to use for the reply based on the original
     *              message's addresses.
     * 'type'     - The reply type used (either 'reply', 'reply_all', or
     *              'reply_list').
     * </pre>
     */
    public function replyMessage($type, $contents, $to = null)
    {
        global $prefs;

        /* The headers of the message. */
        $header = array(
            'to' => '',
            'cc' => '',
            'bcc' => '',
            'subject' => ''
        );

        $charset = $GLOBALS['registry']->getCharset();
        $h = $contents->getHeaderOb();
        $match_identity = $this->_getMatchingIdentity($h);
        $reply_type = 'reply';

        if (!$this->getMetadata('reply_type')) {
            $this->_metadata['mailbox'] = $contents->getMailbox();
            $this->_metadata['uid'] = $contents->getUid();
            $this->_modified = true;

            /* Set the message-id related headers. */
            if (($msg_id = $h->getValue('message-id'))) {
                $this->_metadata['in_reply_to'] = chop($msg_id);

                if (($refs = $h->getValue('references'))) {
                    $refs .= ' ' . $this->_metadata['in_reply_to'];
                } else {
                    $refs = $this->_metadata['in_reply_to'];
                }
                $this->_metadata['references'] = $refs;
            }
        }

        $subject = $h->getValue('subject');
        $header['subject'] = empty($subject)
            ? 'Re: '
            : 'Re: ' . $GLOBALS['injector']->getInstance('IMP_Imap')->getOb()->getUtils()->getBaseSubject($subject, array('keepblob' => true));

        $force = false;
        if (in_array($type, array('reply', 'reply_auto', '*'))) {
            if (($header['to'] = $to) ||
                ($header['to'] = Horde_Mime_Address::addrArray2String($h->getOb('reply-to'), array('charset' => $charset)))) {
                $force = true;
            } else {
                $header['to'] = Horde_Mime_Address::addrArray2String($h->getOb('from'), array('charset' => $charset));
            }

            if ($type == '*') {
                $all_headers['reply'] = $header;
            }
        }

        /* We might need $list_info in the reply_all section. */
        if (in_array($type, array('reply_auto', 'reply_list', '*'))) {
            $imp_ui = new IMP_Ui_Message();
            $list_info = $imp_ui->getListInformation($h);
        } else {
            $list_info = null;
        }

        if (!is_null($list_info) && !empty($list_info['reply_list'])) {
            /* If To/Reply-To and List-Reply address are the same, no need
             * to handle these address separately. */
            if (Horde_Mime_Address::bareAddress($list_info['reply_list']) != Horde_Mime_Address::bareAddress($header['to'])) {
                $header['to'] = $list_info['reply_list'];
                $reply_type = 'reply_list';
            }

            if ($type == '*') {
                $all_headers['reply_list'] = $header;
            }
        } elseif (in_array($type, array('reply_all', 'reply_auto', '*'))) {
            /* Clear the To field if we are auto-determining addresses. */
            if ($type == 'reply_auto') {
                $header['to'] = '';
            }

            /* Filter out our own address from the addresses we reply to. */
            $identity = $GLOBALS['injector']->getInstance('IMP_Identity');
            $all_addrs = array_keys($identity->getAllFromAddresses(true));

            /* Build the To: header. It is either:
             * 1) the Reply-To address (if not a personal address)
             * 2) the From address (if not a personal address)
             * 3) all remaining Cc addresses. */
            $cc_addrs = array();
            foreach (array('reply-to', 'from', 'to', 'cc') as $val) {
                /* If either a reply-to or $to is present, we use this address
                 * INSTEAD of the from address. */
                if ($force && ($val == 'from')) {
                    continue;
                }

                $ob = $h->getOb($val);
                if (!empty($ob)) {
                    $addr_obs = Horde_Mime_Address::getAddressesFromObject($ob, array('charset' => $charset, 'filter' => $all_addrs));
                    if (!empty($addr_obs)) {
                        if (isset($addr_obs[0]['groupname'])) {
                            $cc_addrs = array_merge($cc_addrs, $addr_obs);
                            foreach ($addr_obs[0]['addresses'] as $addr_ob) {
                                $all_addrs[] = $addr_ob['inner'];
                            }
                        } elseif (($val != 'to') ||
                                  is_null($list_info) ||
                                  !$force ||
                                  empty($list_info['exists'])) {
                            /* Don't add To address if this is a list that
                             * doesn't have a post address but does have a
                             * reply-to address. */
                            if ($val == 'reply-to') {
                                /* If reply-to doesn't have personal
                                 * information, check from address. */
                                if (!$addr_obs[0]['personal'] &&
                                    ($to_ob = $h->getOb('from')) &&
                                    $to_ob[0]['personal'] &&
                                    ($to_addr = Horde_Mime_Address::addrArray2String($to_ob, array('charset' => $charset))) &&
                                    Horde_Mime_Address::bareAddress($to_addr) == $addr_obs[0]['address']) {
                                    $header['to'] = $to_addr;
                                } else {
                                    $header['to'] = $addr_obs[0]['address'];
                                }
                            } else {
                                $cc_addrs = array_merge($cc_addrs, $addr_obs);
                            }

                            foreach ($addr_obs as $addr_ob) {
                                $all_addrs[] = $addr_ob['inner'];
                            }
                        }
                    }
                }
            }

            /* Build the Cc: (or possibly the To:) header. If this is a
             * reply to a message that was already replied to by the user,
             * this reply will go to the original recipients (Request
             * #8485).  */
            $hdr_cc = array();
            foreach ($cc_addrs as $ob) {
                if (isset($ob['groupname'])) {
                    $hdr_cc[] = Horde_Mime_Address::writeGroupAddress($ob['groupname'], $ob['addresses']) . ' ';
                } else {
                    $hdr_cc[] = $ob['address'] . ', ';
                }
            }

            if (empty($header['to']) && (count($hdr_cc) > 1)) {
                $reply_type = 'reply_all';
            }
            $header[empty($header['to']) ? 'to' : 'cc'] = rtrim(implode('', $hdr_cc), ' ,');

            /* Build the Bcc: header. */
            $header['bcc'] = Horde_Mime_Address::addrArray2String($h->getOb('bcc') + $identity->getBccAddresses(), array('charset' => $charset, 'filter' => $all_addrs));
            if ($type == '*') {
                $all_headers['reply_all'] = $header;
            }
        }

        if ($type == '*') {
            $header = $all_headers;
        }

        if (!isset($this->_metadata['reply_type']) ||
            ($reply_type != $this->_metadata['reply_type'])) {
            $this->_metadata['reply_type'] = $reply_type;
            $this->_modified = true;
        }

        if (!$prefs->getValue('reply_quote')) {
            return array(
                'body' => '',
                'format' => 'text',
                'headers' => $header,
                'identity' => $match_identity,
                'type' => $reply_type
            );
        }

        $from = Horde_Mime_Address::addrArray2String($h->getOb('from'), array('charset' => $charset));

        if ($prefs->getValue('reply_headers') && !empty($h)) {
            $msg_pre = '----- ' .
                ($from ? sprintf(_("Message from %s"), $from) : _("Message")) .
                /* Extra '-'s line up with "End Message" below. */
                " ---------\n" .
                $this->_getMsgHeaders($h) . "\n\n";

            $msg_post = "\n\n----- " .
                ($from ? sprintf(_("End message from %s"), $from) : _("End message")) .
                " -----\n";
        } else {
            $msg_pre = $this->_expandAttribution($prefs->getValue('attrib_text'), $from, $h) . "\n\n";
            $msg_post = '';
        }

        $compose_html = (($_SESSION['imp']['view'] != 'mimp') && $GLOBALS['prefs']->getValue('compose_html'));

        $msg_text = $this->_getMessageText($contents, array(
            'html' => ($GLOBALS['prefs']->getValue('reply_format') || $compose_html),
            'replylimit' => true,
            'toflowed' => true,
            'type' => 'reply'
        ));

        if (!empty($msg_text) &&
            ($compose_html || ($msg_text['mode'] == 'html'))) {
            $msg = '<p>' . $this->text2html(trim($msg_pre)) . '</p>' .
                   '<blockquote type="cite" style="background-color:#f0f0f0;border-left:1px solid blue;padding-left:1em;">' .
                   (($msg_text['mode'] == 'text') ? $this->text2html($msg_text['text']) : $msg_text['text']) .
                   '</blockquote>' .
                   ($msg_post ? $this->text2html($msg_post) : '') . '<br />';
            $msg_text['mode'] = 'html';
        } else {
            $msg = empty($msg_text['text'])
                ? '[' . _("No message body text") . ']'
                : $msg_pre . $msg_text['text'] . $msg_post;
        }

        return array(
            'body' => $msg . "\n",
            'encoding' => $msg_text['encoding'],
            'format' => $msg_text['mode'],
            'headers' => $header,
            'identity' => $match_identity,
            'type' => $reply_type
        );
    }

    /**
     * Determine the text and headers for a forwarded message.
     *
     * @param string $type            The forward type (forward_attach,
     *                                forward_body, forward_both,
     *                                forward_auto).
     * @param IMP_Contents $contents  An IMP_Contents object.
     * @param boolean $attach         Attach the forwarded message?
     *
     * @return array  An array with the following keys:
     * <pre>
     * 'body'     - The text of the body part
     * 'encoding' - The guessed charset to use for the reply
     * 'format'   - The format of the body message
     * 'headers'  - The headers of the message to use for the reply
     * 'identity' - The identity to use for the reply based on the original
     *              message's addresses.
     * 'type'     - The forward type used (either 'forward_attach',
     *              'forward_body', or 'forward_both').
     * </pre>
     */
    public function forwardMessage($type, $contents, $attach = true)
    {
        /* The headers of the message. */
        $header = array(
            'to' => '',
            'cc' => '',
            'bcc' => '',
            'subject' => ''
        );

        if ($type == 'forward_auto') {
            if (!($type = $GLOBALS['prefs']->getValue('forward_default'))) {
                $type = 'attach';
            }
            $type = 'forward_' . $type;
        }

        $h = $contents->getHeaderOb();
        $format = 'text';
        $msg = '';

        $this->_metadata['mailbox'] = $contents->getMailbox();
        $this->_metadata['uid'] = $contents->getUid();

        /* We need the Message-Id so we can log this event. This header is not
         * added to the outgoing messages. */
        $this->_metadata['in_reply_to'] = trim($h->getValue('message-id'));
        $this->_metadata['reply_type'] = 'forward';
        $this->_modified = true;

        $header['subject'] = $h->getValue('subject');
        if (!empty($header['subject'])) {
            $subject = $GLOBALS['injector']->getInstance('IMP_Imap')->getOb()->getUtils()->getBaseSubject($header['subject'], array('keepblob' => true));
            $header['title'] = _("Forward") . ': ' . $subject;
            $header['subject'] = 'Fwd: ' . $subject;
        } else {
            $header['title'] = _("Forward");
            $header['subject'] = 'Fwd:';
        }

        if (in_array($type, array('forward_body', 'forward_both'))) {
            $from = Horde_Mime_Address::addrArray2String($h->getOb('from'), array('charset' => $GLOBALS['registry']->getCharset()));

            $msg_pre = "\n----- " .
                ($from ? sprintf(_("Forwarded message from %s"), $from) : _("Forwarded message")) .
                " -----\n" . $this->_getMsgHeaders($h) . "\n";
            $msg_post = "\n\n----- " . _("End forwarded message") . " -----\n";

            $compose_html = (($_SESSION['imp']['view'] != 'mimp') && $GLOBALS['prefs']->getValue('compose_html'));

            $msg_text = $this->_getMessageText($contents, array(
                'html' => ($GLOBALS['prefs']->getValue('forward_format') || $compose_html),
                'type' => 'forward'
            ));

            if (!empty($msg_text) &&
                ($compose_html || ($msg_text['mode'] == 'html'))) {
                $msg = $this->text2html($msg_pre) .
                    (($msg_text['mode'] == 'text') ? $this->text2html($msg_text['text']) : $msg_text['text']) .
                    $this->text2html($msg_post);
                $format = 'html';
            } else {
                $msg = $msg_pre . $msg_text['text'] . $msg_post;
            }
        }

        if ($attach &&
            in_array($type, array('forward_attach', 'forward_both'))) {
            $this->attachIMAPMessage(new IMP_Indices($contents));
        }

        return array(
            'body' => $msg,
            'encoding' => isset($msg_text) ? $msg_text['encoding'] : $GLOBALS['registry']->getCharset(),
            'format' => $format,
            'headers' => $header,
            'identity' => $this->_getMatchingIdentity($h),
            'type' => $type
        );
    }

    /**
     * Prepare a redirect message.
     *
     * @param IMP_Contents $contents  An IMP_Contents object.
     */
    public function redirectMessage($contents)
    {
        $this->_metadata['mailbox'] = $contents->getMailbox();
        $this->_metadata['reply_type'] = 'redirect';
        $this->_metadata['uid'] = $contents->getUid();
        $this->_modified = true;
    }

    /**
     * Send a redirect (a/k/a resent) message. See RFC 5322 [3.6.6].
     *
     * @param string $to  The addresses to redirect to.
     *
     * @throws IMP_Compose_Exception
     */
    public function sendRedirectMessage($to)
    {
        $recip = $this->recipientList(array('to' => $to));
        $recipients = implode(', ', $recip['list']);

        $identity = $GLOBALS['injector']->getInstance('IMP_Identity');
        $from_addr = $identity->getFromAddress();

        $contents = $this->getContentsOb();
        $headers = $contents->getHeaderOb();

        /* Generate the 'Resent' headers (RFC 5322 [3.6.6]). These headers are
         * prepended to the message. */
        $resent_headers = new Horde_Mime_Headers();
        $resent_headers->addHeader('Resent-Date', date('r'));
        $resent_headers->addHeader('Resent-From', $from_addr);
        $resent_headers->addHeader('Resent-To', $recip['header']['to']);
        $resent_headers->addHeader('Resent-Message-ID', Horde_Mime::generateMessageId());

        $header_text = trim($resent_headers->toString(array('encode' => $GLOBALS['registry']->getCharset()))) . "\n" . trim($contents->getHeaderOb(false));

        $to = $this->_prepSendMessage($recipients);
        $hdr_array = $headers->toArray(array('charset' => $GLOBALS['registry']->getCharset()));
        $hdr_array['_raw'] = $header_text;

        try {
            $GLOBALS['injector']->getInstance('IMP_Mail')->send($to, $hdr_array, $contents->getBody());
        } catch (Horde_Mail_Exception $e) {
            throw new IMP_Compose_Exception($e);
        }

        Horde::logMessage(sprintf("%s Redirected message sent to %s from %s", $_SERVER['REMOTE_ADDR'], $recipients, $GLOBALS['registry']->getAuth()), 'INFO');

        /* Store history information. */
        if (!empty($GLOBALS['conf']['maillog']['use_maillog'])) {
            IMP_Maillog::log('redirect', $headers->getValue('message-id'), $recipients);
        }

        if ($GLOBALS['conf']['sentmail']['driver'] != 'none') {
            $GLOBALS['injector']->getInstance('IMP_Sentmail')>log('redirect', $headers->getValue('message-id'), $recipients);
        }
    }

    /**
     * Get "tieto" identity information.
     *
     * @param Horde_Mime_Headers $h  The headers object for the message.
     *
     * @return mixed  See Imp_Prefs_Identity::getMatchingIdentity().
     */
    protected function _getMatchingIdentity($h)
    {
        $msgAddresses = array();
        foreach (array('to', 'cc', 'bcc') as $val) {
            $msgAddresses[] = $h->getValue($val);
        }

        return $GLOBALS['injector']->getInstance('IMP_Identity')->getMatchingIdentity($msgAddresses);
    }

    /**
     * Add mail message(s) from the mail server as a message/rfc822
     * attachment.
     *
     * @param IMP_Indices $indices  An indices object.
     *
     * @return mixed  String or false.
     */
    public function attachIMAPMessage($indices)
    {
        if (!count($indices)) {
            return false;
        }

        $attached = 0;
        foreach ($indices as $mbox => $idx) {
            ++$attached;
             $contents = $GLOBALS['injector']->getInstance('IMP_Contents')->getOb(new IMP_Indices($mbox, $idx));
             $headerob = $contents->getHeaderOb();

             $part = new Horde_Mime_Part();
             $part->setCharset($GLOBALS['registry']->getCharset());
             $part->setType('message/rfc822');
             $part->setName(_("Forwarded Message"));
             $part->setContents($contents->fullMessageText(array('stream' => true)));

             try {
                 $this->addMIMEPartAttachment($part);
             } catch (IMP_Compose_Exception $e) {
                 $GLOBALS['notification']->push($e);
                 return false;
            }
        }

        if ($attached == 1) {
            if (!($name = $headerob->getValue('subject'))) {
                $name = _("[No Subject]");
            } else {
                $name = Horde_String::truncate($name, 80);
            }
            return 'Fwd: ' . $GLOBALS['injector']->getInstance('IMP_Imap')->getOb()->getUtils()->getBaseSubject($name, array('keepblob' => true));
        } else {
            return 'Fwd: ' . sprintf(_("%u Forwarded Messages"), $attached);
        }
    }

    /**
     * Determine the header information to display in the forward/reply.
     *
     * @param Horde_Mime_Headers &$h  The headers object for the message.
     *
     * @return string  The header information for the original message.
     */
    protected function _getMsgHeaders($h)
    {
        $charset = $GLOBALS['registry']->getCharset();
        $tmp = array();

        if (($ob = $h->getValue('date'))) {
            $tmp[_("Date")] = $ob;
        }

        if (($ob = Horde_Mime_Address::addrArray2String($h->getOb('from'), array('charset' => $charset)))) {
            $tmp[_("From")] = $ob;
        }

        if (($ob = Horde_Mime_Address::addrArray2String($h->getOb('reply-to'), array('charset' => $charset)))) {
            $tmp[_("Reply-To")] = $ob;
        }

        if (($ob = $h->getValue('subject'))) {
            $tmp[_("Subject")] = $ob;
        }

        if (($ob = Horde_Mime_Address::addrArray2String($h->getOb('to'), array('charset' => $charset)))) {
            $tmp[_("To")] = $ob;
        }

        if (($ob = Horde_Mime_Address::addrArray2String($h->getOb('cc'), array('charset' => $charset)))) {
            $tmp[_("Cc")] = $ob;
        }

        $max = max(array_map(array('Horde_String', 'length'), array_keys($tmp))) + 2;
        $text = '';

        foreach ($tmp as $key => $val) {
            $text .= Horde_String::pad($key . ': ', $max, ' ', STR_PAD_LEFT) . $val . "\n";
        }

        return $text;
    }

    /**
     * Adds an attachment to a Horde_Mime_Part from an uploaded file.
     * The actual attachment data is stored in a separate file - the
     * Horde_Mime_Part information entries 'temp_filename' and 'temp_filetype'
     * are set with this information.
     *
     * @param string $name  The input field name from the form.
     *
     * @return string  The filename.
     * @throws IMP_Compose_Exception
     */
    public function addUploadAttachment($name)
    {
        global $conf;

        try {
            $GLOBALS['browser']->wasFileUploaded($name, _("attachment"));
        } catch (Horde_Browser_Exception $e) {
            throw new IMP_Compose_Exception($e);
        }

        $filename = Horde_Util::dispelMagicQuotes($_FILES[$name]['name']);
        $tempfile = $_FILES[$name]['tmp_name'];

        /* Check for filesize limitations. */
        if (!empty($conf['compose']['attach_size_limit']) &&
            (($conf['compose']['attach_size_limit'] - $this->sizeOfAttachments() - $_FILES[$name]['size']) < 0)) {
            throw new IMP_Compose_Exception(sprintf(_("Attached file \"%s\" exceeds the attachment size limits. File NOT attached."), $filename));
        }

        /* Determine the MIME type of the data. */
        $type = empty($_FILES[$name]['type'])
            ? 'application/octet-stream'
            : $_FILES[$name]['type'];

        /* User hook to do MIME magic determinations. */
        try {
            $type = Horde::callHook('attachmimetype', array($filename, $tempfile, $type), 'imp');
        } catch (Horde_Exception_HookNotSet $e) {}

        $part = new Horde_Mime_Part();
        $part->setType($type);
        if ($part->getPrimaryType() == 'text') {
            if ($analyzetype = Horde_Mime_Magic::analyzeFile($tempfile, empty($conf['mime']['magic_db']) ? null : $conf['mime']['magic_db'], array('nostrip' => true))) {
                $analyzetype = Horde_Mime::decodeParam('Content-Type', $analyzetype);
                $part->setCharset(isset($analyzetype['params']['charset']) ? $analyzetype['params']['charset'] : $GLOBALS['registry']->getCharset());
            } else {
                $part->setCharset($GLOBALS['registry']->getCharset());
            }
        } else {
            $part->setHeaderCharset($GLOBALS['registry']->getCharset());
        }
        $part->setName($filename);
        $part->setBytes($_FILES[$name]['size']);
        $part->setDisposition('attachment');

        if ($conf['compose']['use_vfs']) {
            $attachment = $tempfile;
        } else {
            $attachment = Horde::getTempFile('impatt', false);
            if (move_uploaded_file($tempfile, $attachment) === false) {
                throw new IMP_Compose_Exception(sprintf(_("The file %s could not be attached."), $filename));
            }
        }

        /* Store the data. */
        $this->_storeAttachment($part, $attachment);

        return $filename;
    }

    /**
     * Adds an attachment to a Horde_Mime_Part from data existing in the part.
     *
     * @param Horde_Mime_Part $part  The object that contains the attachment
     *                               data.
     *
     * @throws IMP_Compose_Exception
     */
    public function addMIMEPartAttachment($part)
    {
        global $conf;

        $type = $part->getType();
        $vfs = $conf['compose']['use_vfs'];

        /* Try to determine the MIME type from 1) the extension and
         * then 2) analysis of the file (if available). */
        if ($type == 'application/octet-stream') {
            $type = Horde_Mime_Magic::filenameToMIME($part->getName(true), false);
        }

        /* Extract the data from the currently existing Horde_Mime_Part and
         * then delete it. If this is an unknown MIME part, we must save to a
         * temporary file to run the file analysis on it. */
        if ($vfs) {
            $vfs_data = $part->getContents();
            if (($type == 'application/octet-stream') &&
                ($analyzetype = Horde_Mime_Magic::analyzeData($vfs_data, !empty($conf['mime']['magic_db']) ? $conf['mime']['magic_db'] : null))) {
                $type = $analyzetype;
            }
        } else {
            $attachment = Horde::getTempFile('impatt', false);
            $res = file_put_contents($attachment, $part->getContents());
            if ($res === false) {
                throw new IMP_Compose_Exception(sprintf(_("Could not attach %s to the message."), $part->getName()));
            }

            if (($type == 'application/octet-stream') &&
                ($analyzetype = Horde_Mime_Magic::analyzeFile($attachment, !empty($conf['mime']['magic_db']) ? $conf['mime']['magic_db'] : null))) {
                $type = $analyzetype;
            }
        }

        $part->setType($type);

        /* Set the size of the Part explicitly since we delete the contents
           later on in this function. */
        $bytes = $part->getBytes();
        $part->setBytes($bytes);
        $part->clearContents();

        /* Check for filesize limitations. */
        if (!empty($conf['compose']['attach_size_limit']) &&
            (($conf['compose']['attach_size_limit'] - $this->sizeOfAttachments() - $bytes) < 0)) {
            throw new IMP_Compose_Exception(sprintf(_("Attached file \"%s\" exceeds the attachment size limits. File NOT attached."), $part->getName()));
        }

        /* Store the data. */
        if ($vfs) {
            $this->_storeAttachment($part, $vfs_data, false);
        } else {
            $this->_storeAttachment($part, $attachment);
        }
    }

    /**
     * Stores the attachment data in its correct location.
     *
     * @param Horde_Mime_Part $part   The object to store.
     * @param string $data            Either the filename of the attachment
     *                                or, if $vfs_file is false, the
     *                                attachment data.
     * @param boolean $vfs_file       If using VFS, is $data a filename?
     *
     * @throws IMP_Compose_Exception
     */
    protected function _storeAttachment($part, $data, $vfs_file = true)
    {
        /* Store in VFS. */
        if ($GLOBALS['conf']['compose']['use_vfs']) {
            try {
                $vfs = $GLOBALS['injector']->getInstance('Horde_Vfs')->getVfs();
                $cacheID = strval(new Horde_Support_Randomid());

                if ($vfs_file) {
                    $vfs->write(self::VFS_ATTACH_PATH, $cacheID, $data, true);
                } else {
                    $vfs->writeData(self::VFS_ATTACH_PATH, $cacheID, $data, true);
                }
            } catch (VFS_Exception $e) {
                throw new IMP_Compose_Exception($e);
            }

            $this->_cache[] = array(
                'filename' => $cacheID,
                'filetype' => 'vfs',
                'part' => $part
            );
        } else {
            chmod($data, 0600);
            $this->_cache[] = array(
                'filename' => $data,
                'filetype' => 'file',
                'part' => $part
            );
        }

        $this->_modified = true;

        /* Add the size information to the counter. */
        $this->_size += $part->getBytes();
    }

    /**
     * Delete attached files.
     *
     * @param mixed $number  Either a single integer or an array of integers
     *                       corresponding to the attachment position.
     *
     * @return array  The list of deleted filenames (MIME encoded).
     */
    public function deleteAttachment($number)
    {
        $names = array();

        if (!is_array($number)) {
            $number = array($number);
        }

        foreach ($number as $val) {
            if (!isset($this->_cache[$val])) {
                continue;
            }

            $atc = &$this->_cache[$val];

            switch ($atc['filetype']) {
            case 'vfs':
                /* Delete from VFS. */
                try {
                    $vfs = $GLOBALS['injector']->getInstance('Horde_Vfs')->getVfs();
                    $vfs->deleteFile(self::VFS_ATTACH_PATH, $atc['filename']);
                } catch (VFS_Exception $e) {}
                break;

            case 'file':
                /* Delete from filesystem. */
                @unlink($filename);
                break;
            }

            $names[] = $atc['part']->getName(true);

            /* Remove the size information from the counter. */
            $this->_size -= $atc['part']->getBytes();

            unset($this->_cache[$val]);

            $this->_modified = true;
        }

        return $names;
    }

    /**
     * Deletes all attachments.
     */
    public function deleteAllAttachments()
    {
        $this->deleteAttachment(array_keys($this->_cache));
    }

    /**
     * Updates information in a specific attachment.
     *
     * @param integer $number  The attachment to update.
     * @param array $params    An array of update information.
     * <pre>
     * 'description'  --  The Content-Description value.
     * </pre>
     */
    public function updateAttachment($number, $params)
    {
        if (isset($this->_cache[$number])) {
            $this->_cache[$number]['part']->setDescription($params['description']);
            $this->_modified = true;
        }
    }

    /**
     * Returns the list of current attachments.
     *
     * @return array  The list of attachments.
     */
    public function getAttachments()
    {
        return $this->_cache;
    }

    /**
     * Returns the number of attachments currently in this message.
     *
     * @return integer  The number of attachments in this message.
     */
    public function numberOfAttachments()
    {
        return count($this->_cache);
    }

    /**
     * Returns the size of the attachments in bytes.
     *
     * @return integer  The size of the attachments (in bytes).
     */
    public function sizeOfAttachments()
    {
        return $this->_size;
    }

    /**
     * Build a single attachment part with its data.
     *
     * @param integer $id  The ID of the part to rebuild.
     *
     * @return Horde_Mime_Part  The Horde_Mime_Part with its contents.
     */
    public function buildAttachment($id)
    {
        $part = $this->_cache[$id]['part'];

        switch ($this->_cache[$id]['filetype']) {
        case 'vfs':
            // TODO: Use streams
            try {
                $vfs = $GLOBALS['injector']->getInstance('Horde_Vfs')->getVfs();
                $part->setContents($vfs->read(self::VFS_ATTACH_PATH, $this->_cache[$id]['filename']));
            } catch (VFS_Exception $e) {}
            break;

        case 'file':
            $fp = fopen($this->_cache[$id]['filename'], 'r');
            $part->setContents($fp);
            fclose($fp);
        }

        return $part;
    }

    /**
     * Expand macros in attribution text when replying to messages.
     *
     * @param string $line            The line of attribution text.
     * @param string $from            The email address of the original
     *                                sender.
     * @param Horde_Mime_Headers &$h  The headers object for the message.
     *
     * @return string  The attribution text.
     */
    protected function _expandAttribution($line, $from, $h)
    {
        $addressList = $nameList = '';

        /* First we'll get a comma seperated list of email addresses
           and a comma seperated list of personal names out of $from
           (there just might be more than one of each). */
        try {
            $addr_list = Horde_Mime_Address::parseAddressList($from);
        } catch (Horde_Mime_Exception $e) {
            $addr_list = array();
        }

        foreach ($addr_list as $entry) {
            if (isset($entry['mailbox'])) {
                if (strlen($addressList) > 0) {
                    $addressList .= ', ';
                }
                $addressList .= $entry['mailbox'];
                if (isset($entry['host'])) {
                    $addressList .= '@' . $entry['host'];
                }
            }

            if (isset($entry['personal'])) {
                if (strlen($nameList) > 0) {
                    $nameList .= ', ';
                }
                $nameList .= $entry['personal'];
            } elseif (isset($entry['mailbox'])) {
                if (strlen($nameList) > 0) {
                    $nameList .= ', ';
                }
                $nameList .= $entry['mailbox'];
            }
        }

        /* Define the macros. */
        if (is_array($message_id = $h->getValue('message_id'))) {
            $message_id = reset($message_id);
        }
        if (!($subject = $h->getValue('subject'))) {
            $subject = _("[No Subject]");
        }
        $udate = strtotime($h->getValue('date'));

        $match = array(
            /* New line. */
            '/%n/' => "\n",

            /* The '%' character. */
            '/%%/' => '%',

            /* Name and email address of original sender. */
            '/%f/' => $from,

            /* Senders email address(es). */
            '/%a/' => $addressList,

            /* Senders name(s). */
            '/%p/' => $nameList,

            /* RFC 822 date and time. */
            '/%r/' => $h->getValue('date'),

            /* Date as ddd, dd mmm yyyy. */
            '/%d/' => Horde_String::convertCharset(strftime("%a, %d %b %Y", $udate), $GLOBALS['registry']->getExternalCharset()),

            /* Date in locale's default. */
            '/%x/' => Horde_String::convertCharset(strftime("%x", $udate), $GLOBALS['registry']->getExternalCharset()),

            /* Date and time in locale's default. */
            '/%c/' => Horde_String::convertCharset(strftime("%c", $udate), $GLOBALS['registry']->getExternalCharset()),

            /* Message-ID. */
            '/%m/' => $message_id,

            /* Message subject. */
            '/%s/' => $subject
        );

        return (preg_replace(array_keys($match), array_values($match), $line));
    }

    /**
     * Obtains the cache ID for the session object.
     *
     * @return string  The message cache ID.
     */
    public function getCacheId()
    {
        return $this->_cacheid;
    }

    /**
     * How many more attachments are allowed?
     *
     * @return mixed  Returns true if no attachment limit.
     *                Else returns the number of additional attachments
     *                allowed.
     */
    public function additionalAttachmentsAllowed()
    {
        return empty($GLOBALS['conf']['compose']['attach_count_limit']) ||
               ($GLOBALS['conf']['compose']['attach_count_limit'] - $this->numberOfAttachments());
    }

    /**
     * What is the maximum attachment size allowed?
     *
     * @return integer  The maximum attachment size allowed (in bytes).
     */
    public function maxAttachmentSize()
    {
        $size = $_SESSION['imp']['file_upload'];

        if (!empty($GLOBALS['conf']['compose']['attach_size_limit'])) {
            return min($size, max($GLOBALS['conf']['compose']['attach_size_limit'] - $this->sizeOfAttachments(), 0));
        }

        return $size;
    }

    /**
     * Adds attachments from the IMP_Contents object to the message.
     *
     * @param IMP_Contents $contents  An IMP_Contents object.
     * @param array $options          Additional options:
     * <pre>
     * 'notify' - (boolean) Add notification message on errors?
     * 'skip' - (array) Skip these MIME IDs.
     * </pre>
     */
    public function attachFilesFromMessage($contents, $options = array())
    {
        $mime_message = $contents->getMIMEMessage();
        $dl_list = array_slice(array_keys($mime_message->contentTypeMap()), 1);
        if (!empty($options['skip'])) {
            $dl_list = array_diff($dl_list, $options['skip']);
        }

        foreach ($dl_list as $key) {
            if (strpos($key, '.', 1) === false) {
                $mime = $contents->getMIMEPart($key);
                if (!empty($mime)) {
                    try {
                        $this->addMIMEPartAttachment($mime);
                    } catch (IMP_Compose_Exception $e) {
                        if (!empty($options['notify'])) {
                            $GLOBALS['notification']->push($e, 'horde.warning');
                        }
                    }
                }
            }
        }
    }

    /**
     * Convert a text/html Horde_Mime_Part object with embedded image links
     * to a multipart/related Horde_Mime_Part with the image data embedded in
     * the part.
     *
     * @param Horde_Mime_Part $mime_part  The text/html object.
     *
     * @return Horde_Mime_Part  The converted Horde_Mime_Part.
     */
    protected function _convertToMultipartRelated($mime_part)
    {
        global $conf;

        /* Return immediately if related conversion is turned off via
         * configuration, this is not a HTML part, or no 'img' tags are
         * found (specifically searching for the 'src' parameter). */
        if (empty($conf['compose']['convert_to_related']) ||
            ($mime_part->getType() != 'text/html') ||
            !preg_match_all('/<img[^>]+src\s*\=\s*([^\s]+)\s+/iU', $mime_part->getContents(), $results)) {
            return $mime_part;
        }

        $client = $GLOBALS['injector']
          ->getInstance('Horde_Http_Client')
          ->getClient();
        $img_data = $img_parts = array();

        /* Go through list of results, download the image, and create
         * Horde_Mime_Part objects with the data. */
        foreach ($results[1] as $url) {
            /* Attempt to download the image data. */
            $response = $client->get(str_replace('&amp;', '&', trim($url, '"\'')));
            if ($response->code == 200) {
                /* We need to determine the image type.  Try getting
                 * that information from the returned HTTP
                 * content-type header.  TODO: Use Horde_Mime_Magic if this
                 * fails (?) */
                $part = new Horde_Mime_Part();
                $part->setType($response->getHeader('content-type'));
                $part->setContents($response->getBody());
                $part->setDisposition('attachment');
                $img_data[$url] = '"cid:' . $part->setContentID() . '"';
                $img_parts[] = $part;
            }
        }

        /* If we could not successfully download any data, return the
         * original Horde_Mime_Part now. */
        if (empty($img_data)) {
            return $mime_part;
        }

        /* Replace the URLs with with CID tags. */
        $mime_part->setContents(str_replace(array_keys($img_data), array_values($img_data), $mime_part->getContents()));

        /* Create new multipart/related part. */
        $related = new Horde_Mime_Part();
        $related->setType('multipart/related');

        /* Get the CID for the 'root' part. Although by default the
         * first part is the root part (RFC 2387 [3.2]), we may as
         * well be explicit and put the CID in the 'start'
         * parameter. */
        $related->setContentTypeParameter('start', $mime_part->setContentID());

        /* Add the root part and the various images to the multipart
         * object. */
        $related->addPart($mime_part);
        foreach (array_keys($img_parts) as $val) {
            $related->addPart($img_parts[$val]);
        }

        return $related;
    }

    /**
     * Remove all attachments from an email message and replace with
     * urls to downloadable links. Should properly save all
     * attachments to a new folder and remove the Horde_Mime_Parts for the
     * attachments.
     *
     * @param Horde_Mime_Part $part  The body of the message.
     *
     * @return Horde_Mime_Part  Modified MIME part with links to attachments.
     * @throws IMP_Compose_Exception
     */
    public function linkAttachments($part)
    {
        global $conf, $prefs;

        if (!$conf['compose']['link_attachments']) {
            throw new IMP_Compose_Exception(_("Linked attachments are forbidden."));
        }

        $auth = $GLOBALS['registry']->getAuth();
        $baseurl = Horde::applicationUrl('attachment.php', true)->setRaw(true);

        try {
            $GLOBALS['injector']->getInstance('Horde_Vfs')->getVfs();
        } catch (VFS_Exception $e) {
            throw new IMP_Compose_Exception($e);
        }

        $ts = time();
        $fullpath = sprintf('%s/%s/%d', self::VFS_LINK_ATTACH_PATH, $auth, $ts);
        $charset = $part->getCharset();

        $trailer = Horde_String::convertCharset(_("Attachments"), $GLOBALS['registry']->getCharset(), $charset);

        if ($prefs->getValue('delete_attachments_monthly')) {
            /* Determine the first day of the month in which the current
             * attachments will be ripe for deletion, then subtract 1 second
             * to obtain the last day of the previous month. */
            $del_time = mktime(0, 0, 0, date('n') + $prefs->getValue('delete_attachments_monthly_keep') + 1, 1, date('Y')) - 1;
            $trailer .= Horde_String::convertCharset(' (' . sprintf(_("Links will expire on %s"), strftime('%x', $del_time)) . ')', $GLOBALS['registry']->getCharset(), $charset);
        }

        foreach ($this->getAttachments() as $att) {
            $trailer .= "\n" . $baseurl->copy()->add(array('u' => $auth, 't' => $ts, 'f' => $att['part']->getName()));

            try {
                if ($att['filetype'] == 'vfs') {
                    $vfs->rename(self::VFS_ATTACH_PATH, $att['filename'], $fullpath, escapeshellcmd($att['part']->getName()));
                } else {
                    $data = file_get_contents($att['filename']);
                    $vfs->writeData($fullpath, escapeshellcmd($att['part']->getName()), $data, true);
                }
            } catch (VFS_Exception $e) {
                Horde::logMessage($e, 'ERR');
                return IMP_Compose_Exception($e);
            }
        }

        $this->deleteAllAttachments();

        if ($part->getPrimaryType() == 'multipart') {
            $mixed_part = new Horde_Mime_Part();
            $mixed_part->setType('multipart/mixed');
            $mixed_part->addPart($part);

            $link_part = new Horde_Mime_Part();
            $link_part->setType('text/plain');
            $link_part->setCharset($charset);
            $link_part->setDisposition('inline');
            $link_part->setContents($trailer);
            $link_part->setDescription(_("Attachment Information"));

            $mixed_part->addPart($link_part);
            return $mixed_part;
        }

        $part->appendContents("\n-----\n" . $trailer);

        return $part;
    }

    /**
     * Regenerates body text for use in the compose screen from IMAP data.
     *
     * @param IMP_Contents $contents  An IMP_Contents object.
     * @param array $options          Additional options:
     * <pre>
     * 'html' - (boolean) Return text/html part, if available.
     * 'replylimit' - (boolean) Enforce length limits?
     * 'toflowed' - (boolean) Convert to flowed?
     * 'type' - (string) 'draft', 'forward', or 'reply'.
     * </pre>
     *
     * @return mixed  Null if bodypart not found, or array with the following
     *                keys:
     * <pre>
     * 'encoding' - (string) The guessed encoding to use.
     * 'id' - (string) The MIME ID of the bodypart.
     * 'mode' - (string) Either 'text' or 'html'.
     * 'text' - (string) The body text.
     * </pre>
     */
    protected function _getMessageText($contents, $options = array())
    {
        $body_id = null;
        $mode = 'text';

        if (!empty($options['html']) && $_SESSION['imp']['rteavail']) {
            $body_id = $contents->findBody('html');
            if (!is_null($body_id)) {
                $mode = 'html';
            }
        }

        if (is_null($body_id)) {
            $body_id = $contents->findBody();
            if (is_null($body_id)) {
                return null;
            }
        }

        $part = $contents->getMIMEPart($body_id);
        $type = $part->getType();
        $part_charset = $part->getCharset();
        $charset = $GLOBALS['registry']->getCharset();
        $msg = Horde_String::convertCharset($part->getContents(), $part_charset, $charset);

        /* Enforce reply limits. */
        if (!empty($options['replylimit']) &&
            !empty($GLOBALS['conf']['compose']['reply_limit'])) {
            $limit = $GLOBALS['conf']['compose']['reply_limit'];
            if (Horde_String::length($msg) > $limit) {
                $msg = Horde_String::substr($msg, 0, $limit) . "\n" . _("[Truncated Text]");
            }
        }

        if ($mode == 'html') {
            $msg = $GLOBALS['injector']->getInstance('Horde_Text_Filter')->filter($msg, array('cleanhtml', 'xss'), array(array('body_only' => true), array('charset' => $charset, 'strip_styles' => true, 'strip_style_attributes' => false)));
        } elseif ($type == 'text/html') {
            $msg = $GLOBALS['injector']->getInstance('Horde_Text_Filter')->filter($msg, 'html2text');
            $type = 'text/plain';
        }

        /* Always remove leading/trailing whitespace. The data in the
         * message body is not intended to be the exact representation of the
         * original message (use forward as message/rfc822 part for that). */
        $msg = trim($msg);

        if ($type == 'text/plain') {
            if ($part->getContentTypeParameter('format') == 'flowed') {
                $flowed = new Horde_Text_Flowed($msg);
                if (Horde_String::lower($part->getContentTypeParameter('delsp')) == 'yes') {
                    $flowed->setDelSp(true);
                }
                $flowed->setMaxLength(0);
                $msg = $flowed->toFixed(false);
            } else {
                /* If the input is *not* in flowed format, make sure there is
                 * no padding at the end of lines. */
                $msg = preg_replace("/\s*\n/U", "\n", $msg);
            }

            if (!empty($options['toflowed'])) {
                $flowed = new Horde_Text_Flowed($msg);
                $msg = $flowed->toFlowed(true);
            }
        }

        /* Determine default encoding. */
        $encoding = $GLOBALS['registry']->getEmailCharset();
        if (($charset == 'UTF-8') &&
            (strcasecmp($part_charset, 'US-ASCII') !== 0) &&
            (strcasecmp($part_charset, $encoding) !== 0)) {
            $encoding = 'UTF-8';
        }

        return array(
            'encoding' => $encoding,
            'id' => $body_id,
            'mode' => $mode,
            'text' => $msg
        );
    }

    /**
     * Attach the user's PGP public key to every message sent by
     * buildAndSendMessage().
     *
     * @param boolean $attach  True if public key should be attached.
     */
    public function pgpAttachPubkey($attach)
    {
        $this->_pgpAttachPubkey = $attach;
    }

    /**
     * Attach the user's vCard to every message sent by buildAndSendMessage().
     *
     * @param boolean $attach  True if vCard should be attached.
     * @param string $name     The user's name.
     *
     * @throws IMP_Compose_Exception
     */
    public function attachVCard($attach, $name)
    {
        if (!$attach) {
            return;
        }

        try {
            $vcard = $GLOBALS['registry']->call('contacts/ownVCard');
        } catch (Horde_Exception $e) {
            throw new IMP_Compose_Exception($e);
        }

        $part = new Horde_Mime_Part();
        $part->setType('text/x-vcard');
        $part->setCharset($GLOBALS['registry']->getCharset());
        $part->setContents($vcard);
        $part->setName((strlen($name) ? $name : 'vcard') . '.vcf');
        $this->_attachVCard = $part;
    }

    /**
     * Has user specifically asked attachments to be linked in outgoing
     * messages?
     *
     * @param boolean $attach  True if attachments should be linked.
     */
    public function userLinkAttachments($attach)
    {
        $this->_linkAttach = $attach;
    }

    /**
     * Add uploaded files from form data.
     *
     * @param string $field    The field prefix (numbering starts at 1).
     * @param boolean $notify  Add a notification message for each successful
     *                         attachment?
     *
     * @return boolean  Returns false if any file was unsuccessfully added.
     */
    public function addFilesFromUpload($field, $notify = false)
    {
        $success = true;

        /* Add new attachments. */
        for ($i = 1, $fcount = count($_FILES); $i <= $fcount; ++$i) {
            $key = $field . $i;
            if (isset($_FILES[$key]) && ($_FILES[$key]['error'] != 4)) {
                $filename = Horde_Util::dispelMagicQuotes($_FILES[$key]['name']);
                if (!empty($_FILES[$key]['error'])) {
                    switch ($_FILES[$key]['error']) {
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                        $GLOBALS['notification']->push(sprintf(_("Did not attach \"%s\" as the maximum allowed upload size has been exceeded."), $filename), 'horde.warning');
                        break;

                    case UPLOAD_ERR_PARTIAL:
                        $GLOBALS['notification']->push(sprintf(_("Did not attach \"%s\" as it was only partially uploaded."), $filename), 'horde.warning');
                        break;

                    default:
                        $GLOBALS['notification']->push(sprintf(_("Did not attach \"%s\" as the server configuration did not allow the file to be uploaded."), $filename), 'horde.warning');
                        break;
                    }
                    $success = false;
                } elseif ($_FILES[$key]['size'] == 0) {
                    $GLOBALS['notification']->push(sprintf(_("Did not attach \"%s\" as the file was empty."), $filename), 'horde.warning');
                    $success = false;
                } else {
                    try {
                        $result = $this->addUploadAttachment($key);
                        if ($notify) {
                            $GLOBALS['notification']->push(sprintf(_("Added \"%s\" as an attachment."), $result), 'horde.success');
                        }
                    } catch (IMP_Compose_Exception $e) {
                        $GLOBALS['notification']->push($e, 'horde.error');
                        $success = false;
                    }
                }
            }
        }

        return $success;
    }

    /**
     * Shortcut function to convert text -> HTML for purposes of composition.
     *
     * @param string $msg  The message text.
     *
     * @return string  HTML text.
     */
    static public function text2html($msg)
    {
        return $GLOBALS['injector']->getInstance('Horde_Text_Filter')->filter($msg, 'text2html', array('parselevel' => Horde_Text_Filter_Text2html::MICRO_LINKURL, 'callback' => null));
    }

    /**
     * Store draft compose data if session expires.
     *
     * @param Horde_Variables $vars  Object with the form data.
     */
    public function sessionExpireDraft($vars)
    {
        if (empty($GLOBALS['conf']['compose']['use_vfs'])) {
            return;
        }

        $imp_ui = new IMP_Ui_Compose();

        $headers = array();
        foreach (array('to', 'cc', 'bcc', 'subject') as $val) {
            $headers[$val] = $imp_ui->getAddressList($vars->$val);
        }

        try {
            $body = $this->_saveDraftMsg($headers, $vars->message, $vars->charset, $vars->rtemode, false);
        } catch (IMP_Compose_Exception $e) {
            return;
        }

        try {
            $vfs = $GLOBALS['injector']->getInstance('Horde_Vfs')->getVfs();
            $vfs->writeData(self::VFS_DRAFTS_PATH, hash('md5', $vars->user), $body, true);

            $GLOBALS['notification']->push(_("The message you were composing has been saved as a draft. The next time you login, you may resume composing your message."));
        } catch (VFS_Exception $e) {}
    }

    /**
     * Restore session expiration draft compose data.
     */
    public function recoverSessionExpireDraft()
    {
        if (empty($GLOBALS['conf']['compose']['use_vfs'])) {
            return;
        }

        $filename = hash('md5', $GLOBALS['registry']->getAuth());

        try {
            $vfs = $GLOBALS['injector']->getInstance('Horde_Vfs')->getVfs();
        } catch (VFS_Exception $e) {
            return;
        }

        if ($vfs->exists(self::VFS_DRAFTS_PATH, $filename)) {
            try {
                $data = $vfs->read(self::VFS_DRAFTS_PATH, $filename);
                $vfs->deleteFile(self::VFS_DRAFTS_PATH, $filename);
            } catch (VFS_Exception $e) {
                return;
            }

            try {
                $this->_saveDraftServer($data);
                $GLOBALS['notification']->push(_("A message you were composing when your session expired has been recovered. You may resume composing your message by going to your Drafts folder."));
            } catch (IMP_Compose_Exception $e) {}
        }
    }

    /**
     * If this object contains sufficient metadata, return an IMP_Contents
     * object reflecting that metadata.
     *
     * @return mixed  Either an IMP_Contents object or null.
     */
    public function getContentsOb()
    {
        return $this->getMetadata('reply_type')
            ? $GLOBALS['injector']->getInstance('IMP_Contents')->getOb(new IMP_Indices($this->getMetadata('mailbox'), $this->getMetadata('uid')))
            : null;
    }

    /* Static utility functions. */

    /**
     * Formats the address properly.
     *
     * @param string $addr  The address to format.
     *
     * @return string  The formatted address.
     */
    static public function formatAddr($addr)
    {
        /* If there are angle brackets (<>), or a colon (group name
         * delimiter), assume the user knew what they were doing. */
        return (!empty($addr) &&
                (strpos($addr, '>') === false) &&
                (strpos($addr, ':') === false))
            ? preg_replace('|\s+|', ', ', trim(strtr($addr, ';,', '  ')))
            : $addr;
    }

    /**
     * Uses the Registry to expand names and return error information for
     * any address that is either not valid or fails to expand. This function
     * will not search if the address string is empty.
     *
     * @param string $addrString  The name(s) or address(es) to expand.
     * @param array $options      Additional options:
     * <pre>
     * 'levenshtein' - (boolean) If true, will sort the results using the
     *                 PHP levenshtein() scoring function.
     * </pre>
     *
     * @return array  All matching addresses.
     */
    static public function expandAddresses($addrString, $options = array())
    {
        if (!preg_match('|[^\s]|', $addrString)) {
            return array();
        }

        $addrString = reset(array_filter(array_map('trim', Horde_Mime_Address::explode($addrString, ',;'))));
        $addr_list = self::getAddressList($addrString);

        if (empty($options['levenshtein'])) {
            return $addr_list;
        }

        $sort_list = array();
        foreach ($addr_list as $val) {
            $sort_list[$val] = levenshtein($addrString, $val);
        }
        asort($sort_list, SORT_NUMERIC);

        return array_keys($sort_list);
    }

    /**
     * Uses the Registry to expand names and return error information for
     * any address that is either not valid or fails to expand.
     *
     * @param string $search  The term to search by.
     *
     * @return array  All matching addresses.
     */
    static public function getAddressList($search = '')
    {
        $sparams = IMP::getAddressbookSearchParams();
        try {
            $res = $GLOBALS['registry']->call('contacts/search', array($search, $sparams['sources'], $sparams['fields'], false));
        } catch (Horde_Exception $e) {
            Horde::logMessage($e, 'ERR');
            return array();
        }

        if (!count($res)) {
            return array();
        }

        /* The first key of the result will be the search term. The matching
         * entries are stored underneath this key. */
        $search = array();
        foreach (reset($res) as $val) {
            if (!empty($val['email'])) {
                if (strpos($val['email'], ',') !== false) {
                    $search[] = Horde_Mime_Address::encode($val['name'], 'personal') . ': ' . $val['email'] . ';';
                } else {
                    $mbox_host = explode('@', $val['email']);
                    if (isset($mbox_host[1])) {
                        $search[] = Horde_Mime_Address::writeAddress($mbox_host[0], $mbox_host[1], $val['name']);
                    }
                }
            }
        }

        return $search;
    }

}
