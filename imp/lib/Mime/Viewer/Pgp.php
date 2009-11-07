<?php
/**
 * The IMP_Horde_Mime_Viewer_Pgp class allows viewing/decrypting of PGP
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
 * Copyright 2002-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Mime
 */
class IMP_Horde_Mime_Viewer_Pgp extends Horde_Mime_Viewer_Driver
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
        /* This driver *does* render raw data, but only for
         * application/pgp-signature parts that have been processed by the
         * text/plain driver. This is handled via the canRender() function. */
        'raw' => false
    );

    /**
     * IMP_Crypt_Pgp object.
     *
     * @var IMP_Crypt_Pgp
     */
    protected $_imppgp;

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
     * @return array  See Horde_Mime_Viewer_Driver::render().
     */
    protected function _renderRaw()
    {
        $id = $this->_mimepart->getMimeId();

        $ret = array(
            $id => array(
                'data' => '',
                'status' => array(),
                'type' => 'text/plain; charset=' . Horde_Nls::getCharset()
            )
        );

        if (empty($this->_imppgp)) {
            $this->_imppgp = Horde_Crypt::singleton(array('IMP', 'Pgp'));
        }

        $parts = $this->_imppgp->parsePGPData($this->_mimepart->getContents());
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
     * @return array  See Horde_Mime_Viewer_Driver::render().
     */
    protected function _renderInline()
    {
        $id = $this->_mimepart->getMimeId();

        if (empty($this->_imppgp) &&
            !empty($GLOBALS['conf']['gnupg']['path'])) {
            $this->_imppgp = Horde_Crypt::singleton(array('IMP', 'Pgp'));
        }

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
            $headers = $this->_params['contents']->getHeaderOb();
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
                        'type' => 'text/plain; charset=' . Horde_Nls::getCharset(),
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

        if (empty($this->_imppgp)) {
            $this->_imppgp = Horde_Crypt::singleton(array('IMP', 'Pgp'));
        }

        /* PGP version information appears in the first MIME subpart. We
         * don't currently need to do anything with this information. The
         * encrypted data appears in the second MIME subpart. */
        $encrypted_part = $this->_params['contents']->getMIMEPart($data_id);
        $encrypted_data = $encrypted_part->getContents();

        $symmetric_pass = $personal_pass = null;

        /* Check if this a symmetrically encrypted message. */
        try {
            $symmetric = $this->_imppgp->encryptedSymmetrically($encrypted_data);
            if ($symmetric) {
                $symmetric_id = $this->_getSymmetricID();
                $symmetric_pass = $this->_imppgp->getPassphrase('symmetric', $symmetric_id);

                if (is_null($symmetric_pass)) {
                    $js_action = '';
                    $status[] = _("The data in this part has been encrypted via PGP.");

                    switch ($_SESSION['imp']['view']) {
                    case 'dimp':
                        $js_action = 'DimpCore.reloadMessage({});';
                        // Fall through

                    case 'imp':
                        /* Ask for the correct passphrase if this is encrypted
                         * symmetrically. */
                        $status[] = Horde::link('#', '', '', '', IMP::passphraseDialogJS('PGPSymmetric', $js_action, array('symmetricid' => $symmetric_id)) . ';return false;') . _("You must enter the passphrase used to encrypt this message to view it.") . '</a>';
                        break;
                    }
                    return null;
                }
            }
        } catch (Horde_Exception $e) {
            Horde::logMessage($e, __FILE__, __LINE__);
            return null;
        }

        /* Check if this is a literal compressed message. */
        try {
            $info = $this->_imppgp->pgpPacketInformation($encrypted_data);
        } catch (Horde_Exception $e) {
            Horde::logMessage($e, __FILE__, __LINE__);
            return null;
        }

        $literal = !empty($info['literal']);
        if ($literal) {
            $status[] = _("The data in this part has been compressed via PGP.");
        } else {
            $status[] = _("The data in this part has been encrypted via PGP.");
            if (!$symmetric) {
                if (!$this->_imppgp->getPersonalPrivateKey()) {
                    /* Output if there is no personal private key to decrypt
                     * with. */
                    $status[] = _("The data in this part has been encrypted via PGP, however, no personal private key exists so the message cannot be decrypted.");
                    return null;
                } else {
                    $personal_pass = $this->_imppgp->getPassphrase('personal');

                    if (is_null($personal_pass)) {
                        $js_action = '';
                        $status[] = _("The data in this part has been encrypted via PGP.");

                        switch ($_SESSION['imp']['view']) {
                        case 'dimp':
                            $js_action = 'DimpCore.reloadMessage({});';
                            // Fall through

                        case 'imp':
                            /* Ask for the private key's passphrase if this is
                             * encrypted asymmetrically. */
                            $status[] = Horde::link('#', '', '', '', IMP::passphraseDialogJS('PGPPersonal', $js_action) . ';return false;') . _("You must enter the passphrase for your PGP private key to view this message.") . '</a>';
                            break;
                        }
                        return null;
                    }
                }
            }
        }

        try {
            if (!is_null($symmetric_pass)) {
                $decrypted_data = $this->_imppgp->decryptMessage($encrypted_data, 'symmetric', $symmetric_pass);
            } elseif (!is_null($personal_pass)) {
                $decrypted_data = $this->_imppgp->decryptMessage($encrypted_data, 'personal', $personal_pass);
            } else {
                $decrypted_data = $this->_imppgp->decryptMessage($encrypted_data, 'literal');
            }
        } catch (Horde_Exception $e) {
            $status[] = _("The data in this part does not appear to be a valid PGP encrypted message. Error: ") . $e->getMessage();
            if (!is_null($symmetric_pass)) {
                $this->_imppgp->unsetPassphrase('symmetric', $this->_getSymmetricID());
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

        if ($GLOBALS['prefs']->getValue('use_pgp') &&
            $GLOBALS['prefs']->getValue('add_source') &&
            $GLOBALS['registry']->hasMethod('contacts/addField')) {
            $status['text'][] = Horde::link('#', '', '', '', $this->_imppgp->savePublicKeyURL($this->_params['contents']->getMailbox(), $this->_params['contents']->getUid(), $mime_id) . 'return false;') . _("Save the key to your address book.") . '</a>';
        }
        $status['text'][] = $this->_params['contents']->linkViewJS($this->_mimepart, 'view_attach', _("View the raw text of the Public Key."), array('jstext' => _("View Public Key"), 'params' => array('mode' => IMP_Contents::RENDER_INLINE, 'rawpgpkey' => 1)));

        try {
            $data = '<span class="fixed">' . nl2br(str_replace(' ', '&nbsp;', $this->_imppgp->pgpPrettyKey($this->_mimepart->getContents()))) . '</span>';
        } catch (Horde_Exception $e) {
            $data = $e->getMessage();
        }

        return array(
            $mime_id => array(
                'data' => $data,
                'status' => array($status),
                'type' => 'text/html; charset=' . Horde_Nls::getCharset()
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
                'type' => 'text/html; charset=' . Horde_Nls::getCharset(),
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
            $graphicsdir = $GLOBALS['registry']->getImageDir('horde');
            $sig_part = $this->_params['contents']->getMIMEPart($sig_id);

            try {
                $sig_result = $sig_part->getMetadata('imp-pgp-signature')
                    ? $this->_imppgp->verifySignature($sig_part->getContents(array('canonical' => true)), $this->_address)
                    : $this->_imppgp->verifySignature($sig_part->replaceEOL($this->_params['contents']->getBodyPart($signed_id, array('mimeheaders' => true)), Horde_Mime_Part::RFC_EOL), $this->_address, $sig_part->getContents());

                $icon = Horde::img('alerts/success.png', _("Success"), null, $graphicsdir);
                $sig_text = $sig_result->message;
                $success = true;
            } catch (Horde_Exception $e) {
                $icon = Horde::img('alerts/error.png', _("Error"), null, $graphicsdir);
                $sig_text = $e->getMessage();
                $success = false;
            }

            $ret[$base_id]['status'][] = array(
                'icon' => $icon,
                'success' => $success,
                'text' => array(
                    Horde_Text_Filter::filter($sig_text, 'text2html', array('parselevel' => Horde_Text_Filter_Text2html::NOHTML))
                )
            );

            $ret[$base_id]['wrap'] = $success
                ? 'mimePartWrapValid'
                : 'mimePartWrapInvalid';
        } else {
            switch ($_SESSION['imp']['view']) {
            case 'imp':
                $status[] = Horde::link(Horde_Util::addParameter(IMP::selfUrl(), array('pgp_verify_msg' => 1))) . _("Click HERE to verify the message.") . '</a>';
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
        return $this->_imppgp->getSymmetricID($this->_params['contents']->getMailbox(), $this->_params['contents']->getUid(), $this->_mimepart->getMimeId());
    }

    /**
     * Can this driver render the the data?
     *
     * @param string $mode  See Horde_Mime_Viewer_Driver::canRender().
     *
     * @return boolean  See Horde_Mime_Viewer_Driver::canRender().
     */
    public function canRender($mode)
    {
        return (($mode == 'raw') &&
                ($this->_mimepart->getType() == 'application/pgp-signature') &&
                $this->_mimepart->getContentTypeParameter('x-imp-pgp-signature'))
            ? true
            : parent::canRender($mode);
    }

}
