<?php
/**
 * The Horde_Mime_Mail:: class wraps around the various MIME library classes
 * to provide a simple interface for creating and sending MIME messages.
 *
 * All content has to be passed UTF-8 encoded. The charset parameters is used
 * for the generated message only.
 *
 * Copyright 2007-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Mime
 */
class Horde_Mime_Mail
{
    /**
     * The message headers.
     *
     * @var Horde_Mime_Headers
     */
    protected $_headers;

    /**
     * The base MIME part.
     *
     * @var Horde_Mime_Part
     */
    protected $_base;

    /**
     * The main body part.
     *
     * @var Horde_Mime_Part
     */
    protected $_body;

    /**
     * The main HTML body part.
     *
     * @var Horde_Mime_Part
     */
    protected $_htmlBody;

    /**
     * The message recipients.
     *
     * @var array
     */
    protected $_recipients = array();

    /**
     * Bcc recipients.
     *
     * @var string
     */
    protected $_bcc;

    /**
     * All MIME parts except the main body part.
     *
     * @var array
     */
    protected $_parts = array();

    /**
     * The Mail driver name.
     *
     * @link http://pear.php.net/Mail
     * @var string
     */
    protected $_mailer_driver = 'smtp';

    /**
     * The charset to use for the message.
     *
     * @var string
     */
    protected $_charset = 'UTF-8';

    /**
     * The Mail driver parameters.
     *
     * @link http://pear.php.net/Mail
     * @var array
     */
    protected $_mailer_params = array();

    /**
     * Constructor.
     *
     * @param array $params  A hash with basic message information. 'charset'
     *                       is the character set of the message.  'body' is
     *                       the message body. All other parameters are
     *                       assumed to be message headers.
     *
     * @throws Horde_Mime_Exception
     */
    public function __construct($params = array())
    {
        /* Set SERVER_NAME. */
        if (!isset($_SERVER['SERVER_NAME'])) {
            $_SERVER['SERVER_NAME'] = php_uname('n');
        }

        $this->_headers = new Horde_Mime_Headers();

        if (isset($params['charset'])) {
            $this->_charset = $params['charset'];
            unset($params['charset']);
        }

        if (isset($params['body'])) {
            $this->setBody($params['body'], $this->_charset);
            unset($params['body']);
        }

        $this->addHeaders($params);
    }

    /**
     * Adds several message headers at once.
     *
     * @param array $header    Hash with header names as keys and header
     *                         contents as values.
     *
     * @throws Horde_Mime_Exception
     */
    public function addHeaders($headers = array())
    {
        foreach ($headers as $header => $value) {
            $this->addHeader($header, $value);
        }
    }

    /**
     * Adds a message header.
     *
     * @param string $header      The header name.
     * @param string $value       The header value.
     * @param string $charset     The header value's charset.
     * @param boolean $overwrite  If true, an existing header of the same name
     *                            is being overwritten; if false, multiple
     *                            headers are added; if null, the correct
     *                            behaviour is automatically chosen depending
     *                            on the header name.
     *
     * @throws Horde_Mime_Exception
     */
    public function addHeader($header, $value, $overwrite = null)
    {
        $lc_header = Horde_String::lower($header);

        if (is_null($overwrite) &&
            in_array($lc_header, $this->_headers->singleFields(true))) {
            $overwrite = true;
        }

        if ($overwrite) {
            $this->_headers->removeHeader($header);
        }

        if ($lc_header === 'bcc') {
            $this->_bcc = $value;
        } else {
            $this->_headers->addHeader($header, $value);
        }
    }

    /**
     * Removes a message header.
     *
     * @param string $header  The header name.
     */
    public function removeHeader($header)
    {
        if (Horde_String::lower($header) === 'bcc') {
            unset($this->_bcc);
        } else {
            $this->_headers->removeHeader($header);
        }
    }

    /**
     * Sets the message body text.
     *
     * @param string $body             The message content.
     * @param string $charset          The character set of the message.
     * @param boolean|integer $wrap    If true, wrap the message at column 76;
     *                                 If an integer wrap the message at that
     *                                 column. Don't use wrapping if sending
     *                                 flowed messages.
     */
    public function setBody($body, $charset = null, $wrap = false)
    {
        if (!$charset) {
            $charset = $this->_charset;
        }
        $body = Horde_String::convertCharset($body, 'UTF-8', $charset);
        if ($wrap) {
            $body = Horde_String::wrap($body, $wrap === true ? 76 : $wrap);
        }
        $this->_body = new Horde_Mime_Part();
        $this->_body->setType('text/plain');
        $this->_body->setCharset($charset);
        $this->_body->setContents($body);
    }

