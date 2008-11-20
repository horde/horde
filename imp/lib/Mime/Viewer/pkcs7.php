<?php
/**
 * The IMP_Horde_Mime_Viewer_pkcs7 class allows viewing/decrypting of S/MIME
 * messages.
 * This class implements parts of RFC 2630, RFC 2632, and RFC 2633.
 *
 * This class handles the following MIME types:
 *   application/pkcs7-mime
 *   application/pkcs7-signature
 *   application/x-pkcs7-mime
 *   application/x-pkcs7-signature
 *
 * This class may add the following parameters to the URL:
 *   'smime_verify_msg' -- Do verification of S/MIME signed data.
 *
 * Copyright 2002-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Mime_Viewer
 */
class IMP_Horde_Mime_Viewer_pkcs7 extends Horde_Mime_Viewer_Driver
{
    /**
     * Can this driver render various views?
     *
     * @var boolean
     */
    protected $_capability = array(
        'embedded' => true,
        'full' => false,
        'info' => false,
        'inline' => true
    );

    /**
     * IMP_Horde_Crypt_smime object.
     *
     * @var IMP_Horde_Crypt_smime
     */
    protected $_impsmime = null;

    /**
     * Cache for inline data.
     *
     * @var array
     */
    static protected $_inlinecache = array();

    /**
     * Return the rendered inline version of the Horde_Mime_Part object.
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
     */
    protected function _renderInline()
    {
        /* Check to see if S/MIME support is available. */
        if (is_null($this->_impsmime) &&
            $GLOBALS['prefs']->getValue('use_smime')) {
            $this->_impsmime = &Horde_Crypt::singleton(array('imp', 'smime'));
            $openssl_check = $this->_impsmime->checkForOpenSSL();
            if (!is_a($openssl_check, 'PEAR_Error')) {
                $this->_impsmime = null;
            }
        }

        if (is_null($this->_impsmime)) {
            $this->_impsmime = false;
        } else {
            /* We need to insert JavaScript code now if S/MIME support is
             * active. */
            Horde::addScriptFile('prototype.js', 'horde', true);
            Horde::addScriptFile('popup.js', 'imp', true);
        }

        switch ($this->_getSMIMEType()) {
        case 'signed':
            return $this->_outputSMIMESigned();
            break;

        case 'encrypted':
            return $this->_outputSMIMEEncrypted();
        }
    }

    /**
     * If this MIME part can contain embedded MIME parts, and those embedded
     * MIME parts exist, return an altered version of the Horde_Mime_Part that
     * contains the embedded MIME part information.
     *
     * @return mixed  A Horde_Mime_Part with the embedded MIME part information
     *                or null if no embedded MIME parts exist.
     */
    protected function _getEmbeddedMimeParts()
    {
    }

    /**
     * Generates HTML output for the S/MIME key in
     * 'application/pkcs7-signature' MIME_Parts.
     *
     * @return string  The HTML output.
     */
    protected function _outputSMIMEKey()
    {
        if (empty($this->_impsmime)) {
            return _("S/MIME support is not enabled.");
        } else {
            $mime = &$this->mime_part;
            $signenc = $mime->getInformation('smime_signenc');
            $raw_text = $this->_getRawSMIMEText();
            if ($signenc && $mime->getInformation('smime_from')) {
                $smime_from = $mime->getInformation('smime_from');
                $raw_text = "From: $smime_from\n" . $raw_text;
            }
            $sig_result = $this->_impsmime->verifySignature($raw_text);
            return $this->_impsmime->certToHTML($sig_result->cert);
        }
    }

