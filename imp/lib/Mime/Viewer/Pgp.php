<?php
/**
 * The IMP_Mime_Viewer_Pgp class allows viewing/decrypting of PGP
 * formatted messages.  This class implements RFC 3156.
 *
 * This class handles the following MIME types:
 *   application/pgp-encrypted (in multipart/encrypted part)
 *   application/pgp-keys
 *   application/pgp-signature (in multipart/signed part)
 *
 * This driver may add the following parameters to the URL:
 *   'pgp_verify_msg' - (boolean) Do verification of PGP signed data.
 *   'rawpgpkey' - (boolean) Display the PGP Public Key in raw, text format?
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Mime_Viewer_Pgp extends Horde_Mime_Viewer_Base
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
        /* This driver *does* render raw data, but only for
         * application/pgp-signature parts that have been processed by the
         * text/plain driver. This is handled via the canRender() function. */
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
     * The address of the sender.
     *
     * @var string
     */
    protected $_address = null;

    /**
     * Cached data.
     *
     * @var array
     */
    static protected $_cache = array();

    /**
     * Return the full rendered version of the Horde_Mime_Part object.
     *
     * @return array  See parent::render().
     */
    protected function _renderRaw()
    {
        $id = $this->_mimepart->getMimeId();

        $ret = array(
            $id => array(
                'data' => '',
                'status' => array(),
                'type' => 'text/plain; charset=' . $this->getConfigParam('charset')
            )
        );

        $parts = $GLOBALS['injector']->getInstance('IMP_Crypt_Pgp')->parsePGPData($this->_mimepart->getContents());
        foreach (array_keys($parts) as $key) {
            if ($parts[$key]['type'] == Horde_Crypt_Pgp::ARMOR_SIGNATURE) {
                $ret[$id]['data'] = implode("\r\n", $parts[$key]['data']);
                break;
            }
        }

        return $ret;
    }

    /**
     * Return the rendered inline version of the Horde_Mime_Part object.
     *
     * @return array  See parent::render().
     */
    protected function _renderInline()
    {
        $id = $this->_mimepart->getMimeId();

        if (Horde_Util::getFormData('rawpgpkey')) {
            return array(
                $id => array(
                    'data' => $this->_mimepart->getContents(),
                    'status' => array(),
                    'type' => 'text/plain; charset=' . $this->_mimepart->getCharset()
                )
            );
        }

        /* Determine the address of the sender. */
        if (is_null($this->_address)) {
            $headers = $this->getConfigParam('imp_contents')->getHeaderOb();
            $this->_address = Horde_Mime_Address::bareAddress($headers->getValue('from'));
        }

        switch ($this->_mimepart->getType()) {
        case 'application/pgp-keys':
            return $this->_outputPGPKey();

        case 'multipart/signed':
            return $this->_outputPGPSigned();

        case 'multipart/encrypted':
            if (isset(self::$_cache[$id])) {
                return array_merge(array(
                    $id => array(
                        'data' => null,
                        'status' => self::$_cache[$id]['status'],
                        'type' => 'text/plain; charset=' . $this->getConfigParam('charset'),
                        'wrap' => self::$_cache[$id]['wrap']
                    )
                ), self::$_cache[$id]['other']);
            }
            // Fall-through

        case 'application/pgp-encrypted':
        case 'application/pgp-signature':
        default:
            return array();
        }
    }

    /**
     * If this MIME part can contain embedded MIME part(s), and those part(s)
     * exist, return a representation of that data.
     *
     * @return mixed  A Horde_Mime_Part object representing the embedded data.
     *                Returns null if no embedded MIME part(s) exist.
     */
    protected function _getEmbeddedMimeParts()
    {
        if ($this->_mimepart->getType() != 'multipart/encrypted') {
            return null;
        }

        $partlist = array_keys($this->_mimepart->contentTypeMap());
        $base_id = reset($partlist);
        $version_id = next($partlist);
        $data_id = Horde_Mime::mimeIdArithmetic($version_id, 'next');

        self::$_cache[$base_id] = array(
            'status' => array(
                array(
                    'icon' => Horde::img('mime/encryption.png', 'PGP'),
                    'text' => array()
                )
            ),
            'other' => array(
                $version_id => null,
                $data_id => null
            ),
            'wrap' => ''
        );
        $status = &self::$_cache[$base_id]['status'][0]['text'];

        /* Is PGP active? */
        if (empty($GLOBALS['conf']['gnupg']['path']) ||
            !$GLOBALS['prefs']->getValue('use_pgp')) {
            $status[] = _("The data in this part has been encrypted via PGP, however, PGP support is disabled so the message cannot be decrypted.");
            return null;
        }

        /* PGP version information appears in the first MIME subpart. We
         * don't currently need to do anything with this information. The
         * encrypted data appears in the second MIME subpart. */
        $encrypted_part = $this->getConfigParam('imp_contents')->getMIMEPart($data_id);
        $encrypted_data = $encrypted_part->getContents();

        $symmetric_pass = $personal_pass = null;

        /* Check if this a symmetrically encrypted message. */
        try {
            $imp_pgp = $GLOBALS['injector']->getInstance('IMP_Crypt_Pgp');
            $symmetric = $imp_pgp->encryptedSymmetrically($encrypted_data);
            if ($symmetric) {
                $symmetric_id = $this->_getSymmetricID();
                $symmetric_pass = $imp_pgp->getPassphrase('symmetric', $symmetric_id);

                if (is_null($symmetric_pass)) {
                    $status[] = _("The data in this part has been encrypted via PGP.");

                    /* Ask for the correct passphrase if this is encrypted
                     * symmetrically. */
                    $imple = $GLOBALS['registry']->getInstance('Horde_Ajax_Imple')->getImple(array('imp', 'PassphraseDialog'), array(
                        'params' => array(
                            'symmetricid' => $symmetric_id
                        ),
                        'type' => 'pgpSymmetric'
                    ));
                    $status[] = Horde::link('#', '', '', '', '', '', '', array('id' => $imple->getPassphraseId())) . _("You must enter the passphrase used to encrypt this message to view it.") . '</a>';
                    return null;
                }
            }
        } catch (Horde_Exception $e) {
            Horde::logMessage($e, 'INFO');
            return null;
        }

        /* Check if this is a literal compressed message. */
        try {
            $info = $imp_pgp->pgpPacketInformation($encrypted_data);
        } catch (Horde_Exception $e) {
            Horde::logMessage($e, 'INFO');
            return null;
        }

        $literal = !empty($info['literal']);
        if ($literal) {
            $status[] = _("The data in this part has been compressed via PGP.");
        } else {
            $status[] = _("The data in this part has been encrypted via PGP.");

            if (!$symmetric) {
                if ($imp_pgp->getPersonalPrivateKey()) {
                    $personal_pass = $imp_pgp->getPassphrase('personal');
                    if (is_null($personal_pass)) {
                        /* Ask for the private key's passphrase if this is
                         * encrypted asymmetrically. */
                        $imple = $GLOBALS['registry']->getInstance('Horde_Ajax_Imple')->getImple(array('imp', 'PassphraseDialog'), array(
                            'type' => 'pgpPersonal'
                        ));
                        $status[] = Horde::link('#', '', '', '', '', '', '', array('id' => $imple->getPassphraseId())) . _("You must enter the passphrase for your PGP private key to view this message.") . '</a>';
                        return null;
                    }
                } else {
                    /* Output if there is no personal private key to decrypt
                     * with. */
                    $status[] = _("However, no personal private key exists so the message cannot be decrypted.");
                    return null;
                }
            }
        }

        try {
            if (!is_null($symmetric_pass)) {
                $decrypted_data = $imp_pgp->decryptMessage($encrypted_data, 'symmetric', $symmetric_pass);
            } elseif (!is_null($personal_pass)) {
                $decrypted_data = $imp_pgp->decryptMessage($encrypted_data, 'personal', $personal_pass);
            } else {
                $decrypted_data = $imp_pgp->decryptMessage($encrypted_data, 'literal');
            }
        } catch (Horde_Exception $e) {
            $status[] = _("The data in this part does not appear to be a valid PGP encrypted message. Error: ") . $e->getMessage();
            if (!is_null($symmetric_pass)) {
                $imp_pgp->unsetPassphrase('symmetric', $this->_getSymmetricID());
                return $this->_getEmbeddedMimeParts();
            }
            return null;
        }

        self::$_cache[$base_id]['wrap'] = 'mimePartWrapValid';

        return Horde_Mime_Part::parseMessage($decrypted_data->message, array('forcemime' => true));
    }

    /**
     * Generates output for 'application/pgp-keys' MIME_Parts.
     *
     * @return string  The HTML output.
     */
    protected function _outputPGPKey()
    {
        /* Is PGP active? */
        if (empty($GLOBALS['conf']['gnupg']['path']) ||
            !$GLOBALS['prefs']->getValue('use_pgp')) {
            return array();
        }

        /* Initialize status message. */
        $status = array(
            'icon' => Horde::img('mime/encryption.png', 'PGP'),
            'text' => array(
                _("A PGP Public Key is attached to the message.")
            )
        );

        $mime_id = $this->_mimepart->getMimeId();
        $imp_pgp = $GLOBALS['injector']->getInstance('IMP_Crypt_Pgp');

        if ($GLOBALS['prefs']->getValue('use_pgp') &&
            $GLOBALS['prefs']->getValue('add_source') &&
            $GLOBALS['registry']->hasMethod('contacts/addField')) {
            $status['text'][] = Horde::link('#', '', '', '', $imp_pgp->savePublicKeyURL($this->getConfigParam('imp_contents')->getMailbox(), $this->getConfigParam('imp_contents')->getUid(), $mime_id) . 'return false;') . _("Save the key to your address book.") . '</a>';
        }
        $status['text'][] = $this->getConfigParam('imp_contents')->linkViewJS($this->_mimepart, 'view_attach', _("View the raw text of the Public Key."), array('jstext' => _("View Public Key"), 'params' => array('mode' => IMP_Contents::RENDER_INLINE, 'rawpgpkey' => 1)));

        try {
            $data = '<span class="fixed">' . nl2br(str_replace(' ', '&nbsp;', $imp_pgp->pgpPrettyKey($this->_mimepart->getContents()))) . '</span>';
        } catch (Horde_Exception $e) {
            $data = $e->getMessage();
        }

        return array(
            $mime_id => array(
                'data' => $data,
                'status' => array($status),
                'type' => 'text/html; charset=' . $this->getConfigParam('charset')
            )
        );
    }

    /**
     * Generates HTML output for 'multipart/signed' MIME parts.
     *
     * @return string  The HTML output.
     */
    protected function _outputPGPSigned()
    {
        $partlist = array_keys($this->_mimepart->contentTypeMap());
        $base_id = reset($partlist);
        $signed_id = next($partlist);
        $sig_id = Horde_Mime::mimeIdArithmetic($signed_id, 'next');

        $ret = array(
            $base_id => array(
                'data' => '',
                'status' => array(
                    array(
                        'icon' => Horde::img('mime/encryption.png', 'PGP'),
                        'text' => array()
                    )
                ),
                'type' => 'text/html; charset=' . $this->getConfigParam('charset'),
                'wrap' => 'mimePartWrap'
            ),
            $sig_id => null
        );
        $status = &$ret[$base_id]['status'][0]['text'];

        if (!$GLOBALS['prefs']->getValue('use_pgp') ||
            empty($GLOBALS['conf']['gnupg']['path'])) {
            /* If PGP not active, hide signature data and output status
             * information. */
            $status[] = _("The data in this part has been digitally signed via PGP, but the signature cannot be verified.");
            return $ret;
        }

        $status[] = _("The data in this part has been digitally signed via PGP.");

        if ($GLOBALS['prefs']->getValue('pgp_verify') ||
            Horde_Util::getFormData('pgp_verify_msg')) {
            $sig_part = $this->getConfigParam('imp_contents')->getMIMEPart($sig_id);

            try {
                $imp_pgp = $GLOBALS['injector']->getInstance('IMP_Crypt_Pgp');
                $sig_result = $sig_part->getMetadata('imp-pgp-signature')
                    ? $imp_pgp->verifySignature($sig_part->getContents(array('canonical' => true)), $this->_address)
                    : $imp_pgp->verifySignature($sig_part->replaceEOL($this->getConfigParam('imp_contents')->getBodyPart($signed_id, array('mimeheaders' => true)), Horde_Mime_Part::RFC_EOL), $this->_address, $sig_part->getContents());

                $icon = Horde::img('alerts/success.png', _("Success"));
                $sig_text = $sig_result->message;
                $success = true;
            } catch (Horde_Exception $e) {
                $icon = Horde::img('alerts/error.png', _("Error"));
                $sig_text = $e->getMessage();
                $success = false;
            }

            $ret[$base_id]['status'][] = array(
                'icon' => $icon,
                'success' => $success,
                'text' => array(
                    $this->_textFilter($sig_text, 'text2html', array(
                        'parselevel' => Horde_Text_Filter_Text2html::NOHTML
                    ))
                )
            );

            $ret[$base_id]['wrap'] = $success
                ? 'mimePartWrapValid'
                : 'mimePartWrapInvalid';
        } else {
            switch ($_SESSION['imp']['view']) {
            case 'imp':
                $status[] = Horde::link(IMP::selfUrl()->add(array('pgp_verify_msg' => 1))) . _("Click HERE to verify the message.") . '</a>';
                break;

            case 'dimp':
                $status[] = Horde::link('#', '', 'pgpVerifyMsg') . _("Click HERE to verify the message.") . '</a>';
                break;
            }
        }

        return $ret;
    }

    /**
     * Generates the symmetric ID for this message.
     *
     * @return string  Symmetric ID.
     */
    protected function _getSymmetricID()
    {
        return $GLOBALS['injector']->getInstance('IMP_Crypt_Pgp')->getSymmetricID($this->getConfigParam('imp_contents')->getMailbox(), $this->getConfigParam('imp_contents')->getUid(), $this->_mimepart->getMimeId());
    }

    /**
     * Can this driver render the the data?
     *
     * @param string $mode  See parent::canRender().
     *
     * @return boolean  See parent::canRender().
     */
    public function canRender($mode)
    {
        return (($mode == 'raw') &&
                ($this->_mimepart->getType() == 'application/pgp-signature') &&
                $this->_mimepart->getMetadata('imp-pgp-signature'))
            ? true
            : parent::canRender($mode);
    }

}
