<?php
/**
 * The IMP_Horde_Mime_Viewer_Smime class allows viewing/decrypting of S/MIME
 * messages (RFC 2633).
 *
 * This class handles the following MIME types:
 *   application/pkcs7-mime
 *   application/x-pkcs7-mime
 *   application/pkcs7-signature (in multipart/signed part)
 *   application/x-pkcs7-signature (in multipart/signed part)
 *
 * This class may add the following parameters to the URL:
 *   'smime_verify_msg' - (boolean) Do verification of S/MIME message.
 *   'view_smime_key' - (boolean) Display the S/MIME Key.
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
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
     * This driver's display capabilities.
     *
     * @var array
     */
    protected $_capability = array(
        'full' => false,
        'info' => false,
        'inline' => true,
        'raw' => false
    );

    /**
     * Metadata for the current viewer/data.
     *
     * @var array
     */
    protected $_metadata = array(
        'compressed' => false,
        'embedded' => true,
        'forceinline' => true
    );

    /**
     * IMP_Crypt_Smime object.
     *
     * @var IMP_Crypt_Smime
     */
    protected $_impsmime = null;

    /**
     * Cached data.
     *
     * @var array
     */
    static protected $_cache = array();

    /**
     * Init the S/MIME Horde_Crypt object.
     */
    protected function _initSmime()
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
     * Return the rendered inline version of the Horde_Mime_Part object.
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
     */
    protected function _renderInline()
    {
        /* Check to see if S/MIME support is available. */
        $this->_initSmime();

        if (Horde_Util::getFormData('view_smime_key')) {
            return $this->_outputSmime();
        }

        if (is_null($this->_impsmime)) {
            $this->_impsmime = false;
        } else {
            /* We need to insert JavaScript code now if S/MIME support is
             * active. */
            Horde::addScriptFile('imp.js', 'imp');
        }

        $id = $this->_mimepart->getMimeId();

        switch ($this->_mimepart->getType()) {
        case 'multipart/signed';
            if (!in_array($this->_mimepart->getContentTypeParameter('protocol'), array('application/pkcs7-signature', 'application/x-pkcs7-signature'))) {
                return array();
            }
            $this->_parseSignedData(true);
            // Fall-through

        case 'application/pkcs7-mime':
        case 'application/x-pkcs7-mime':
            if (isset(self::$_cache[$id])) {
                return array(
                    $id => array(
                        'data' => null,
                        'status' => self::$_cache[$id]['status'],
                        'type' => 'text/plain; charset=' . Horde_Nls::getCharset(),
                        'wrap' => self::$_cache[$id]['wrap']
                    )
                );
            }
            // Fall-through

        default:
            return array();
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

        // 'smime-type' must be 'enveloped-data' or 'signed-data'
        switch ($this->_mimepart->getContentTypeParameter('smime-type')) {
        case 'enveloped-data':
            return $this->_parseEnvelopedData();

        case 'signed-data':
            return $this->_parseSignedData();

        default:
            return null;
        }
    }

    /**
     * Parse enveloped (encrypted) data.
     *
     * @return mixed  See self::_getEmbeddedMimeParts().
     */
    protected function _parseEnvelopedData()
    {
        $base_id = $this->_mimepart->getMimeId();

        /* Initialize inline data. */
        self::$_cache[$base_id] = array(
            'status' => array(
                array(
                    'icon' => Horde::img('mime/encryption.png', 'S/MIME'),
                    'text' => array(_("The data in this part has been encrypted via S/MIME."))
                )
            ),
            'wrap' => ''
        );
        $status = &self::$_cache[$base_id]['status'][0]['text'];

        /* Is PGP active? */
        $this->_initSmime();
        if (empty($this->_impsmime)) {
            $status[] = _("S/MIME support is not currently enabled so the data is unable to be decrypted.");
            return null;
        }

        if (!$this->_impsmime->getPersonalPrivateKey()) {
            $status[] = _("No personal private key exists so the data is unable to be decrypted.");
            return null;
        }

        /* Make sure we have a passphrase. */
        $passphrase = $this->_impsmime->getPassphrase();
        if (is_null($passphrase)) {
            $status[] = Horde::link('#', '', '', '', IMP::passphraseDialogJS('SMIMEPersonal') . ';return false;') . _("You must enter the passphrase for your S/MIME private key to view this data.") . '</a>';
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

        self::$_cache[$base_id]['wrap'] = 'mimePartWrapValid';

        $new_part = Horde_Mime_Part::parseMessage($decrypted_data, array('forcemime' => true));

        $hdrs = $this->_params['contents']->getHeaderOb();
        $new_part->setMetadata('imp-smime-from', $hdrs->getValue('from'));

        return $new_part;
    }

    /**
     * Parse signed data.
     *
     * @param boolean $sig_only  Only do signature checking?
     *
     * @return mixed  See self::_getEmbeddedMimeParts().
     */
    protected function _parseSignedData($sig_only = false)
    {
        $partlist = array_keys($this->_mimepart->contentTypeMap());
        $base_id = reset($partlist);
        $sig_id = Horde_Mime::mimeIdArithmetic(next($partlist), 'next');
        $graphicsdir = $GLOBALS['registry']->getImageDir('horde');

        /* Initialize inline data. */
        self::$_cache[$base_id] = array(
            'status' => array(
                array(
                    'icon' => Horde::img('mime/encryption.png', 'S/MIME'),
                    'text' => array(_("The data in this part has been digitally signed via S/MIME."))
                )
            ),
            'wrap' => 'mimePartWrap'
        );
        $status = &self::$_cache[$base_id]['status'][0]['text'];

        if (!$GLOBALS['prefs']->getValue('use_smime')) {
            $status[] = _("S/MIME support is not enabled so the digital signature is unable to be verified.");
            return null;
        }

        if ($this->_params['contents']->isEmbedded($base_id)) {
            $hdrs = new Horde_Mime_Headers();
            $hdrs->addHeader('From', $this->_mimepart->getMetadata('imp-smime-from'));
            $stream = $this->_mimepart->toString(array('headers' => $hdrs, 'stream' => true));
        } else {
            $stream = $base_id
                ? $this->_params['contents']->getBodyPart($base_id, array('mimeheaders' => true, 'stream' => true))
                : $this->_params['contents']->fullMessageText(array('stream' => true));
        }

        $raw_text = $this->_mimepart->replaceEOL($stream, Horde_Mime_Part::RFC_EOL);

        $this->_initSmime();
        $sig_result = null;

        if ($GLOBALS['prefs']->getValue('smime_verify') ||
            Horde_Util::getFormData('smime_verify_msg')) {
            try {
                $sig_result = $this->_impsmime->verifySignature($raw_text);
                self::$_cache[$base_id]['status'][0]['icon'] = Horde::img('alerts/success.png', _("Success"), null, $graphicsdir);
                self::$_cache[$base_id]['wrap'] = 'mimePartWrapValid';

                /* This data has been verified but there was no output from
                 * the S/MIME program. */
                if (empty($sig_result->result) ||
                    ($sig_result->result === true)) {
                    $email = is_array($sig_result->email)
                        ? implode(', ', $sig_result->email)
                        : $sig_result->email;
                    $status[] = sprintf(_("The data has been verified. Sender: %s."), htmlspecialchars($email));
                }

                if (!empty($sig_result->cert)) {
                    $cert = $this->_impsmime->parseCert($sig_result->cert);
                    if (isset($cert['certificate']['subject']['CommonName'])) {
                        $subject = $cert['certificate']['subject']['CommonName'];
                    } elseif (isset($cert['certificate']['subject']['Email'])) {
                        $subject = $cert['certificate']['subject']['Email'];
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
            } catch (Horde_Exception $e) {
                self::$_cache[$base_id]['status'][0]['icon'] = ($e->getCode() == 'horde.warning')
                    ? Horde::img('alerts/warning.png', _("Warning"), null, $graphicsdir)
                    : Horde::img('alerts/error.png', _("Error"), null, $graphicsdir);
                self::$_cache[$base_id]['wrap'] = 'mimePartWrapInvalid';
                $status[] = $e->getMessage();
            }
        } else {
            switch ($_SESSION['imp']['view']) {
            case 'imp':
                $status[] = Horde::link(IMP::selfUrl()->add('smime_verify_msg', 1)) . _("Click HERE to verify the data.") . '</a>';
                break;

            case 'dimp':
                $status[] = Horde::link('#', '', 'smimeVerifyMsg') . _("Click HERE to verify the data.") . '</a>';
                break;
            }
        }

        if ($sig_only) {
            return;
        }

        $subpart = $this->_params['contents']->getMIMEPart($sig_id);
        if (empty($subpart)) {
            try {
                $msg_data = $this->_impsmime->extractSignedContents($raw_text);
                $subpart = Horde_Mime_Part::parseMessage($msg_data, array('forcemime' => true));
            } catch (Horde_Exception $e) {
                $this->_status[] = $e->getMessage();
                return null;
            }
        }

        return $subpart;
    }

    /**
     * Generates HTML output for the S/MIME key.
     *
     * @return string  The HTML output.
     */
    protected function _outputSmimeKey()
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

}