    /**
     * Generates HTML output for 'multipart/signed' MIME parts.
     *
     * @return string  The HTML output.
     */
    protected function _outputSMIMESigned()
    {
        if (Util::getFormData('viewkey')) {
            return $this->_outputSMIMEKey();
        }

        $partlist = array_keys($this->_mimepart->contentTypeMap());
        $base_id = reset($partlist);
        $signed_id = next($partlist);
        $sig_id = Horde_Mime::mimeIdArithmetic($signed_id, 'next');

        $ret = array(
            $base_id => array(
                'data' => '',
                'status' => array(
                    array(
                        'icon' => Horde::img('mime/encryption.png', 'S/MIME'),
                        'text' => array()
                    )
                ),
                'type' => 'text/html; charset=' . NLS::getCharset()
            ),
            $sig_id => null
        );
        $status = &$ret[$base_id]['status'][0]['text'];

        if (!$GLOBALS['prefs']->getValue('use_smime')) {
            $status[] = _("S/MIME support is not enabled so the digital signature is unable to be verified.");
            return $ret;
        }

        $status[] = _("This message has been digitally signed via S/MIME.");

        /* Store S/MIME results in $sig_result. */
        $raw_text = $this->_getRawSMIMEText();

        if ($GLOBALS['prefs']->getValue('smime_verify') ||
            Util::getFormData('smime_verify_msg')) {
            $sig_result = $this->_impsmime->verifySignature($raw_text);
        } elseif (isset($_SESSION['imp']['viewmode']) &&
                  ($_SESSION['imp']['viewmode'] == 'imp')) {
            // TODO: Fix to work with DIMP
            $status[] = Horde::link(Util::addParameter(Horde::selfUrl(true), 'smime_verify_msg', 1)) . _("Click HERE to verify the message.") . '</a>';
        }

        if (!isset($subpart)) {
            $msg_data = $this->_impsmime->extractSignedContents($raw_text);
            if (is_a($msg_data, 'PEAR_Error')) {
                $this->_status[] = $msg_data->getMessage();
                $mime_message = $mime;
            } else {
                $mime_message = Horde_Mime_Message::parseMessage($msg_data);
            }
        }

        $text = $this->_outputStatus();
        if (!is_null($sig_result)) {
            $text .= $this->_outputSMIMESignatureTest($sig_result->result, $sig_result->email);
            if (!empty($sig_result->cert)) {
                $cert_details = $this->_impsmime->parseCert($sig_result->cert);
                if (isset($cert_details['certificate']['subject']['CommonName'])) {
                    $subject = $cert_details['certificate']['subject']['CommonName'];
                } elseif (isset($cert_details['certificate']['subject']['Email'])) {
                    $subject = $cert_details['certificate']['subject']['Email'];
                } elseif (isset($sig_result->email)) {
                    $subject = $sig_result->email;
                } elseif (isset($smime_from)) {
                    $subject = $smime_from;
                } elseif (($from = $this->_headers->getValue('from'))) {
                    $subject = $from;
                } else {
                    $subject = null;
                }
                if (isset($subpart) &&
                    !empty($subject) &&
                    $GLOBALS['registry']->hasMethod('contacts/addField') &&
                    $GLOBALS['prefs']->getValue('add_source')) {
                    $this->_status[] = sprintf(_("The S/MIME certificate of %s: "), @htmlspecialchars($subject, ENT_COMPAT, NLS::getCharset())) .
                        $this->_contents->linkViewJS($subpart, 'view_attach', _("View"), '', null, array('viewkey' => 1)) . '/' .
                        Horde::link('#', '', null, null, $this->_impsmime->savePublicKeyURL($sig_result->cert) . ' return false;') . _("Save in your Address Book") . '</a>';
                    $text .= $this->_outputStatus();
                }
            }
        }

        return array();
    }

    /**
     * Generates HTML output for 'multipart/encrypted',
     * 'application/pkcs7-mime' and
     * 'application/x-pkcs7-mime' MIME_Parts.
     *
     * @return string  The HTML output.
     */
    protected function _outputSMIMEEncrypted()
    {
        $active = $GLOBALS['prefs']->getValue('use_smime');
        $mime = &$this->mime_part;
        $mimetype = $mime->getType();
        $msg = '';

        $this->_initStatus($this->getIcon($mime->getType()), _("S/MIME"));
        $this->_status[] = _("This message has been encrypted via S/MIME.");

        if (!$active) {
            $this->_status[] = _("S/MIME support is not currently enabled so the message is unable to be decrypted.");
            return $this->_outputStatus();
        }

        if (!$this->_impsmime->getPersonalPrivateKey()) {
            $this->_status[] = _("No personal private key exists so the message is unable to be decrypted.");
            return $this->_outputStatus();
        }

        /* Make sure we have a passphrase. */
        $passphrase = $this->_impsmime->getPassphrase();
        if ($passphrase === false) {
            if (isset($_SESSION['imp']['viewmode']) &&
                ($_SESSION['imp']['viewmode'] == 'imp')) {
                // TODO: Fix to work with DIMP
                $url = $this->_impsmime->getJSOpenWinCode('open_passphrase_dialog');
                $this->_status[] = Horde::link('#', _("You must enter the passphrase for your S/MIME private key to view this message"), null, null, $url . ' return false;') . '<em>' . _("You must enter the passphrase for your S/MIME private key to view this message") . '</em></a>.';
                $msg .= $this->_outputStatus() .
                    '<script type="text/javascript">' . $url . ';</script>';
            }
            return $msg;
        }

        $raw_text = $this->_getRawSMIMEText();
        $decrypted_data = $this->_impsmime->decryptMessage($raw_text);

        if (is_a($decrypted_data, 'PEAR_Error')) {
            $this->_status[] = $decrypted_data->getMessage();
            return $this->_outputStatus();
        }

        /* We need to check if this is a signed/encrypted message. */
        $mime_message = Horde_Mime_Message::parseMessage($decrypted_data);
        if ($mime_message) {
            /* Check for signed and encoded data. */
            if (in_array($mime_message->getType(), array('multipart/signed', 'application/pkcs7-mime', 'application/x-pkcs7-mime'))) {
                $mime_message->setContents($decrypted_data);
                $mime_message->splitContents();
                $mime_message->setInformation('smime_signenc', true);
                if (($from = $this->_headers->getValue('from'))) {
                    $mime_message->setInformation('smime_from', $from);
                }
            } else {
                $msg .= $this->_outputStatus();
            }

            /* We need to stick the output into a IMP_Contents object. */
            $mc = new IMP_Contents($mime_message, array('download' => 'download_attach', 'view' => 'view_attach'), array(&$this->_contents));
            $mc->buildMessage();
            $msg .= '<table cellpadding="0" cellspacing="0">' . $mc->getMessage(true) . '</table>';
        } else {
            require_once 'Horde/Text/Filter.php';
            $msg .= $this->_outputStatus() .
                '<span class="fixed">' . Text_Filter::filter($decrypted_data, 'text2html', array('parselevel' => TEXT_HTML_SYNTAX)) . '</span>';
        }

        return $msg;
    }

