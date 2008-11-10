<?php

require_once IMP_BASE . '/lib/Crypt/SMIME.php';

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
     * IMP_SMIME object.
     *
     * @var IMP_SMIME
     */
    protected $_impSmime;

    /**
     * Classwide cache for icons for status messages.
     *
     * @var string
     */
    protected $_icon = null;

    /**
     * Pointer to the IMP_Contents item.
     *
     * @var IMP_Contents
     */
    protected $_contents = null;

    /**
     * Classwide cache for status messages.
     *
     * @var array
     */
    protected $_status = array();

    /**
     * The MIME_Headers object for the message data.
     *
     * @var MIME_Headers
     */
    protected $_headers;

    /**
     * Some mailers set S/MIME messages to always be attachments.  However,
     * most of the time S/MIME is used to secure the contents of the message,
     * so displaying as an attachment makes no sense.  Therefore, force
     * viewing inline (or at least let Horde_Mime_Viewer/IMP_Contents make the
     * determination on whether the data can be viewed inline or not).
     *
     * @var boolean
     */
    protected $_forceinline = true;

    /**
     * Render out the currently set contents.
     *
     * @param array $params  An array with a reference to a IMP_Contents
     *                       object.
     *
     * @return string  The rendered text in HTML.
     */
    public function render($params)
    {
        /* Set the IMP_Contents class variable. */
        $this->_contents = &$params[0];

        $msg = '';

        if (empty($this->_impSmime)) {
            $this->_impSmime = new IMP_SMIME();
        }

        /* Check to see if S/MIME support is available. */
        $openssl_check = $this->_impSmime->checkForOpenSSL();
        if ($GLOBALS['prefs']->getValue('use_smime') &&
            !is_a($openssl_check, 'PEAR_Error')) {
            /* We need to insert JavaScript code now if S/MIME support is
               active. */
            $msg = Util::bufferOutput(array('Horde', 'addScriptFile'), 'prototype.js', 'horde', true);
            $msg .= Util::bufferOutput(array('Horde', 'addScriptFile'), 'popup.js', 'imp', true);
        }

        /* Get the type of message now. */
        $type = $this->_getSMIMEType();
        switch ($type) {
        case 'signed':
            $msg .= $this->_outputSMIMESigned();
            break;

        case 'encrypted':
            $msg .= $this->_outputSMIMEEncrypted();
            break;
        }

        return $msg;
    }

    /**
     * Generates HTML output for the S/MIME key in
     * 'application/pkcs7-signature' MIME_Parts.
     *
     * @return string  The HTML output.
     */
    protected function _outputSMIMEKey()
    {
        if (!$GLOBALS['prefs']->getValue('use_smime')) {
            return _("S/MIME support is not enabled.");
        } else {
            $mime = &$this->mime_part;
            $signenc = $mime->getInformation('smime_signenc');
            $raw_text = $this->_getRawSMIMEText();
            if ($signenc && $mime->getInformation('smime_from')) {
                $smime_from = $mime->getInformation('smime_from');
                $raw_text = "From: $smime_from\n" . $raw_text;
            }
            $sig_result = $this->_impSmime->verifySignature($raw_text);
            return $this->_impSmime->certToHTML($sig_result->cert);
        }
    }

    /**
     * Generates HTML output for 'multipart/signed',
     * 'application/pkcs7-signature' and
     * 'application/x-pkcs7-signature' MIME_Parts.
     *
     * @return string  The HTML output.
     */
    protected function _outputSMIMESigned()
    {
        if (Util::getFormData('viewkey')) {
            return $this->_outputSMIMEKey();
        }

        $cert = $text = '';
        $mime = &$this->mime_part;
        $mimetype = $mime->getType();
        $active = $GLOBALS['prefs']->getValue('use_smime');

        $signenc = $mime->getInformation('smime_signenc');
        if ($signenc) {
            $this->_status[] = _("This message has been encrypted via S/MIME.");
        }

        $this->_initStatus($this->getIcon($mimetype), _("S/MIME"));
        $this->_status[] = _("This message has been digitally signed via S/MIME.");

        if (!$active) {
            $this->_status[] = _("S/MIME support is not enabled so the digital signature is unable to be verified.");
        }

        /* Store S/MIME results in $sig_result. */
        $sig_result = null;
        if ($mimetype == 'multipart/signed') {
            if (!$signenc) {
                if (($mimeID = $mime->getMIMEId())) {
                    $mime->setContents($this->_contents->getBodyPart($mimeID));
                } else {
                    $mime->setContents($this->_contents->getBody());
                }
                $mime->splitContents();
            }

            /* Data that is signed appears in the first MIME subpart. */
            $signed_part = $mime->getPart($mime->getRelativeMIMEId(1));
            $signed_data = rtrim($signed_part->getCanonicalContents(), "\r");
            $mime_message = Horde_Mime_Message::parseMessage($signed_data);

            /* The S/MIME signature appears in the second MIME subpart. */
            $subpart = $mime->getPart($mime->getRelativeMIMEId(2));
            if (!$subpart ||
                !in_array($subpart->getType(), array('application/pkcs7-signature', 'application/x-pkcs7-signature'))) {
                $this->_status[] = _("This message does not appear to be in the correct S/MIME format.");
            }
        } elseif (!$active) {
            $this->_status[] = _("S/MIME support is not enabled so the contents of this signed message cannot be displayed.");
        }

        if ($active) {
            $raw_text = $this->_getRawSMIMEText();
            if ($signenc && $mime->getInformation('smime_from')) {
                $smime_from = $mime->getInformation('smime_from');
                $raw_text = "From: $smime_from\n" . $raw_text;
            }

            if ($GLOBALS['prefs']->getValue('smime_verify') ||
                Util::getFormData('smime_verify_msg')) {
                $sig_result = $this->_impSmime->verifySignature($raw_text);
            } elseif (isset($_SESSION['imp']['viewmode']) &&
                      ($_SESSION['imp']['viewmode'] == 'imp')) {
                // TODO: Fix to work with DIMP
                $this->_status[] = Horde::link(Util::addParameter(Horde::selfUrl(true), 'smime_verify_msg', 1)) . _("Click HERE to verify the message.") . '</a>';
            }

            if (!isset($subpart)) {
                $msg_data = $this->_impSmime->extractSignedContents($raw_text);
                if (is_a($msg_data, 'PEAR_Error')) {
                    $this->_status[] = $msg_data->getMessage();
                    $mime_message = $mime;
                } else {
                    $mime_message = Horde_Mime_Message::parseMessage($msg_data);
                }
            }

            $text = $this->_outputStatus();
            if ($sig_result !== null) {
                $text .= $this->_outputSMIMESignatureTest($sig_result->result, $sig_result->email);
                if (!empty($sig_result->cert)) {
                    $cert_details = $this->_impSmime->parseCert($sig_result->cert);
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
                            Horde::link('#', '', null, null, $this->_impSmime->savePublicKeyURL($sig_result->cert) . ' return false;') . _("Save in your Address Book") . '</a>';
                        $text .= $this->_outputStatus();
                    }
                }
            }
        }

        if (isset($mime_message)) {
            /* We need to stick the output into a IMP_Contents object. */
            $mc = new IMP_Contents($mime_message, array('download' => 'download_attach', 'view' => 'view_attach'), array(&$this->_contents));
            $mc->buildMessage();

            $text .= '<table cellpadding="0" cellspacing="0">' . $mc->getMessage(true) . '</table>';
        } else {
            $text = $this->_outputStatus();
        }

        return $text;
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

        if (!$this->_impSmime->getPersonalPrivateKey()) {
            $this->_status[] = _("No personal private key exists so the message is unable to be decrypted.");
            return $this->_outputStatus();
        }

        /* Make sure we have a passphrase. */
        $passphrase = $this->_impSmime->getPassphrase();
        if ($passphrase === false) {
            if (isset($_SESSION['imp']['viewmode']) &&
                ($_SESSION['imp']['viewmode'] == 'imp')) {
                // TODO: Fix to work with DIMP
                $url = $this->_impSmime->getJSOpenWinCode('open_passphrase_dialog');
                $this->_status[] = Horde::link('#', _("You must enter the passphrase for your S/MIME private key to view this message"), null, null, $url . ' return false;') . '<em>' . _("You must enter the passphrase for your S/MIME private key to view this message") . '</em></a>.';
                $msg .= $this->_outputStatus() .
                    '<script type="text/javascript">' . $url . ';</script>';
            }
            return $msg;
        }

        $raw_text = $this->_getRawSMIMEText();
        $decrypted_data = $this->_impSmime->decryptMessage($raw_text);

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
        $mime = &$this->mime_part;

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

    /* Various formatting helper functions. */
    protected function _initStatus($src, $alt = '')
    {
        if ($this->_icon === null) {
            $this->_icon = Horde::img($src, $alt, 'height="16" width="16"', '');
        }
    }

    protected function _outputStatus()
    {
        $output = '';
        if (!empty($this->_status)) {
            $output = $this->formatStatusMsg($this->_status, $this->_icon);
        }
        $this->_icon = null;
        $this->_status = array();
        return $output;
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
     * Render out attachment information.
     *
     * @param array $params  An array with a reference to a IMP_Contents
     *                       object.
     *
     * @return string  The rendered text in HTML.
     */
    public function renderAttachmentInfo($params)
    {
        $this->_contents = &$params[0];

        $type = $this->_getSMIMEType();
        switch ($type) {
            case 'signed':
                $this->_status[] = _("This message contains an attachment that has been digitally signed via S/MIME.");
                break;

            case 'encrypted':
                $this->_status[] = _("This message contains an attachment that has been encrypted via S/MIME.");
                break;
        }

        $this->_status[] = sprintf(_("Click %s to view the attachment in a separate window."), $this->_contents->linkViewJS($this->mime_part, 'view_attach', _("HERE"), _("View attachment in a separate window")));
        $this->_initStatus($this->getIcon($this->mime_part->getType()), _("S/MIME"));
        return $this->_outputStatus();
    }

    /**
     * Deterimne the S/MIME type of the message.
     *
     * @return string  Either 'encrypted' or 'signed'.
     */
    protected function _getSMIMEType()
    {
        $type = $this->mime_part->getType();
        if (in_array($type, array('application/pkcs7-mime', 'application/x-pkcs7-mime'))) {
            $smime_type = $this->mime_part->getContentTypeParameter('smime-type');
            if ($smime_type == 'signed-data') {
                return 'signed';
            } elseif (!$smime_type || ($smime_type == 'enveloped-data')) {
                return 'encrypted';
            }
        }

        switch ($type) {
        case 'multipart/signed':
        case 'application/pkcs7-signature':
        case 'application/x-pkcs7-signature':
            return 'signed';
        }
    }
}
