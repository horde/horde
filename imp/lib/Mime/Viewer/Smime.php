<?php
/**
 * Copyright 2002-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2000-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Renderer for viewing/decrypting of S/MIME v3.2 messages (RFC 5751).
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
 * @author    Mike Cochrane <mike@graftonhall.co.nz>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2000-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
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
        case 'multipart/signed':
            if (!in_array($this->_mimepart->getContentTypeParameter('protocol'), array('application/pkcs7-signature', 'application/x-pkcs7-signature'))) {
                return array();
            }
            $this->_parseSignedData(true);
            // Fall-through

        case 'application/pkcs7-mime':
        case 'application/x-pkcs7-mime':
            $cache = $this->getConfigParam('imp_contents')->getViewCache();

            if (isset($cache->smime[$id])) {
                $ret = array(
                    $id => array(
                        'data' => null,
                        'status' => $cache->smime[$id]['status'],
                        'type' => 'text/plain; charset=' . $this->getConfigParam('charset'),
                        'wrap' => $cache->smime[$id]['wrap']
                    )
                );
                if (isset($cache->smime[$id]['sig'])) {
                    $ret[$cache->smime[$id]['sig']] = null;
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

        switch ($this->_getSmimeType($this->_mimepart)) {
        case 'signed-data':
            return $this->_parseSignedData();

        case 'enveloped-data':
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
        $status = new IMP_Mime_Status(
            $this->_mimepart,
            _("The data in this part has been encrypted via S/MIME.")
        );
        $status->icon('mime/encryption.png', 'S/MIME');

        $cache = $this->getConfigParam('imp_contents')->getViewCache();
        $cache->smime[$base_id] = array(
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

        $cache->smime[$base_id]['wrap'] = 'mimePartWrapValid';

        $new_part = Horde_Mime_Part::parseMessage($decrypted_data, array(
            'forcemime' => true
        ));

        switch ($new_part->getType()) {
        case 'application/pkcs7-mime':
        case 'application/x-pkcs7-mime':
            $signed_data = ($this->_getSmimeType($new_part) === 'signed-data');
            break;

        case 'multipart/signed':
            $signed_data = true;
            break;

        default:
            $signed_data = false;
            break;
        }

        if ($signed_data) {
            $hdrs = $this->getConfigParam('imp_contents')->getHeader();

            $data = new Horde_Stream_Temp();
            $data->add(
                'From:' . $hdrs['From'] . "\n" .
                $decrypted_data
            );

            $new_part->setMetadata('imp-smime-decrypt', $data);
            $new_part->setContents($decrypted_data, array(
                'encoding' => 'binary'
            ));
        }

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
        $iterator = $this->_mimepart->partIterator(false);
        $iterator->rewind();

        $base_id = $iterator->current()->getMimeId();
        $iterator->next();
        $data_id = $iterator->current()->getMimeId();

        $id_ob = new Horde_Mime_Id($data_id);
        $sig_id = $id_ob->idArithmetic($id_ob::ID_NEXT);

        /* Initialize inline data. */
        $status = new IMP_Mime_Status(
            $this->_mimepart,
            _("The data in this part has been digitally signed via S/MIME.")
        );
        $status->icon('mime/encryption.png', 'S/MIME');

        $cache = $this->getConfigParam('imp_contents')->getViewCache();
        $cache->smime[$base_id] = array(
            'sig' => $sig_id,
            'status' => $status,
            'wrap' => 'mimePartWrap'
        );

        if (!$GLOBALS['prefs']->getValue('use_smime')) {
            $status->addText(_("S/MIME support is not enabled so the digital signature is unable to be verified."));
            return null;
        }

        /* Sanity checking to make sure MIME structure is correct. */
        if (!$this->_mimepart[$sig_id]) {
            $status->action(IMP_Mime_Status::ERROR);
            $cache->smime[$base_id]['wrap'] = 'mimePartWrapInvalid';
            $status->addText(_("Invalid S/MIME data."));
            /* This will suppress displaying the invalid part. */
            $cache->smime[$base_id]['sig'] = $data_id;
            return null;
        }

        $imp_contents = $this->getConfigParam('imp_contents');
        $stream = $imp_contents->isEmbedded($base_id)
            ? $this->_mimepart->getMetadata('imp-smime-decrypt')->stream
            : $this->_getPartStream($base_id);
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
                $cache->smime[$base_id]['wrap'] = 'mimePartWrapValid';

                $email = is_array($sig_result->email)
                    ? implode(', ', $sig_result->email)
                    : $sig_result->email;

                $status->addText($sig_result->msg);

                if (!empty($sig_result->cert)) {
                    $cert = $this->_impsmime->parseCert($sig_result->cert);
                    if (isset($cert['certificate']['subject']['CommonName']) &&
                        (strcasecmp($email, $cert['certificate']['subject']['CommonName']) !== 0)) {
                        $email = $cert['certificate']['subject']['CommonName'] . ' (' . trim($email) . ')';
                    }
                }

                if (!empty($sig_result->cert) &&
                    isset($sig_result->email) &&
                    $GLOBALS['registry']->hasMethod('contacts/addField') &&
                    $GLOBALS['prefs']->getValue('add_source')) {
                    $status->addText(sprintf(_("Sender: %s"), $imp_contents->linkViewJS($this->_mimepart, 'view_attach', htmlspecialchars(strlen($email) ? $email : $sig_result->email), array(
                        'jstext' => _("View certificate details"),
                        'params' => array(
                            'mode' => IMP_Contents::RENDER_INLINE,
                            'view_smime_key' => 1
                        )
                    ))));

                    try {
                        $this->_impsmime->getPublicKey($sig_result->email);
                    } catch (Horde_Exception $e) {
                        $imple = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Imple')->create('IMP_Ajax_Imple_ImportEncryptKey', array(
                            'mime_id' => $base_id,
                            'muid' => strval($imp_contents->getIndicesOb()),
                            'type' => 'smime'
                        ));
                        $status->addText(Horde::link('#', '', '', '', '', '', '', array('id' => $imple->getDomId())) . _("Save the certificate to your Address Book.") . '</a>');
                    }
                } elseif (strlen($email)) {
                    $status->addText(sprintf(_("Sender: %s"), htmlspecialchars($email)));
                }
            } catch (Horde_Exception $e) {
                $status->action(IMP_Mime_Status::ERROR);
                $cache->smime[$base_id]['wrap'] = 'mimePartWrapInvalid';
                $status->addText($e->getMessage());
            }
        } else {
            $status->addMimeAction(
                'smimeVerifyMsg',
                _("Click to verify the data.")
            );
        }

        if ($sig_only) {
            return;
        }

        if (!($subpart = $imp_contents->getMimePart($sig_id))) {
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
            ? $this->getConfigParam('imp_contents')->getBodyPart($id, array('mimeheaders' => true, 'stream' => true))->data
            : $this->getConfigParam('imp_contents')->fullMessageText();
    }

    /**
     * Determines the S/MIME type of a part. Uses the smime-type content
     * parameter (if it exists), and falls back to ASN.1 parsing of data if
     * it doesn't exist.
     *
     * @param Horde_Mime_Part $part  MIME part with S/MIME data.
     *
     * @return string  'signed-data', 'enveloped-data', or null.
     */
    protected function _getSmimeType(Horde_Mime_Part $part)
    {
        if ($type = $part->getContentTypeParameter('smime-type')) {
            return strtolower($type);
        }

        if (!class_exists('File_ASN1')) {
            return null;
        }

        $asn1 = new File_ASN1();
        $decoded = $asn1->decodeBER($part->getContents());

        foreach ($decoded as $val) {
            if ($val['type'] == FILE_ASN1_TYPE_SEQUENCE) {
                foreach ($val['content'] as $val2) {
                    if ($val2['type'] == FILE_ASN1_TYPE_OBJECT_IDENTIFIER) {
                        /* ASN.1 values from STD 70/RFC 5652 - CMS syntax */
                        switch ($val2['content']) {
                        case '1.2.840.113549.1.7.2':
                            return 'signed-data';

                        case '1.2.840.113549.1.7.3':
                            return 'enveloped-data';

                        default:
                            // Other types not supported as of now.
                            return null;
                        }
                    }
                }
            }
        }

        return null;
    }

}