    /**
     * Sets the HTML message body text.
     *
     * @param string $body          The message content.
     * @param string $charset       The character set of the message.
     * @param boolean $alternative  If true, a multipart/alternative message is
     *                              created and the text/plain part is
     *                              generated automatically. If false, a
     *                              text/html message is generated.
     */
    public function setHtmlBody($body, $charset = null, $alternative = true)
    {
        if (!$charset) {
            $charset = $this->_charset;
        }
        $this->_htmlBody = new Horde_Mime_Part();
        $this->_htmlBody->setType('text/html');
        $this->_htmlBody->setCharset($charset);
        $this->_htmlBody->setContents($body);
        if ($alternative) {
            $this->setBody(Horde_Text_Filter::filter($body, 'Html2text', array('charset' => $charset, 'wrap' => false)), $charset);
        }
    }

    /**
     * Adds a message part.
     *
     * @param string $mime_type    The content type of the part.
     * @param string $content      The content of the part.
     * @param string $charset      The character set of the part.
     * @param string $disposition  The content disposition of the part.
     *
     * @return integer  The part number.
     */
    public function addPart($mime_type, $content, $charset = 'us-ascii',
                            $disposition = null)
    {
        $part = new Horde_Mime_Part();
        $part->setType($mime_type);
        $part->setCharset($charset);
        $part->setDisposition($disposition);
        $part->setContents($content);
        return $this->addMimePart($part);
    }

    /**
     * Adds a MIME message part.
     *
     * @param Horde_Mime_Part $part  A Horde_Mime_Part object.
     *
     * @return integer  The part number.
     */
    public function addMimePart($part)
    {
        $this->_parts[] = $part;
        return count($this->_parts) - 1;
    }

    /**
     * Sets the base MIME part.
     *
     * If the base part is set, any text bodies will be ignored when building
     * the message.
     *
     * @param Horde_Mime_Part $part  A Horde_Mime_Part object.
     */
    public function setBasePart($part)
    {
        $this->_base = $part;
    }

    /**
     * Adds an attachment.
     *
     * @param string $file     The path to the file.
     * @param string $name     The file name to use for the attachment.
     * @param string $type     The content type of the file.
     * @param string $charset  The character set of the part (only relevant for
     *                         text parts.
     *
     * @return integer  The part number.
     */
    public function addAttachment($file, $name = null, $type = null,
                                  $charset = 'us-ascii')
    {
        if (empty($name)) {
            $name = basename($file);
        }

        if (empty($type)) {
            $type = Horde_Mime_Magic::filenameToMime($file, false);
        }

        $num = $this->addPart($type, file_get_contents($file), $charset, 'attachment');
        $this->_parts[$num]->setName($name);
        return $num;
    }

    /**
     * Removes a message part.
     *
     * @param integer $part  The part number.
     */
    public function removePart($part)
    {
        if (isset($this->_parts[$part])) {
            unset($this->_parts[$part]);
        }
    }

    /**
     * Removes all (additional) message parts but leaves the body parts
     * untouched.
     *
     * @since Horde_Mime 1.2.0
     */
    public function clearParts()
    {
        $this->_parts = array();
    }

    /**
     * Adds message recipients.
     *
     * Recipients specified by To:, Cc:, or Bcc: headers are added
     * automatically.
     *
     * @param string|array  List of recipients, either as a comma separated
     *                      list or as an array of email addresses.
     *
     * @throws Horde_Mime_Exception
     */
    public function addRecipients($recipients)
    {
        $this->_recipients = array_merge($this->_recipients, $this->_buildRecipients($recipients));
    }

    /**
     * Removes message recipients.
     *
     * @param string|array  List of recipients, either as a comma separated
     *                      list or as an array of email addresses.
     *
     * @throws Horde_Mime_Exception
     */
    public function removeRecipients($recipients)
    {
        $this->_recipients = array_diff($this->_recipients, $this->_buildRecipients($recipients));
    }

    /**
     * Removes all message recipients.
     */
    public function clearRecipients()
    {
        $this->_recipients = array();
    }

