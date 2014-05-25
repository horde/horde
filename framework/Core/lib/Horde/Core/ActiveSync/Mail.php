<?php
/**
 * Horde_Core_ActiveSync_Mail::
 *
 * @copyright 2010-2014 Horde LLC (http://www.horde.org/)
 * @license http://www.horde.org/licenses/lgpl21 LGPL
 * @author  Michael J Rubinsky <mrubinsk@horde.org>
 * @package Core
 */
/**
 * Horde_Core_ActiveSync_Mail::
 *
 * Wraps functionality related to sending/replying/forwarding email from
 * EAS clients.
 *
 * @copyright 2010-2014 Horde LLC (http://www.horde.org/)
 * @license http://www.horde.org/licenses/lgpl21 LGPL
 * @author  Michael J Rubinsky <mrubinsk@horde.org>
 * @package Core
 */
class Horde_Core_ActiveSync_Mail
{
    const HTML_BLOCKQUOTE = '<blockquote type="cite" style="border-left:2px solid blue;margin-left:2px;padding-left:12px;">';

    protected $_headers;
    protected $_raw;
    protected $_parentFolder = false;
    protected $_id;
    protected $_forward = false;
    protected $_reply = false;
    protected $_replaceMime = false;
    protected $_user;
    protected $_replyTop = false;
    protected $_mailer;
    protected $_imapMessage;

    public function __construct($imap, $user, $eas_version)
    {
        $this->_imap = $imap;
        $this->_user = $user;
        $this->_version = $eas_version;
    }

    public function &__get($property)
    {
        switch ($property) {
        case 'imapMessage':
            if (isset($this->_getImapMessage)) {
                $this->_getImapMessage();
            }
            return $this->_imapMessage;
        }
        $property = '_' . $property;
        return $this->$property;
    }

    public function setRawMessage(Horde_ActiveSync_Rfc822 $raw)
    {
        $this->_headers = $raw->getHeaders();
        $this->_headers->removeHeader('From');
        $this->_headers->addHeader('From', $this->_getIdentityFromAddress());
        $this->_raw = $raw;
    }

    public function setForward($parent, $id)
    {
        if (!empty($this->_reply)) {
            throw new Horde_ActiveSync_Exception('Cannot set both Forward and Reply.');
        }
        $this->_id = $id;
        $this->_parentFolder = $parent;
        $this->_forward = true;
    }

    public function setReply($parent, $id)
    {
        if (!empty($this->_forward)) {
            throw new Horde_ActiveSync_Exception('Cannot set both Forward and Reply.');
        }
        $this->_id = $id;
        $this->_parentFolder = $parent;
        $this->_reply = true;
    }

    public function send()
    {
        if (!$this->_parentFolder || ($this->_parentFolder && $this->_replaceMime)) {
            $this->_sendRaw();
        } else {
            $this->_sendSmart();
        }
    }

    public function getSentMail()
    {
        if (!empty($this->_mailer)) {
            return $this->_mailer->getRaw();
        }

        return $this->_raw->getString();
    }

    protected function _sendRaw()
    {
        $h_array = $this->_headers->toArray(array('charset' => 'UTF-8'));
        $recipients = $h_array['To'];
        if (!empty($h_array['Cc'])) {
            $recipients .= ',' . $h_array['Cc'];
        }
        if (!empty($h_array['Bcc'])) {
            $recipients .= ',' . $h_array['Bcc'];
            unset($h_array['Bcc']);
        }

        $GLOBALS['injector']->getInstance('Horde_Mail')->send($recipients, $h_array, $this->_raw->getMessage()->stream);

        // Replace MIME? Don't have original body, but still need headers.
        // @TODO: Get JUST the headers?
        if ($this->_replaceMime) {
            $this->_getImapMessage();
        }
    }

