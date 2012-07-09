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
    const RESUME = 12;
    const EDITASNEW = 13;
    const TEMPLATE = 14;

    /* The blockquote tag to use to indicate quoted text in HTML data. */
    const HTML_BLOCKQUOTE = '<blockquote type="cite" style="border-left:2px solid blue;margin-left:2px;padding-left:12px;">';

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
     * The cached attachment data.
     *
     * @var array
     */
    protected $_atc = array();

    /**
     * Whether the user's vCard should be attached to outgoing messages.
     *
     * @var string
     */
    protected $_attachVCard = false;

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
     * Saves a draft message.
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
        $base = $this->_createMimeMessage(new Horde_Mail_Rfc822_List(), $message, array(
            'html' => !empty($opts['html']),
            'noattach' => !$has_session,
            'nofinal' => true
        ));
        $base->isBasePart(true);

        $recip_list = $this->recipientList($headers);
        foreach ($recip_list['list'] as $val) {
            try {
                IMP::parseAddressList($val->writeAddress(true), array(
                    'validate' => true
                ));
            } catch (Horde_Mail_Exception $e) {
                throw new IMP_Compose_Exception(sprintf(_("Saving the draft failed because it contains an invalid e-mail address: %s."), strval($val), $e->getMessage()), $e->getCode());
            }
        }

        /* Initalize a header object for the draft. */
        $draft_headers = $this->_prepareHeaders($headers, array_merge($opts, array('bcc' => true)));

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
            throw new IMP_Compose_Exception(_("Saving the draft failed. No drafts mailbox specified."));
        }

        /* Check for access to drafts mailbox. */
        if (!$drafts_mbox->create()) {
            throw new IMP_Compose_Exception(_("Saving the draft failed. Could not create a drafts mailbox."));
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
            return sprintf(_("The draft has been saved to the \"%s\" mailbox."), $drafts_mbox->display);
        } catch (IMP_Imap_Exception $e) {
            return _("The draft was not successfully saved.");
        }
    }

    /**
     * Edits a message as new.
     *
     * @see resumeDraft().
     *
     * @param IMP_Indices $indices  An indices object.
     * @param array $opts           Additional options:
     *   - format: (string) Force to this format.
     *             DEFAULT: Auto-determine.
     *
     * @return mixed  See resumeDraft().
     *
     * @throws IMP_Compose_Exception
     */
    public function editAsNew($indices, array $opts = array())
    {
        $ret = $this->_resumeDraft($indices, self::EDITASNEW, $opts);
        $ret['type'] = self::EDITASNEW;
        return $ret;
    }

    /**
     * Edit an existing template message. Saving this template later
     * (using saveTemplate()) will cause the original message to be deleted.
     *
     * @param IMP_Indices $indices  An indices object.
     *
     * @return mixed  See resumeDraft().
     *
     * @throws IMP_Compose_Exception
     */
    public function editTemplate($indices)
    {
        $res = $this->useTemplate($indices);
        $this->_metadata['template_uid_edit'] = $indices;
        return $res;
    }

    /**
     * Resumes a previously saved draft message.
     *
     * @param IMP_Indices $indices  An indices object.
     * @param array $opts           Additional options:
     *   - format: (string) Force to this format.
     *             DEFAULT: Auto-determine.
     *
     * @return mixed  An array with the following keys:
     *   - body: (string) The text of the body part.
     *   - format: (string) The format of the body message ('html', 'text').
     *   - headers: (array) The list of headers to add to the outgoing message.
     *   - identity: (mixed) See Imp_Prefs_Identity#getMatchingIdentity().
     *   - priority: (string) The message priority.
     *   - readreceipt: (boolean) Add return receipt headers?
     *   - type: (integer) - The compose type.
     *
     * @throws IMP_Compose_Exception
     */
    public function resumeDraft($indices, array $opts = array())
    {
        $res = $this->_resumeDraft($indices, self::RESUME, $opts);
        $this->_metadata['draft_uid_resume'] = $indices;
        return $res;
    }

    /**
     * Uses a template to create a message.
     *
     * @see resumeDraft().
     *
     * @param IMP_Indices $indices  An indices object.
     * @param array $opts           Additional options:
     *   - format: (string) Force to this format.
     *             DEFAULT: Auto-determine.
     *
     * @return mixed  See resumeDraft().
     *
     * @throws IMP_Compose_Exception
     */
    public function useTemplate($indices, array $opts = array())
    {
        $ret = $this->_resumeDraft($indices, self::TEMPLATE, $opts);
        $ret['type'] = self::TEMPLATE;
        return $ret;
    }

    /**
     * Resumes a previously saved draft message.
     *
     * @param IMP_Indices $indices  See resumeDraft().
     * @param integer $type         Compose type.
     * @param array $opts           Additional options:
     *   - format: (string) Force to this format.
     *             DEFAULT: Auto-determine.
     *
     * @return mixed  See resumeDraft().
     *
     * @throws IMP_Compose_Exception
     */
    protected function _resumeDraft($indices, $type, $opts)
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

        if ($draft_url = $headers->getValue('x-imp-draft-reply')) {
            if (is_null($type) &&
                !($type = $headers->getValue('x-imp-draft-reply-type'))) {
                $type = self::REPLY;
            }
            $imp_draft = self::REPLY;
        } elseif ($draft_url = $headers->getValue('x-imp-draft-forward')) {
            $imp_draft = self::FORWARD;
            if (is_null($type)) {
                $type = self::FORWARD;
            }
        } elseif ($headers->getValue('x-imp-draft')) {
            $imp_draft = self::COMPOSE;
        }

        if (!empty($opts['format'])) {
            $compose_html = ($opts['format'] == 'html');
        } elseif ($prefs->getValue('compose_html')) {
            $compose_html = true;
        } else {
            switch ($type) {
            case self::EDITASNEW:
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

            case self::TEMPLATE:
                $compose_html = true;
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
            $body = '';
            $format = 'text';
            $text_id = 0;
        } else {
            /* Use charset at time of initial composition if this is an IMP
             * draft. */
            if ($imp_draft !== false) {
                $this->charset = $msg_text['charset'];
            }
            $body = $msg_text['text'];
            $format = $msg_text['mode'];
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
        if (($fromaddr = IMP::bareAddress($headers->getValue('from')))) {
            $identity = $injector->getInstance('IMP_Identity');
            $identity_id = $identity->getMatchingIdentity($fromaddr);
        }

        if ($type != self::EDITASNEW) {
            $header = array(
                'to' => strval($headers->getOb('to')),
                'cc' => strval($headers->getOb('cc')),
                'bcc' => strval($headers->getOb('bcc'))
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
        }

        $header['subject'] = $headers->getValue('subject');

        $imp_ui_hdrs = new IMP_Ui_Headers();
        $priority = $imp_ui_hdrs->getPriority($headers);

        $mdn = new Horde_Mime_Mdn($headers);
        $readreceipt = (bool)$mdn->getMdnReturnAddr();

        $this->changed = 'changed';

        return array(
            'body' => $body,
            'format' => $format,
            'headers' => $header,
            'identity' => $identity_id,
            'priority' => $priority,
            'readreceipt' => $readreceipt
        );
    }

    /**
     * Save a template message on the IMAP server.

     * @param array $header   List of message headers (UTF-8).
     * @param mixed $message  Either the message text (string) or a
     *                        Horde_Mime_Part object that contains the text
     *                        to save.
     * @param array $opts     An array of options w/the following keys:
     *   - html: (boolean) Is this an HTML message?
     *   - priority: (string) The message priority ('high', 'normal', 'low').
     *   - readreceipt: (boolean) Add return receipt headers?
     *
     * @return string  Notification text on success.
     *
     * @throws IMP_Compose_Exception
     */
    public function saveTemplate($headers, $message, array $opts = array())
    {
        if (!$mbox = IMP_Mailbox::getPref('composetemplates_mbox')) {
            throw new IMP_Compose_Exception(_("Saving the template failed: no template mailbox exists."));
        }

        /* Check for access to mailbox. */
        if (!$mbox->create()) {
            throw new IMP_Compose_Exception(_("Saving the template failed: could not create the templates mailbox."));
        }

        $append_flags = array(
            // Don't mark as draft, since other MUAs could potentially
            // delete it.
            Horde_Imap_Client::FLAG_SEEN
        );

        $old_uid = $this->getMetadata('template_uid_edit');

        /* Add the message to the mailbox. */
        try {
            $ids = $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->append($mbox, array(array(
                'data' => $this->_saveDraftMsg($headers, $message, $opts),
                'flags' => $append_flags
            )));

            if ($old_uid) {
                $GLOBALS['injector']->getInstance('IMP_Message')->delete($old_uid, array('nuke' => true));
            }
        } catch (IMP_Imap_Exception $e) {
            return _("The template was not successfully saved.");
        }

        return _("The template has been saved.");
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
     *   add_signature: (integer) Add the signature to the outgoing message?
     *                  This is the ID of the identity to use.
     *                  DEFAULT: No signature added
     *  </li>
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
     *   sent_mail: (IMP_Mailbox) The sent-mail mailbox (UTF-8).
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
        if (!count($recip['list'])) {
            if ($recip['has_input']) {
                throw new IMP_Compose_Exception(_("Invalid e-mail address."));
            }
            throw new IMP_Compose_Exception(_("Need at least one message recipient."));
        }
        $header = array_merge($header, $recip['header']);

        /* Check for correct identity usage. */
        if (!$this->getMetadata('identity_check') &&
            (count($recip['list']) === 1) &&
            isset($opts['identity'])) {
            $identity_search = $opts['identity']->getMatchingIdentity($recip['list'], false);
            if (!is_null($identity_search) &&
                ($opts['identity']->getDefault() != $identity_search)) {
                $this->_metadata['identity_check'] = true;
                $this->changed = 'changed';

                $e = new IMP_Compose_Exception(_("Recipient address does not match the currently selected identity."));
                $e->tied_identity = $identity_search;
                throw $e;
            }
        }

        $barefrom = IMP::bareAddress($header['from'], true);
        $encrypt = empty($opts['encrypt']) ? 0 : $opts['encrypt'];

        /* Prepare the array of messages to send out.  May be more
         * than one if we are encrypting for multiple recipients or
         * are storing an encrypted message locally. */
        $send_msgs = array();
        $msg_options = array(
            'encrypt' => $encrypt,
            'html' => !empty($opts['html']),
            'signature' => isset($opts['add_signature']) ? $opts['add_signature'] : null
        );

        /* Must encrypt & send the message one recipient at a time. */
        if ($prefs->getValue('use_smime') &&
            in_array($encrypt, array(IMP_Crypt_Smime::ENCRYPT, IMP_Crypt_Smime::SIGNENC))) {
            foreach ($recip['list'] as $val) {
                $list_ob = new Horde_Mail_Rfc822_List($val);
                $send_msgs[] = array(
                    'base' => $this->_createMimeMessage($list_ob, $body, $msg_options),
                    'recipients' => $list_ob
                );
            }

            /* Must target the encryption for the sender before saving message
             * in sent-mail. */
            $save_msg = $this->_createMimeMessage(IMP::parseAddressList($header['from']), $body, $msg_options);
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

        $recipients = strval($recip['list']);

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

        /* Should we save this message in the sent mail mailbox? */
        if (!empty($opts['sent_mail']) &&
            ((!$prefs->isLocked('save_sent_mail') &&
              !empty($opts['save_sent'])) ||
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
            $fcc = $save_msg->toString(array(
                'defserver' => $session->get('imp', 'maildomain'),
                'headers' => $headers,
                'stream' => true
            ));

            /* Make sure sent mailbox is created. */
            $sent_mail = IMP_Mailbox::get($opts['sent_mail']);
            $sent_mail->create();

            $flags = array(Horde_Imap_Client::FLAG_SEEN);

            /* RFC 3503 [3.3] - set MDNSent flag on sent message. */
            if ($prefs->getValue('request_mdn') != 'never') {
                $mdn = new Horde_Mime_Mdn($headers);
                if ($mdn->getMdnReturnAddr()) {
                    $flags[] = Horde_Imap_Client::FLAG_MDNSENT;
                }
            }

            try {
                $injector->getInstance('IMP_Factory_Imap')->create()->append($sent_mail, array(array('data' => $fcc, 'flags' => $flags)));
            } catch (IMP_Imap_Exception $e) {
                $notification->push(sprintf(_("Message sent successfully, but not saved to %s."), $sent_mail->display));
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
    }

    /**
     * Prepare header object with basic header fields and converts headers
     * to the current compose charset.
     *
     * @param array $headers  Array with 'from', 'to', 'cc', 'bcc', and
     *                        'subject' values.
     * @param array $opts     An array of options w/the following keys:
     *   - bcc: (boolean) Add BCC header to output.
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

        if (!empty($opts['bcc']) &&
            isset($headers['bcc']) &&
            strlen($headers['bcc'])) {
            $ob->addHeader('Bcc', $headers['bcc']);
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
            $mdn->addMdnRequestHeaders(IMP::bareAddress($ob->getValue('from'), true));
        }

        return $ob;
    }

    /**
     * Sends a message.
     *
     * @param Horde_Mail_Rfc822_List $email  The e-mail list to send to.
     * @param Horde_Mime_Headers $headers    The object holding this message's
     *                                       headers.
     * @param Horde_Mime_Part $message       The object that contains the text
     *                                       to send.
     *
     * @throws IMP_Compose_Exception
     */
    public function sendMessage(Horde_Mail_Rfc822_List $email,
                                Horde_Mime_Headers $headers,
                                Horde_Mime_Part $message)
    {
        $email = $this->_prepSendMessage($email, $message);

        $opts = array();
        if ($this->getMetadata('encrypt_sign')) {
            /* Signing requires that the body not be altered in transport. */
            $opts['encode'] = Horde_Mime_Part::ENCODE_7BIT;
        }

        try {
            $message->send($email, $headers, $GLOBALS['injector']->getInstance('IMP_Mail'), $opts);
        } catch (Horde_Mime_Exception $e) {
            throw new IMP_Compose_Exception($e);
        }
    }

    /**
     * Sanity checking/MIME formatting before sending a message.
     *
     * @param Horde_Mail_Rfc822_List $email  The e-mail list to send to.
     * @param Horde_Mime_Part $message       The object that contains the text
     *                                       to send.
     *
     * @return string  The encoded $email list.
     *
     * @throws IMP_Compose_Exception
     */
    protected function _prepSendMessage(Horde_Mail_Rfc822_List $email,
                                        $message = null)
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
     * @param Horde_Mail_Rfc822_List $email  The e-mail list to send to.
     * @param Horde_Mime_Headers $headers    The object holding this message's
     *                                       headers.
     * @param Horde_Mime_Part $message       The object that contains the text
     *                                       to send.
     *
     * @throws IMP_Compose_Exception
     */
    protected function _prepSendMessageAssert(Horde_Mail_Rfc822_List $email,
                                              Horde_Mime_Headers $headers = null,
                                              Horde_Mime_Part $message = null)
    {
        global $conf, $injector, $registry;

        $perms = $injector->getInstance('Horde_Core_Perms');
        $email_count = count($email);

        if (!IMP::hasPermission('max_timelimit', array('value' => $email_count))) {
            Horde::permissionDeniedError('imp', 'max_timelimit');
            throw new IMP_Compose_Exception(sprintf(_("You are not allowed to send messages to more than %d recipients within %d hours."), $perms->hasAppPermission('max_timelimit'), $conf['sentmail']['params']['limit_period']));
        }

        /* Count recipients if necessary. We need to split email groups
         * because the group members count as separate recipients. */
        if (!IMP::hasPermission('max_recipients', array('value' => $email_count))) {
            Horde::permissionDeniedError('imp', 'max_recipients');
            throw new IMP_Compose_Exception(sprintf(_("You are not allowed to send messages to more than %d recipients."), $perms->hasAppPermission('max_recipients')));
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
     * @param Horde_Mail_Rfc822_List $email  The e-mail list to send to.
     * @param string $charset                The charset to encode to.
     *
     * @return string  The encoded $email list.
     *
     * @throws IMP_Compose_Exception
     */
    protected function _prepSendMessageEncode(Horde_Mail_Rfc822_List $email,
                                              $charset)
    {
        $out = array();

        foreach ($email as $val) {
            /* $email contains address objects that already have the default
             * maildomain appended. Need to encode personal part and encode
             * IDN domain names. */
            $tmp = $val->writeAddress(array(
                'encode' => $charset,
                'idn' => true
            ));

            try {
                /* We have written address, but it still may not be valid.
                 * So double-check. */
                IMP::parseAddressList($tmp, array(
                    'validate' => true
                ));
            } catch (Horde_Mail_Exception $e) {
                throw new IMP_Compose_Exception(sprintf(_("Invalid e-mail address (%s)."), $val->writeAddress()));
            }

            $out[] = $tmp;
        }

        return implode(', ', $out);
    }

    /**
     * Save the recipients done in a sendMessage().
     *
     * @param Horde_Mail_Rfc822_List $recipients  The list of recipients.
     */
    public function _saveRecipients(Horde_Mail_Rfc822_List $recipients)
    {
        global $notification, $prefs, $registry, $session;

        if (!$prefs->getValue('save_recipients') ||
            !$registry->hasMethod('contacts/import') ||
            !($abook = $prefs->getValue('add_source'))) {
            return;
        }

        foreach ($recipients as $recipient) {
            $name = is_null($recipient->personal)
                ? $recipient->mailbox
                : $recipient->personal;

            try {
                $registry->call('contacts/import', array(array('name' => $name, 'email' => $recipient->bare_address), 'array', $abook));
                $notification->push(sprintf(_("Entry \"%s\" was successfully added to the address book"), $name), 'horde.success');
            } catch (Turba_Exception_ObjectExists $e) {
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
     * @param array $hdr  An array of MIME headers. Recipients will be
     *                    extracted from the 'to', 'cc', and 'bcc' entries.
     *
     * @return array  An array with the following entries:
     *   - has_input: (boolean) True if at least one of the headers contains
     *                user input.
     *   - header: (array) Contains the cleaned up 'to', 'cc', and 'bcc'
     *             header strings.
     *   - list: (Horde_Mail_Rfc822_List) Recipient addresses.
     */
    public function recipientList($hdr)
    {
        $addrlist = new Horde_Mail_Rfc822_List();
        $header = array();

        foreach (array('to', 'cc', 'bcc') as $key) {
            if (isset($hdr[$key])) {
                $obs = IMP::parseAddressList($hdr[$key]);
                $header[$key] = strval($obs);
                $addrlist->add($obs);
            }
        }

        return array(
            'has_input' => isset($obs),
            'header' => $header,
            'list' => $addrlist
        );
    }

    /**
     * Create the base Horde_Mime_Part for sending.
     *
     * @param Horde_Mail_Rfc822_List $to  The recipient list.
     * @param string $body                Message body.
     * @param array $options              Additional options:
     *   - encrypt: (integer) The encryption flag.
     *   - from: (string) The outgoing from address - only needed for multiple
     *           PGP encryption.
     *   - html: (boolean) Is this a HTML message?
     *   - nofinal: (boolean) This is not a message which will be sent out.
     *   - noattach: (boolean) Don't add attachment information.
     *   - signature: (integer) If set, will add the users' signature to the
     *                message.
     *
     * @return Horde_Mime_Part  The MIME message to send.
     *
     * @throws Horde_Exception
     * @throws IMP_Compose_Exception
     */
    protected function _createMimeMessage(Horde_Mail_Rfc822_List $to, $body,
                                          array $options = array())
    {
        $body = Horde_String::convertCharset($body, 'UTF-8', $this->charset);

        /* Get body text. */
        if (!empty($options['html'])) {
            $body_html = $body;
            $body = $GLOBALS['injector']->getInstance('Horde_Core_Factory_TextFilter')->filter($body, 'Html2text', array('wrap' => false, 'charset' => $this->charset));
        }

        /* Add signature data. */
        if (isset($options['signature'])) {
            $identity = $GLOBALS['injector']->getInstance('IMP_Identity');
            $sig = $identity->getSignature('text', $options['signature']);
            $body .= $sig;

            if (!empty($options['html'])) {
                $html_sig = $identity->getSignature('html', $options['signature']);
                if (!strlen($html_sig) && strlen($sig)) {
                    $html_sig = $this->text2html($sig);
                }
                $body_html .= $html_sig;
            }
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

            $textBody->setDescription(Horde_String::convertCharset(_("Plaintext Message"), 'UTF-8', $this->charset));

            $textpart = new Horde_Mime_Part();
            $textpart->setType('multipart/alternative');
            $textpart->addPart($textBody);
            $textpart->setHeaderCharset($this->charset);

            if (empty($options['nofinal'])) {
                $body_html = $this->_addExternalData($body_html);
            }

            $htmlBody->setContents($body_html);

            /* Now, all parts referred to in the HTML data have been added
             * to the attachment list. Convert to multipart/related if
             * this is the case. */
            $textpart->addPart((empty($options['nofinal']) && $this->getMetadata('compose_related'))
                ? $this->_convertToRelated($htmlBody)
                : $htmlBody
            );

            $htmlBody->setContents($GLOBALS['injector']->getInstance('Horde_Core_Factory_TextFilter')->filter($htmlBody->getContents(), 'cleanhtml', array(
                'charset' => $this->charset
            )));
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
                    $base = $imp_pgp->impSignMimePart($base);
                    $this->_metadata['encrypt_sign'] = true;
                    break;

                case IMP_Crypt_Pgp::ENCRYPT:
                case IMP_Crypt_Pgp::SYM_ENCRYPT:
                    $to_list = clone $to;
                    if (!empty($options['from'])) {
                        $to_list->add($options['from']);
                    }
                    $base = $imp_pgp->IMPencryptMIMEPart($base, $to_list, ($encrypt == IMP_Crypt_Pgp::SYM_ENCRYPT) ? $symmetric_passphrase : null);
                    break;

                case IMP_Crypt_Pgp::SIGNENC:
                case IMP_Crypt_Pgp::SYM_SIGNENC:
                    $to_list = clone $to;
                    if (!empty($options['from'])) {
                        $to_list->add($options['from']);
                    }
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
                    $this->_metadata['encrypt_sign'] = true;
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

        /* Flag this as the base part and rebuild MIME IDs. */
        $base->isBasePart(true);
        $base->buildMimeIds();

        return $base;
    }

    /**
     * Determines the reply text and headers for a message.
     *
     * @param integer $type           The reply type (self::REPLY* constant).
     * @param IMP_Contents $contents  An IMP_Contents object.
     * @param array $opts             Additional options:
     *   - format: (string) Force to this format.
     *             DEFAULT: Auto-determine.
     *   - to: (string) The recipient of the reply. Overrides the
     *         automatically determined value.
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
    public function replyMessage($type, $contents, array $opts = array())
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
            if ((isset($opts['to']) && ($header['to'] = $opts['to'])) ||
                ($header['to'] = strval($h->getOb('reply-to')))) {
                $force = true;
            } else {
                $header['to'] = strval($h->getOb('from'));
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
            if (IMP::bareAddress($list_info['reply_list']) != IMP::bareAddress($header['to'])) {
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
            $all_addrs = $identity->getAllFromAddresses();

            /* Build the To: header. It is either:
             * 1) the Reply-To address (if not a personal address)
             * 2) the From address(es) (if it doesn't contain a personal
             * address)
             * 3) all remaining Cc addresses. */
            $cc_addrs = new Horde_Mail_Rfc822_List();
            $to_addrs = new Horde_Mail_Rfc822_List();
            $to_fields = array('from', 'reply-to');

            foreach (array('reply-to', 'from', 'to', 'cc') as $val) {
                /* If either a reply-to or $to is present, we use this address
                 * INSTEAD of the from address. */
                if (($force && ($val == 'from')) ||
                    !($ob = $h->getOb($val))) {
                    continue;
                }

                /* For From: need to check if at least one of the addresses is
                 * personal. */
                if ($val == 'from') {
                    foreach ($ob->raw_addresses as $addr_ob) {
                        if ($all_addrs->contains($addr_ob)) {
                            /* The from field contained a personal address.
                             * Use the 'To' header as the primary reply-to
                             * address instead. */
                            $to_fields[] = 'to';

                            /* Add other non-personal from addresses to the
                             * list of CC addresses. */
                            $ob->setIteratorFilter($ob::BASE_ELEMENTS, $all_addrs);
                            $cc_addrs->add($ob);
                            $all_addrs->add($ob);
                            continue 2;
                        }
                    }
                }

                $ob->setIteratorFilter($ob::BASE_ELEMENTS, $all_addrs);

                foreach ($ob as $hdr_ob) {
                    if ($hdr_ob instanceof Horde_Mail_Rfc822_Group) {
                        $cc_addrs->add($hdr_ob);
                        $all_addrs->add($hdr_ob->addresses);
                    } elseif (($val != 'to') ||
                              is_null($list_info) ||
                              !$force ||
                              empty($list_info['exists'])) {
                        /* Don't add as To address if this is a list that
                         * doesn't have a post address but does have a
                         * reply-to address. */
                        if (in_array($val, $to_fields)) {
                            /* If from/reply-to doesn't have personal
                             * information, check from address. */
                            if (is_null($hdr_ob->personal) &&
                                ($to_ob = $h->getOb('from')) &&
                                !is_null($to_ob[0]->personal) &&
                                ($hdr_ob->match($to_ob[0]))) {
                                $to_addrs->add($to_ob);
                            } else {
                                $to_addrs->add($hdr_ob);
                            }
                        } else {
                            $cc_addrs->add($hdr_ob);
                        }

                        $all_addrs->add($hdr_ob);
                    }
                }
            }

            /* Build the Cc: (or possibly the To:) header. If this is a
             * reply to a message that was already replied to by the user,
             * this reply will go to the original recipients (Request
             * #8485).  */
            if (count($to_addrs)) {
                $header['to'] = strval($to_addrs);
            }
            if (count($cc_addrs)) {
                $reply_type = self::REPLY_ALL;
            }
            $header[empty($header['to']) ? 'to' : 'cc'] = strval($cc_addrs);

            /* Build the Bcc: header. */
            if ($bcc = $h->getOb('bcc')) {
                $bcc->add($identity->getBccAddresses());
                $bcc->setIteratorFilter(0, $all_addrs);
                $header['bcc'] = strval($bcc);
            }
        }

        if (!$this->_replytype || ($reply_type != $this->_replytype)) {
            $this->_replytype = $reply_type;
            $this->changed = 'changed';
        }

        $ret = $this->replyMessageText($contents, array(
            'format' => isset($opts['format']) ? $opts['format'] : null
        ));
        if ($prefs->getValue('reply_charset') &&
            ($ret['charset'] != $this->charset)) {
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
                if (($list_parse = $GLOBALS['injector']->getInstance('Horde_ListHeaders')->parse('list-id', $h->getValue('list-id'))) &&
                    !is_null($list_parse->label)) {
                    $ret['reply_list_id'] = $list_parse->label;
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

        $from = strval($h->getOb('from'));

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
        if (!empty($opts['format'])) {
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
     *   - body: (string) The text of the body part.
     *   - format: (string) The format of the body message ('html', 'text').
     *   - headers: (array) The list of headers to add to the outgoing message.
     *   - identity: (mixed) See Imp_Prefs_Identity#getMatchingIdentity().
     *   - type: (integer) - The compose type.
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

            case 'editasnew':
                $ret = $this->editAsNew(new IMP_Indices($contents));
                $ret['headers']['title'] = _("New Message");
                return $ret;

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

        $from = strval($h->getOb('from'));

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
     * @param mixed $to  The addresses to redirect to.
     * @param boolean $log  Whether to log the resending in the history and
     *                      sentmail log.
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
    public function sendRedirectMessage($to, $log = true)
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

                $recipients = strval($recip['list']);

                Horde::logMessage(sprintf("%s Redirected message sent to %s from %s", $_SERVER['REMOTE_ADDR'], $recipients, $GLOBALS['registry']->getAuth()), 'INFO');

                if ($log) {
                    /* Store history information. */
                    if (!empty($GLOBALS['conf']['maillog']['use_maillog'])) {
                        IMP_Maillog::log(self::REDIRECT, $headers->getValue('message-id'), $recipients);
                    }

                    $GLOBALS['injector']->getInstance('IMP_Sentmail')->log(IMP_Sentmail::REDIRECT, $headers->getValue('message-id'), $recipients);
                }

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

        if (($ob = strval($h->getOb('from')))) {
            $tmp[_("From")] = $ob;
        }

        if (($ob = strval($h->getOb('reply-to')))) {
            $tmp[_("Reply-To")] = $ob;
        }

        if (($ob = $h->getValue('subject'))) {
            $tmp[_("Subject")] = $ob;
        }

        if (($ob = strval($h->getOb('to')))) {
            $tmp[_("To")] = $ob;
        }

        if (($ob = strval($h->getOb('cc')))) {
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
                $analyzetype = Horde_Mime::decodeParam('Content-Type', $analyzetype);
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
     * Add an attachment referred to in a related part.
     *
     * @param Horde_Mime_Part $part  Attachment data.
     * @param DOMElement $node       Node element containg the reference.
     * @param string $attribute      Element attribute containing the
     *                               reference.
     *
     * @return integer  The attachment ID.
     */
    protected function _addRelatedAttachment(Horde_Mime_Part $part, $node,
                                             $attribute)
    {
        $this->addMimePartAttachment($part);

        end($this->_atc);
        $key = key($this->_atc);
        $this->_atc[$key]['related'] = true;

        $this->_metadata['compose_related'] = true;

        $node->setAttribute('imp_related_attr', $attribute);
        $node->setAttribute('imp_related_attr_id', $key);

        return $key;
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

            $this->_atc[] = array(
                'filename' => $cacheID,
                'filetype' => 'vfs',
                'part' => $part
            );
        } else {
            chmod($data, 0600);
            $this->_atc[] = array(
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
        foreach ($this->_atc as $key => $val) {
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
        $addressList = $nameList = array();

        /* First we'll get a comma seperated list of email addresses
         * and a comma seperated list of personal names out of $from
         * (there just might be more than one of each). */
        $addr_list = IMP::parseAddressList($from);

        foreach ($addr_list as $addr) {
            if (!is_null($addr->mailbox)) {
                $addressList[] = $addr->bare_address;
            }

            if (!is_null($addr->personal)) {
                $nameList[] = $addr->personal;
            } elseif (!is_null($addr->mailbox)) {
                $nameList[] = $addr->mailbox;
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
            '/%a/' => implode(', ', $addressList),

            /* Senders name(s). */
            '/%p/' => implode(', ', $nameList),

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
     * Convert external image links into internal links to attachment data.
     *
     * @param string $html  The HTML data.
     *
     * @return string  HTML with external data moved to attachments.
     */
    protected function _addExternalData($html)
    {
        /* Return immediately if related conversion is turned off via
         * configuration. */
        if (empty($GLOBALS['conf']['compose']['convert_to_related'])) {
            return;
        }

        $client = $GLOBALS['injector']
          ->getInstance('Horde_Core_Factory_HttpClient')
          ->create();
        $dom = new Horde_Domhtml($html, $this->charset);
        $downloaded = false;

        /* Find external images, download the image, and add as an
         * attachment. */
        foreach ($dom->dom->getElementsByTagName('img') as $node) {
            if ($node->hasAttribute('src') &&
                !$node->hasAttribute('imp_related_attr')) {
                /* Attempt to download the image data. */
                $response = $client->get($node->getAttribute('src'));
                if ($response->code == 200) {
                    /* We need to determine the image type. Try getting that
                     * info from the returned HTTP content-type header.
                     * TODO: Use Horde_Mime_Magic if this fails (?) */
                    $part = new Horde_Mime_Part();
                    $part->setType($response->getHeader('content-type'));
                    $part->setContents($response->getBody());
                    $part->setDisposition('attachment');

                    $this->_addRelatedAttachment($part, $node, 'src');
                    $downloaded = true;
                }
            }
        }

        return $downloaded
            ? $dom->returnHtml()
            : $html;
    }

    /**
     * Converts an HTML part to a multipart/related part.
     *
     * @param Horde_Mime_Part $part  The HTML part.
     *
     * @return Horde_Mime_Part  The multipart/related part.
     */
    protected function _convertToRelated(Horde_Mime_Part $part)
    {
        /* Create new multipart/related part. */
        $related = new Horde_Mime_Part();
        $related->setType('multipart/related');

        /* Get the CID for the 'root' part. Although by default the first part
         * is the root part (RFC 2387 [3.2]), we may as well be explicit and
         * put the CID in the 'start' parameter. */
        $related->setContentTypeParameter('start', $part->setContentId());

        /* Go through the HTML part and generate internal Content-ID links. */
        $dom = new Horde_Domhtml($part->getContents(), $part->getCharset());
        $ids = array();

        foreach ($dom as $node) {
            if (($node instanceof DOMElement) &&
                $node->hasAttribute('imp_related_attr')) {
                $id = $ids[] = $node->getAttribute('imp_related_attr_id');
                $node->setAttribute($node->getAttribute('imp_related_attr'), 'cid:' . $this[$id]['part']->setContentId());

                $node->removeAttribute('imp_related_attr');
                $node->removeAttribute('imp_related_attr_id');
            }
        }

        $part->setContents($dom->returnHtml());

        /* Add the root part and the various data to the multipart object. */
        $related->addPart($part);
        foreach ($ids as $id) {
            $related->addPart($this->buildAttachment($id));
        }

        return $related;
    }

    /**
     * Remove all attachments from an email message and replace with
     * urls to downloadable links. Should properly save all attachments to a
     * new mailbox and remove the Horde_Mime_Parts for the attachments.
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

        /* Verify that message is below the linked attachment size limit. */
        if (!empty($conf['compose']['link_attach_size_limit'])) {
            $size_check = $conf['compose']['link_attach_size_limit'] - $this->sizeOfAttachments();
            if ($size_check < 0) {
                throw new IMP_Compose_Exception(sprintf(_("Attached file(s) exceeds the linked attachment size limit (%d KB too large). Delete one of the attachments to continue."), IMP::numberFormat(abs($size) / 1024, 0)));
            }
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
        global $conf, $injector, $prefs, $session;

        $body_id = null;
        $mode = 'text';
        $options = array_merge(array(
            'imp_msg' => self::COMPOSE
        ), $options);

        if (!empty($options['html']) &&
            $session->get('imp', 'rteavail') &&
            (($body_id = $contents->findBody('html')) !== null)) {
            $mime_message = $contents->getMIMEMessage();

            switch ($mime_message->getPrimaryType()) {
            case 'multipart':
                if (($mime_message->getSubType() == 'mixed') &&
                    !Horde_Mime::isChild('1', $body_id)) {
                    $body_id = null;
                } else {
                    $mode = 'html';
                }
                break;

            default:
                if (strval($body_id) != '1') {
                    $body_id = null;
                } else {
                    $mode = 'html';
                }
                break;
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
            !empty($conf['compose']['reply_limit'])) {
            $limit = $conf['compose']['reply_limit'];
            if (Horde_String::length($msg) > $limit) {
                $msg = Horde_String::substr($msg, 0, $limit) . "\n" . _("[Truncated Text]");
            }
        }

        if ($mode == 'html') {
            $msg = $injector->getInstance('Horde_Core_Factory_TextFilter')->filter($msg, array('Cleanhtml', 'Xss'), array(array('body_only' => true), array('strip_style_attributes' => false)));

            /* If we are replying to a related part, and this part refers
             * to local message parts, we need to move those parts into this
             * message (since the original message may disappear during the
             * compose process). */
            if ($related_part = $contents->findMimeType($body_id, 'multipart/related')) {
                $this->_metadata['related_contents'] = $contents;
                $related_ob = new Horde_Mime_Related($related_part);
                $msg = $related_ob->cidReplace($msg, array($this, '_getMessageTextCallback'), $part_charset)->returnBody();
                unset($this->_metadata['related_contents']);
            }
        } elseif ($type == 'text/html') {
            $msg = $injector->getInstance('Horde_Core_Factory_TextFilter')->filter($msg, 'Html2text');
            $type = 'text/plain';
        }

        /* Always remove leading/trailing whitespace. The data in the
         * message body is not intended to be the exact representation of the
         * original message (use forward as message/rfc822 part for that). */
        $msg = trim($msg);

        if ($type == 'text/plain') {
            if ($prefs->getValue('reply_strip_sig') &&
                (($pos = strrpos($msg, "\n-- ")) !== false)) {
                $msg = rtrim(substr($msg, 0, $pos));
            }

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
     * Callback used in _getMessageText().
     */
    public function _getMessageTextCallback($id, $attribute, $node)
    {
        $key = $this->_addRelatedAttachment($this->getMetadata('related_contents')->getMIMEPart($id), $node, $attribute);

        return $this->getMetadata('related_contents')->urlView(null, 'compose_attach_preview', array(
            'params' => array(
                'composeCache' => strval($this),
                'id' => $key
            )
        ));
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
            $headers[$val] = $vars->$val;
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
                $GLOBALS['notification']->push(_("A message you were composing when your session expired has been recovered. You may resume composing your message by going to your Drafts mailbox."));
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

    /* ArrayAccess methods. */

    public function offsetExists($offset)
    {
        return isset($this->_atc[$offset]);
    }

    public function offsetGet($offset)
    {
        return isset($this->_atc[$offset])
            ? $this->_atc[$offset]
            : null;
    }

    public function offsetSet($offset, $value)
    {
        $this->_atc[$offset] = $value;
        $this->changed = 'changed';
    }

    public function offsetUnset($offset)
    {
        if (!isset($this->_atc[$offset])) {
            return;
        }

        $atc = &$this->_atc[$offset];

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

        unset($this->_atc[$offset]);

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
        return count(iterator_to_array($this));
    }

    /* Iterator methods. */

    public function current()
    {
        return current($this->_atc);
    }

    public function key()
    {
        return key($this->_atc);
    }

    public function next()
    {
        $this->_next();
    }

    protected function _next($rewind = false)
    {
        do {
            if ($rewind) {
                $rewind = false;
            } else {
                next($this->_atc);
            }
            if (($curr = $this->current()) && empty($curr['related'])) {
                break;
            }
        } while ($this->valid());
    }

    public function rewind()
    {
        reset($this->_atc);
        $this->_next(true);
    }

    public function valid()
    {
        return (key($this->_atc) !== null);
    }

    /* Serializable methods. */

    /**
     */
    public function serialize()
    {
        /* Make sure we don't have data in the Mime Part parts. */
        $atc = array();
        foreach ($this->_atc as $key => $val) {
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
            $this->_atc,
            $this->_cacheid,
            $this->_linkAttach,
            $this->_metadata,
            $this->_pgpAttachPubkey,
            $this->_replytype,
            $this->_size
        ) = unserialize($data);
    }

}
