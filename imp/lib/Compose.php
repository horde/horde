<?php
/**
 * Copyright 2002-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2002-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * The IMP_Compose:: class represents an outgoing mail message.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2002-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Compose implements ArrayAccess, Countable, IteratorAggregate
{
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
    const EDITASNEW = 12;
    const TEMPLATE = 13;

    /* Related part attribute name. */
    const RELATED_ATTR = 'imp_related_attr';

    /* Draft mail metadata headers. */
    const DRAFT_HDR = 'X-IMP-Draft';
    const DRAFT_REPLY = 'X-IMP-Draft-Reply';
    const DRAFT_REPLY_TYPE = 'X-IMP-Draft-Reply-Type';
    const DRAFT_FWD = 'X-IMP-Forward';

    /* The blockquote tag to use to indicate quoted text in HTML data. */
    const HTML_BLOCKQUOTE = '<blockquote type="cite" style="border-left:2px solid blue;margin-left:2px;padding-left:12px;">';

    /**
     * Attachment ID counter.
     *
     * @var integer
     */
    public $atcId = 0;

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
     * Attachment data.
     *
     * @var array
     */
    protected $_atc = array();

    /**
     * The cache ID used to store object in session.
     *
     * @var string
     */
    protected $_cacheid;

    /**
     * Various metadata for this message.
     *
     * @var array
     */
    protected $_metadata = array();

    /**
     * The reply type.
     *
     * @var integer
     */
    protected $_replytype = self::COMPOSE;

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
     * Tasks to do upon unserialize().
     */
    public function __wakeup()
    {
        $this->changed = '';
    }

    /**
     * Destroys an IMP_Compose instance.
     *
     * @param string $action  The action performed to cause the end of this
     *                        instance.  Either 'cancel', 'discard',
     *                        'save_draft', or 'send'.
     */
    public function destroy($action)
    {
        switch ($action) {
        case 'discard':
        case 'send':
            /* Delete the draft. */
            $i = new IMP_Indices($this->getMetadata('draft_uid'));
            $i->delete(array('nuke' => true));
            break;

        case 'save_draft':
            /* Don't delete any drafts. */
            $this->changed = 'deleted';
            return;

        case 'cancel':
            if ($this->getMetadata('draft_auto')) {
                $this->destroy('discard');
                return;
            }
            // Fall-through

        default:
            // No-op
            break;
        }

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
     * Sets metadata for the current object.
     *
     * @param string $name  The metadata name.
     * @param mixed $value  The metadata value.
     */
    protected function _setMetadata($name, $value)
    {
        if (is_null($value)) {
            unset($this->_metadata[$name]);
        } else {
            $this->_metadata[$name] = $value;
        }
        $this->changed = 'changed';
    }

    /**
     * Saves a draft message.
     *
     * @param array $headers  List of message headers (UTF-8).
     * @param mixed $message  Either the message text (string) or a
     *                        Horde_Mime_Part object that contains the text
     *                        to send.
     * @param array $opts     An array of options w/the following keys:
     *   - autosave: (boolean) Is this an auto-saved draft?
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
        $ret = $this->_saveDraftServer($body);
        $this->_setMetadata('draft_auto', !empty($opts['autosave']));
        return $ret;
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
     *   - verify_email: (boolean) Verify e-mail messages? Default: no.
     *
     * @return string  The body text.
     *
     * @throws IMP_Compose_Exception
     */
    protected function _saveDraftMsg($headers, $message, $opts)
    {
        global $injector, $registry;

        $has_session = (bool)$registry->getAuth();

        /* Set up the base message now. */
        $base = $this->_createMimeMessage(new Horde_Mail_Rfc822_List(), $message, array(
            'html' => !empty($opts['html']),
            'noattach' => !$has_session,
            'nofinal' => true
        ));
        $base->isBasePart(true);

        $imp_imap = $injector->getInstance('IMP_Factory_Imap')->create();

        $recip_list = $this->recipientList($headers);
        if (!empty($opts['verify_email'])) {
            foreach ($recip_list['list'] as $val) {
                try {
                    /* For draft messages, the key is whether the IMAP server
                     * supports EAI addresses. */
                    $utf8 = $imp_imap->client_ob->capability->query(
                        'UTF8', 'ACCEPT'
                    );
                    IMP::parseAddressList($val->writeAddress(true), array(
                        'validate' => $utf8 ? 'eai' : true
                    ));
                } catch (Horde_Mail_Exception $e) {
                    throw new IMP_Compose_Exception(sprintf(
                        _("Saving the message failed because it contains an invalid e-mail address: %s."),
                        strval($val),
                        $e->getMessage()
                    ), $e->getCode());
                }
            }
        }
        $headers = array_merge($headers, $recip_list['header']);

        /* Initalize a header object for the draft. */
        $draft_headers = $this->_prepareHeaders($headers, array_merge($opts, array('bcc' => true)));

        /* Add information necessary to log replies/forwards when finally
         * sent. */
        if ($this->_replytype) {
            try {
                $indices = $this->getMetadata('indices');

                $imap_url = new Horde_Imap_Client_Url();
                $imap_url->hostspec = $imp_imap->getParam('hostspec');
                $imap_url->protocol = $imp_imap->isImap() ? 'imap' : 'pop';
                $imap_url->username = $imp_imap->getParam('username');

                $urls = array();
                foreach ($indices as $val) {
                    $imap_url->mailbox = $val->mbox;
                    $imap_url->uidvalidity = $val->mbox->uidvalid;
                    foreach ($val->uids as $val2) {
                        $imap_url->uid = $val2;
                        $urls[] = '<' . strval($imap_url) . '>';
                    }
                }

                switch ($this->replyType(true)) {
                case self::FORWARD:
                    $draft_headers->addHeader(self::DRAFT_FWD, implode(', ', $urls));
                    break;

                case self::REPLY:
                    $draft_headers->addHeader(self::DRAFT_REPLY, implode(', ', $urls));
                    $draft_headers->addHeader(self::DRAFT_REPLY_TYPE, $this->_replytype);
                    break;
                }
            } catch (Horde_Exception $e) {}
        } else {
            $draft_headers->addHeader(self::DRAFT_HDR, 'Yes');
        }

        return $base->toString(array(
            'defserver' => $has_session ? $imp_imap->config->maildomain : null,
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
        if (!$drafts_mbox = IMP_Mailbox::getPref(IMP_Mailbox::MBOX_DRAFTS)) {
            throw new IMP_Compose_Exception(_("Saving the draft failed. No drafts mailbox specified."));
        }

        /* Check for access to drafts mailbox. */
        if (!$drafts_mbox->create()) {
            throw new IMP_Compose_Exception(_("Saving the draft failed. Could not create a drafts mailbox."));
        }

        $append_flags = array(
            Horde_Imap_Client::FLAG_DRAFT,
            /* RFC 3503 [3.4] - MUST set MDNSent flag on draft message. */
            Horde_Imap_Client::FLAG_MDNSENT
        );
        if (!$GLOBALS['prefs']->getValue('unseen_drafts')) {
            $append_flags[] = Horde_Imap_Client::FLAG_SEEN;
        }

        $old_uid = $this->getMetadata('draft_uid');

        /* Add the message to the mailbox. */
        try {
            $ids = $drafts_mbox->imp_imap->append($drafts_mbox, array(array('data' => $data, 'flags' => $append_flags)));

            if ($old_uid) {
                $old_uid->delete(array('nuke' => true));
            }

            $this->_setMetadata('draft_uid', $drafts_mbox->getIndicesOb($ids));
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
        $this->_setMetadata('template_uid_edit', $indices);
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
     *   - addr: (array) Address lists (to, cc, bcc; Horde_Mail_Rfc822_List
     *           objects).
     *   - body: (string) The text of the body part.
     *   - format: (string) The format of the body message ('html', 'text').
     *   - identity: (mixed) See IMP_Prefs_Identity#getMatchingIdentity().
     *   - priority: (string) The message priority.
     *   - readreceipt: (boolean) Add return receipt headers?
     *   - subject: (string) Formatted subject.
     *   - type: (integer) - The compose type.
     *
     * @throws IMP_Compose_Exception
     */
    public function resumeDraft($indices, array $opts = array())
    {
        $res = $this->_resumeDraft($indices, null, $opts);
        $this->_setMetadata('draft_uid', $indices);
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

        $contents_factory = $injector->getInstance('IMP_Factory_Contents');

        try {
            $contents = $contents_factory->create($indices);
        } catch (IMP_Exception $e) {
            throw new IMP_Compose_Exception($e);
        }

        $headers = $contents->getHeader();
        $imp_draft = false;

        if ($draft_url = $headers[self::DRAFT_REPLY]) {
            if (is_null($type) &&
                !($type = $headers[self::DRAFT_REPLY_TYPE])) {
                $type = self::REPLY;
            }
            $imp_draft = self::REPLY;
        } elseif ($draft_url = $headers[self::DRAFT_FWD]) {
            $imp_draft = self::FORWARD;
            if (is_null($type)) {
                $type = self::FORWARD;
            }
        } elseif (isset($headers[self::DRAFT_HDR])) {
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
                    $this->addAttachmentFromPart($part);
                } catch (IMP_Compose_Exception $e) {
                    $GLOBALS['notification']->push($e, 'horde.warning');
                }
            }
        }

        $alist = new Horde_Mail_Rfc822_List();
        $addr = array(
            'to' => clone $alist,
            'cc' => clone $alist,
            'bcc' => clone $alist
        );

        if ($type != self::EDITASNEW) {
            foreach (array('to', 'cc', 'bcc') as $val) {
                if ($h = $headers[$val]) {
                    $addr[$val] = $h->getAddressList(true);
                }
            }

            if ($val = $headers['References']) {
                $this->_setMetadata('references', $val->getIdentificationOb()->ids);

                if ($val = $headers['In-Reply-To']) {
                    $this->_setMetadata('in_reply_to', $val);
                }
            }

            if ($draft_url) {
                $imp_imap = $injector->getInstance('IMP_Factory_Imap')->create();
                $indices = new IMP_Indices();

                foreach (explode(',', $draft_url->value_single) as $val) {
                    $imap_url = new Horde_Imap_Client_Url(rtrim(ltrim($val, '<'), '>'));

                    try {
                        if (($imap_url->protocol == ($imp_imap->isImap() ? 'imap' : 'pop')) &&
                            ($imap_url->username == $imp_imap->getParam('username')) &&
                            // Ignore hostspec and port, since these can change
                            // even though the server is the same. UIDVALIDITY
                            // should catch any true server/backend changes.
                            (IMP_Mailbox::get($imap_url->mailbox)->uidvalid == $imap_url->uidvalidity) &&
                            $contents_factory->create(new IMP_Indices($imap_url->mailbox, $imap_url->uid))) {
                            $indices->add($imap_url->mailbox, $imap_url->uid);
                        }
                    } catch (Exception $e) {}
                }

                if (count($indices)) {
                    $this->_setMetadata('indices', $indices);
                    $this->_replytype = $type;
                }
            }
        }

        $mdn = new Horde_Mime_Mdn($headers);
        $readreceipt = (bool)$mdn->getMdnReturnAddr();

        $this->changed = 'changed';

        return array(
            'addr' => $addr,
            'body' => $body,
            'format' => $format,
            'identity' => $this->_getMatchingIdentity($headers, array('from')),
            'priority' => $injector->getInstance('IMP_Mime_Headers')->getPriority($headers),
            'readreceipt' => $readreceipt,
            'subject' => strval($headers['Subject']),
            'type' => $type
        );
    }

    /**
     * Save a template message on the IMAP server.
     *
     * @param array $headers  List of message headers (UTF-8).
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
        if (!$mbox = IMP_Mailbox::getPref(IMP_Mailbox::MBOX_TEMPLATES)) {
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
            $mbox->imp_imap->append($mbox, array(array(
                'data' => $this->_saveDraftMsg($headers, $message, $opts),
                'flags' => $append_flags,
                'verify_email' => true
            )));

            if ($old_uid) {
                $old_uid->delete(array('nuke' => true));
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
        return (bool)$this->getMetadata('draft_uid');
    }

    /**
     * Builds and sends a MIME message.
     *
     * @param string $body                  The message body.
     * @param array $header                 List of message headers.
     * @param IMP_Prefs_Identity $identity  The Identity object for the sender
     *                                      of this message.
     * @param array $opts                   An array of options w/the
     *                                      following keys:
     *  - encrypt: (integer) A flag whether to encrypt or sign the message.
     *            One of:
     *    - IMP_Crypt_Pgp::ENCRYPT</li>
     *    - IMP_Crypt_Pgp::SIGNENC</li>
     *    - IMP_Crypt_Smime::ENCRYPT</li>
     *    - IMP_Crypt_Smime::SIGNENC</li>
     *  - html: (boolean) Whether this is an HTML message.
     *          DEFAULT: false
     *  - pgp_attach_pubkey: (boolean) Attach the user's PGP public key to the
     *                       message?
     *  - priority: (string) The message priority ('high', 'normal', 'low').
     *  - save_sent: (boolean) Save sent mail?
     *  - sent_mail: (IMP_Mailbox) The sent-mail mailbox (UTF-8).
     *  - strip_attachments: (bool) Strip attachments from the message?
     *  - signature: (string) The message signature.
     *  - readreceipt: (boolean) Add return receipt headers?
     *  - useragent: (string) The User-Agent string to use.
     *  - vcard_attach: (string) Attach the user's vCard (value is name to
     *                  display as vcard filename).
     *
     * @throws Horde_Exception
     * @throws IMP_Compose_Exception
     * @throws IMP_Compose_Exception_Address
     * @throws IMP_Exception
     */
    public function buildAndSendMessage(
        $body, $header, IMP_Prefs_Identity $identity, array $opts = array()
    )
    {
        global $injector, $prefs, $registry, $session;

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
            (count($recip['list']) === 1)) {
            $identity_search = $identity->getMatchingIdentity($recip['list'], false);
            if (!is_null($identity_search) &&
                ($identity->getDefault() != $identity_search)) {
                $this->_setMetadata('identity_check', true);

                $e = new IMP_Compose_Exception(_("Recipient address does not match the currently selected identity."));
                $e->tied_identity = $identity_search;
                throw $e;
            }
        }

        /* Check body size of message. */
        $imp_imap = $injector->getInstance('IMP_Factory_Imap')->create();
        if (!$imp_imap->accessCompose(IMP_Imap::ACCESS_COMPOSE_BODYSIZE, strlen($body))) {
            Horde::permissionDeniedError('imp', 'max_bodysize');
            throw new IMP_Compose_Exception(sprintf(
                _("Your message body has exceeded the limit by body size by %d characters."),
                (strlen($body) - $imp_imap->max_compose_bodysize)
            ));
        }

        $from = new Horde_Mail_Rfc822_Address($header['from']);
        if (is_null($from->host)) {
            $from->host = $imp_imap->config->maildomain;
        }

        /* Prepare the array of messages to send out.  May be more
         * than one if we are encrypting for multiple recipients or
         * are storing an encrypted message locally. */
        $encrypt = empty($opts['encrypt']) ? 0 : $opts['encrypt'];
        $send_msgs = array();
        $msg_options = array(
            'encrypt' => $encrypt,
            'html' => !empty($opts['html']),
            'identity' => $identity,
            'pgp_attach_pubkey' => (!empty($opts['pgp_attach_pubkey']) && $prefs->getValue('use_pgp') && $prefs->getValue('pgp_public_key')),
            'signature' => is_null($opts['signature']) ? $identity : $opts['signature'],
            'vcard_attach' => ((!empty($opts['vcard_attach']) && $registry->hasMethod('contacts/ownVCard')) ? ((strlen($opts['vcard_attach']) ? $opts['vcard_attach'] : 'vcard') . '.vcf') : null)
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
            $msg_options['from'] = $from;
            $save_msg = $this->_createMimeMessage($recip['list'], $body, $msg_options);
            $send_msgs[] = array(
                'base' => $save_msg,
                'recipients' => $recip['list']
            );
        }

        /* Initalize a header object for the outgoing message. */
        $headers = $this->_prepareHeaders($header, $opts);

        /* Add a Received header for the hop from browser to server. */
        $headers->addHeaderOb(
            Horde_Core_Mime_Headers_Received::createHordeHop()
        );

        /* Add Reply-To header. */
        if (!empty($header['replyto']) &&
            ($header['replyto'] != $from->bare_address)) {
            $headers->addHeader('Reply-to', $header['replyto']);
        }

        /* Add the 'User-Agent' header. */
        $headers->addHeaderOb(Horde_Mime_Headers_UserAgent::create(
            empty($opts['useragent'])
                ? 'Internet Messaging Program (IMP) ' . $registry->getVersion()
                : $opts['useragent']
        ));

        /* Add preferred reply language(s). */
        if ($lang = @unserialize($prefs->getValue('reply_lang'))) {
            $headers->addHeader('Accept-Language', implode(',', $lang));
        }

        /* Send the messages out now. */
        $sentmail = $injector->getInstance('IMP_Sentmail');

        foreach ($send_msgs as $val) {
            switch (intval($this->replyType(true))) {
            case self::REPLY:
                $senttype = IMP_Sentmail::REPLY;
                break;

            case self::FORWARD:
                $senttype = IMP_Sentmail::FORWARD;
                break;

            case self::REDIRECT:
                $senttype = IMP_Sentmail::REDIRECT;
                break;

            default:
                $senttype = IMP_Sentmail::NEWMSG;
                break;
            }

            try {
                $this->_prepSendMessageAssert($val['recipients'], $headers, $val['base']);
                $this->sendMessage($val['recipients'], $headers, $val['base']);

                /* Store history information. */
                if ($msg_id = $headers['Message-ID']) {
                    $sentmail->log(
                        $senttype,
                        reset($msg_id->getIdentificationOb()->ids),
                        $val['recipients'],
                        true
                    );
                }
            } catch (IMP_Compose_Exception_Address $e) {
                throw $e;
            } catch (IMP_Compose_Exception $e) {
                /* Unsuccessful send. */
                if ($e->log()) {
                    $sentmail->log(
                        $senttype,
                        reset($headers['Message-ID']->getIdentificationOb()->ids),
                        $val['recipients'],
                        false
                    );
                }
                throw new IMP_Compose_Exception(sprintf(_("There was an error sending your message: %s"), $e->getMessage()));
            }
        }

        $recipients = strval($recip['list']);

        if ($this->_replytype) {
            /* Log the reply. */
            if ($indices = $this->getMetadata('indices')) {
                switch ($this->_replytype) {
                case self::FORWARD:
                case self::FORWARD_ATTACH:
                case self::FORWARD_BODY:
                case self::FORWARD_BOTH:
                    $log = new IMP_Maillog_Log_Forward($recipients);
                    break;

                case self::REPLY:
                case self::REPLY_SENDER:
                    $log = new IMP_Maillog_Log_Reply();
                    break;

                case IMP_Compose::REPLY_ALL:
                    $log = new IMP_Maillog_Log_Replyall();
                    break;

                case IMP_Compose::REPLY_LIST:
                    $log = new IMP_Maillog_Log_Replylist();
                    break;
                }

                $log_msgs = array();
                foreach ($indices as $val) {
                    foreach ($val->uids as $val2) {
                        $log_msgs[] = new IMP_Maillog_Message(
                            new IMP_Indices($val->mbox, $val2)
                        );
                    }
                }

                $injector->getInstance('IMP_Maillog')->log($log_msgs, $log);
            }

            $reply_uid = new IMP_Indices($this);

            switch ($this->replyType(true)) {
            case self::FORWARD:
                /* Set the Forwarded flag, if possible, in the mailbox.
                 * See RFC 5550 [5.9] */
                $reply_uid->flag(array(Horde_Imap_Client::FLAG_FORWARDED));
                break;

            case self::REPLY:
                /* Make sure to set the IMAP reply flag and unset any
                 * 'flagged' flag. */
                $reply_uid->flag(
                    array(Horde_Imap_Client::FLAG_ANSWERED),
                    array(Horde_Imap_Client::FLAG_FLAGGED)
                );
                break;
            }
        }

        Horde::log(
            sprintf(
                "Message sent to %s from %s (%s)",
                $recipients,
                $registry->getAuth(),
                $session->get('horde', 'auth/remoteAddr')
            ),
            'INFO'
        );

        /* Save message to the sent mail mailbox. */
        $this->_saveToSentMail($header, $headers, $save_msg, $recip['list'], $opts);

        /* Delete the attachment data. */
        $this->deleteAllAttachments();

        /* Save recipients to address book? */
        $this->_saveRecipients($recip['list']);

        /* Call post-sent hook. */
        try {
            $injector->getInstance('Horde_Core_Hooks')->callHook(
                'post_sent',
                'imp',
                array($save_msg['msg'], $headers)
            );
        } catch (Horde_Exception_HookNotSet $e) {}
    }

    /**
     * Save message to sent-mail mailbox, if configured to do so.
     *
     * @param array $header                   See buildAndSendMessage().
     * @param Horde_Mime_Headers $headers     Headers object.
     * @param Horde_Mime_Part $save_msg       Message data to save.
     * @param Horde_Mail_Rfc822_List $recips  Recipient list.
     * @param array $opts                     See buildAndSendMessage()
     */
    protected function _saveToSentMail($header,
        Horde_Mime_Headers $headers,
        Horde_Mime_Part $save_msg,
        Horde_Mail_Rfc822_List $recips,
        $opts
    )
    {
        global $injector, $language, $notification, $prefs;

        if (empty($opts['sent_mail']) ||
            ($prefs->isLocked('save_sent_mail') &&
             !$prefs->getValue('save_sent_mail')) ||
            (!$prefs->isLocked('save_sent_mail') &&
             empty($opts['save_sent']))) {
            return;
        }

        $imp_imap = $injector->getInstance('IMP_Factory_Imap')->create();

        /* If message contains EAI addresses, we need to verify that the IMAP
         * server can handle this data in order to save. */
        foreach ($recips as $val) {
            if ($val->eai) {
                if ($imp_imap->client_ob->capability->query('UTF8', 'ACCEPT')) {
                    break;
                }

                $notification->push(sprintf(
                    _("Message sent successfully, but not saved to %s."),
                    $sent_mail->display
                ));
                return;
            }
        }

        /* Keep Bcc: headers on saved messages. */
        if (count($header['bcc'])) {
            $headers->addHeader('Bcc', $header['bcc']);
        }

        /* Strip attachments if requested. */
        if (!empty($opts['strip_attachments'])) {
            $save_msg->buildMimeIds();

            /* Don't strip any part if this is a text message with both
             * plaintext and HTML representation. */
            if ($save_msg->getType() != 'multipart/alternative') {
                for ($i = 2; ; ++$i) {
                    if (!($oldPart = $save_msg->getPart($i))) {
                        break;
                    }

                    $replace_part = new Horde_Mime_Part();
                    $replace_part->setType('text/plain');
                    $replace_part->setCharset($this->charset);
                    $replace_part->setLanguage($language);
                    $replace_part->setContents('[' . _("Attachment stripped: Original attachment type") . ': "' . $oldPart->getType() . '", ' . _("name") . ': "' . $oldPart->getName(true) . '"]');
                    $save_msg->alterPart($i, $replace_part);
                }
            }
        }

        /* Generate the message string. */
        $fcc = $save_msg->toString(array(
            'defserver' => $imp_imap->config->maildomain,
            'headers' => $headers,
            'stream' => true
        ));

        /* Make sure sent mailbox is created. */
        $sent_mail = IMP_Mailbox::get($opts['sent_mail']);
        $sent_mail->create();

        $flags = array(
            Horde_Imap_Client::FLAG_SEEN,
            /* RFC 3503 [3.3] - MUST set MDNSent flag on sent message. */
            Horde_Imap_Client::FLAG_MDNSENT
        );

        try {
            $imp_imap->append($sent_mail, array(array('data' => $fcc, 'flags' => $flags)));
        } catch (IMP_Imap_Exception $e) {
            $notification->push(sprintf(_("Message sent successfully, but not saved to %s."), $sent_mail->display));
        }
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

        $ob->addHeaderOb(Horde_Mime_Headers_Date::create());
        $ob->addHeaderOb(Horde_Mime_Headers_MessageId::create());

        if (isset($headers['from']) && strlen($headers['from'])) {
            $ob->addHeader('From', $headers['from']);
        }

        if (isset($headers['to']) &&
            (is_object($headers['to']) || strlen($headers['to']))) {
            $ob->addHeader('To', $headers['to']);
        } elseif (!isset($headers['cc'])) {
            $ob->addHeader('To', 'undisclosed-recipients:;');
        }

        if (isset($headers['cc']) &&
            (is_object($headers['cc']) || strlen($headers['cc']))) {
            $ob->addHeader('Cc', $headers['cc']);
        }

        if (!empty($opts['bcc']) &&
            isset($headers['bcc']) &&
            (is_object($headers['bcc']) || strlen($headers['bcc']))) {
            $ob->addHeader('Bcc', $headers['bcc']);
        }

        if (isset($headers['subject']) && strlen($headers['subject'])) {
            $ob->addHeader('Subject', $headers['subject']);
        }

        if ($this->replyType(true) == self::REPLY) {
            if ($refs = $this->getMetadata('references')) {
                $ob->addHeader('References', implode(' ', $refs));
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
        if (!empty($opts['readreceipt']) && ($h = $ob['from'])) {
            $from = $h->getAddressList(true);
            if (is_null($from->host)) {
                $from->host = $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->config->maildomain;
            }

            $mdn = new Horde_Mime_Mdn($ob);
            $mdn->addMdnRequestHeaders($from);
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
        global $injector;

        $email_count = count($email);
        $imp_imap = $injector->getInstance('IMP_Factory_Imap')->create();

        if (!$imp_imap->accessCompose(IMP_Imap::ACCESS_COMPOSE_TIMELIMIT, $email_count)) {
            Horde::permissionDeniedError('imp', 'max_timelimit');
            throw new IMP_Compose_Exception(sprintf(
                ngettext(
                    "You are not allowed to send messages to more than %d recipient within %d hours.",
                    "You are not allowed to send messages to more than %d recipients within %d hours.",
                    $imp_imap->max_compose_timelimit
                ),
                $imp_imap->max_compose_timelimit,
                $injector->getInstance('IMP_Sentmail')->limit_period
            ));
        }

        /* Count recipients if necessary. We need to split email groups
         * because the group members count as separate recipients. */
        if (!$imp_imap->accessCompose(IMP_Imap::ACCESS_COMPOSE_RECIPIENTS, $email_count)) {
            Horde::permissionDeniedError('imp', 'max_recipients');
            throw new IMP_Compose_Exception(sprintf(
                ngettext(
                    "You are not allowed to send messages to more than %d recipient.",
                    "You are not allowed to send messages to more than %d recipients.",
                    $imp_imap->max_compose_recipients
                ),
                $imp_imap->max_compose_recipients
            ));
        }

        /* Pass to hook to allow alteration of message details. */
        if (!is_null($message)) {
            try {
                $injector->getInstance('Horde_Core_Hooks')->callHook(
                    'pre_sent',
                    'imp',
                    array($message, $headers, $this)
                );
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
     * @throws IMP_Compose_Exception_Address
     */
    protected function _prepSendMessageEncode(Horde_Mail_Rfc822_List $email,
                                              $charset)
    {
        global $injector;

        $exception = new IMP_Compose_Exception_Address();
        $hook = true;
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
                 * So double-check. Key here is MTA server support for
                 * UTF-8. */
                $utf8 = $injector->getInstance('IMP_Mail')->eai;
                $alist = IMP::parseAddressList($tmp, array(
                    'validate' => $utf8 ? 'eai' : true
                ));

                $error = null;

                if ($hook) {
                    try {
                        $error = $injector->getInstance('Horde_Core_Hooks')->callHook(
                            'compose_addr',
                            'imp',
                            array($alist[0])
                        );
                    } catch (Horde_Exception_HookNotSet $e) {
                        $hook = false;
                    }
                }
            } catch (Horde_Mail_Exception $e) {
                $error = array(
                    'msg' => sprintf(_("Invalid e-mail address (%s)."), $val)
                );
            }

            if (is_array($error)) {
                switch (isset($error['level']) ? $error['level'] : $exception::BAD) {
                case $exception::WARN:
                case 'warn':
                    if (($warn = $this->getMetadata('warn_addr')) &&
                        in_array(strval($val), $warn)) {
                        $out[] = $tmp;
                        continue 2;
                    }
                    $warn[] = strval($val);
                    $this->_setMetadata('warn_addr', $warn);
                    $this->changed = 'changed';
                    $level = $exception::WARN;
                    break;

                default:
                    $level = $exception::BAD;
                    break;
                }

                $exception->addAddress($val, $error['msg'], $level);
            } else {
                $out[] = $tmp;
            }
        }

        if (count($exception)) {
            throw $exception;
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
        global $notification, $prefs, $registry;

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
     * @param array $hdr  An array of MIME headers and/or address list
     *                    objects. Recipients will be extracted from the 'to',
     *                    'cc', and 'bcc' entries.
     *
     * @return array  An array with the following entries:
     *   - has_input: (boolean) True if at least one of the headers contains
     *                user input.
     *   - header: (array) Contains the cleaned up 'to', 'cc', and 'bcc'
     *             address list (Horde_Mail_Rfc822_List objects).
     *   - list: (Horde_Mail_Rfc822_List) Recipient addresses.
     */
    public function recipientList($hdr)
    {
        $addrlist = new Horde_Mail_Rfc822_List();
        $has_input = false;
        $header = array();

        foreach (array('to', 'cc', 'bcc') as $key) {
            if (isset($hdr[$key])) {
                $ob = IMP::parseAddressList($hdr[$key]);
                if (count($ob)) {
                    $addrlist->add($ob);
                    $header[$key] = $ob;
                    $has_input = true;
                } else {
                    $header[$key] = null;
                }
            }
        }

        return array(
            'has_input' => $has_input,
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
     *   - from: (Horde_Mail_Rfc822_Address) The outgoing from address (only
     *           needed for multiple PGP encryption).
     *   - html: (boolean) Is this a HTML message?
     *   - identity: (IMP_Prefs_Identity) Identity of the sender.
     *   - nofinal: (boolean) This is not a message which will be sent out.
     *   - noattach: (boolean) Don't add attachment information.
     *   - pgp_attach_pubkey: (boolean) Attach the user's PGP public key?
     *   - signature: (IMP_Prefs_Identity|string) If set, add the signature to
     *                the message.
     *   - vcard_attach: (string) If set, attach user's vcard to message.
     *
     * @return Horde_Mime_Part  The MIME message to send.
     *
     * @throws Horde_Exception
     * @throws IMP_Compose_Exception
     */
    protected function _createMimeMessage(
        Horde_Mail_Rfc822_List $to, $body, array $options = array()
    )
    {
        global $conf, $injector, $prefs, $registry;

        /* Get body text. */
        if (empty($options['html'])) {
            $body_html = null;
        } else {
            $tfilter = $injector->getInstance('Horde_Core_Factory_TextFilter');

            $body_html = $tfilter->filter(
                $body,
                'Xss',
                array(
                    'return_dom' => true,
                    'strip_style_attributes' => false
                )
            );
            $body_html_body = $body_html->getBody();

            $body = $tfilter->filter(
                $body_html->returnHtml(),
                'Html2text',
                array(
                    'wrap' => false
                )
            );
        }

        $hooks = $injector->getInstance('Horde_Core_Hooks');

        /* We need to do the attachment check before any of the body text
         * has been altered. */
        if (!count($this) && !$this->getMetadata('attach_body_check')) {
            $this->_setMetadata('attach_body_check', true);

            try {
                $check = $hooks->callHook(
                    'attach_body_check',
                    'imp',
                    array($body)
                );
            } catch (Horde_Exception_HookNotSet $e) {
                $check = array();
            }

            if (!empty($check) &&
                preg_match('/\b(' . implode('|', array_map('preg_quote', $check)) . ')\b/i', $body, $matches)) {
                throw IMP_Compose_Exception::createAndLog('DEBUG', sprintf(_("Found the word %s in the message text although there are no files attached to the message. Did you forget to attach a file? (This check will not be performed again for this message.)"), $matches[0]));
            }
        }

        /* Add signature data. */
        if (!empty($options['signature'])) {
            if (is_string($options['signature'])) {
                if (empty($options['html'])) {
                    $body .= "\n\n" . trim($options['signature']);
                } else {
                    $html_sig = trim($options['signature']);
                    $body .= "\n" . $tfilter->filter($html_sig, 'Html2text');
                }
            } else {
                $sig = $options['signature']->getSignature('text');
                $body .= $sig;

                if (!empty($options['html'])) {
                    $html_sig = $options['signature']->getSignature('html');
                    if (!strlen($html_sig) && strlen($sig)) {
                        $html_sig = $this->text2html($sig);
                    }
                }
            }

            if (!empty($options['html'])) {
                try {
                    $sig_ob = new IMP_Compose_HtmlSignature($html_sig);
                } catch (IMP_Exception $e) {
                    throw new IMP_Compose_Exception($e);
                }

                foreach ($sig_ob->dom->getBody()->childNodes as $child) {
                    $body_html_body->appendChild(
                        $body_html->dom->importNode($child, true)
                    );
                }
            }
        }

        /* Add linked attachments. */
        if (empty($options['nofinal'])) {
            $this->_linkAttachments($body, $body_html);
        }

        /* Get trailer text (if any). */
        if (empty($options['nofinal'])) {
            try {
                $trailer = $hooks->callHook(
                    'trailer',
                    'imp',
                    array(false, $options['identity'], $to)
                );
                $html_trailer = $hooks->callHook(
                    'trailer',
                    'imp',
                    array(true, $options['identity'], $to)
                );
            } catch (Horde_Exception_HookNotSet $e) {
                $trailer = $html_trailer = null;
            }

            $body .= strval($trailer);

            if (!empty($options['html'])) {
                if (is_null($html_trailer) && strlen($trailer)) {
                    $html_trailer = $this->text2html($trailer);
                }

                if (strlen($html_trailer)) {
                    $t_dom = new Horde_Domhtml($html_trailer, 'UTF-8');
                    foreach ($t_dom->getBody()->childNodes as $child) {
                        $body_html_body->appendChild($body_html->dom->importNode($child, true));
                    }
                }
            }
        }

        /* Convert text to sending charset. HTML text will be converted
         * via Horde_Domhtml. */
        $body = Horde_String::convertCharset($body, 'UTF-8', $this->charset);

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
        $text_contents = $flowed->toFlowed();
        $textBody->setContents($text_contents);

        /* Determine whether or not to send a multipart/alternative
         * message with an HTML part. */
        if (!empty($options['html'])) {
            $htmlBody = new Horde_Mime_Part();
            $htmlBody->setType('text/html');
            $htmlBody->setCharset($this->charset);
            $htmlBody->setDisposition('inline');
            $htmlBody->setDescription(Horde_String::convertCharset(_("HTML Message"), 'UTF-8', $this->charset));

            /* Add default font CSS information here. */
            $styles = array();
            if ($font_family = $prefs->getValue('compose_html_font_family')) {
                $styles[] = 'font-family:' . $font_family;
            }
            if ($font_size = intval($prefs->getValue('compose_html_font_size'))) {
                $styles[] = 'font-size:' . $font_size . 'px';
            }

            if (!empty($styles)) {
                $body_html_body->setAttribute('style', implode(';', $styles));
            }

            if (empty($options['nofinal'])) {
                $this->_cleanHtmlOutput($body_html);
            }

            $to_add = $this->_convertToRelated($body_html, $htmlBody);

            /* Now, all parts referred to in the HTML data have been added
             * to the attachment list. Convert to multipart/related if
             * this is the case. Exception: if text representation is empty,
             * just send HTML part. */
            if (strlen(trim($text_contents))) {
                $textpart = new Horde_Mime_Part();
                $textpart->setType('multipart/alternative');
                $textpart->addPart($textBody);
                $textpart->addPart($to_add);
                $textpart->setHeaderCharset($this->charset);

                $textBody->setDescription(Horde_String::convertCharset(_("Plaintext Message"), 'UTF-8', $this->charset));
            } else {
                $textpart = $to_add;
            }

            $htmlBody->setContents(
                $tfilter->filter(
                    $body_html->returnHtml(array(
                        'charset' => $this->charset,
                        'metacharset' => true
                    )),
                    'Cleanhtml',
                    array(
                        'charset' => $this->charset
                    )
                )
            );

            $base = $textpart;
        } else {
            $base = $textpart = strlen(trim($text_contents))
                ? $textBody
                : null;
        }

        /* Add attachments. */
        if (empty($options['noattach'])) {
            $parts = array();

            foreach ($this as $val) {
                if (!$val->related && !$val->linked) {
                    $parts[] = $val->getPart(true);
                }
            }

            if (!empty($options['pgp_attach_pubkey'])) {
                $parts[] = $injector->getInstance('IMP_Crypt_Pgp')->publicKeyMIMEPart();
            }

            if (!empty($options['vcard_attach'])) {
                try {
                    $vpart = new Horde_Mime_Part();
                    $vpart->setType('text/x-vcard');
                    $vpart->setCharset('UTF-8');
                    $vpart->setContents($registry->call('contacts/ownVCard'));
                    $vpart->setName($options['vcard_attach']);

                    $parts[] = $vpart;
                } catch (Horde_Exception $e) {
                    throw new IMP_Compose_Exception(sprintf(_("Can't attach contact information: %s"), $e->getMessage()));
                }
            }

            if (!empty($parts)) {
                if (is_null($base) && (count($parts) === 1)) {
                    /* If this is a single attachment with no text, the
                     * attachment IS the message. */
                    $base = reset($parts);
                } else {
                    $base = new Horde_Mime_Part();
                    $base->setType('multipart/mixed');
                    if (!is_null($textpart)) {
                        $base->addPart($textpart);
                    }
                    foreach ($parts as $val) {
                        $base->addPart($val);
                    }
                }
            }
        }

        /* If we reach this far with no base, we are sending a blank message.
         * Assume this is what the user wants. */
        if (is_null($base)) {
            $base = $textBody;
        }

        /* Set up the base message now. */
        $encrypt = empty($options['encrypt'])
            ? IMP::ENCRYPT_NONE
            : $options['encrypt'];
        if ($prefs->getValue('use_pgp') &&
            !empty($conf['gnupg']['path']) &&
            in_array($encrypt, array(IMP_Crypt_Pgp::ENCRYPT, IMP_Crypt_Pgp::SIGN, IMP_Crypt_Pgp::SIGNENC, IMP_Crypt_Pgp::SYM_ENCRYPT, IMP_Crypt_Pgp::SYM_SIGNENC))) {
            $imp_pgp = $injector->getInstance('IMP_Crypt_Pgp');
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
                    $this->_setMetadata('encrypt_sign', true);
                    break;

                case IMP_Crypt_Pgp::ENCRYPT:
                case IMP_Crypt_Pgp::SYM_ENCRYPT:
                    $to_list = clone $to;
                    if (count($options['from'])) {
                        $to_list->add($options['from']);
                    }
                    $base = $imp_pgp->IMPencryptMIMEPart($base, $to_list, ($encrypt == IMP_Crypt_Pgp::SYM_ENCRYPT) ? $symmetric_passphrase : null);
                    break;

                case IMP_Crypt_Pgp::SIGNENC:
                case IMP_Crypt_Pgp::SYM_SIGNENC:
                    $to_list = clone $to;
                    if (count($options['from'])) {
                        $to_list->add($options['from']);
                    }
                    $base = $imp_pgp->IMPsignAndEncryptMIMEPart($base, $to_list, ($encrypt == IMP_Crypt_Pgp::SYM_SIGNENC) ? $symmetric_passphrase : null);
                    break;
                }
            } catch (Horde_Exception $e) {
                throw new IMP_Compose_Exception(_("PGP Error: ") . $e->getMessage(), $e->getCode());
            }
        } elseif ($prefs->getValue('use_smime') &&
                  in_array($encrypt, array(IMP_Crypt_Smime::ENCRYPT, IMP_Crypt_Smime::SIGN, IMP_Crypt_Smime::SIGNENC))) {
            $imp_smime = $injector->getInstance('IMP_Crypt_Smime');

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
                    $this->_setMetadata('encrypt_sign', true);
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
     *   - addr: (array) Address lists (to, cc, bcc; Horde_Mail_Rfc822_List
     *           objects).
     *   - body: (string) The text of the body part.
     *   - format: (string) The format of the body message (html, text).
     *   - identity: (integer) The identity to use for the reply based on the
     *               original message's addresses.
     *   - lang: (array) Language code (keys)/language name (values) of the
     *           original sender's preferred language(s).
     *   - reply_list_id: (string) List ID label.
     *   - reply_recip: (integer) Number of recipients in reply list.
     *   - subject: (string) Formatted subject.
     *   - type: (integer) The reply type used (either self::REPLY_ALL,
     *           self::REPLY_LIST, or self::REPLY_SENDER).
     * @throws IMP_Exception
     */
    public function replyMessage($type, $contents, array $opts = array())
    {
        global $injector, $language, $prefs;

        if (!($contents instanceof IMP_Contents)) {
            throw new IMP_Exception(
                _("Could not retrieve message data from the mail server.")
            );
        }

        $alist = new Horde_Mail_Rfc822_List();
        $addr = array(
            'to' => clone $alist,
            'cc' => clone $alist,
            'bcc' => clone $alist
        );

        $h = $contents->getHeader();
        $match_identity = $this->_getMatchingIdentity($h);
        $reply_type = self::REPLY_SENDER;

        if (!$this->_replytype) {
            $this->_setMetadata('indices', $contents->getIndicesOb());

            /* Set the Message-ID related headers (RFC 5322 [3.6.4]). */
            if ($tmp = $h['Message-ID']) {
                $msg_id = $tmp->getIdentificationOb();
                if (count($msg_id->ids)) {
                    $this->_setMetadata('in_reply_to', reset($msg_id->ids));
                }
            }

            if ($tmp = $h['References']) {
                $ref_ob = $tmp->getIdentificationOb();
                if (!count($ref_ob->ids) &&
                    ($tmp = $h['In-Reply-To'])) {
                    $ref_ob = $tmp->getIdentificationOb();
                    if (count($ref_ob->ids) > 1) {
                        $ref_ob->ids = array();
                    }
                }
            }

            if (count($ref_ob->ids)) {
                $this->_setMetadata(
                    'references',
                    array_merge($ref_ob->ids, array(reset($msg_id->ids)))
                );
            }
        }

        $subject = strlen($s = $h['Subject'])
            ? 'Re: ' . strval(new Horde_Imap_Client_Data_BaseSubject($s, array('keepblob' => true)))
            : 'Re: ';

        $force = false;
        if (in_array($type, array(self::REPLY_AUTO, self::REPLY_SENDER))) {
            if (isset($opts['to'])) {
                $addr['to']->add($opts['to']);
                $force = true;
            } elseif ($tmp = $h['reply-to']) {
                $addr['to']->add($tmp->getAddressList());
                $force = true;
            } elseif ($tmp = $h['from']) {
                $addr['to']->add($tmp->getAddressList());
            }
        }

        /* We might need $list_info in the reply_all section. */
        $list_info = in_array($type, array(self::REPLY_AUTO, self::REPLY_LIST))
            ? $injector->getInstance('IMP_Message_Ui')->getListInformation($h)
            : null;

        if (!is_null($list_info) && !empty($list_info['reply_list'])) {
            /* If To/Reply-To and List-Reply address are the same, no need
             * to handle these address separately. */
            $rlist = new Horde_Mail_Rfc822_Address($list_info['reply_list']);
            if (!$rlist->match($addr['to'])) {
                $addr['to'] = clone $alist;
                $addr['to']->add($rlist);
                $reply_type = self::REPLY_LIST;
            }
        } elseif (in_array($type, array(self::REPLY_ALL, self::REPLY_AUTO))) {
            /* Clear the To field if we are auto-determining addresses. */
            if ($type == self::REPLY_AUTO) {
                $addr['to'] = clone $alist;
            }

            /* Filter out our own address from the addresses we reply to. */
            $identity = $injector->getInstance('IMP_Identity');
            $all_addrs = $identity->getAllFromAddresses();

            /* Build the To: header. It is either:
             * 1) the Reply-To address (if not a personal address)
             * 2) the From address(es) (if it doesn't contain a personal
             * address)
             * 3) all remaining Cc addresses. */
            $to_fields = array('from', 'reply-to');

            foreach (array('reply-to', 'from', 'to', 'cc') as $val) {
                /* If either a reply-to or $to is present, we use this address
                 * INSTEAD of the from address. */
                if (($force && ($val == 'from')) ||
                    !($tmp = $h[$val])) {
                    continue;
                }

                $ob = $tmp->getAddressList(true);

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
                            $addr['cc']->add($ob);
                            $all_addrs->add($ob);
                            continue 2;
                        }
                    }
                }

                $ob->setIteratorFilter($ob::BASE_ELEMENTS, $all_addrs);

                foreach ($ob as $hdr_ob) {
                    if ($hdr_ob instanceof Horde_Mail_Rfc822_Group) {
                        $addr['cc']->add($hdr_ob);
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
                                ($tmp = $h['from']) &&
                                ($to_ob = $tmp->getAddressList(true)->first()) &&
                                !is_null($to_ob->personal) &&
                                ($hdr_ob->match($to_ob))) {
                                $addr['to']->add($to_ob);
                            } else {
                                $addr['to']->add($hdr_ob);
                            }
                        } else {
                            $addr['cc']->add($hdr_ob);
                        }

                        $all_addrs->add($hdr_ob);
                    }
                }
            }

            /* Build the Cc: (or possibly the To:) header. If this is a
             * reply to a message that was already replied to by the user,
             * this reply will go to the original recipients (Request
             * #8485).  */
            if (count($addr['cc'])) {
                $reply_type = self::REPLY_ALL;
            }
            if (!count($addr['to'])) {
                $addr['to'] = $addr['cc'];
                $addr['cc'] = clone $alist;
            }

            /* Build the Bcc: header. */
            if ($tmp = $h['bcc']) {
                $bcc = $tmp->getAddressList(true);
                $bcc->add($identity->getBccAddresses());
                $bcc->setIteratorFilter(0, $all_addrs);
                foreach ($bcc as $val) {
                    $addr['bcc']->add($val);
                }
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
                    $recip_list = $this->recipientList($addr);
                    $ret['reply_recip'] = count($recip_list['list']);
                } catch (IMP_Compose_Exception $e) {
                    $ret['reply_recip'] = 0;
                }
                break;

            case self::REPLY_LIST:
                if (($list_parse = $injector->getInstance('Horde_ListHeaders')->parse('list-id', strval($h['List-Id']))) &&
                    !is_null($list_parse->label)) {
                    $ret['reply_list_id'] = $list_parse->label;
                }
                break;
            }
        }

        if (($lang = $h['Accept-Language']) ||
            ($lang = $h['X-Accept-Language'])) {
            $langs = array();
            foreach (explode(',', $lang->value_single) as $val) {
                if (($name = Horde_Nls::getLanguageISO($val)) !== null) {
                    $langs[trim($val)] = $name;
                }
            }
            $ret['lang'] = array_unique($langs);

            /* Don't show display if original recipient is asking for reply in
             * the user's native language. */
            if ((count($ret['lang']) == 1) &&
                reset($ret['lang']) &&
                (substr(key($ret['lang']), 0, 2) == substr($language, 0, 2))) {
                unset($ret['lang']);
            }
        }

        return array_merge(array(
            'addr' => $addr,
            'identity' => $match_identity,
            'subject' => $subject,
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

        $from = ($tmp = $h['from'])
            ? $tmp->getAddressList(true)
            : '';

        if ($prefs->getValue('reply_headers') && !empty($h)) {
            $from_text = strval(new IMP_Prefs_AttribText($from, $h, '%f'));

            $msg_pre = '----- ' .
                ($from_text ? sprintf(_("Message from %s"), $from_text) : _("Message")) .
                /* Extra '-'s line up with "End Message" below. */
                " ---------\n" .
                $this->_getMsgHeaders($h);

            $msg_post = "\n\n----- " .
                ($from_text ? sprintf(_("End message from %s"), $from_text) : _("End message")) .
                " -----\n";
        } else {
            $msg_pre = strval(new IMP_Prefs_AttribText($from, $h));
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
                : $msg_pre . "\n\n" . $msg_text['text'] . $msg_post;
            $msg_text['mode'] = 'text';
        }

        // Bug #10148: Message text might be us-ascii, but reply headers may
        // contain 8-bit characters.
        if (($msg_text['charset'] == 'us-ascii') &&
            (Horde_Mime::is8bit($msg_pre) ||
             Horde_Mime::is8bit($msg_post))) {
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
     * @param array $opts             Additional options:
     *   - format: (string) Force to this format.
     *             DEFAULT: Auto-determine.
     *
     * @return array  An array with the following keys:
     *   - attach: (boolean) True if original message was attached.
     *   - body: (string) The text of the body part.
     *   - format: (string) The format of the body message ('html', 'text').
     *   - identity: (mixed) See IMP_Prefs_Identity#getMatchingIdentity().
     *   - subject: (string) Formatted subject.
     *   - title: (string) Title to use on page.
     *   - type: (integer) - The compose type.
     * @throws IMP_Exception
     */
    public function forwardMessage($type, $contents, $attach = true,
                                   array $opts = array())
    {
        global $prefs;

        if (!($contents instanceof IMP_Contents)) {
            throw new IMP_Exception(
                _("Could not retrieve message data from the mail server.")
            );
        }

        if ($type == self::FORWARD_AUTO) {
            switch ($prefs->getValue('forward_default')) {
            case 'body':
                $type = self::FORWARD_BODY;
                break;

            case 'both':
                $type = self::FORWARD_BOTH;
                break;

            case 'editasnew':
                $ret = $this->editAsNew(new IMP_Indices($contents));
                $ret['title'] = _("New Message");
                return $ret;

            case 'attach':
            default:
                $type = self::FORWARD_ATTACH;
                break;
            }
        }

        $h = $contents->getHeader();

        $this->_replytype = $type;
        $this->_setMetadata('indices', $contents->getIndicesOb());

        if (strlen($s = $h['Subject'])) {
            $s = strval(new Horde_Imap_Client_Data_BaseSubject($s, array(
                'keepblob' => true
            )));
            $subject = 'Fwd: ' . $s;
            $title = _("Forward") . ': ' . $s;
        } else {
            $subject = 'Fwd:';
            $title = _("Forward");
        }

        $fwd_attach = false;
        if ($attach &&
            in_array($type, array(self::FORWARD_ATTACH, self::FORWARD_BOTH))) {
            try {
                $this->attachImapMessage(new IMP_Indices($contents));
                $fwd_attach = true;
            } catch (IMP_Exception $e) {}
        }

        if (in_array($type, array(self::FORWARD_BODY, self::FORWARD_BOTH))) {
            $ret = $this->forwardMessageText($contents, array(
                'format' => isset($opts['format']) ? $opts['format'] : null
            ));
            unset($ret['charset']);
        } else {
            $ret = array(
                'body' => '',
                'format' => $prefs->getValue('compose_html') ? 'html' : 'text'
            );
        }

        return array_merge(array(
            'attach' => $fwd_attach,
            'identity' => $this->_getMatchingIdentity($h),
            'subject' => $subject,
            'title' => $title,
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
        $h = $contents->getHeader();

        $from = strval($h['from']);

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
            (Horde_Mime::is8bit($msg_pre) ||
             Horde_Mime::is8bit($msg_post))) {
            $msg_text['charset'] = 'UTF-8';
        }

        return array(
            'body' => $msg,
            'charset' => $msg_text['charset'],
            'format' => $format
        );
    }

    /**
     * Prepares a forwarded message using multiple messages.
     *
     * @param IMP_Indices $indices  An indices object containing the indices
     *                              of the forwarded messages.
     *
     * @return array  An array with the following keys:
     *   - body: (string) The text of the body part.
     *   - format: (string) The format of the body message ('html', 'text').
     *   - identity: (mixed) See IMP_Prefs_Identity#getMatchingIdentity().
     *   - subject: (string) Formatted subject.
     *   - title: (string) Title to use on page.
     *   - type: (integer) The compose type.
     */
    public function forwardMultipleMessages(IMP_Indices $indices)
    {
        global $injector, $prefs, $session;

        $this->_setMetadata('indices', $indices);
        $this->_replytype = self::FORWARD_ATTACH;

        $subject = $this->attachImapMessage($indices);

        return array(
            'body' => '',
            'format' => ($prefs->getValue('compose_html') && $session->get('imp', 'rteavail')) ? 'html' : 'text',
            'identity' => $injector->getInstance('IMP_Identity')->getDefault(),
            'subject' => $subject,
            'title' => $subject,
            'type' => self::FORWARD
        );
    }

    /**
     * Prepare a redirect message.
     *
     * @param IMP_Indices $indices  An indices object.
     */
    public function redirectMessage(IMP_Indices $indices)
    {
        $this->_setMetadata('redirect_indices', $indices);
        $this->_replytype = self::REDIRECT;
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
        global $injector, $registry;

        $recip = $this->recipientList(array('to' => $to));

        $identity = $injector->getInstance('IMP_Identity');
        $from_addr = $identity->getFromAddress();

        $out = array();

        foreach ($this->getMetadata('redirect_indices') as $val) {
            foreach ($val->uids as $val2) {
                try {
                    $contents = $injector->getInstance('IMP_Factory_Contents')->create($val->mbox->getIndicesOb($val2));
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
                $resent_headers->addHeader(
                    'Resent-Message-ID',
                    Horde_Mime_Headers_MessageId::create()
                );

                $header_text = trim($resent_headers->toString(array('encode' => 'UTF-8'))) . "\n" . trim($contents->getHeader(IMP_Contents::HEADER_TEXT));

                $this->_prepSendMessageAssert($recip['list']);
                $to = $this->_prepSendMessage($recip['list']);
                $hdr_array = $headers->toArray(array('charset' => 'UTF-8'));
                $hdr_array['_raw'] = $header_text;

                try {
                    $injector->getInstance('IMP_Mail')->send($to, $hdr_array, $contents->getBody());
                } catch (Horde_Mail_Exception $e) {
                    throw new IMP_Compose_Exception($e);
                }

                $recipients = strval($recip['list']);

                Horde::log(sprintf("%s Redirected message sent to %s from %s", $_SERVER['REMOTE_ADDR'], $recipients, $registry->getAuth()), 'INFO');

                if ($log && ($tmp = $headers['Message-ID'])) {
                    $msg_id = reset($tmp->getIdentificationob()->ids);

                    /* Store history information. */
                    $injector->getInstance('IMP_Maillog')->log(
                        new IMP_Maillog_Message($msg_id),
                        new IMP_Maillog_Log_Redirect($recipients)
                    );

                    $injector->getInstance('IMP_Sentmail')->log(
                        IMP_Sentmail::REDIRECT,
                        $msg_id,
                        $recipients
                    );
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
     * @param array $only            Only use these headers.
     *
     * @return integer  The matching identity. If no exact match, returns the
     *                  default identity.
     */
    protected function _getMatchingIdentity($h, array $only = array())
    {
        global $injector;

        $identity = $injector->getInstance('IMP_Identity');
        $msgAddresses = array();
        if (empty($only)) {
            /* Bug #9271: Check 'from' address first; if replying to a message
             * originally sent by user, this should be the identity used for
             * the reply also. */
            $only = array('from', 'to', 'cc', 'bcc');
        }

        foreach ($only as $val) {
            $msgAddresses[] = $h[$val];
        }

        $match = $identity->getMatchingIdentity(array_filter($msgAddresses));

        return is_null($match)
            ? $identity->getDefault()
            : $match;
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
                $this->addAttachmentFromPart($part);

                $part->clearContents();
            }
        }

        if ($attached > 1) {
            return 'Fwd: ' . sprintf(_("%u Forwarded Messages"), $attached);
        }

        if ($name = $headerob['Subject']) {
            $name = Horde_String::truncate($name, 80);
        } else {
            $name = _("[No Subject]");
        }

        return 'Fwd: ' . strval(new Horde_Imap_Client_Data_BaseSubject($name, array('keepblob' => true)));
    }

    /**
     * Determine the header information to display in the forward/reply.
     *
     * @param Horde_Mime_Headers $h  The headers object for the message.
     *
     * @return string  The header information for the original message.
     */
    protected function _getMsgHeaders($h)
    {
        $tmp = array();

        if ($ob = $h['date']) {
            $tmp[_("Date")] = $ob->value;
        }

        if ($ob = strval($h['from'])) {
            $tmp[_("From")] = $ob;
        }

        if ($ob = strval($h['reply-to'])) {
            $tmp[_("Reply-To")] = $ob;
        }

        if ($ob = $h['subject']) {
            $tmp[_("Subject")] = $ob->value;
        }

        if ($ob = strval($h['to'])) {
            $tmp[_("To")] = $ob;
        }

        if ($ob = strval($h['cc'])) {
            $tmp[_("Cc")] = $ob;
        }

        $text = '';

        if (!empty($tmp)) {
            $max = max(array_map(array('Horde_String', 'length'), array_keys($tmp))) + 2;

            foreach ($tmp as $key => $val) {
                $text .= Horde_String::pad($key . ': ', $max, ' ', STR_PAD_LEFT) . $val . "\n";
            }
        }

        return $text;
    }

    /**
     * Add an attachment referred to in a related part.
     *
     * @param IMP_Compose_Attachment $act_ob  Attachment data.
     * @param DOMElement $node                Node element containg the
     *                                        related reference.
     * @param string $attribute               Element attribute containing the
     *                                        related reference.
     */
    public function addRelatedAttachment(IMP_Compose_Attachment $atc_ob,
                                         DOMElement $node, $attribute)
    {
        $atc_ob->related = true;
        $node->setAttribute(self::RELATED_ATTR, $attribute . ';' . $atc_ob->id);
    }

    /**
     * Deletes all attachments.
     */
    public function deleteAllAttachments()
    {
        foreach (array_keys($this->_atc) as $key) {
            unset($this[$key]);
        }
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
     * Generate HMAC hash used to validate data on a session expiration. Uses
     * the unique compose cache ID of the expired message, the username, and
     * the secret key of the server to generate a reproducible value that can
     * be validated if session data doesn't exist.
     *
     * @param string $cacheid  The cache ID to use. If null, uses cache ID of
     *                         the compose object.
     * @param string $user     The user ID to use. If null, uses the current
     *                         authenticated username.
     *
     * @return string  The HMAC hash string.
     */
    public function getHmac($cacheid = null, $user = null)
    {
        global $conf, $registry;

        return hash_hmac(
            (PHP_MINOR_VERSION >= 4) ? 'fnv132' : 'sha1',
            (is_null($cacheid) ? $this->getCacheId() : $cacheid) . '|' .
                (is_null($user) ? $registry->getAuth() : $user),
            $conf['secret_key']
        );
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
        global $conf;

        return empty($conf['compose']['attach_count_limit'])
            ? true
            : ($conf['compose']['attach_count_limit'] - count($this));
    }

    /**
     * What is the maximum attachment size?
     *
     * @return integer  The maximum attachment size (in bytes).
     */
    public function maxAttachmentSize()
    {
        $size = $GLOBALS['session']->get('imp', 'file_upload');

        return empty($GLOBALS['conf']['compose']['attach_size_limit'])
            ? $size
            : min($size, $GLOBALS['conf']['compose']['attach_size_limit']);
    }

    /**
     * Clean outgoing HTML (remove unexpected data URLs).
     *
     * @param Horde_Domhtml $html  The HTML data.
     */
    protected function _cleanHtmlOutput(Horde_Domhtml $html)
    {
        global $registry;

        $xpath = new DOMXPath($html->dom);

        foreach ($xpath->query('//*[@src]') as $node) {
            $src = $node->getAttribute('src');

            /* Check for attempts to sneak data URL information into the
             * output. */
            if (Horde_Url_Data::isData($src)) {
                if (IMP_Compose_HtmlSignature::isSigImage($node, true)) {
                    /* This is HTML signature image data. Convert to an
                     * attachment. */
                    $sig_img = new Horde_Url_Data($src);
                    if ($sig_img->data) {
                        $data_part = new Horde_Mime_Part();
                        $data_part->setContents($sig_img->data);
                        $data_part->setType($sig_img->type);

                        try {
                            $this->addRelatedAttachment(
                                $this->addAttachmentFromPart($data_part),
                                $node,
                                'src'
                            );
                        } catch (IMP_Compose_Exception $e) {
                            // Remove image on error.
                        }
                    }
                }

                $node->removeAttribute('src');
            } elseif (strcasecmp($node->tagName, 'IMG') === 0) {
                /* Check for smileys. They live in the JS directory, under
                 * the base ckeditor directory, so search for that and replace
                 * with the filesystem information if found (Request
                 * #13051). Need to ignore other image links that may have
                 * been explicitly added by the user. */
                $js_path = strval(Horde::url($registry->get('jsuri', 'horde'), true));
                if (stripos($src, $js_path . '/ckeditor') === 0) {
                    $file = str_replace(
                        $js_path,
                        $registry->get('jsfs', 'horde'),
                        $src
                    );

                    if (is_readable($file)) {
                        $data_part = new Horde_Mime_Part();
                        $data_part->setContents(file_get_contents($file));
                        $data_part->setName(basename($file));

                        try {
                            $this->addRelatedAttachment(
                                $this->addAttachmentFromPart($data_part),
                                $node,
                                'src'
                            );
                        } catch (IMP_Compose_Exception $e) {
                            // Keep existing data on error.
                        }
                    }
                }
            }
        }
    }

    /**
     * Converts an HTML part to a multipart/related part, if necessary.
     *
     * @param Horde_Domhtml $html    HTML data.
     * @param Horde_Mime_Part $part  The HTML part.
     *
     * @return Horde_Mime_Part  The part to add to the compose output.
     */
    protected function _convertToRelated(Horde_Domhtml $html,
                                         Horde_Mime_Part $part)
    {
        $r_part = false;
        foreach ($this as $atc) {
            if ($atc->related) {
                $r_part = true;
                break;
            }
        }

        if (!$r_part) {
            return $part;
        }

        /* Create new multipart/related part. */
        $related = new Horde_Mime_Part();
        $related->setType('multipart/related');
        /* Get the CID for the 'root' part. Although by default the first part
         * is the root part (RFC 2387 [3.2]), we may as well be explicit and
         * put the CID in the 'start' parameter. */
        $related->setContentTypeParameter('start', $part->setContentId());
        $related->addPart($part);

        /* HTML iteration is from child->parent, so need to gather related
         * parts and add at end after sorting to generate a more sensible
         * attachment list. */
        $add = array();

        foreach ($html as $node) {
            if (($node instanceof DOMElement) &&
                $node->hasAttribute(self::RELATED_ATTR)) {
                list($attr_name, $atc_id) = explode(';', $node->getAttribute(self::RELATED_ATTR));

                /* If attachment can't be found, ignore. */
                if ($r_atc = $this[$atc_id]) {
                    if ($r_atc->linked) {
                        $attr = strval($r_atc->link_url);
                    } else {
                        $related_part = $r_atc->getPart(true);
                        $attr = 'cid:' . $related_part->setContentId();
                        $add[] = $related_part;
                    }

                    $node->setAttribute($attr_name, $attr);
                }

                $node->removeAttribute(self::RELATED_ATTR);
            }
        }

        array_map(array($related, 'addPart'), array_reverse($add));

        return $related;
    }

    /**
     * Adds linked attachments to message.
     *
     * @param string &$body  Plaintext data.
     * @param mixed $html    HTML data (Horde_Domhtml) or null.
     *
     * @throws IMP_Compose_Exception
     */
    protected function _linkAttachments(&$body, $html)
    {
        global $conf;

        $link_all = false;
        $linked = array();

        if (!empty($conf['compose']['link_attach_size_hard'])) {
            $limit = intval($conf['compose']['link_attach_size_hard']);
            foreach ($this as $val) {
                if (($limit -= $val->getPart()->getBytes()) < 0) {
                    $link_all = true;
                    break;
                }
            }
        }

        foreach (iterator_to_array($this) as $key => $val) {
            if ($link_all && !$val->linked) {
                $val = new IMP_Compose_Attachment($this, $val->getPart(), $val->storage->getTempFile());
                $val->forceLinked = true;
                unset($this[$key]);
                $this[$key] = $val;
            }

            if ($val->linked && !$val->related) {
                $linked[] = $val;
            }
        }

        if (empty($linked)) {
            return;
        }

        if ($del_time = IMP_Compose_LinkedAttachment::keepDate(false)) {
            /* Subtract 1 from time to get the last day of the previous
             * month. */
            $expire = ' (' . sprintf(_("links will expire on %s"), strftime('%x', $del_time - 1)) . ')';
        }

        $body .= "\n-----\n" . _("Attachments") . $expire . ":\n";
        if ($html) {
            $body = $html->getBody();
            $dom = $html->dom;

            $body->appendChild($dom->createElement('HR'));
            $body->appendChild($div = $dom->createElement('DIV'));
            $div->appendChild($dom->createElement('H4', _("Attachments") . $expire . ':'));
            $div->appendChild($ol = $dom->createElement('OL'));
        }

        $i = 0;
        foreach ($linked as $val) {
            $apart = $val->getPart();
            $name = $apart->getName(true);
            $size = IMP::sizeFormat($apart->getBytes());
            $url = strval($val->link_url->setRaw(true));

            $body .= "\n" . (++$i) . '. ' .
                $name . ' (' . $size . ') [' . $apart->getType() . "]\n" .
                sprintf(_("Download link: %s"), $url) . "\n";

            if ($html) {
                $ol->appendChild($li = $dom->createElement('LI'));
                $li->appendChild($dom->createElement('STRONG', $name));
                $li->appendChild($dom->createTextNode(' (' . $size . ') [' . htmlspecialchars($apart->getType()) . ']'));
                $li->appendChild($dom->createElement('BR'));
                $li->appendChild($dom->createTextNode(_("Download link") . ': '));
                $li->appendChild($a = $dom->createElement('A', htmlspecialchars($url)));
                $a->setAttribute('href', $url);
            }
        }
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
        global $conf, $injector, $notification, $prefs, $session;

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
                if (($body_id != '1') &&
                    ($mime_message->getSubType() == 'mixed') &&
                    ($id_ob = new Horde_Mime_Id('1')) &&
                    !$id_ob->isChild($body_id)) {
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
            $dom = $injector->getInstance('Horde_Core_Factory_TextFilter')->filter(
                $msg,
                'Xss',
                array(
                    'charset' => $this->charset,
                    'return_dom' => true,
                    'strip_style_attributes' => false
                )
            );

            /* If we are replying to a related part, and this part refers
             * to local message parts, we need to move those parts into this
             * message (since the original message may disappear during the
             * compose process). */
            if ($related_part = $contents->findMimeType($body_id, 'multipart/related')) {
                $this->_setMetadata('related_contents', $contents);
                $related_ob = new Horde_Mime_Related($related_part);
                $related_ob->cidReplace($dom, array($this, '_getMessageTextCallback'), $part_charset);
                $this->_setMetadata('related_contents', null);
            }

            /* Convert any Data URLs to attachments. */
            $xpath = new DOMXPath($dom->dom);
            foreach ($xpath->query('//*[@src]') as $val) {
                $data_url = new Horde_Url_Data($val->getAttribute('src'));
                if (strlen($data_url->data)) {
                    $data_part = new Horde_Mime_Part();
                    $data_part->setContents($data_url->data);
                    $data_part->setType($data_url->type);

                    try {
                        $atc = $this->addAttachmentFromPart($data_part);
                        $val->setAttribute('src', $atc->viewUrl());
                        $this->addRelatedAttachment($atc, $val, 'src');
                    } catch (IMP_Compose_Exception $e) {
                        $notification->push($e, 'horde.warning');
                    }
                }
            }

            $msg = $dom->returnBody();
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

            /* Remove PGP armored text. */
            $pgp = $injector->getInstance('Horde_Crypt_Pgp_Parse')->parseToPart($msg);
            if (!is_null($pgp)) {
                $msg = '';
                $pgp->buildMimeIds();
                foreach ($pgp->contentTypeMap() as $key => $val) {
                    if (strpos($val, 'text/') === 0) {
                        $msg .= $pgp[$key]->getContents();
                    }
                }
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
     *
     * @return Horde_Url
     */
    public function _getMessageTextCallback($id, $attribute, $node)
    {
        $atc = $this->addAttachmentFromPart($this->getMetadata('related_contents')->getMIMEPart($id));
        $this->addRelatedAttachment($atc, $node, $attribute);

        return $atc->viewUrl();
    }

    /**
     * Adds an attachment from Horde_Mime_Part data.
     *
     * @param Horde_Mime_Part $part  The object that contains the attachment
     *                               data.
     *
     * @return IMP_Compose_Attachment  Attachment object.
     * @throws IMP_Compose_Exception
     */
    public function addAttachmentFromPart($part)
    {
        /* Extract the data from the Horde_Mime_Part. */
        $atc_file = Horde::getTempFile('impatt');
        $stream = $part->getContents(array(
            'stream' => true
        ));
        rewind($stream);

        if (file_put_contents($atc_file, $stream) === false) {
            throw new IMP_Compose_Exception(sprintf(_("Could not attach %s to the message."), $part->getName()));
        }

        return $this->_addAttachment(
            $atc_file,
            ftell($stream),
            $part->getName(true),
            $part->getType()
        );
    }

    /**
     * Add attachment from uploaded (form) data.
     *
     * @param string $field  The form field name.
     *
     * @return array  A list of IMP_Compose_Attachment objects (if
     *                successfully attached) or IMP_Compose_Exception objects
     *                (if error when attaching).
     * @throws IMP_Compose_Exception
     */
    public function addAttachmentFromUpload($field)
    {
        global $browser;

        try {
            $browser->wasFileUploaded($field, _("attachment"));
        } catch (Horde_Browser_Exception $e) {
            throw new IMP_Compose_Exception($e);
        }

        $finfo = array();
        if (is_array($_FILES[$field]['size'])) {
            for ($i = 0; $i < count($_FILES[$field]['size']); ++$i) {
                $tmp = array();
                foreach ($_FILES[$field] as $key => $val) {
                    $tmp[$key] = $val[$i];
                }
                $finfo[] = $tmp;
            }
        } else {
            $finfo[] = $_FILES[$field];
        }

        $out = array();

        foreach ($finfo as $val) {
            switch (empty($val['type']) ? $val['type'] : '') {
            case 'application/unknown':
            case '':
                $type = 'application/octet-stream';
                break;

            default:
                $type = $val['type'];
                break;
            }

            try {
                $out[] = $this->_addAttachment(
                    $val['tmp_name'],
                    $val['size'],
                    Horde_Util::dispelMagicQuotes($val['name']),
                    $type
                );
            } catch (IMP_Compose_Exception $e) {
                $out[] = $e;
            }
        }

        return $out;
    }

    /**
     * Adds an attachment to the outgoing compose message.
     *
     * @param string $atc_file  Temporary file containing attachment contents.
     * @param integer $bytes    Size of data, in bytes.
     * @param string $filename  Filename of data.
     * @param string $type      MIME type of data.
     *
     * @return IMP_Compose_Attachment  Attachment object.
     * @throws IMP_Compose_Exception
     */
    protected function _addAttachment($atc_file, $bytes, $filename, $type)
    {
        global $conf, $injector;

        $atc = new Horde_Mime_Part();
        $atc->setBytes($bytes);

        /* Try to determine the MIME type from 1) the extension and
         * then 2) analysis of the file (if available). */
        if (strlen($filename)) {
            $atc->setName($filename);
            if ($type == 'application/octet-stream') {
                $type = Horde_Mime_Magic::filenameToMIME($filename, false);
            }
        }

        $atc->setType($type);

        if (($atc->getType() == 'application/octet-stream') ||
            ($atc->getPrimaryType() == 'text')) {
            $analyze = Horde_Mime_Magic::analyzeFile($atc_file, empty($conf['mime']['magic_db']) ? null : $conf['mime']['magic_db'], array(
                'nostrip' => true
            ));
            $atc->setCharset('UTF-8');

            if ($analyze) {
                $ctype = new Horde_Mime_Headers_ContentParam(
                    'Content-Type',
                    $analyze
                );
                $atc->setType($ctype->value);
                if (isset($ctype->params['charset'])) {
                    $atc->setCharset($ctype->params['charset']);
                }
            }
        } else {
            $atc->setHeaderCharset('UTF-8');
        }

        $atc_ob = new IMP_Compose_Attachment($this, $atc, $atc_file);

        /* Check for attachment size limitations. */
        $size_limit = null;
        if ($atc_ob->linked) {
            if (!empty($conf['compose']['link_attach_size_limit'])) {
                $linked = true;
                $size_limit = 'link_attach_size_limit';
            }
        } elseif (!empty($conf['compose']['attach_size_limit'])) {
            $linked = false;
            $size_limit = 'attach_size_limit';
        }

        if (!is_null($size_limit)) {
            $total_size = $conf['compose'][$size_limit] - $bytes;
            foreach ($this as $val) {
                if ($val->linked == $linked) {
                    $total_size -= $val->getPart()->getBytes();
                }
            }

            if ($total_size < 0) {
                throw new IMP_Compose_Exception(strlen($filename) ? sprintf(_("Attached file \"%s\" exceeds the attachment size limits. File NOT attached."), $filename) : _("Attached file exceeds the attachment size limits. File NOT attached."));
            }
        }

        try {
            $injector->getInstance('Horde_Core_Hooks')->callHook(
                'compose_attachment',
                'imp',
                array($atc_ob)
            );
        } catch (Horde_Exception_HookNotSet $e) {}

        $this->_atc[$atc_ob->id] = $atc_ob;
        $this->changed = 'changed';

        return $atc_ob;
    }

    /**
     * Store draft compose data if session expires.
     *
     * @param Horde_Variables $vars  Object with the form data.
     */
    public function sessionExpireDraft(Horde_Variables $vars)
    {
        global $injector;

        if (!isset($vars->composeCache) ||
            !isset($vars->composeHmac) ||
            !isset($vars->user) ||
            ($this->getHmac($vars->composeCache, $vars->user) != $vars->composeHmac)) {
            return;
        }

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

            $injector->getInstance('Horde_Core_Factory_Vfs')->create()->writeData(self::VFS_DRAFTS_PATH, hash('sha1', $vars->user), $body, true);
        } catch (Exception $e) {}
    }

    /**
     * Restore session expiration draft compose data.
     */
    public function recoverSessionExpireDraft()
    {
        global $injector, $notification;

        $filename = hash('sha1', $GLOBALS['registry']->getAuth());

        try {
            $vfs = $injector->getInstance('Horde_Core_Factory_Vfs')->create();

            if ($vfs->exists(self::VFS_DRAFTS_PATH, $filename)) {
                $data = $vfs->read(self::VFS_DRAFTS_PATH, $filename);
                $this->_saveDraftServer($data);
                $vfs->deleteFile(self::VFS_DRAFTS_PATH, $filename);
                $notification->push(
                    _("A message you were composing when your session expired has been recovered. You may resume composing your message by going to your Drafts mailbox."),
                    'horde.message',
                    array('sticky')
                );
            }
        } catch (Exception $e) {}
    }

    /**
     * If this object contains sufficient metadata, return an IMP_Contents
     * object reflecting that metadata.
     *
     * @return mixed  Either an IMP_Contents object or null.
     */
    public function getContentsOb()
    {
        return ($this->_replytype && ($indices = $this->getMetadata('indices')) && (count($indices) === 1))
            ? $GLOBALS['injector']->getInstance('IMP_Factory_Contents')->create($indices)
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

    /* Static methods. */

    /**
     * Is composing messages allowed?
     *
     * @return boolean  True if compose allowed.
     * @throws Horde_Exception
     */
    public static function canCompose()
    {
        try {
            return !$GLOBALS['injector']->getInstance('Horde_Core_Hooks')->callHook('disable_compose', 'imp');
        } catch (Horde_Exception_HookNotSet $e) {
            return true;
        }
    }

    /**
     * Can attachments be uploaded?
     *
     * @return boolean  True if attachments can be uploaded.
     */
    public static function canUploadAttachment()
    {
        return ($GLOBALS['session']->get('imp', 'file_upload') != 0);
    }

    /**
     * Shortcut function to convert text -> HTML for purposes of composition.
     *
     * @param string $msg  The message text.
     *
     * @return string  HTML text.
     */
    public static function text2html($msg)
    {
        return $GLOBALS['injector']->getInstance('Horde_Core_Factory_TextFilter')->filter($msg, 'Text2html', array(
            'always_mailto' => true,
            'flowed' => self::HTML_BLOCKQUOTE,
            'parselevel' => Horde_Text_Filter_Text2html::MICRO
        ));
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
        if (($atc = $this->_atc[$offset]) === null) {
            return;
        }

        $atc->delete();
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
        return count($this->_atc);
    }

    /* IteratorAggregate method. */

    /**
     */
    public function getIterator()
    {
        return new ArrayIterator($this->_atc);
    }

}