    protected function _sendSmart()
    {
        $mime_message = $this->_raw->getMimeObject();
        $mail = new Horde_Mime_Mail($this->_headers->toArray());
        $this->_getImapMessage();
        $base_part = $this->imapMessage->getStructure();
        $plain_id = $base_part->findBody('plain');
        $html_id = $base_part->findBody('html');
        $body_data = $this->imapMessage->getMessageBodyData(array(
            'protocolversion' => $this->_version,
            'bodyprefs' => array(Horde_ActiveSync::BODYPREF_TYPE_MIME => true))
        );
        if (!empty($html_id)) {
            $newbody_text_html = $this->_getHtmlPart($html_id, $mime_message, $body_data, $base_part);
            $mail->setHtmlBody($newbody_text_html);
        }
        if (!empty($plain_id)) {
            $newbody_text_plan = $this->_getPlainPart();
            $mail->setBody($newbody_text_plain);
        }
        if ($this->_forward) {
            foreach ($base_part->contentTypeMap() as $mid => $type) {
                if ($this->imapMessage->isAttachment($mid, $type)) {
                    $apart = $this->imapMessage->getMimePart($mid);
                    $mail->addMimePart($apart);
                }
            }
        }
        foreach ($mime_message->contentTypeMap() as $mid => $type) {
            if ($mid != 0 && $mid != $mime_message->findBody('plain') && $mid != $mime_message->findBody('html')) {
                $part = $mime_message->getPart($mid);
                $mail->addMimePart($part);
            }
        }

        try {
            $mail->send($GLOBALS['injector']->getInstance('Horde_Mail'));
            $this->_mailer = $mail;
        } catch (Horde_Mail_Exception $e) {
            throw new Horde_ActiveSync_Exception($e);
        }
    }

    protected function _getPlainPart($plain_id, $mime_message, $body_data, $base_part)
    {
        if (!$id = $mime_message->findBody('plain')) {
            $smart_text = self::html2text(
                Horde_ActiveSync_Utils::ensureUtf8(
                    $mime_message->getPart($mime_message->findBody())->getContents(),
                    $mime_message->getCharset()));
        } else {
            $smart_text = Horde_ActiveSync_Utils::ensureUtf8(
                $mime_message->getPart($id)->getContents(),
                $mime_message->getCharset());
        }

        if ($forward) {
            return $smart_text . $this->_forwardText($body_data, $base_part->getPart($plain_id));
        }

        return ($this->_replyTop ? $smart_text : '')
            . $this->_replyText($body_data, $base_part->getPart($plain_id))
            . ($this->_replyTop ? '' : $smart_text);
    }

    protected function _getHtmlPart($html_id, $mime_message, $body_data, $base_part)
    {
        if (!$id = $mime_message->findBody('html')) {
            $smart_text = self::text2html(
                Horde_ActiveSync_Utils::ensureUtf8(
                    $mime_message->getPart($mime_message->findBody('plain'))->getContents(),
                    $mime_message->getCharset()));
        } else {
            $smart_text = Horde_ActiveSync_Utils::ensureUtf8(
                $mime_message->getPart($id)->getContents(),
                $mime_message->getCharset());
        }

        if ($this->_forward) {
            return $smart_text . $this->_forwardText($body_data, $base_part->getPart($html_id), true);
        }
        return ($this->_replyTop ? $smart_text : '')
            . $this->_replyText($body_data, $base_part->getPart($html_id), true)
            . ($this->_replyTop ? '' : $smart_text);
    }

    /**
     *
     * @param  [type] $folder [description]
     * @param  [type] $uid    [description]
     *
     */
    protected function _getImapMessage()
    {
        $this->_imapMessage = array_pop($this->_imap->getImapMessage($this->_parentFolder, $this->_id, array('headers' => true)));
        if (empty($this->_imapMessage)) {
            throw new Horde_Exception_NotFound('The forwarded/replied message was not found.');
        }
    }

    /**
     * Return the current user's From/Reply_To address.
     *
     * @return string  A RFC822 valid email string.
     */
    protected function _getIdentityFromAddress()
    {
        global $prefs;

        $ident = $GLOBALS['injector']
            ->getInstance('Horde_Core_Factory_Identity')
            ->create($this->_user);

        $as_ident = $prefs->getValue('activesync_identity');
        $name = $ident->getValue('fullname', $as_ident == 'horde' ? $prefs->getValue('default_identity') : $prefs->getValue('activesync_identity'));
        $from_addr = $ident->getValue('from_addr', $as_ident == 'horde' ? $prefs->getValue('default_identity') : $prefs->getValue('activesync_identity'));
        $rfc822 = new Horde_Mail_Rfc822_Address($from_addr);
        $rfc822->personal = $name;

        return $rfc822->encoded;
    }

