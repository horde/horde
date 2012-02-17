<?php
/**
 * The IMP_Compose:: class represents an outgoing mail message.
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
class IMP_Compose implements ArrayAccess, Countable, Iterator, Serializable
{
    /* The virtual path to use for VFS data. */
    const VFS_ATTACH_PATH = '.horde/imp/compose';

    /* The virtual path to save linked attachments. */
    const VFS_LINK_ATTACH_PATH = '.horde/imp/attachments';

    /* The virtual path to save drafts. */
    const VFS_DRAFTS_PATH = '.horde/imp/drafts';

    /* Compose types. */
    const COMPOSE = 0;
    const REPLY = 1;
    const REPLY_ALL = 2;
    const REPLY_AUTO = 3;
    const REPLY_LIST = 4;
    const REPLY_SENDER = 5;
    const FORWARD = 6;
    const FORWARD_ATTACH = 7;
    const FORWARD_AUTO = 8;
    const FORWARD_BODY = 9;
    const FORWARD_BOTH = 10;
    const REDIRECT = 11;

    /* The blockquote tag to use to indicate quoted text in HTML data. */
    const HTML_BLOCKQUOTE = '<blockquote type="cite" style="border-left:2px solid blue;margin-left:8px;padding-left:8px;">';

    /**
     * Mark as changed for purposes of storing in the session.
     * Either empty, 'changed', or 'deleted'.
     *
     * @var string
     */
    public $changed = '';

    /**
     * The charset to use for sending.
     *
     * @var string
     */
    public $charset;

    /**
     * Whether the user's vCard should be attached to outgoing messages.
     *
     * @var string
     */
    protected $_attachVCard = false;

    /**
     * The cached attachment data.
     *
     * @var array
     */
    protected $_cache = array();

    /**
     * The cache ID used to store object in session.
     *
     * @var string
     */
    protected $_cacheid;

    /**
     * Whether attachments should be linked.
     *
     * @var boolean
     */
    protected $_linkAttach = false;

    /**
     * Various metadata for this message.
     *
     * @var array
     */
    protected $_metadata = array();

    /**
     * Whether the user's PGP public key should be attached to outgoing
     * messages.
     *
     * @var boolean
     */
    protected $_pgpAttachPubkey = false;

    /**
     * The reply type.
     *
     * @var integer
     */
    protected $_replytype = self::COMPOSE;

    /**
     * The aggregate size of all attachments (in bytes).
     *
     * @var integer
     */
    protected $_size = 0;

    /**
     * Constructor.
     *
     * @param string $cacheid  The cache ID string.
     */
    public function __construct($cacheid)
    {
        $this->_cacheid = $cacheid;
        $this->charset = $GLOBALS['registry']->getEmailCharset();
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

        $this->changed = 'deleted';
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
     * @param array $header   List of message headers (UTF-8).
     * @param mixed $message  Either the message text (string) or a
     *                        Horde_Mime_Part object that contains the text
     *                        to send.
     * @param array $opts     An array of options w/the following keys:
     *   - html: (boolean) Is this an HTML message?
     *   - priority: (string) The message priority ('high', 'normal', 'low').
     *   - readreceipt: (boolean) Add return receipt headers?
     *
     * @return string  Notification text on success (not HTML encoded).
     *
     * @throws IMP_Compose_Exception
     */
    public function saveDraft($headers, $message, array $opts = array())
    {
        $body = $this->_saveDraftMsg($headers, $message, $opts);
        return $this->_saveDraftServer($body);
    }

    /**
     * Prepare the draft message.
     *
     * @param array $headers  List of message headers.
     * @param mixed $message  Either the message text (string) or a
     *                        Horde_Mime_Part object that contains the text
     *                        to send.
     * @param array $opts     An array of options w/the following keys:
     *   - html: (boolean) Is this an HTML message?
     *   - priority: (string) The message priority ('high', 'normal', 'low').
     *   - readreceipt: (boolean) Add return receipt headers?
     *
     * @return string  The body text.
     *
     * @throws IMP_Compose_Exception
     */
    protected function _saveDraftMsg($headers, $message, $opts)
    {
        $has_session = (bool)$GLOBALS['registry']->getAuth();

        /* Set up the base message now. */
        $base = $this->_createMimeMessage(array(null), $message, array(
            'html' => !empty($opts['html']),
            'noattach' => !$has_session,
            'nofinal' => true
        ));
        $base->isBasePart(true);

        if ($has_session) {
            foreach (array('to', 'cc', 'bcc') as $v) {
                if (isset($headers[$v])) {
                    try {
                        Horde_Mime::encodeAddress(self::formatAddr($headers[$v]), $this->charset, $GLOBALS['session']->get('imp', 'maildomain'));
                    } catch (Horde_Mime_Exception $e) {
                        throw new IMP_Compose_Exception(sprintf(_("Saving the draft failed. The %s header contains an invalid e-mail address: %s."), $v, $e->getMessage()), $e->getCode());
                    }
                }
            }
        }

        /* Initalize a header object for the draft. */
        $draft_headers = $this->_prepareHeaders($headers, $opts);

        /* Add information necessary to log replies/forwards when finally
         * sent. */
        if ($this->_replytype) {
            $imp_imap = $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create();
            try {
                $imap_url = $imp_imap->getUtils()->createUrl(array(
                    'type' => $imp_imap->pop3 ? 'pop' : 'imap',
                    'username' => $imp_imap->getParam('username'),
                    'hostspec' => $imp_imap->getParam('hostspec'),
                    'mailbox' => $this->getMetadata('mailbox'),
                    'uid' => $this->getMetadata('uid'),
                    'uidvalidity' => $this->getMetadata('mailbox')->uidvalid
                ));

                switch ($this->replyType(true)) {
                case self::FORWARD:
                    $draft_headers->addHeader('X-IMP-Draft-Forward', '<' . $imap_url . '>');
                    break;

                case self::REPLY:
                    $draft_headers->addHeader('X-IMP-Draft-Reply', '<' . $imap_url . '>');
                    $draft_headers->addHeader('X-IMP-Draft-Reply-Type', $this->_replytype);
                    break;
                }
            } catch (Horde_Exception $e) {}
        } else {
            $draft_headers->addHeader('X-IMP-Draft', 'Yes');
        }

        return $base->toString(array(
            'defserver' => $has_session ? $GLOBALS['session']->get('imp', 'maildomain') : null,
            'headers' => $draft_headers
        ));
    }

    /**
     * Save a draft message on the IMAP server.
     *
     * @param string $data  The text of the draft message.
     *
     * @return string  Status string (not HTML escaped).
     *
     * @throws IMP_Compose_Exception
     */
    protected function _saveDraftServer($data)
    {
        if (!$drafts_mbox = IMP_Mailbox::getPref('drafts_folder')) {
            throw new IMP_Compose_Exception(_("Saving the draft failed. No draft folder specified."));
        }

        /* Check for access to drafts folder. */
        if (!$drafts_mbox->create()) {
            throw new IMP_Compose_Exception(_("Saving the draft failed. Could not create a drafts folder."));
        }

        $append_flags = array(Horde_Imap_Client::FLAG_DRAFT);
        if (!$GLOBALS['prefs']->getValue('unseen_drafts')) {
            $append_flags[] = Horde_Imap_Client::FLAG_SEEN;
        }

        /* RFC 3503 [3.4] states that when saving a draft, the client MUST
         * set the MDNSent keyword. However, IMP doesn't write MDN headers
         * until send time so no need to set the flag here. */

        $old_uid = $this->getMetadata('draft_uid');

        /* Add the message to the mailbox. */
        try {
            $ids = $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->append($drafts_mbox, array(array('data' => $data, 'flags' => $append_flags)));

            if ($old_uid) {
                $GLOBALS['injector']->getInstance('IMP_Message')->delete($old_uid, array('nuke' => true));
            }

            $this->_metadata['draft_uid'] = $drafts_mbox->getIndicesOb($ids);
            $this->changed = 'changed';
            return sprintf(_("The draft has been saved to the \"%s\" folder."), $drafts_mbox->display);
        } catch (IMP_Imap_Exception $e) {
            return _("The draft was not successfully saved.");
        }
    }

    /**
     * Resumes a previously saved draft message.
     *
     * @param IMP_Indices $indices  An indices object.
     * @param boolean $addheaders   Populate header entries?
     *
     * @return mixed  An array with the following keys:
     *   - header: (array) A list of headers to add to the outgoing message.
     *   - identity: (integer) The identity used to create the message.
     *   - mode: (string) 'html' or 'text'.
     *   - msg: (string) The message text.
     *   - priority: (string) The message priority.
     *   - readreceipt: (boolean) Add return receipt headers?
     *
     * @throws IMP_Compose_Exception
     */
    public function resumeDraft($indices, $addheaders = true)
    {
        global $injector, $prefs;

        try {
            $contents = $injector->getInstance('IMP_Factory_Contents')->create($indices);
        } catch (IMP_Exception $e) {
            throw new IMP_Compose_Exception($e);
        }

        $header = array();
        $headers = $contents->getHeader();
        $imp_draft = false;
        $reply_type = null;

        if ($draft_url = $headers->getValue('x-imp-draft-reply')) {
            if (!($reply_type = $headers->getValue('x-imp-draft-reply-type'))) {
                $reply_type = self::REPLY;
            }
            $imp_draft = self::REPLY;
        } elseif ($draft_url = $headers->getValue('x-imp-draft-forward')) {
            $imp_draft = $reply_type = self::FORWARD;
        } elseif ($headers->getValue('x-imp-draft')) {
            $imp_draft = self::COMPOSE;
        }

        if (IMP::getViewMode() == 'mimp') {
            $compose_html = false;
        } elseif ($prefs->getValue('compose_html')) {
            $compose_html = true;
        } else {
            switch ($reply_type) {
            case self::FORWARD:
            case self::FORWARD_BODY:
            case self::FORWARD_BOTH:
                $compose_html = $prefs->getValue('forward_format');
                break;

            case self::REPLY:
            case self::REPLY_ALL:
            case self::REPLY_LIST:
            case self::REPLY_SENDER:
                $compose_html = $prefs->getValue('reply_format');
                break;

            default:
                /* If this is an draft saved by IMP, we know 100% for sure
                 * that if an HTML part exists, the user was composing in
                 * HTML. */
                $compose_html = ($imp_draft !== false);
                break;
            }
        }

        $msg_text = $this->_getMessageText($contents, array(
            'html' => $compose_html,
            'imp_msg' => $imp_draft,
            'toflowed' => false
        ));

        if (empty($msg_text)) {
            $charset = $this->charset;
            $message = '';
            $mode = 'text';
            $text_id = 0;
        } else {
            $charset = $msg_text['charset'];
            $message = $msg_text['text'];
            $mode = $msg_text['mode'];
            $text_id = $msg_text['id'];
        }

        $mime_message = $contents->getMIMEMessage();

        /* Add attachments. */
        if (($mime_message->getPrimaryType() == 'multipart') &&
            ($mime_message->getType() != 'multipart/alternative')) {
            for ($i = 1; ; ++$i) {
                if (intval($text_id) == $i) {
                    continue;
                }

                if (!($part = $contents->getMIMEPart($i))) {
                    break;
                }

                try {
                    $this->addMimePartAttachment($part);
                } catch (IMP_Compose_Exception $e) {
                    $GLOBALS['notification']->push($e, 'horde.warning');
                }
            }
        }

        $identity_id = null;
        if (($fromaddr = Horde_Mime_Address::bareAddress($headers->getValue('from')))) {
            $identity = $injector->getInstance('IMP_Identity');
            $identity_id = $identity->getMatchingIdentity($fromaddr);
        }

        if ($addheaders) {
            $header = array(
                'to' => Horde_Mime_Address::addrArray2String($headers->getOb('to')),
                'cc' => Horde_Mime_Address::addrArray2String($headers->getOb('cc')),
                'bcc' => Horde_Mime_Address::addrArray2String($headers->getOb('bcc')),
                'subject' => $headers->getValue('subject')
            );

            if ($val = $headers->getValue('references')) {
                $this->_metadata['references'] = $val;

                if ($val = $headers->getValue('in-reply-to')) {
                    $this->_metadata['in_reply_to'] = $val;
                }
            }

            if ($draft_url) {
                $imp_imap = $injector->getInstance('IMP_Factory_Imap')->create();
                $imap_url = $imp_imap->getUtils()->parseUrl(rtrim(ltrim($draft_url, '<'), '>'));
                $protocol = $imp_imap->pop3 ? 'pop' : 'imap';

                try {
                    if (($imap_url['type'] == $protocol) &&
                        ($imap_url['username'] == $imp_imap->getParam('username')) &&
                        // Ignore hostspec and port, since these can change
                        // even though the server is the same. UIDVALIDITY
                        // should catch any true server/backend changes.
                    (IMP_Mailbox::get($imap_url['mailbox'])->uidvalid == $imap_url['uidvalidity']) &&
                        $injector->getInstance('IMP_Factory_Contents')->create(new IMP_Indices($imap_url['mailbox'], $imap_url['uid']))) {
                        $this->_metadata['mailbox'] = IMP_Mailbox::get($imap_url['mailbox']);
                        $this->_metadata['uid'] = $imap_url['uid'];
                        $this->_replytype = $reply_type;
                    }
                } catch (Exception $e) {}
            }

            $this->_metadata['draft_uid_resume'] = $indices;
        }

        $imp_ui_hdrs = new IMP_Ui_Headers();
        $priority = $imp_ui_hdrs->getPriority($headers);

        $mdn = new Horde_Mime_Mdn($headers);
        $readreceipt = (bool)$mdn->getMdnReturnAddr();

        $this->charset = $charset;
        $this->changed = 'changed';

        return array(
            'header' => $header,
            'identity' => $identity_id,
            'mode' => $mode,
            'msg' => $message,
            'priority' => $priority,
            'readreceipt' => $readreceipt
        );
    }

    /**
     * Does this message have any drafts associated with it?
     *
     * @return boolean  True if draft messages exist.
     */
    public function hasDrafts()
    {
        return (!empty($this->_metadata['draft_uid']) ||
                !empty($this->_metadata['draft_uid_resume']));
    }

    /**
     * Builds and sends a MIME message.
     *
     * @param string $body   The message body.
     * @param array $header  List of message headers.
     * @param array $opts    An array of options w/the following keys:
     * <ul>
     *  <li>
     *   encrypt: (integer) A flag whether to encrypt or sign the message.
     *            One of:
     *   <ul>
     *    <li>IMP_Crypt_Pgp::ENCRYPT</li>
     *    <li>IMP_Crypt_Pgp::SIGNENC</li>
     *    <li>IMP_Crypt_Smime::ENCRYPT</li>
     *    <li>IMP_Crypt_Smime::SIGNENC</li>
     *   </ul>
     *  </li>
     *  <li>
     *   html: (boolean) Whether this is an HTML message.
     *         DEFAULT: false
     *  </li>
     *  <li>
     *   identity: (IMP_Prefs_Identity) If set, checks for proper tie-to
     *             addresses.
     *  </li>
     *  <li>
     *   priority: (string) The message priority ('high', 'normal', 'low').
     *  </li>
     *  <li>
     *   save_sent: (boolean) Save sent mail?
     *  </li>
     *  <li>
     *   sent_folder: (IMP_Mailbox) The sent-mail folder (UTF7-IMAP).
     *  </li>
     *  <li>
     *   save_attachments: (bool) Save attachments with the message?
     *  </li>
     *  <li>
     *   readreceipt: (boolean) Add return receipt headers?
     *  </li>
     *  <li>
     *   useragent: (string) The User-Agent string to use.
     *  </li>
     * </ul>
     *
     * @return boolean  Whether the sent message has been saved in the
     *                  sent-mail folder.
     *
     * @throws Horde_Exception
     * @throws IMP_Compose_Exception
     * @throws IMP_Exception
     */
    public function buildAndSendMessage($body, $header, array $opts = array())
    {
        global $conf, $injector, $notification, $prefs, $session, $registry;

        /* We need at least one recipient & RFC 2822 requires that no 8-bit
         * characters can be in the address fields. */
        $recip = $this->recipientList($header);
        $header = array_merge($header, $recip['header']);

        /* Check for correct identity usage. */
        if (!$this->getMetadata('identity_check') &&
            (count($recip['list']) === 1) &&
            isset($opts['identity'])) {
            $identity_search = $opts['identity']->getMatchingIdentity($recip['recips'], false);
            if (!is_null($identity_search) &&
                ($opts['identity']->getDefault() != $identity_search)) {
                $this->_metadata['identity_check'] = true;
                $e = new IMP_Compose_Exception(_("Recipient address does not match the currently selected identity."));
                $e->tied_identity = $identity_search;
                throw $e;
            }
        }

        $barefrom = Horde_Mime_Address::bareAddress($header['from'], $session->get('imp', 'maildomain'));
        $encrypt = empty($opts['encrypt']) ? 0 : $opts['encrypt'];

        /* Prepare the array of messages to send out.  May be more
         * than one if we are encrypting for multiple recipients or
         * are storing an encrypted message locally. */
        $send_msgs = array();
        $msg_options = array(
            'encrypt' => $encrypt,
            'html' => !empty($opts['html'])
        );

        /* Must encrypt & send the message one recipient at a time. */
        if ($prefs->getValue('use_smime') &&
            in_array($encrypt, array(IMP_Crypt_Smime::ENCRYPT, IMP_Crypt_Smime::SIGNENC))) {
            foreach ($recip['list'] as $val) {
                $send_msgs[] = array(
                    'base' => $this->_createMimeMessage(array($val), $body, $msg_options),
                    'recipients' => array($val)
                );
            }

            /* Must target the encryption for the sender before saving message
             * in sent-mail. */
            $save_msg = $this->_createMimeMessage(array($header['from']), $body, $msg_options);
        } else {
            /* Can send in clear-text all at once, or PGP can encrypt
             * multiple addresses in the same message. */
            $msg_options['from'] = $barefrom;
            $save_msg = $this->_createMimeMessage($recip['list'], $body, $msg_options);
            $send_msgs[] = array(
                'base' => $save_msg,
                'recipients' => $recip['list']
            );
        }

        /* Initalize a header object for the outgoing message. */
        $headers = $this->_prepareHeaders($header, $opts);

        /* Add a Received header for the hop from browser to server. */
        $headers->addReceivedHeader(array(
            'dns' => $injector->getInstance('Net_DNS2_Resolver'),
            'server' => $conf['server']['name']
        ));

        /* Add Reply-To header. */
        if (!empty($header['replyto']) &&
            ($header['replyto'] != $barefrom)) {
            $headers->addHeader('Reply-to', $header['replyto']);
        }

        /* Add the 'User-Agent' header. */
        if (empty($opts['useragent'])) {
            $headers->setUserAgent('Internet Messaging Program (IMP) ' . $registry->getVersion());
        } else {
            $headers->setUserAgent($opts['useragent']);
        }
        $headers->addUserAgentHeader();

        /* Add preferred reply language(s). */
        if ($lang = @unserialize($prefs->getValue('reply_lang'))) {
            $headers->addHeader('Accept-Language', implode(',', $lang));
        }

        /* Send the messages out now. */
        $sentmail = $injector->getInstance('IMP_Sentmail');

        foreach ($send_msgs as $val) {
            switch ($this->_replytype) {
            case self::COMPOSE:
                $senttype = IMP_Sentmail::NEWMSG;
                break;

            case self::REPLY:
            case self::REPLY_ALL:
            case self::REPLY_LIST:
            case self::REPLY_SENDER:
                $senttype = IMP_Sentmail::REPLY;
                break;

            case self::FORWARD:
            case self::FORWARD_ATTACH:
            case self::FORWARD_BODY:
            case self::FORWARD_BOTH:
                $senttype = IMP_Sentmail::FORWARD;
                break;

            case self::REDIRECT:
                $senttype = IMP_Sentmail::REDIRECT;
                break;
            }

            try {
                $this->_prepSendMessageAssert($val['recipients'], $headers, $val['base']);
                $this->sendMessage($val['recipients'], $headers, $val['base']);

                /* Store history information. */
                $sentmail->log($senttype, $headers->getValue('message-id'), $val['recipients'], true);
            } catch (IMP_Compose_Exception $e) {
                /* Unsuccessful send. */
                if ($e->log()) {
                    $sentmail->log($senttype, $headers->getValue('message-id'), $val['recipients'], false);
                }
                throw new IMP_Compose_Exception(sprintf(_("There was an error sending your message: %s"), $e->getMessage()));
            }

        }

        $recipients = implode(', ', $recip['recips']);
        $sent_saved = true;

        if ($this->_replytype) {
            /* Log the reply. */
            if ($this->getMetadata('in_reply_to') &&
                !empty($conf['maillog']['use_maillog'])) {
                IMP_Maillog::log($this->_replytype, $this->getMetadata('in_reply_to'), $recipients);
            }

            $imp_message = $injector->getInstance('IMP_Message');
            $reply_uid = new IMP_Indices($this);

            switch ($this->replyType(true)) {
            case self::FORWARD:
                /* Set the Forwarded flag, if possible, in the mailbox.
                 * See RFC 5550 [5.9] */
                $imp_message->flag(array(Horde_Imap_Client::FLAG_FORWARDED), $reply_uid);
                break;

            case self::REPLY:
                /* Make sure to set the IMAP reply flag and unset any
                 * 'flagged' flag. */
                $imp_message->flag(array(Horde_Imap_Client::FLAG_ANSWERED), $reply_uid);
                $imp_message->flag(array(Horde_Imap_Client::FLAG_FLAGGED), $reply_uid, false);
                break;
            }
        }

        $entry = sprintf("%s Message sent to %s from %s", $_SERVER['REMOTE_ADDR'], $recipients, $registry->getAuth());
        Horde::logMessage($entry, 'INFO');

        /* Should we save this message in the sent mail folder? */
        if (!empty($opts['sent_folder']) &&
            ((!$prefs->isLocked('save_sent_mail') && !empty($opts['save_sent'])) ||
             ($prefs->isLocked('save_sent_mail') &&
              $prefs->getValue('save_sent_mail')))) {
            /* Keep Bcc: headers on saved messages. */
            if (!empty($header['bcc'])) {
                $headers->addHeader('Bcc', $header['bcc']);
            }

            /* Strip attachments if requested. */
            $save_attach = $prefs->getValue('save_attachments');
            if (($save_attach == 'never') ||
                ((strpos($save_attach, 'prompt') === 0) &&
                 empty($opts['save_attachments']))) {
                $save_msg->buildMimeIds();

                /* Don't strip any part if this is a text message with both
                 * plaintext and HTML representation. */
                if ($save_msg->getType() != 'multipart/alternative') {
                    for ($i = 2;; ++$i) {
                        if (!($oldPart = $save_msg->getPart($i))) {
                            break;
                        }

                        $replace_part = new Horde_Mime_Part();
                        $replace_part->setType('text/plain');
                        $replace_part->setCharset($this->charset);
                        $replace_part->setLanguage($GLOBALS['language']);
                        $replace_part->setContents('[' . _("Attachment stripped: Original attachment type") . ': "' . $oldPart->getType() . '", ' . _("name") . ': "' . $oldPart->getName(true) . '"]');
                        $save_msg->alterPart($i, $replace_part);
                    }
                }
            }

            /* Generate the message string. */
            $fcc = $save_msg->toString(array('defserver' => $session->get('imp', 'maildomain'), 'headers' => $headers, 'stream' => true));

            /* Make sure sent folder is created. */
            $sent_folder = IMP_Mailbox::get($opts['sent_folder']);
            $sent_folder->create();

            $flags = array(Horde_Imap_Client::FLAG_SEEN);

            /* RFC 3503 [3.3] - set MDNSent flag on sent message. */
            if ($prefs->getValue('request_mdn') != 'never') {
                $mdn = new Horde_Mime_Mdn($headers);
                if ($mdn->getMdnReturnAddr()) {
                    $flags[] = Horde_Imap_Client::FLAG_MDNSENT;
                }
            }

            try {
                $injector->getInstance('IMP_Factory_Imap')->create()->append($sent_folder, array(array('data' => $fcc, 'flags' => $flags)));
            } catch (IMP_Imap_Exception $e) {
                $notification->push(sprintf(_("Message sent successfully, but not saved to %s."), $sent_folder->display));
                $sent_saved = false;
            }
        }

        /* Delete the attachment data. */
        $this->deleteAllAttachments();

        /* Save recipients to address book? */
        $this->_saveRecipients($recip['list']);

        /* Call post-sent hook. */
        try {
            Horde::callHook('post_sent', array($save_msg['msg'], $headers), 'imp');
        } catch (Horde_Exception_HookNotSet $e) {}

        return $sent_saved;
    }

    /**
     * Prepare header object with basic header fields and converts headers
     * to the current compose charset.
     *
     * @param array $headers  Array with 'from', 'to', 'cc', 'bcc', and
     *                        'subject' values.
     * @param array $opts     An array of options w/the following keys:
     *   - priority: (string) The message priority ('high', 'normal', 'low').
     *
     * @return Horde_Mime_Headers  Headers object with the appropriate headers
     *                             set.
     */
    protected function _prepareHeaders($headers, array $opts = array())
    {
        $ob = new Horde_Mime_Headers();

        $ob->addHeader('Date', date('r'));
        $ob->addMessageIdHeader();

        if (isset($headers['from']) && strlen($headers['from'])) {
            $ob->addHeader('From', $headers['from']);
        }

        if (isset($headers['to']) && strlen($headers['to'])) {
            $ob->addHeader('To', $headers['to']);
        } elseif (!isset($headers['cc'])) {
            $ob->addHeader('To', 'undisclosed-recipients:;');
        }

        if (isset($headers['cc']) && strlen($headers['cc'])) {
            $ob->addHeader('Cc', $headers['cc']);
        }

        if (isset($headers['subject']) && strlen($headers['subject'])) {
            $ob->addHeader('Subject', $headers['subject']);
        }

        if ($this->replyType(true) == self::REPLY) {
            if ($this->getMetadata('references')) {
                $ob->addHeader('References', implode(' ', preg_split('|\s+|', trim($this->getMetadata('references')))));
            }
            if ($this->getMetadata('in_reply_to')) {
                $ob->addHeader('In-Reply-To', $this->getMetadata('in_reply_to'));
            }
        }

        /* Add priority header, if requested. */
        if (!empty($opts['priority'])) {
            switch ($opts['priority']) {
            case 'high':
                $ob->addHeader('Importance', 'High');
                $ob->addHeader('X-Priority', '1 (Highest)');
                break;

            case 'low':
                $ob->addHeader('Importance', 'Low');
                $ob->addHeader('X-Priority', '5 (Lowest)');
                break;
            }
        }

        /* Add Return Receipt Headers. */
        if (!empty($opts['readreceipt']) &&
            ($GLOBALS['prefs']->getValue('request_mdn') != 'never')) {
            $mdn = new Horde_Mime_Mdn($ob);
            $mdn->addMdnRequestHeaders(Horde_Mime_Address::bareAddress($ob->getValue('from'), $GLOBALS['session']->get('imp', 'maildomain')));
        }

        return $ob;
    }

    /**
     * Sends a message.
     *
     * @param array $email                 The e-mail list to send to.
     * @param Horde_Mime_Headers $headers  The object holding this message's
     *                                     headers.
     * @param Horde_Mime_Part $message     The Horde_Mime_Part object that
     *                                     contains the text to send.
     *
     * @throws IMP_Compose_Exception
     */
    public function sendMessage($email, $headers, $message)
    {
        $email = $this->_prepSendMessage($email, $message);

        try {
            $message->send($email, $headers, $GLOBALS['injector']->getInstance('IMP_Mail'));
        } catch (Horde_Mime_Exception $e) {
            throw new IMP_Compose_Exception($e);
        }
    }

    /**
     * Sanity checking/MIME formatting before sending a message.
     *
     * @param array $email             The e-mail list to send to.
     * @param Horde_Mime_Part $message  The Horde_Mime_Part object that
     *                                  contains the text to send.
     *
     * @return string  The encoded $email list.
     *
     * @throws IMP_Compose_Exception
     */
    protected function _prepSendMessage($email, $message = null)
    {
        /* Properly encode the addresses we're sending to. Always try
         * charset of original message as we know that the user can handle
         * that charset. */
        try {
            return $this->_prepSendMessageEncode($email, is_null($message) ? 'UTF-8' : $message->getHeaderCharset());
        } catch (IMP_Compose_Exception $e) {
            if (is_null($message)) {
                throw $e;
            }
        }

        /* Fallback to UTF-8 (if replying, original message might be in
         * US-ASCII, for example, but To/Subject/Etc. may contain 8-bit
         * characters. */
        $message->setHeaderCharset('UTF-8');
        return $this->_prepSendMessageEncode($email, 'UTF-8');
    }

    /**
     * Additonal checks to do if this is a user-generated compose message.
     *
     * @param array $email                 The e-mail list to send to.
     * @param Horde_Mime_Headers $headers  The object holding this message's
     *                                     headers.
     * @param Horde_Mime_Part $message     The Horde_Mime_Part object that
     *                                     contains the text to send.
     *
     * @throws IMP_Compose_Exception
     */
    protected function _prepSendMessageAssert($email, $headers = null,
                                              $message = null)
    {
        global $conf, $injector, $registry;

        $core_perms = $injector->getInstance('Horde_Core_Perms');

        if (!$core_perms->hasAppPermission('max_timelimit', array('opts' => array('value' => count($email))))) {
            Horde::permissionDeniedError('imp', 'max_timelimit');
            throw new IMP_Compose_Exception(sprintf(_("You are not allowed to send messages to more than %d recipients within %d hours."), $injector->getInstance('Horde_Perms')->getPermissions('imp:max_timelimit', $registry->getAuth()), $conf['sentmail']['params']['limit_period']));
        }

        /* Count recipients if necessary. We need to split email groups
         * because the group members count as separate recipients. */
        if (!$core_perms->hasAppPermission('max_recipients', array('opts' => array('value' => count($email))))) {
            Horde::permissionDeniedError('imp', 'max_recipients');
            throw new IMP_Compose_Exception(sprintf(_("You are not allowed to send messages to more than %d recipients."), $injector->getInstance('Horde_Perms')->getPermissions('imp:max_recipients', $registry->getAuth())));
        }

        /* Pass to hook to allow alteration of message details. */
        if (!is_null($message)) {
            try {
                Horde::callHook('pre_sent', array($message, $headers, $this), 'imp');
            } catch (Horde_Exception_HookNotSet $e) {}
        }
    }

    /**
     * Encode address and do sanity checking on encoded address.
     *
     * @param array $email     The e-mail list to send to.
     * @param string $charset  The charset to encode to.
     *
     * @return string  The encoded $email list.
     *
     * @throws IMP_Compose_Exception
     */
    protected function _prepSendMessageEncode($email, $charset)
    {
        $out = array();

        // Here, $email is list of address objects.
        foreach ($email as $val) {
            // Convert IDN hosts to ASCII.
            if (function_exists('idn_to_ascii')) {
                $val['host'] = @idn_to_ascii(trim($val['host']));
            } elseif (Horde_Mime::is8bit($val['mailbox'], 'UTF-8')) {
                throw new IMP_Compose_Exception(sprintf(_("Invalid character in e-mail address: %s."), Horde_Mime_Address::addrObject2String($val)));
            }

            // Encode personal part of e-mail address.
            if (isset($val['personal'])) {
                $val['personal'] = Horde_Mime::encode($val['personal'], 'UTF-8');
            }

            // Write out address.
            $tmp = Horde_Mime_Address::writeAddress($val['mailbox'], trim($val['host']), isset($val['personal']) ? $val['personal'] : '');

            // Check if address is valid.
            try {
                Horde_Mime_Address::parseAddressList($tmp, array(
                    'validate' => true
                ));
            } catch (Horde_Mime_Exception $e) {
                throw new IMP_Compose_Exception(sprintf(_("Invalid e-mail address (%s)."), $tmp));
            }

            $out[] = $tmp;
        }

        return implode(', ', $out);
    }

    /**
     * Save the recipients done in a sendMessage().
     *
     * @param array $recipients  The list of recipients.
     */
    protected function _saveRecipients($recipients)
    {
        global $notification, $prefs, $registry;

        if (empty($recipients) ||
            !$prefs->getValue('save_recipients') ||
            !$registry->hasMethod('contacts/import') ||
            !$registry->hasMethod('contacts/search')) {
            return;
        }

        $abook = $prefs->getValue('add_source');
        if (empty($abook)) {
            return;
        }

        /* Filter out anyone that matches an email address already
         * in the address book. */
        $emails = array();
        foreach ($recipients as $recipient) {
            $emails[] = $recipient['mailbox'] . '@' . $recipient['host'];
        }

        try {
            $results = $registry->call('contacts/search', array($emails, array($abook), array($abook => array('email'))));
        } catch (Horde_Exception $e) {
            Horde::logMessage($e, 'ERR');
            $notification->push(_("Could not save recipients."));
            return;
        }

        foreach ($recipients as $recipient) {
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
            $name = Horde_Mime::decode($name, 'UTF-8');

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
     * Cleans up and returns the recipient list. Method designed to parse
     * user entered data; does not encode/validate addresses.
     *
     * @param array $hdr  An array of MIME headers.  Recipients will be
     *                    extracted from the 'to', 'cc', and 'bcc' entries.
     *
     * @return array  An array with the following entries:
     *   - header: (array) Contains the cleaned up 'to', 'cc', and 'bcc'
     *             header strings.
     *   - list: (array) Recipient addresses (address objects).
     *   - recips: (array) List of recipient addresses (string).
     */
    public function recipientList($hdr)
    {
        $addrlist = $header = $recips = array();

        foreach (array('to', 'cc', 'bcc') as $key) {
            if (!isset($hdr[$key])) {
                continue;
            }

            $arr = array_filter(array_map('trim', Horde_Mime_Address::explode($hdr[$key], ',;')));
            $tmp = array();

            foreach ($arr as $email) {
                if (!strlen($email)) {
                    continue;
                }

                try {
                    $obs = Horde_Mime_Address::parseAddressList($email, array(
                        'defserver' => $GLOBALS['session']->get('imp', 'maildomain'),
                        'nestgroups' => true,
                        'validate' => false
                    ));
                } catch (Horde_Mime_Exception $e) {
                    throw new IMP_Compose_Exception(sprintf(_("Invalid e-mail address: %s."), $email));
                }

                foreach ($obs as $ob) {
                    if (isset($ob['groupname'])) {
                        $group_addresses = array();
                        foreach ($ob['addresses'] as $ad) {
                            $addrlist[] = $ad;
                            $recips[] = $group_addresses[] = Horde_Mime_Address::writeAddress($ad['mailbox'], trim($ad['host']), isset($ad['personal']) ? $ad['personal'] : '');
                        }

                        $tmp[] = Horde_Mime_Address::writeGroupAddress($ob['groupname'], $group_addresses) . ' ';
                    } else {
                        $addrlist[] = $ob;
                        $recips[] = $tmp[] = Horde_Mime_Address::writeAddress($ob['mailbox'], trim($ob['host']), isset($ob['personal']) ? $ob['personal'] : '');
                    }
                }
            }

            $header[$key] = implode(', ', $tmp);
        }

        return array(
            'header' => $header,
            'list' => $addrlist,
            'recips' => $recips
        );
    }

    /**
     * Create the base Horde_Mime_Part for sending.
     *
     * @param array $to        The recipient list.
     * @param string $body     Message body.
     * @param array $options   Additional options:
     *   - encrypt: (integer) The encryption flag.
     *   - from: (string) The outgoing from address - only needed for multiple
     *           PGP encryption.
     *   - html: (boolean) Is this a HTML message?
     *   - nofinal: (boolean) This is not a message which will be sent out.
     *   - noattach: (boolean) Don't add attachment information.
     *
     * @return Horde_Mime_Part  The MIME message to send.
     *
     * @throws Horde_Exception
     * @throws IMP_Compose_Exception
     */
    protected function _createMimeMessage($to, $body, array $options = array())
    {
        $body = Horde_String::convertCharset($body, 'UTF-8', $this->charset);

        if (!empty($options['html'])) {
            $body_html = $body;
            $body = $GLOBALS['injector']->getInstance('Horde_Core_Factory_TextFilter')->filter($body, 'Html2text', array('wrap' => false, 'charset' => $this->charset));
        }

        /* Get trailer text (if any). */
        if (empty($options['nofinal'])) {
            try {
                if ($trailer = Horde::callHook('trailer', array(), 'imp')) {
                    $body .= $trailer;
                    if (!empty($options['html'])) {
                        $body_html .= $this->text2html($trailer);
                    }
                }
            } catch (Horde_Exception_HookNotSet $e) {}
        }

        /* Set up the body part now. */
        $textBody = new Horde_Mime_Part();
        $textBody->setType('text/plain');
        $textBody->setCharset($this->charset);
        $textBody->setDisposition('inline');

        /* Send in flowed format. */
        $flowed = new Horde_Text_Flowed($body, $this->charset);
        $flowed->setDelSp(true);
        $textBody->setContentTypeParameter('format', 'flowed');
        $textBody->setContentTypeParameter('DelSp', 'Yes');
        $textBody->setContents($flowed->toFlowed());

        /* Determine whether or not to send a multipart/alternative
         * message with an HTML part. */
        if (!empty($options['html'])) {
            $htmlBody = new Horde_Mime_Part();
            $htmlBody->setType('text/html');
            $htmlBody->setCharset($this->charset);
            $htmlBody->setDisposition('inline');
            $htmlBody->setDescription(Horde_String::convertCharset(_("HTML Message"), 'UTF-8', $this->charset));

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

            $htmlBody->setContents($GLOBALS['injector']->getInstance('Horde_Core_Factory_TextFilter')->filter($body_html, 'cleanhtml', array('charset' => $this->charset)));

            $textBody->setDescription(Horde_String::convertCharset(_("Plaintext Message"), 'UTF-8', $this->charset));

            $textpart = new Horde_Mime_Part();
            $textpart->setType('multipart/alternative');
            $textpart->addPart($textBody);
            $textpart->setHeaderCharset($this->charset);

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
        if (empty($options['noattach']) && count($this)) {
            if (($this->_linkAttach &&
                 $GLOBALS['conf']['compose']['link_attachments']) ||
                !empty($GLOBALS['conf']['compose']['link_all_attachments'])) {
                $base = $this->linkAttachments($textpart);

                if ($this->_pgpAttachPubkey ||
                    ($this->_attachVCard !== false)) {
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
                foreach ($this as $id => $val) {
                    $base->addPart($this->buildAttachment($id));
                }
            }
        } elseif ($this->_pgpAttachPubkey ||
                  ($this->_attachVCard !== false)) {
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

            if ($this->_attachVCard !== false) {
                try {
                    $vcard = $GLOBALS['registry']->call('contacts/ownVCard');

                    $vpart = new Horde_Mime_Part();
                    $vpart->setType('text/x-vcard');
                    $vpart->setCharset('UTF-8');
                    $vpart->setContents($vcard);
                    $vpart->setName($this->_attachVCard);

                    $base->addPart($vpart);
                } catch (Horde_Exception $e) {}
            }
        }

        /* Set up the base message now. */
        $encrypt = empty($options['encrypt'])
            ? IMP::ENCRYPT_NONE
            : $options['encrypt'];
        if ($GLOBALS['prefs']->getValue('use_pgp') &&
            !empty($GLOBALS['conf']['gnupg']['path']) &&
            in_array($encrypt, array(IMP_Crypt_Pgp::ENCRYPT, IMP_Crypt_Pgp::SIGN, IMP_Crypt_Pgp::SIGNENC, IMP_Crypt_Pgp::SYM_ENCRYPT, IMP_Crypt_Pgp::SYM_SIGNENC))) {
            $imp_pgp = $GLOBALS['injector']->getInstance('IMP_Crypt_Pgp');
            $symmetric_passphrase = null;

            switch ($encrypt) {
            case IMP_Crypt_Pgp::SIGN:
            case IMP_Crypt_Pgp::SIGNENC:
            case IMP_Crypt_Pgp::SYM_SIGNENC:
                /* Check to see if we have the user's passphrase yet. */
                $passphrase = $imp_pgp->getPassphrase('personal');
                if (empty($passphrase)) {
                    $e = new IMP_Compose_Exception(_("PGP: Need passphrase for personal private key."));
                    $e->encrypt = 'pgp_passphrase_dialog';
                    throw $e;
                }
                break;

            case IMP_Crypt_Pgp::SYM_ENCRYPT:
            case IMP_Crypt_Pgp::SYM_SIGNENC:
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
                case IMP_Crypt_Pgp::SIGN:
                    $base = $imp_pgp->IMPsignMIMEPart($base);
                    break;

                case IMP_Crypt_Pgp::ENCRYPT:
                case IMP_Crypt_Pgp::SYM_ENCRYPT:
                    $to_list = empty($options['from'])
                        ? $to
                        : array_keys(array_flip(array_merge($to, array($options['from']))));
                    $base = $imp_pgp->IMPencryptMIMEPart($base, $to_list, ($encrypt == IMP_Crypt_Pgp::SYM_ENCRYPT) ? $symmetric_passphrase : null);
                    break;

                case IMP_Crypt_Pgp::SIGNENC:
                case IMP_Crypt_Pgp::SYM_SIGNENC:
                    $to_list = empty($options['from'])
                        ? $to
                        : array_keys(array_flip(array_merge($to, array($options['from']))));
                    $base = $imp_pgp->IMPsignAndEncryptMIMEPart($base, $to_list, ($encrypt == IMP_Crypt_Pgp::SYM_SIGNENC) ? $symmetric_passphrase : null);
                    break;
                }
            } catch (Horde_Exception $e) {
                throw new IMP_Compose_Exception(_("PGP Error: ") . $e->getMessage(), $e->getCode());
            }
        } elseif ($GLOBALS['prefs']->getValue('use_smime') &&
                  in_array($encrypt, array(IMP_Crypt_Smime::ENCRYPT, IMP_Crypt_Smime::SIGN, IMP_Crypt_Smime::SIGNENC))) {
            $imp_smime = $GLOBALS['injector']->getInstance('IMP_Crypt_Smime');

            /* Check to see if we have the user's passphrase yet. */
            if (in_array($encrypt, array(IMP_Crypt_Smime::SIGN, IMP_Crypt_Smime::SIGNENC))) {
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
                case IMP_Crypt_Smime::SIGN:
                    $base = $imp_smime->IMPsignMIMEPart($base);
                    break;

                case IMP_Crypt_Smime::ENCRYPT:
                    $base = $imp_smime->IMPencryptMIMEPart($base, $to[0]);
                    break;

                case IMP_Crypt_Smime::SIGNENC:
                    $base = $imp_smime->IMPsignAndEncryptMIMEPart($base, $to[0]);
                    break;
                }
            } catch (Horde_Exception $e) {
                throw new IMP_Compose_Exception(_("S/MIME Error: ") . $e->getMessage(), $e->getCode());
            }
        }

        /* Flag this as the base part. */
        $base->isBasePart(true);

        return $base;
    }

    /**
     * Determines the reply text and headers for a message.
     *
     * @param integer $type           The reply type (self::REPLY* constant).
     * @param IMP_Contents $contents  An IMP_Contents object.
     * @param string $to              The recipient of the reply. Overrides
     *                                the automatically determined value.
     *
     * @return array  An array with the following keys:
     *   - body: The text of the body part
     *   - format: The format of the body message
     *   - headers: The headers of the message to use for the reply
     *   - identity: The identity to use for the reply based on the original
     *            message's addresses.
     *   - lang: An array of language code (keys)/language name (values) of
     *           the original sender's preferred language(s).
     *   - reply_list_id: List ID label.
     *   - reply_recip: Number of recipients in reply list.
     *   - type: The reply type used (either self::REPLY_ALL,
     *           self::REPLY_LIST, or self::REPLY_SENDER).
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

        $h = $contents->getHeader();
        $match_identity = $this->_getMatchingIdentity($h);
        $reply_type = self::REPLY_SENDER;

        if (!$this->_replytype) {
            $this->_metadata['mailbox'] = $contents->getMailbox();
            $this->_metadata['uid'] = $contents->getUid();
            $this->changed = 'changed';

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
            : 'Re: ' . $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->getUtils()->getBaseSubject($subject, array('keepblob' => true));

        $force = false;
        if (in_array($type, array(self::REPLY_AUTO, self::REPLY_SENDER))) {
            if (($header['to'] = $to) ||
                ($header['to'] = Horde_Mime_Address::addrArray2String($h->getOb('reply-to')))) {
                $force = true;
            } else {
                $header['to'] = Horde_Mime_Address::addrArray2String($h->getOb('from'));
            }
        }

        /* We might need $list_info in the reply_all section. */
        if (in_array($type, array(self::REPLY_AUTO, self::REPLY_LIST))) {
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
                $reply_type = self::REPLY_LIST;
            }
        } elseif (in_array($type, array(self::REPLY_ALL, self::REPLY_AUTO))) {
            /* Clear the To field if we are auto-determining addresses. */
            if ($type == self::REPLY_AUTO) {
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
                    $addr_obs = Horde_Mime_Address::getAddressesFromObject($ob, array('filter' => $all_addrs));
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
                            /* Don't add as To address if this is a list that
                             * doesn't have a post address but does have a
                             * reply-to address. */
                            if (in_array($val, array('from', 'reply-to'))) {
                                /* If from/reply-to doesn't have personal
                                 * information, check from address. */
                                if (!$addr_obs[0]['personal'] &&
                                    ($to_ob = $h->getOb('from')) &&
                                    $to_ob[0]['personal'] &&
                                    ($to_addr = Horde_Mime_Address::addrArray2String($to_ob)) &&
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

            if (count($hdr_cc)) {
                $reply_type = self::REPLY_ALL;
            }
            $header[empty($header['to']) ? 'to' : 'cc'] = rtrim(implode('', $hdr_cc), ' ,');

            /* Build the Bcc: header. */
            $header['bcc'] = Horde_Mime_Address::addrArray2String($h->getOb('bcc') + $identity->getBccAddresses(), array('filter' => $all_addrs));
        }

        if (!$this->_replytype || ($reply_type != $this->_replytype)) {
            $this->_replytype = $reply_type;
            $this->changed = 'changed';
        }

        $ret = $this->replyMessageText($contents);
        if ($ret['charset'] != $this->charset) {
            $this->charset = $ret['charset'];
            $this->changed = 'changed';
        }
        unset($ret['charset']);

        if ($type == self::REPLY_AUTO) {
            switch ($reply_type) {
            case self::REPLY_ALL:
                try {
                    $recip_list = $this->recipientList($header);
                    $ret['reply_recip'] = count($recip_list['list']);
                } catch (IMP_Compose_Exception $e) {
                    $ret['reply_recip'] = 0;
                }
                break;

            case self::REPLY_LIST:
                $addr_ob = Horde_Mime_Address::parseAddressList($h->getValue('list-id'));
                if (isset($addr_ob[0]['personal'])) {
                    $ret['reply_list_id'] = $addr_ob[0]['personal'];
                }
                break;
            }
        }

        if (($lang = $h->getValue('accept-language')) ||
            ($lang = $h->getValue('x-accept-language'))) {
            $langs = array();
            foreach (explode(',', $lang) as $val) {
                if (($name = Horde_Nls::getLanguageISO($val)) !== null) {
                    $langs[trim($val)] = $name;
                }
            }
            $ret['lang'] = array_unique($langs);

            /* Don't show display if original recipient is asking for reply in
             * the user's native language. */
            if ((count($ret['lang']) == 1) &&
                reset($ret['lang']) &&
                (substr(key($ret['lang']), 0, 2) == substr($GLOBALS['language'], 0, 2))) {
                unset($ret['lang']);
            }
        }

        return array_merge(array(
            'headers' => $header,
            'identity' => $match_identity,
            'type' => $reply_type
        ), $ret);
    }

    /**
     * Returns the reply text for a message.
     *
     * @param IMP_Contents $contents  An IMP_Contents object.
     * @param array $opts             Additional options:
     *   - format: (string) Force to this format.
     *             DEFAULT: Auto-determine.
     *
     * @return array  An array with the following keys:
     *   - body: (string) The text of the body part.
     *   - charset: (string) The guessed charset to use for the reply.
     *   - format: (string) The format of the body message ('html', 'text').
     */
    public function replyMessageText($contents, array $opts = array())
    {
        global $prefs;

        if (!$prefs->getValue('reply_quote')) {
            return array(
                'body' => '',
                'charset' => '',
                'format' => 'text'
            );
        }

        $h = $contents->getHeader();

        $from = Horde_Mime_Address::addrArray2String($h->getOb('from'));

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

        list($compose_html, $force_html) = $this->_msgTextFormat($opts, 'reply_format');

        $msg_text = $this->_getMessageText($contents, array(
            'html' => $compose_html,
            'replylimit' => true,
            'toflowed' => true
        ));

        if (!empty($msg_text) &&
            (($msg_text['mode'] == 'html') || $force_html)) {
            $msg = '<p>' . $this->text2html(trim($msg_pre)) . '</p>' .
                   self::HTML_BLOCKQUOTE .
                   (($msg_text['mode'] == 'text') ? $this->text2html($msg_text['flowed'] ? $msg_text['flowed'] : $msg_text['text']) : $msg_text['text']) .
                   '</blockquote><br />' .
                   ($msg_post ? $this->text2html($msg_post) : '') . '<br />';
            $msg_text['mode'] = 'html';
        } else {
            $msg = empty($msg_text['text'])
                ? '[' . _("No message body text") . ']'
                : $msg_pre . $msg_text['text'] . $msg_post;
            $msg_text['mode'] = 'text';
        }

        // Bug #10148: Message text might be us-ascii, but reply headers may
        // contain 8-bit characters.
        if (($msg_text['charset'] == 'us-ascii') &&
            (Horde_Mime::is8bit($msg_pre, 'UTF-8') ||
             Horde_Mime::is8bit($msg_post, 'UTF-8'))) {
            $msg_text['charset'] = 'UTF-8';
        }

        return array(
            'body' => $msg . "\n",
            'charset' => $msg_text['charset'],
            'format' => $msg_text['mode']
        );
    }

    /**
     * Determine text editor format.
     *
     * @param array $opts        Options (contains 'format' param).
     * @param string $pref_name  The pref name that controls formatting.
     *
     * @return array  Use HTML? and Force HTML?
     */
    protected function _msgTextFormat($opts, $pref_name)
    {
        if (IMP::getViewMode() == 'mimp') {
            $compose_html = $force_html = false;
        } elseif (!empty($opts['format'])) {
            $compose_html = $force_html = ($opts['format'] == 'html');
        } elseif ($GLOBALS['prefs']->getValue('compose_html')) {
            $compose_html = $force_html = true;
        } else {
            $compose_html = $GLOBALS['prefs']->getValue($pref_name);
            $force_html = false;
        }

        return array($compose_html, $force_html);
    }

    /**
     * Determine the text and headers for a forwarded message.
     *
     * @param integer $type           The forward type (self::FORWARD*
     *                                constant).
     * @param IMP_Contents $contents  An IMP_Contents object.
     * @param boolean $attach         Attach the forwarded message?
     *
     * @return array  An array with the following keys:
     * <ul>
     *  <li>
     *   body: (string) The text of the body part.
     *  </li>
     *  <li>
     *   format: (string) The format of the body message ('html', 'text').
     *  </li>
     *  <li>
     *   headers: (array) The headers of the message to use for the reply.
     *  </li>
     *  <li>
     *   identity: (mixed) See Imp_Prefs_Identity#getMatchingIdentity().
     *  </li>
     *  <li>
     *   type: (integer) - The forward type used. Either:
     *   <ul>
     *    <li>self::FORWARD_ATTACH</li>
     *    <li>self::FORWARD_BODY</li>
     *    <li>self::FORWARD_BOTH</li>
     *   </ul>
     *  </li>
     * </ul>
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

        if ($type == self::FORWARD_AUTO) {
            switch ($GLOBALS['prefs']->getValue('forward_default')) {
            case 'body':
                $type = self::FORWARD_BODY;
                break;

            case 'both':
                $type = self::FORWARD_BOTH;
                break;

            case 'attach':
            default:
                $type = self::FORWARD_ATTACH;
                break;
            }
        }

        $h = $contents->getHeader();
        $format = 'text';
        $msg = '';

        $this->_metadata['mailbox'] = $contents->getMailbox();
        $this->_metadata['uid'] = $contents->getUid();

        /* We need the Message-Id so we can log this event. This header is not
         * added to the outgoing messages. */
        $this->_metadata['in_reply_to'] = trim($h->getValue('message-id'));
        $this->_replytype = $type;
        $this->changed = 'changed';

        $header['subject'] = $h->getValue('subject');
        if (!empty($header['subject'])) {
            $subject = $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->getUtils()->getBaseSubject($header['subject'], array('keepblob' => true));
            $header['title'] = _("Forward") . ': ' . $subject;
            $header['subject'] = 'Fwd: ' . $subject;
        } else {
            $header['title'] = _("Forward");
            $header['subject'] = 'Fwd:';
        }

        if ($attach &&
            in_array($type, array(self::FORWARD_ATTACH, self::FORWARD_BOTH))) {
            try {
                $this->attachImapMessage(new IMP_Indices($contents));
            } catch (IMP_Exception $e) {}
        }

        if (in_array($type, array(self::FORWARD_BODY, self::FORWARD_BOTH))) {
            $ret = $this->forwardMessageText($contents);
            $this->charset = $ret['charset'];
            unset($ret['charset']);
        } else {
            $ret = array(
                'body' => '',
                'format' => $GLOBALS['prefs']->getValue('compose_html') ? 'html' : 'text'
            );
        }

        return array_merge(array(
            'headers' => $header,
            'identity' => $this->_getMatchingIdentity($h),
            'type' => $type
        ), $ret);
    }

    /**
     * Returns the forward text for a message.
     *
     * @param IMP_Contents $contents  An IMP_Contents object.
     * @param array $opts             Additional options:
     *   - format: (string) Force to this format.
     *             DEFAULT: Auto-determine.
     *
     * @return array  An array with the following keys:
     *   - body: (string) The text of the body part.
     *   - charset: (string) The guessed charset to use for the forward.
     *   - format: (string) The format of the body message ('html', 'text').
     */
    public function forwardMessageText($contents, array $opts = array())
    {
        global $prefs;

        $h = $contents->getHeader();

        $from = Horde_Mime_Address::addrArray2String($h->getOb('from'));

        $msg_pre = "\n----- " .
            ($from ? sprintf(_("Forwarded message from %s"), $from) : _("Forwarded message")) .
            " -----\n" . $this->_getMsgHeaders($h) . "\n";
        $msg_post = "\n\n----- " . _("End forwarded message") . " -----\n";

        list($compose_html, $force_html) = $this->_msgTextFormat($opts, 'forward_format');

        $msg_text = $this->_getMessageText($contents, array(
            'html' => $compose_html
        ));

        if (!empty($msg_text) &&
            (($msg_text['mode'] == 'html') || $force_html)) {
            $msg = $this->text2html($msg_pre) .
                (($msg_text['mode'] == 'text') ? $this->text2html($msg_text['text']) : $msg_text['text']) .
                $this->text2html($msg_post);
            $format = 'html';
        } else {
            $msg = $msg_pre . $msg_text['text'] . $msg_post;
            $format = 'text';
        }

        // Bug #10148: Message text might be us-ascii, but forward headers may
        // contain 8-bit characters.
        if (($msg_text['charset'] == 'us-ascii') &&
            (Horde_Mime::is8bit($msg_pre, 'UTF-8') ||
             Horde_Mime::is8bit($msg_post, 'UTF-8'))) {
            $msg_text['charset'] = 'UTF-8';
        }

        return array(
            'body' => $msg,
            'charset' => $msg_text['charset'],
            'format' => $format
        );
    }

    /**
     * Prepare a redirect message.
     *
     * @param IMP_Indices $indices  An indices object.
     */
    public function redirectMessage(IMP_Indices $indices)
    {
        $this->_metadata['redirect_indices'] = $indices;
        $this->_replytype = self::REDIRECT;
        $this->changed = 'changed';
    }

    /**
     * Send a redirect (a/k/a resent) message. See RFC 5322 [3.6.6].
     *
     * @param string $to  The addresses to redirect to.
     *
     * @return array  An object with the following properties for each
     *                redirected message:
     *   - contents: (IMP_Contents) The contents object.
     *   - headers: (Horde_Mime_Headers) The header object.
     *   - mbox: (IMP_Mailbox) Mailbox of the message.
     *   - uid: (string) UID of the message.
     *
     * @throws IMP_Compose_Exception
     */
    public function sendRedirectMessage($to)
    {
        $recip = $this->recipientList(array('to' => $to));

        $identity = $GLOBALS['injector']->getInstance('IMP_Identity');
        $from_addr = $identity->getFromAddress();

        $out = array();

        foreach ($this->getMetadata('redirect_indices') as $val) {
            foreach ($val->uids as $val2) {
                try {
                    $contents = $GLOBALS['injector']->getInstance('IMP_Factory_Contents')->create($val->mbox->getIndicesOb($val2));
                } catch (IMP_Exception $e) {
                    throw new IMP_Compose_Exception(_("Error when redirecting message."));
                }

                $headers = $contents->getHeader();

                /* We need to set the Return-Path header to the current user -
                 * see RFC 2821 [4.4]. */
                $headers->removeHeader('return-path');
                $headers->addHeader('Return-Path', $from_addr);

                /* Generate the 'Resent' headers (RFC 5322 [3.6.6]). These
                 * headers are prepended to the message. */
                $resent_headers = new Horde_Mime_Headers();
                $resent_headers->addHeader('Resent-Date', date('r'));
                $resent_headers->addHeader('Resent-From', $from_addr);
                $resent_headers->addHeader('Resent-To', $recip['header']['to']);
                $resent_headers->addHeader('Resent-Message-ID', Horde_Mime::generateMessageId());

                $header_text = trim($resent_headers->toString(array('encode' => 'UTF-8'))) . "\n" . trim($contents->getHeader(IMP_Contents::HEADER_TEXT));

                $this->_prepSendMessageAssert($recip['list']);
                $to = $this->_prepSendMessage($recip['list']);
                $hdr_array = $headers->toArray(array('charset' => 'UTF-8'));
                $hdr_array['_raw'] = $header_text;

                try {
                    $GLOBALS['injector']->getInstance('IMP_Mail')->send($to, $hdr_array, $contents->getBody());
                } catch (Horde_Mail_Exception $e) {
                    throw new IMP_Compose_Exception($e);
                }

                $recipients = implode(', ', $recip['list']);

                Horde::logMessage(sprintf("%s Redirected message sent to %s from %s", $_SERVER['REMOTE_ADDR'], $recipients, $GLOBALS['registry']->getAuth()), 'INFO');

                /* Store history information. */
                if (!empty($GLOBALS['conf']['maillog']['use_maillog'])) {
                    IMP_Maillog::log(self::REDIRECT, $headers->getValue('message-id'), $recipients);
                }

                $GLOBALS['injector']->getInstance('IMP_Sentmail')->log(IMP_Sentmail::REDIRECT, $headers->getValue('message-id'), $recipients);

                $tmp = new stdClass;
                $tmp->contents = $contents;
                $tmp->headers = $headers;
                $tmp->mbox = $val->mbox;
                $tmp->uid = $val2;

                $out[] = $tmp;
            }
        }

        return $out;
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

        /* Bug #9271: Check 'from' address first; if replying to a message
         * originally sent by user, this should be the identity used for the
         * reply also. */
        foreach (array('from', 'to', 'cc', 'bcc') as $val) {
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
     * @return string  Subject string.
     *
     * @throws IMP_Exception
     */
    public function attachImapMessage($indices)
    {
        if (!count($indices)) {
            return false;
        }

        $attached = 0;
        foreach ($indices as $ob) {
            foreach ($ob->uids as $idx) {
                ++$attached;
                $contents = $GLOBALS['injector']->getInstance('IMP_Factory_Contents')->create(new IMP_Indices($ob->mbox, $idx));
                $headerob = $contents->getHeader();

                $part = new Horde_Mime_Part();
                $part->setCharset('UTF-8');
                $part->setType('message/rfc822');
                $part->setName(_("Forwarded Message"));
                $part->setContents($contents->fullMessageText(array(
                    'stream' => true
                )), array(
                    'usestream' => true
                ));

                // Throws IMP_Compose_Exception.
                $this->addMimePartAttachment($part);
            }
        }

        if ($attached > 1) {
            return 'Fwd: ' . sprintf(_("%u Forwarded Messages"), $attached);
        }

        if ($name = $headerob->getValue('subject')) {
            $name = Horde_String::truncate($name, 80);
        } else {
            $name = _("[No Subject]");
        }

        return 'Fwd: ' . $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->getUtils()->getBaseSubject($name, array('keepblob' => true));
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
        $tmp = array();

        if (($ob = $h->getValue('date'))) {
            $tmp[_("Date")] = $ob;
        }

        if (($ob = Horde_Mime_Address::addrArray2String($h->getOb('from')))) {
            $tmp[_("From")] = $ob;
        }

        if (($ob = Horde_Mime_Address::addrArray2String($h->getOb('reply-to')))) {
            $tmp[_("Reply-To")] = $ob;
        }

        if (($ob = $h->getValue('subject'))) {
            $tmp[_("Subject")] = $ob;
        }

        if (($ob = Horde_Mime_Address::addrArray2String($h->getOb('to')))) {
            $tmp[_("To")] = $ob;
        }

        if (($ob = Horde_Mime_Address::addrArray2String($h->getOb('cc')))) {
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
     *
     * @param string $name  The input field name from the form.
     *
     * @return string  The filename.
     *
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

        /* User hook to do file scanning/MIME magic determinations. */
        try {
            $type = Horde::callHook('compose_attach', array($filename, $tempfile, $type), 'imp');
        } catch (Horde_Exception_HookNotSet $e) {}

        $part = new Horde_Mime_Part();
        $part->setType($type);
        if ($part->getPrimaryType() == 'text') {
            if ($analyzetype = Horde_Mime_Magic::analyzeFile($tempfile, empty($conf['mime']['magic_db']) ? null : $conf['mime']['magic_db'], array('nostrip' => true))) {
                $analyzetype = Horde_Mime::decodeParam('Content-Type', $analyzetype, 'UTF-8');
                $part->setCharset(isset($analyzetype['params']['charset']) ? $analyzetype['params']['charset'] : 'UTF-8');
            } else {
                $part->setCharset('UTF-8');
            }
        } else {
            $part->setHeaderCharset('UTF-8');
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
    public function addMimePartAttachment($part)
    {
        global $conf;

        $type = $part->getType();
        $vfs = $conf['compose']['use_vfs'];

        /* Try to determine the MIME type from 1) the extension and
         * then 2) analysis of the file (if available). */
        if ($type == 'application/octet-stream') {
            $type = Horde_Mime_Magic::filenameToMIME($part->getName(true), false);
        }

        /* Extract the data from the currently existing Horde_Mime_Part.
         * If this is an unknown MIME part, we must save to a temporary file
         * to run the file analysis on it. */
        if ($vfs) {
            $data = $part->getContents();
            if (($type == 'application/octet-stream') &&
                ($analyzetype = Horde_Mime_Magic::analyzeData($data, !empty($conf['mime']['magic_db']) ? $conf['mime']['magic_db'] : null))) {
                $type = $analyzetype;
            }
        } else {
            $data = Horde::getTempFile('impatt', false);
            $res = file_put_contents($data, $part->getContents());
            if ($res === false) {
                throw new IMP_Compose_Exception(sprintf(_("Could not attach %s to the message."), $part->getName()));
            }

            if (($type == 'application/octet-stream') &&
                ($analyzetype = Horde_Mime_Magic::analyzeFile($data, !empty($conf['mime']['magic_db']) ? $conf['mime']['magic_db'] : null))) {
                $type = $analyzetype;
            }
        }

        $part->setType($type);

        /* Set the size of the part explicitly since the part will not
         * contain the data until send time. */
        $bytes = $part->getBytes();
        $part->setBytes($bytes);

        /* We don't want the contents stored in the serialized object, so
         * remove. We store the data in VFS in binary format so indicate that
         * to the part for use when we reconsitute it. */
        $part->clearContents();
        $part->setTransferEncoding('binary');

        /* Check for filesize limitations. */
        if (!empty($conf['compose']['attach_size_limit']) &&
            (($conf['compose']['attach_size_limit'] - $this->sizeOfAttachments() - $bytes) < 0)) {
            throw new IMP_Compose_Exception(sprintf(_("Attached file \"%s\" exceeds the attachment size limits. File NOT attached."), $part->getName()));
        }

        /* Store the data. */
        $this->_storeAttachment($part, $data, !$vfs);
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
                $vfs = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Vfs')->create();
                $cacheID = strval(new Horde_Support_Randomid());

                if ($vfs_file) {
                    $vfs->write(self::VFS_ATTACH_PATH, $cacheID, $data, true);
                } else {
                    $vfs->writeData(self::VFS_ATTACH_PATH, $cacheID, $data, true);
                }
            } catch (Horde_Vfs_Exception $e) {
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

        $this->changed = 'changed';

        /* Add the size information to the counter. */
        $this->_size += $part->getBytes();
    }

    /**
     * Deletes all attachments.
     */
    public function deleteAllAttachments()
    {
        foreach ($this as $key => $val) {
            unset($this[$key]);
        }
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
        $atc = $this[$id];

        switch ($atc['filetype']) {
        case 'file':
            $fp = fopen($atc['filename'], 'r');
            $atc['part']->setContents($fp);
            fclose($fp);
            break;

        case 'vfs':
            try {
                $vfs = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Vfs')->create();
                $atc['part']->setContents($vfs->read(self::VFS_ATTACH_PATH, $atc['filename']));
            } catch (Horde_Vfs_Exception $e) {}
            break;
        }

        return $atc['part'];
    }

    /**
     * Expand macros in attribution text when replying to messages.
     *
     * @param string $line           The line of attribution text.
     * @param string $from           The email address of the original sender.
     * @param Horde_Mime_Headers $h  The headers object for the message.
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
            '/%d/' => strftime("%a, %d %b %Y", $udate),

            /* Date in locale's default. */
            '/%x/' => strftime("%x", $udate),

            /* Date and time in locale's default. */
            '/%c/' => strftime("%c", $udate),

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
               ($GLOBALS['conf']['compose']['attach_count_limit'] - count($this));
    }

    /**
     * What is the maximum attachment size allowed?
     *
     * @return integer  The maximum attachment size allowed (in bytes).
     */
    public function maxAttachmentSize()
    {
        $size = $GLOBALS['session']->get('imp', 'file_upload');

        if (!empty($GLOBALS['conf']['compose']['attach_size_limit'])) {
            return min($size, max($GLOBALS['conf']['compose']['attach_size_limit'] - $this->sizeOfAttachments(), 0));
        }

        return $size;
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
          ->getInstance('Horde_Core_Factory_HttpClient')
          ->create();
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
     *
     * @throws IMP_Compose_Exception
     */
    public function linkAttachments($part)
    {
        global $conf, $prefs;

        if (!$conf['compose']['link_attachments']) {
            throw new IMP_Compose_Exception(_("Linked attachments are forbidden."));
        }

        $auth = $GLOBALS['registry']->getAuth();
        $baseurl = Horde::url('attachment.php', true)->setRaw(true);

        try {
            $vfs = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Vfs')->create();
        } catch (Horde_Vfs_Exception $e) {
            throw new IMP_Compose_Exception($e);
        }

        $ts = time();
        $fullpath = sprintf('%s/%s/%d', self::VFS_LINK_ATTACH_PATH, $auth, $ts);
        $charset = $part->getCharset();

        $trailer = Horde_String::convertCharset(_("Attachments"), 'UTF-8', $charset);

        if ($damk = $prefs->getValue('delete_attachments_monthly_keep')) {
            /* Determine the first day of the month in which the current
             * attachments will be ripe for deletion, then subtract 1 second
             * to obtain the last day of the previous month. */
            $del_time = mktime(0, 0, 0, date('n') + $damk + 1, 1, date('Y')) - 1;
            $trailer .= Horde_String::convertCharset(' (' . sprintf(_("Links will expire on %s"), strftime('%x', $del_time)) . ')', 'UTF-8', $charset);
        }

        foreach ($this as $att) {
            $trailer .= "\n" . $baseurl->copy()->add(array(
                'f' => $att['part']->getName(),
                't' => $ts,
                'u' => $auth
            ));

            try {
                if ($att['filetype'] == 'vfs') {
                    $vfs->rename(self::VFS_ATTACH_PATH, $att['filename'], $fullpath, escapeshellcmd($att['part']->getName()));
                } else {
                    $data = file_get_contents($att['filename']);
                    $vfs->writeData($fullpath, escapeshellcmd($att['part']->getName()), $data, true);
                }
            } catch (Horde_Vfs_Exception $e) {
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
            $link_part->setLanguage($GLOBALS['language']);
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
     * <ul>
     *  <li>html: (boolean) Return text/html part, if available.</li>
     *  <li>imp_msg: (integer) If non-empty, the message data was created by
     *               IMP. Either:
     *   <ul>
     *    <li>self::COMPOSE</li>
     *    <li>self::FORWARD</li>
     *    <li>self::REPLY</li>
     *   </ul>
     *  </li>
     *  <li>replylimit: (boolean) Enforce length limits?</li>
     *  <li>toflowed: (boolean) Do flowed conversion?</li>
     * </ul>
     *
     * @return mixed  Null if bodypart not found, or array with the following
     *                keys:
     *   - charset: (string) The guessed charset to use.
     *   - flowed: (Horde_Text_Flowed) A flowed object, if the text is flowed.
     *             Otherwise, null.
     *   - id: (string) The MIME ID of the bodypart.
     *   - mode: (string) Either 'text' or 'html'.
     *   - text: (string) The body text.
     */
    protected function _getMessageText($contents, array $options = array())
    {
        $body_id = null;
        $mode = 'text';
        $options = array_merge(array(
            'imp_msg' => self::COMPOSE
        ), $options);

        if (!empty($options['html']) &&
            $GLOBALS['session']->get('imp', 'rteavail') &&
            (($body_id = $contents->findBody('html')) !== null)) {
            if (($contents->getMIMEMessage()->getType() != 'multipart/mixed') &&
                in_array($options['imp_msg'], array(self::COMPOSE, self::REPLY))) {
                $check_id = '2';
            } else {
                $check_id = '1';
            }

            if ((strval($body_id) == $check_id) ||
                Horde_Mime::isChild($check_id, $body_id)) {
                $mode = 'html';
            } else {
                $body_id = null;
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

        $msg = Horde_String::convertCharset($part->getContents(), $part_charset, 'UTF-8');

        /* Enforce reply limits. */
        if (!empty($options['replylimit']) &&
            !empty($GLOBALS['conf']['compose']['reply_limit'])) {
            $limit = $GLOBALS['conf']['compose']['reply_limit'];
            if (Horde_String::length($msg) > $limit) {
                $msg = Horde_String::substr($msg, 0, $limit) . "\n" . _("[Truncated Text]");
            }
        }

        if ($mode == 'html') {
            $msg = $GLOBALS['injector']->getInstance('Horde_Core_Factory_TextFilter')->filter($msg, array('Cleanhtml', 'Xss'), array(array('body_only' => true), array('strip_styles' => true, 'strip_style_attributes' => false)));
        } elseif ($type == 'text/html') {
            $msg = $GLOBALS['injector']->getInstance('Horde_Core_Factory_TextFilter')->filter($msg, 'Html2text');
            $type = 'text/plain';
        }

        /* Always remove leading/trailing whitespace. The data in the
         * message body is not intended to be the exact representation of the
         * original message (use forward as message/rfc822 part for that). */
        $msg = trim($msg);

        if ($type == 'text/plain') {
            if ($part->getContentTypeParameter('format') == 'flowed') {
                $flowed = new Horde_Text_Flowed($msg, 'UTF-8');
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

            if (isset($options['toflowed'])) {
                $flowed = new Horde_Text_Flowed($msg, 'UTF-8');
                $msg = $options['toflowed']
                    ? $flowed->toFlowed(true)
                    : $flowed->toFlowed(false, array('nowrap' => true));
            }
        }

        if (strcasecmp($part->getCharset(), 'windows-1252') === 0) {
            $part_charset = 'ISO-8859-1';
        }

        return array(
            'charset' => $part_charset,
            'flowed' => isset($flowed) ? $flowed : null,
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
        $this->_pgpAttachPubkey = (bool)$attach;
    }

    /**
     * Attach the user's vCard to every message sent by buildAndSendMessage().
     *
     * @param mixed $name  The user's name. If false, will not attach
     *                     vCard to message.
     *
     * @throws IMP_Compose_Exception
     */
    public function attachVCard($name)
    {
        $this->_attachVCard = ($name === false)
            ? false
            : ((strlen($name) ? $name : 'vcard') . '.vcf');
    }

    /**
     * Has user specifically asked attachments to be linked in outgoing
     * messages?
     *
     * @param boolean $attach  True if attachments should be linked.
     */
    public function userLinkAttachments($attach)
    {
        $this->_linkAttach = (bool)$attach;
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
        return $GLOBALS['injector']->getInstance('Horde_Core_Factory_TextFilter')->filter($msg, 'Text2html', array(
            'always_mailto' => true,
            'flowed' => self::HTML_BLOCKQUOTE,
            'parselevel' => Horde_Text_Filter_Text2html::MICRO
        ));
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

        if ($vars->charset) {
            $this->charset = $vars->charset;
        }

        try {
            $body = $this->_saveDraftMsg($headers, $vars->message, array(
                'html' => $vars->rtemode,
                'priority' => $vars->priority,
                'readreceipt' => $vars->request_read_receipt
            ));
        } catch (IMP_Compose_Exception $e) {
            return;
        }

        try {
            $vfs = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Vfs')->create();
            $vfs->writeData(self::VFS_DRAFTS_PATH, hash('md5', $vars->user), $body, true);

            $GLOBALS['notification']->push(_("The message you were composing has been saved as a draft. The next time you login, you may resume composing your message."));
        } catch (Horde_Vfs_Exception $e) {}
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
            $vfs = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Vfs')->create();
        } catch (Horde_Vfs_Exception $e) {
            return;
        }

        if ($vfs->exists(self::VFS_DRAFTS_PATH, $filename)) {
            try {
                $data = $vfs->read(self::VFS_DRAFTS_PATH, $filename);
                $vfs->deleteFile(self::VFS_DRAFTS_PATH, $filename);
            } catch (Horde_Vfs_Exception $e) {
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
        return ($this->_replytype && $this->getMetadata('mailbox'))
            ? $GLOBALS['injector']->getInstance('IMP_Factory_Contents')->create(new IMP_Indices($this->getMetadata('mailbox'), $this->getMetadata('uid')))
            : null;
    }

    /**
     * Return the reply type.
     *
     * @param boolean $base  Return the base reply type?
     *
     * @return string  The reply type, or null if not a reply.
     */
    public function replyType($base = false)
    {
        switch ($this->_replytype) {
        case self::FORWARD:
        case self::FORWARD_ATTACH:
        case self::FORWARD_BODY:
        case self::FORWARD_BOTH:
            return $base
                ? self::FORWARD
                : $this->_replytype;

        case self::REPLY:
        case self::REPLY_ALL:
        case self::REPLY_LIST:
        case self::REPLY_SENDER:
            return $base
                ? self::REPLY
                : $this->_replytype;

        case self::REDIRECT:
            return $this->_replytype;

        default:
            return null;
        }
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
     *   - levenshtein: (boolean) If true, will sort the results using the
     *                  PHP levenshtein() scoring function.
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
     * Uses the Registry to obtain a list of e-mail addresses in the
     * addressbook.
     *
     * @param string $search  The term to search by.
     * @param boolean $email  Return the e-mail only? Otherwise, returns
     *                        the full address.
     *
     * @return array  All matching addresses.
     */
    static public function getAddressList($search = '', $email = false)
    {
        $sparams = IMP::getAddressbookSearchParams();
        try {
            $res = $GLOBALS['registry']->call(
                'contacts/search', array($search, $sparams['sources'], $sparams['fields'], false, false, array('name', 'email')));
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
                if (!$email && (strpos($val['email'], ',') !== false)) {
                    $search[] = Horde_Mime_Address::encode($val['name'], 'personal') . ': ' . $val['email'] . ';';
                } else {
                    $mbox_host = explode('@', $val['email']);
                    if (isset($mbox_host[1])) {
                        $search[] = Horde_Mime_Address::writeAddress($mbox_host[0], $mbox_host[1], $email ? '' : $val['name']);
                    }
                }
            }
        }

        return $search;
    }

    /* ArrayAccess methods. */

    public function offsetExists($offset)
    {
        return isset($this->_cache[$offset]);
    }

    public function offsetGet($offset)
    {
        return isset($this->_cache[$offset])
            ? $this->_cache[$offset]
            : null;
    }

    public function offsetSet($offset, $value)
    {
        $this->_cache[$offset] = $value;
        $this->changed = 'changed';
    }

    public function offsetUnset($offset)
    {
        if (!isset($this->_cache[$offset])) {
            return;
        }

        $atc = &$this->_cache[$offset];

        switch ($atc['filetype']) {
        case 'file':
            /* Delete from filesystem. */
            @unlink($atc['filename']);
            break;

        case 'vfs':
            /* Delete from VFS. */
            try {
                $vfs = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Vfs')->create();
                $vfs->deleteFile(self::VFS_ATTACH_PATH, $atc['filename']);
            } catch (Horde_Vfs_Exception $e) {}
            break;
        }

        /* Remove the size information from the counter. */
        $this->_size -= $atc['part']->getBytes();

        unset($this->_cache[$offset]);

        $this->changed = 'changed';
    }

    /* Magic methods. */

    /**
     * String representation: the cache ID.
     */
    public function __toString()
    {
        return $this->getCacheId();
    }

    /* Countable method. */

    /**
     * Returns the number of attachments currently in this message.
     *
     * @return integer  The number of attachments in this message.
     */
    public function count()
    {
        return count($this->_cache);
    }

    /* Iterator methods. */

    public function current()
    {
        return current($this->_cache);
    }

    public function key()
    {
        return key($this->_cache);
    }

    public function next()
    {
        next($this->_cache);
    }

    public function rewind()
    {
        reset($this->_cache);
    }

    public function valid()
    {
        return (key($this->_cache) !== null);
    }

    /* Serializable methods. */

    /**
     */
    public function serialize()
    {
        /* Make sure we don't have data in the Mime Part parts. */
        $atc = array();
        foreach ($this->_cache as $key => $val) {
            $val['part'] = clone($val['part']);
            $val['part']->clearContents();
            $atc[$key] = $val;
        }

        return serialize(array(
            $this->charset,
            $this->_attachVCard,
            $atc,
            $this->_cacheid,
            $this->_linkAttach,
            $this->_metadata,
            $this->_pgpAttachPubkey,
            $this->_replytype,
            $this->_size
        ));
    }

    /**
     */
    public function unserialize($data)
    {
        list(
            $this->charset,
            $this->_attachVCard,
            $this->_cache,
            $this->_cacheid,
            $this->_linkAttach,
            $this->_metadata,
            $this->_pgpAttachPubkey,
            $this->_replytype,
            $this->_size
        ) = unserialize($data);
    }

}
