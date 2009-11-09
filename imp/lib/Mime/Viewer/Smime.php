<?php
/**
 * The IMP_Horde_Mime_Viewer_Smime class allows viewing/decrypting of S/MIME
 * messages.
 * This class implements parts of RFC 2630, RFC 2632, and RFC 2633.
 *
 * This class handles the following MIME types:
 *   application/pkcs7-mime
 *   application/x-pkcs7-mime
 *   application/pkcs7-signature (in multipart/signed part)
 *   application/x-pkcs7-signature (in multipart/signed part)
 *
 * This class may add the following parameters to the URL:
 *   'smime_verify_msg' - (boolean) Do verification of S.
 *   'view_smime_key' - (boolean) Display the S/MIME Key.
 *
 * Copyright 2002-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Mime_Viewer
 */
class IMP_Horde_Mime_Viewer_Smime extends Horde_Mime_Viewer_Driver
{
    /**
     * Can this driver render various views?
     *
     * @var boolean
     */
    protected $_capability = array(
        'embedded' => true,
        'forceinline' => true,
        'full' => false,
        'info' => false,
        'inline' => true,
        'raw' => false
    );

    /**
     * IMP_Crypt_Smime object.
     *
     * @var IMP_Crypt_Smime
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
        $this->_initSMIME();

        if (Horde_Util::getFormData('view_smime_key')) {
            return $this->_outputSMIMEKey();
        }

        if (is_null($this->_impsmime)) {
            $this->_impsmime = false;
        } else {
            /* We need to insert JavaScript code now if S/MIME support is
             * active. */
            Horde::addScriptFile('imp.js', 'imp');
        }

        switch ($this->_mimepart->getType()) {
        case 'multipart/signed':
            return $this->_outputSMIMESigned();

        case 'application/pkcs7-mime':
        case 'application/x-pkcs7-mime':
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
        if (!in_array($this->_mimepart->getType(), array('application/pkcs7-mime', 'application/x-pkcs7-mime'))) {
            return null;
        }

        // 'smime-type' must be empty or 'enveloped-data'
        $smime_type = $this->_mimepart->getContentTypeParameter('smime-type');
        if ($smime_type == 'signed-data') {
            // TODO
            return null;
        }

        $base_id = $this->_mimepart->getMimeId();

        /* Initialize inline data. */
        self::$_inlinecache[$base_id] = array(
            $base_id => array(
                'data' => '',
                'status' => array(
                    array(
                        'icon' => Horde::img('mime/encryption.png', 'S/MIME'),
                        'text' => array(_("This message has been encrypted via S/MIME."))
                    )
                ),
                'type' => 'text/html; charset=' . Horde_Nls::getCharset()
            )
        );
        $status = &self::$_inlinecache[$base_id][$base_id]['status'][0]['text'];

        /* Is PGP active? */
        $this->_initSMIME();
        if (empty($this->_impsmime)) {
            $status[] = _("S/MIME support is not currently enabled so the message is unable to be decrypted.");
            return null;
        }

        if (!$this->_impsmime->getPersonalPrivateKey()) {
            $status[] = _("No personal private key exists so the message is unable to be decrypted.");
            return null;
        }

        /* Make sure we have a passphrase. */
        $passphrase = $this->_impsmime->getPassphrase();
        if ($passphrase === false) {
            $js_action = '';

            switch ($_SESSION['imp']['view'] == 'imp') {
            case 'dimp':
                $js_action = 'DimpCore.reloadMessage({});';
                // Fall through

            case 'imp':
                $status[] = Horde::link('#', '', '', '', IMP::passphraseDialogJS('SMIMEPersonal', $js_action) . ';return false;') . _("You must enter the passphrase for your S/MIME private key to view this message.") . '</a>';
                break;
            }
            return null;
        }

        $raw_text = $this->_mimepart->getMimeId()
            ? $this->_params['contents']->getBodyPart($this->_mimepart->getMimeId(), array('mimeheaders' => true, 'stream' => true))
            : $this->_params['contents']->fullMessageText();

        try {
            $decrypted_data = $this->_impsmime->decryptMessage($this->_mimepart->replaceEOL($raw_text, Horde_Mime_Part::RFC_EOL));
        } catch (Horde_Exception $e) {
            $status[] = $e->getMessage();
            return null;
        }

        return array($base_id => Horde_Mime_Part::parseMessage($decrypted_data));
    }

    /**
     * Generates HTML output for the S/MIME key.
     *
     * @return string  The HTML output.
     */
    protected function _outputSMIMEKey()
    {
        if (empty($this->_impsmime)) {
            return array();
        }

        $raw_text = $this->_mimepart->getMimeId()
            ? $this->_params['contents']->getBodyPart($this->_mimepart->getMimeId(), array('mimeheaders' => true, 'stream' => true))
            : $this->_params['contents']->fullMessageText();

        try {
            $sig_result = $this->_impsmime->verifySignature($this->_mimepart->replaceEOL($raw_text, Horde_Mime_Part::RFC_EOL));
        } catch (Horde_Exception $e) {
            return array();
        }

        return array(
            $this->_mimepart->getMimeId() => array(
                'data' => $this->_impsmime->certToHTML($sig_result->cert),
                'status' => array(),
                'type' => 'text/html; charset=' . Horde_Nls::getCharset()
            )
        );
    }

    /**
     * Init the S/MIME Horde_Crypt object.
     */
    protected function _initSMIME()
    {
        if (is_null($this->_impsmime) &&
            $GLOBALS['prefs']->getValue('use_smime')) {
            try {
                $this->_impsmime = Horde_Crypt::singleton(array('IMP', 'Smime'));
                $this->_impsmime->checkForOpenSSL();
            } catch (Horde_Exception $e) {
                $this->_impsmime = null;
            }
        }
    }

    /**
     * Generates HTML output for 'multipart/signed' MIME parts.
     *
     * @return array  TODO
     */
    protected function _outputSMIMESigned()
    {
        $partlist = array_keys($this->_mimepart->contentTypeMap());
        $base_id = reset($partlist);
        $sig_id = Horde_Mime::mimeIdArithmetic(next($partlist), 'next');

        $ret = array(
            $base_id => array(
                'data' => '',
                'status' => array(
                    array(
                        'icon' => Horde::img('mime/encryption.png', 'S/MIME'),
                        'text' => array(_("This message has been digitally signed via S/MIME."))
                    )
                ),
                'type' => 'text/html; charset=' . Horde_Nls::getCharset()
            ),
            $sig_id => null
        );
        $status = &$ret[$base_id]['status'][0]['text'];

        if (!$GLOBALS['prefs']->getValue('use_smime')) {
            $status[] = _("S/MIME support is not enabled so the digital signature is unable to be verified.");
            return $ret;
        }

        $stream = $base_id
            ? $this->_params['contents']->getBodyPart($base_id, array('mimeheaders' => true, 'stream' => true))
            : $this->_params['contents']->fullMessageText();
        $raw_text = $this->_mimepart->replaceEOL($stream, Horde_Mime_Part::RFC_EOL);

        $sig_result = null;

        if ($GLOBALS['prefs']->getValue('smime_verify') ||
            Horde_Util::getFormData('smime_verify_msg')) {
            try {
                $sig_result = $this->_impsmime->verifySignature($raw_text);
            } catch (Horde_Exception $e) {
                $ret[$base_id]['status'][0]['icon'] = ($e->getCode() == 'horde.warning')
                    ? Horde::img('alerts/warning.png', _("Warning"), null, $graphicsdir)
                    : Horde::img('alerts/error.png', _("Error"), null, $graphicsdir);
                $status[] = $e->getMessage();
                return $ret;
            }
        } else {
            switch ($_SESSION['imp']['view']) {
            case 'imp':
                $status[] = Horde::link(Horde_Util::addParameter(IMP::selfUrl(), 'smime_verify_msg', 1)) . _("Click HERE to verify the message.") . '</a>';
                break;

            case 'dimp':
                $status[] = Horde::link('#', '', 'smimeVerifyMsg') . _("Click HERE to verify the message.") . '</a>';
                break;
            }
            return $ret;
        }

        $subpart = $this->_params['contents']->getMIMEPart($sig_id);
        if (!isset($subpart)) {
            try {
                $msg_data = $this->_impsmime->extractSignedContents($raw_text);
                $subpart = Horde_Mime_Part::parseMessage($msg_data);
            } catch (Horde_Exception $e) {
                $this->_status[] = $e->getMessage();
                $subpart = $this->_mimepart;
            }
        }

        $graphicsdir = $GLOBALS['registry']->getImageDir('horde');

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

            if (!empty($subject) &&
                $GLOBALS['registry']->hasMethod('contacts/addField') &&
                $GLOBALS['prefs']->getValue('add_source')) {
                $status[] = sprintf(_("The S/MIME certificate of %s: "), @htmlspecialchars($subject, ENT_COMPAT, Horde_Nls::getCharset())) . $this->_params['contents']->linkViewJS($this->_mimepart, 'view_attach', _("View"), array('params' => array('mode' => IMP_Contents::RENDER_INLINE, 'view_smime_key' => 1))) . '/' . Horde::link('#', '', null, null, $this->_impsmime->savePublicKeyURL($sig_result->cert, $this->_params['contents']->getUid(), $sig_id) . ' return false;') . _("Save in your Address Book") . '</a>';
            }
        }

        return $ret;
    }

    /**
     * Generates output for encrypted S/MIME parts.
     *
     * @return array  TODO
     */
    protected function _outputSMIMEEncrypted()
    {
        $id = $this->_mimepart->getMimeId();
        return isset(self::$_inlinecache[$id])
            ? self::$_inlinecache[$id]
            : array();
    }
}
