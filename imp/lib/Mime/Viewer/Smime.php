<?php
/**
 * The IMP_Mime_Viewer_Smime class allows viewing/decrypting of S/MIME
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
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Mike Cochrane <mike@graftonhall.co.nz>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Mime_Viewer_Smime extends Horde_Mime_Viewer_Base
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
                $this->_impsmime = $GLOBALS['injector']->getInstance('IMP_Crypt_Smime');
                $this->_impsmime->checkForOpenSSL();
            } catch (Horde_Exception $e) {
                $this->_impsmime = null;
            }
        }
    }

    /**
     * Return the rendered inline version of the Horde_Mime_Part object.
     *
     * @return array  See parent::render().
     */
    protected function _renderInline()
    {
        /* Check to see if S/MIME support is available. */
        $this->_initSmime();

        if ($GLOBALS['injector']->getInstance('Horde_Variables')->view_smime_key) {
            return $this->_outputSmimeKey();
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
                $ret = array(
                    $id => array(
                        'data' => null,
                        'status' => self::$_cache[$id]['status'],
                        'type' => 'text/plain; charset=' . $this->getConfigParam('charset'),
                        'wrap' => self::$_cache[$id]['wrap']
                    )
                );
                if (isset(self::$_cache[$id]['sig'])) {
                    $ret[self::$_cache[$id]['sig']] = null;
                }
                return $ret;
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
        case 'signed-data':
            return $this->_parseSignedData();

        case 'enveloped-data':
        default:
            /* Thunderbird bug: it doesn't include the smime-type parameter
             * for 'enveloped-data'. So do explicit check for enveloped
             * data. */
            return $this->_parseEnvelopedData();
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
        $status = new IMP_Mime_Status(_("The data in this part has been encrypted via S/MIME."));
        $status->icon('mime/encryption.png', 'S/MIME');
        self::$_cache[$base_id] = array(
            'status' => $status,
            'wrap' => ''
        );

        /* Is PGP active? */
        $this->_initSmime();
        if (empty($this->_impsmime)) {
            $status->addText(_("S/MIME support is not currently enabled so the data is unable to be decrypted."));
            return null;
        }

        if (!$this->_impsmime->getPersonalPrivateKey()) {
            $status->addText(_("No personal private key exists so the data is unable to be decrypted."));
            return null;
        }

        /* Make sure we have a passphrase. */
        $passphrase = $this->_impsmime->getPassphrase();
        if ($passphrase === false) {
            $imple = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Imple')->create('IMP_Ajax_Imple_PassphraseDialog', array(
                'type' => 'smimePersonal'
            ));
            $status->addText(Horde::link('#', '', '', '', '', '', '', array('id' => $imple->getDomId())) . _("You must enter the passphrase for your S/MIME private key to view this data.") . '</a>');
            return null;
        }

        $raw_text = $this->_getPartStream($this->_mimepart->getMimeId());

        try {
            $decrypted_data = $this->_impsmime->decryptMessage($this->_mimepart->replaceEOL($raw_text, Horde_Mime_Part::RFC_EOL));
        } catch (Horde_Exception $e) {
            $status->addText($e->getMessage());
            return null;
        }

        self::$_cache[$base_id]['wrap'] = 'mimePartWrapValid';

        $new_part = Horde_Mime_Part::parseMessage($decrypted_data, array(
            'forcemime' => true
        ));

        $hdrs = $this->getConfigParam('imp_contents')->getHeader();
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

        /* Initialize inline data. */
        $status = new IMP_Mime_Status(_("The data in this part has been digitally signed via S/MIME."));
        $status->icon('mime/encryption.png', 'S/MIME');

        self::$_cache[$base_id] = array(
            'sig' => $sig_id,
            'status' => $status,
            'wrap' => 'mimePartWrap'
        );

        if (!$GLOBALS['prefs']->getValue('use_smime')) {
            $status->addText(_("S/MIME support is not enabled so the digital signature is unable to be verified."));
            return null;
        }

        $imp_contents = $this->getConfigParam('imp_contents');
        if ($imp_contents->isEmbedded($base_id)) {
            $hdrs = new Horde_Mime_Headers();
            $hdrs->addHeader('From', $this->_mimepart->getMetadata('imp-smime-from'));

            $stream = $this->_mimepart->toString(array(
                'headers' => $hdrs,
                'stream' => true
            ));
        } else {
            $stream = $this->_getPartStream($base_id);
        }

        $raw_text = $this->_mimepart->replaceEOL($stream, Horde_Mime_Part::RFC_EOL);

        $this->_initSmime();
        $sig_result = null;

        if ($GLOBALS['prefs']->getValue('smime_verify') ||
            $GLOBALS['injector']->getInstance('Horde_Variables')->smime_verify_msg) {
            try {
                $sig_result = $this->_impsmime->verifySignature($raw_text);
                if ($sig_result->verify) {
                    $status->action(IMP_Mime_Status::SUCCESS);
                } else {
                    $status->action(IMP_Mime_Status::WARNING);
                }
                self::$_cache[$base_id]['wrap'] = 'mimePartWrapValid';

                $email = is_array($sig_result->email)
                    ? implode(', ', $sig_result->email)
                    : $sig_result->email;

                $status->addText($sig_result->msg);

                if (!empty($sig_result->cert)) {
                    $cert = $this->_impsmime->parseCert($sig_result->cert);
                    if (isset($cert['certificate']['subject']['CommonName'])) {
                        $email = $cert['certificate']['subject']['CommonName'] . ' (' . trim($email) . ')';
                    }
                }

                if (strlen($email)) {
                    $status->addText(sprintf(_("Sender: %s"), htmlspecialchars($email)));
                }

                if (!empty($sig_result->cert) &&
                    isset($sig_result->email) &&
                    $GLOBALS['registry']->hasMethod('contacts/addField') &&
                    $GLOBALS['prefs']->getValue('add_source')) {
                    try {
                        $this->_impsmime->getPublicKey($sig_result->email);
                    } catch (Horde_Exception $e) {
                        $imple = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Imple')->create('IMP_Ajax_Imple_ImportEncryptKey', array(
                            'mailbox' => $imp_contents->getMailbox(),
                            'mime_id' => $base_id,
                            'type' => 'smime',
                            'uid' => $imp_contents->getUid()
                        ));
                        $status->addText(Horde::link('#', '', '', '', '', '', '', array('id' => $imple->getDomId())) . _("Save the certificate to your Address Book.") . '</a>');
                    }
                    $status->addText($imp_contents->linkViewJS($this->_mimepart, 'view_attach', _("View certificate details."), array('params' => array('mode' => IMP_Contents::RENDER_INLINE, 'view_smime_key' => 1))));
                }
            } catch (Horde_Exception $e) {
                $status->action(IMP_Mime_Status::ERROR);
                self::$_cache[$base_id]['wrap'] = 'mimePartWrapInvalid';
                $status->addText($e->getMessage());
            }
        } else {
            switch ($GLOBALS['registry']->getView()) {
            case Horde_Registry::VIEW_BASIC:
                $status->addText(Horde::link(IMP::selfUrl()->add('smime_verify_msg', 1)) . _("Click HERE to verify the data.") . '</a>');
                break;

            case Horde_Registry::VIEW_DYNAMIC:
                $status->addText(Horde::link('#', '', 'smimeVerifyMsg') . _("Click HERE to verify the data.") . '</a>');
                break;
            }
        }

        if ($sig_only) {
            return;
        }

        $subpart = $imp_contents->getMIMEPart($sig_id);
        if (empty($subpart)) {
            try {
                $msg_data = $this->_impsmime->extractSignedContents($raw_text);
                $subpart = Horde_Mime_Part::parseMessage($msg_data, array('forcemime' => true));
            } catch (Horde_Exception $e) {
                $status->addText($e->getMessage());
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

        $raw_text = $this->_getPartStream($this->_mimepart->getMimeId());

        try {
            $sig_result = $this->_impsmime->verifySignature($this->_mimepart->replaceEOL($raw_text, Horde_Mime_Part::RFC_EOL));
        } catch (Horde_Exception $e) {
            return array();
        }

        return array(
            $this->_mimepart->getMimeId() => array(
                'data' => $this->_impsmime->certToHTML($sig_result->cert),
                'type' => 'text/html; charset=' . $this->getConfigParam('charset')
            )
        );
    }

    /**
     */
    protected function _getPartStream($id)
    {
        return $id
            ? $this->getConfigParam('imp_contents')->getBodyPart($id, array('mimeheaders' => true, 'stream' => true))
            : $this->getConfigParam('imp_contents')->fullMessageText();
    }

}
