<?php
/**
 * The IMP_Horde_Mime_Viewer_smime class allows viewing/decrypting of S/MIME
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
class IMP_Horde_Mime_Viewer_smime extends Horde_Mime_Viewer_Driver
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
            if (is_a($openssl_check, 'PEAR_Error')) {
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
// TODO
            $raw_text = Horde_Imap_Client::removeBareNewlines($this->_params['contents']->getBodyPart($signed_id, array('mimeheaders' => true)));
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

        $raw_text = Horde_Imap_Client::removeBareNewlines($this->_params['contents']->getBodyPart($signed_id, array('mimeheaders' => true)));
        $sig_result = null;

        if ($GLOBALS['prefs']->getValue('smime_verify') ||
            Util::getFormData('smime_verify_msg')) {
            $sig_result = $this->_impsmime->verifySignature($raw_text);
        } elseif (isset($_SESSION['imp']['viewmode']) &&
                  ($_SESSION['imp']['viewmode'] == 'imp')) {
            // TODO: Fix to work with DIMP
            $status[] = Horde::link(Util::addParameter(Horde::selfUrl(true), 'smime_verify_msg', 1)) . _("Click HERE to verify the message.") . '</a>';
            return $ret;
        }

        $subpart = $this->_params['contents']->getBodyPart($sig_id);
        if (!isset($subpart)) {
            $msg_data = $this->_impsmime->extractSignedContents($raw_text);
            if (is_a($msg_data, 'PEAR_Error')) {
                $this->_status[] = $msg_data->getMessage();
                $mime_message = $this->_mimepart;
            } else {
                $mime_message = Horde_Mime_Part::parseMessage($msg_data);
            }
        }

        $graphicsdir = $GLOBALS['registry']->getImageDir('horde');

        if (is_a($sig_result->result, 'PEAR_Error')) {
            $ret[$base_id]['status'][0]['icon'] = ($sig_result->result->getCode() == 'horde.warning')
                ? Horde::img('alerts/warning.png', _("Warning"), null, $graphicsdir)
                : Horde::img('alerts/error.png', _("Error"), null, $graphicsdir);
            $status[] = $sig_result->result->getMessage();
        } else {
            $ret[$base_id]['status'][0]['icon'] = Horde::img('alerts/success.png', _("Success"), null, $graphicsdir);

            /* This message has been verified but there was no output
             * from the PGP program. */
            if (empty($sig_result->result) || ($sig_result->result === true)) {
                $email = (is_array($sig_result->email))
                    ? implode(', ', $sig_result->email)
                    : $sig_result->email;
                $status[] = sprintf(_("The message has been verified. Sender: %s."), htmlspecialchars($email));
            }

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
                } else {
                    $subject = null;
                }

                if (isset($subpart) &&
                    !empty($subject) &&
                    $GLOBALS['registry']->hasMethod('contacts/addField') &&
                    $GLOBALS['prefs']->getValue('add_source')) {
                    $status[] = sprintf(_("The S/MIME certificate of %s: "), @htmlspecialchars($subject, ENT_COMPAT, NLS::getCharset())) . $this->_params['contents']->linkViewJS($subpart, 'view_attach', _("View"), array('params' => array('viewkey' => 1))) . '/' . Horde::link('#', '', null, null, $this->_impsmime->savePublicKeyURL($sig_result->cert) . ' return false;') . _("Save in your Address Book") . '</a>';
                }
            }
        }

        return $ret;
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