    /**
     * Return the body of the forwarded message in the appropriate type.
     *
     * @param array $body_data         The body data array.
     * @param Horde_Mime_Part $partId  The body part (minus contents).
     * @param boolean $html            Is this an html part?
     *
     * @return string  The propertly formatted forwarded body text.
     */
    protected function _forwardText(array $body_data, Horde_Mime_Part $part, $html = false)
    {
        $fwd_headers = $this->imapMessage->getForwardHeaders();
        $from = $this->imapMessage->getFromAddress();

        $msg = $this->_msgBody($body_data, $part, $html);
        $msg_pre = "\n----- "
            . ($from ? sprintf(Horde_Core_Translation::t("Forwarded message from %s"), $from) : Horde_Core_Translation::t("Forwarded message"))
            . " -----\n" . $fwd_headers . "\n";
        $msg_post = "\n\n----- " . Horde_Core_Translation::t("End forwarded message") . " -----\n";

        return ($html ? self::text2html($msg_pre) : $msg_pre)
            . $msg
            . ($html ? self::text2html($msg_post) : $msg_post);
    }

    /**
     * Return the body of the replied message in the appropriate type.
     *
     * @param array $body_data         The body data array.
     * @param Horde_Mime_Part $partId  The body part (minus contents).
     * @param boolean $html            Is this an html part?
     *
     * @return string  The propertly formatted replied body text.
     */
    protected function _replyText(array $body_data, Horde_Mime_Part $part, $html = false)
    {
        $headers = $this->imapMessage->getHeaders();
        $from = strval($headers->getOb('from'));
        $msg_pre = ($from ? sprintf(Horde_Core_Translation::t("Quoting %s"), $from) : Horde_Core_Translation::t("Quoted")) . "\n\n";
        $msg = $this->_msgBody($body_data, $part, $html, true);
        if (!empty($msg) && $html) {
            $msg = '<p>' . self::text2html($msg_pre) . '</p>'
                . self::HTML_BLOCKQUOTE . $msg . '</blockquote><br /><br />';
        } else {
            $msg = empty($msg)
                ? '[' . Horde_Core_Translation::t("No message body text") . ']'
                : $msg_pre . $msg;
        }

        return $msg;
    }

    /**
     * Return the body text of the original email from a smart request.
     *
     * @param array $body_data       The message's main mime part.
     * @param Horde_Mime_Part $part  The body mime part (minus contents).
     * @param boolean $html          Do we want an html body?
     * @param boolean $flow          Should the body be flowed?
     *
     * @return string  The properly formatted/flowed message body.
     */
    protected function _msgBody(array $body_data, Horde_Mime_Part $part, $html, $flow = false)
    {
        $subtype = $html == true ? 'html' : 'plain';
        $msg = Horde_String::convertCharset(
            $body_data[$subtype]['body'],
            $body_data[$subtype]['charset'],
            'UTF-8');
        trim($msg);
        if (!$html) {
            if ($part->getContentTypeParameter('format') == 'flowed') {
                $flowed = new Horde_Text_Flowed($msg, 'UTF-8');
                if (Horde_String::lower($part->getContentTypeParameter('delsp')) == 'yes') {
                    $flowed->setDelSp(true);
                }
                $flowed->setMaxLength(0);
                $msg = $flowed->toFixed(false);
            } else {
                // If not flowed, remove padding at eol
                $msg = preg_replace("/\s*\n/U", "\n", $msg);
            }
            if ($flow) {
                $flowed = new Horde_Text_Flowed($msg, 'UTF-8');
                $msg = $flowed->toFlowed(true);
            }
        }

        return $msg;
    }

    /**
     * Shortcut function to convert text -> HTML.
     *
     * @param string $msg  The message text.
     *
     * @return string  HTML text.
     */
    static public function text2html($msg)
    {
        return Horde_Text_Filter::filter(
            $msg,
            'Text2html',
            array(
                'flowed' => self::HTML_BLOCKQUOTE,
                'parselevel' => Horde_Text_Filter_Text2html::MICRO)
        );
    }

    static public function html2text($msg)
    {
        Horde_Text_Filter::filter($msg, 'Html2text');
    }


}