    /**
     * Builds a recipients list.
     *
     * @param string|array  List of recipients, either as a comma separated
     *                      list or as an array of email addresses.
     *
     * @return array  Normalized list of recipients.
     * @throws Horde_Mime_Exception
     */
    protected function _buildRecipients($recipients)
    {
        if (is_string($recipients)) {
            $recipients = Horde_Mime_Address::explode($recipients, ',');
        }
        $recipients = array_filter(array_map('trim', $recipients));

        $addrlist = array();
        foreach ($recipients as $email) {
            if (!empty($email)) {
                $unique = Horde_Mime_Address::bareAddress($email);
                if ($unique) {
                    $addrlist[$unique] = $email;
                } else {
                    $addrlist[$email] = $email;
                }
            }
        }

        foreach (Horde_Mime_Address::bareAddress(implode(', ', $addrlist), null, true) as $val) {
            if (Horde_Mime::is8bit($val)) {
                throw new Horde_Mime_Exception(sprintf(Horde_Mime_Translation::t("Invalid character in e-mail address: %s."), $val));
            }
        }

        return $addrlist;
    }

    /**
     * Sends this message.
     *
     * @param Mail $mailer     A Mail object.
     * @param boolean $resend  If true, the message id and date are re-used;
     *                         If false, they will be updated.
     * @param boolean $flowed  Send message in flowed text format.
     *
     * @throws Horde_Mime_Exception
     */
    public function send($mailer, $resend = false, $flowed = true)
    {
        /* Add mandatory headers if missing. */
        $has_header = $this->_headers->getValue('Message-ID');
        if (!$resend || !$has_header) {
            if ($has_header) {
                $this->_headers->removeHeader('Message-ID');
            }
            $this->_headers->addMessageIdHeader();
        }
        if (!$this->_headers->getValue('User-Agent')) {
            $this->_headers->addUserAgentHeader();
        }
        $has_header = $this->_headers->getValue('Date');
        if (!$resend || !$has_header) {
            if ($has_header) {
                $this->_headers->removeHeader('Date');
            }
            $this->_headers->addHeader('Date', date('r'));
        }

        if (isset($this->_base)) {
            $basepart = $this->_base;
        } else {
            /* Send in flowed format. */
            if ($flowed && !empty($this->_body)) {
                $flowed = new Horde_Text_Flowed($this->_body->getContents(), $this->_body->getCharset());
                $flowed->setDelSp(true);
                $this->_body->setContentTypeParameter('format', 'flowed');
                $this->_body->setContentTypeParameter('DelSp', 'Yes');
                $this->_body->setContents($flowed->toFlowed());
            }

            /* Build mime message. */
            $body = new Horde_Mime_Part();
            if (!empty($this->_body) && !empty($this->_htmlBody)) {
                $body->setType('multipart/alternative');
                $this->_body->setDescription(Horde_Mime_Translation::t("Plaintext Version of Message"));
                $body->addPart($this->_body);
                $this->_htmlBody->setDescription(Horde_Mime_Translation::t("HTML Version of Message"));
                $body->addPart($this->_htmlBody);
            } elseif (!empty($this->_htmlBody)) {
                $body = $this->_htmlBody;
            } elseif (!empty($this->_body)) {
                $body = $this->_body;
            }
            if (count($this->_parts)) {
                $basepart = new Horde_Mime_Part();
                $basepart->setType('multipart/mixed');
                if ($body) {
                    $basepart->addPart($body);
                }
                foreach ($this->_parts as $mime_part) {
                    $basepart->addPart($mime_part);
                }
            } else {
                $basepart = $body;
            }
        }
        $basepart->setHeaderCharset($this->_charset);

        /* Build recipients. */
        $recipients = $this->_recipients;
        foreach (array('to', 'cc') as $header) {
            $value = $this->_headers->getValue($header, Horde_Mime_Headers::VALUE_BASE);
            if (is_null($value)) {
                continue;
            }
            $value = Horde_Mime::encodeAddress($value, $this->_charset);
            $recipients = array_merge($recipients, $this->_buildRecipients($value));
        }
        if ($this->_bcc) {
            $recipients = array_merge($recipients, $this->_buildRecipients(Horde_Mime::encodeAddress($this->_bcc, $this->_charset)));
        }

        /* Trick Horde_Mime_Part into re-generating the message headers. */
        $this->_headers->removeHeader('MIME-Version');

        /* Send message. */
        $basepart->send(implode(', ', $recipients), $this->_headers, $mailer);
    }

}