    /**
     * Return text/html as the content-type.
     *
     * @return string  "text/html" constant.
     */
    public function getType()
    {
        return 'text/html; charset=' . NLS::getCharset();
    }

    /**
     * Get the headers of the S/MIME message.
     */
    protected function _getRawSMIMEText()
    {
        $mime->setContents($this->_contents->getBody());
        if (is_a($this->_contents, 'IMP_Contents') &&
            (($mime->getMIMEId() == 0) ||
             ($mime->splitContents() == false))) {
            $this->_headers = $this->_contents->getHeaderOb();
            return $this->_contents->fullMessageText();
        } else {
            $header_text = $mime->getCanonicalContents();
            $header_text = substr($header_text, 0, strpos($header_text, "\r\n\r\n"));
            $this->_headers = MIME_Headers::parseHeaders($header_text);

            $mime_headers = new MIME_Headers();
            foreach (array('Content-Type', 'From', 'To') as $val) {
                $tmp = $this->_headers->getValue($val);
                if (!empty($tmp)) {
                    $mime_headers->addHeader($val, $tmp);
                }
            }

            return $mime_headers->toString() . $mime->toCanonicalString();
        }
    }

    /**
     * Generates HTML output for the S/MIME signature test.
     *
     * @param string $result  Result string of the S/MIME output concerning
     *                        the signature test.
     * @param string $email   The email of the sender.
     *
     * @return string  The HTML output.
     */
    protected function _outputSMIMESignatureTest($result, $email)
    {
        $text = '';

        if (is_a($result, 'PEAR_Error')) {
            if ($result->getCode() == 'horde.warning') {
                $this->_initStatus($GLOBALS['registry']->getImageDir('horde') . '/alerts/warning.png', _("Warning"));
            } else {
                $this->_initStatus($GLOBALS['registry']->getImageDir('horde') . '/alerts/error.png', _("Error"));
            }
            $result = $result->getMessage();
        } else {
            $this->_initStatus($GLOBALS['registry']->getImageDir('horde') . '/alerts/success.png', _("Success"));
            /* This message has been verified but there was no output
               from the PGP program. */
            if (empty($result) || ($result === true)) {
               $email = (is_array($email)) ? implode(', ', $email): $email;
               $result = sprintf(_("The message has been verified. Sender: %s."), htmlspecialchars($email));
            }
        }

        require_once 'Horde/Text/Filter.php';

        $this->_status[] = Text_Filter::filter($result, 'text2html', array('parselevel' => TEXT_HTML_NOHTML));

        return $this->_outputStatus();
    }

    /**
     * Determine the S/MIME type of the message.
     *
     * @return string  Either 'encrypted' or 'signed'.
     */
    protected function _getSMIMEType()
    {
        switch ($this->_mimepart->getType()) {
        case 'application/pkcs7-mime':
        case 'application/x-pkcs7-mime':
            $smime_type = $this->_mimepart->getContentTypeParameter('smime-type');
            if ($smime_type == 'signed-data') {
                return 'signed';
            } elseif (!$smime_type || ($smime_type == 'enveloped-data')) {
                return 'encrypted';
            }
            break;

        case 'multipart/signed':
        case 'application/pkcs7-signature':
        case 'application/x-pkcs7-signature':
            return 'signed';
        }
    }
}
