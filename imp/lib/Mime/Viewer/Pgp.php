<?php
/**
 * The IMP_Mime_Viewer_Pgp class allows viewing/decrypting of PGP
 * formatted messages.  This class implements RFC 3156.
 *
 * This class handles the following MIME types:
 *   - application/pgp-encrypted (in multipart/encrypted part)
 *   - application/pgp-keys
 *   - application/pgp-signature (in multipart/signed part)
 *
 * This driver may add the following parameters to the URL:
 *   - pgp_verify_msg: (boolean) Do verification of PGP signed data?
 *   - pgp_view_key: (boolean) View PGP key details?
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Mime_Viewer_Pgp extends Horde_Mime_Viewer_Base
{
    /* Metadata constants. */
    const PGP_ARMOR = 'imp-pgp-armor';
    const PGP_SIG = 'imp-pgp-signature';
    const PGP_CHARSET = 'imp-pgp-charset';

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
         * text/plain driver and for displaying raw pgp keys. Altering this
         * value is handled via the canRender() function. */
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
    protected function _render()
    {
        switch ($this->_mimepart->getType()) {
        case 'application/pgp-keys':
            $vars = $GLOBALS['injector']->getInstance('Horde_Variables');
            if ($vars->pgp_view_key) {
                // Throws exception on error.
                return array(
                    $this->_mimepart->getMimeId() => array(
                        'data' => '<html><body><tt>' . nl2br(str_replace(' ', '&nbsp;', $GLOBALS['injector']->getInstance('IMP_Crypt_Pgp')->pgpPrettyKey($this->_mimepart->getContents()))) . '</tt></body></html>',
                        'type' => 'text/html; charset=' . $this->getConfigParam('charset')
                    )
                );
            }

            return array(
                $this->_mimepart->getMimeId() => array(
                    'data' => $this->_mimepart->getContents(),
                    'type' => 'text/plain; charset=' . $this->_mimepart->getCharset()
                )
            );
        }
    }

    /**
     * Return the full rendered version of the Horde_Mime_Part object.
     *
     * @return array  See parent::render().
     */
    protected function _renderRaw()
    {
        $ret = array(
            'data' => '',
            'type' => 'text/plain; charset=' . $this->getConfigParam('charset')
        );

        switch ($this->_mimepart->getType()) {
        case 'application/pgp-signature':
            $parts = $GLOBALS['injector']->getInstance('IMP_Crypt_Pgp')->parsePGPData($this->_mimepart->getContents());
            foreach (array_keys($parts) as $key) {
                if ($parts[$key]['type'] == Horde_Crypt_Pgp::ARMOR_SIGNATURE) {
                    $ret['data'] = implode("\r\n", $parts[$key]['data']);
                    break;
                }
            }
            break;
        }

        return array(
            $this->_mimepart->getMimeId() => $ret
        );
    }

    /**
     * Return the rendered inline version of the Horde_Mime_Part object.
     *
     * @return array  See parent::render().
     */
    protected function _renderInline()
    {
        $id = $this->_mimepart->getMimeId();

        /* Determine the address of the sender. */
        if (is_null($this->_address)) {
            $headers = $this->getConfigParam('imp_contents')->getHeader();
            $this->_address = IMP::bareAddress($headers->getValue('from'));
        }

        switch ($this->_mimepart->getType()) {
        case 'application/pgp-keys':
            return $this->_outputPGPKey();

        case 'multipart/signed':
            return $this->_outputPGPSigned();

        case 'multipart/encrypted':
            if (!isset($headers)) {
                $headers = $this->getConfigParam('imp_contents')->getHeader();
            }

            $mid = $headers->getValue('message-id');
            if (isset(self::$_cache[$mid][$id])) {
                return array_merge(array(
                    $id => array(
                        'data' => null,
                        'status' => self::$_cache[$mid][$id]['status'],
                        'type' => 'text/plain; charset=' . $this->getConfigParam('charset'),
                        'wrap' => self::$_cache[$mid][$id]['wrap']
                    )
                ), self::$_cache[$mid][$id]['other']);
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

        $mid = $this->getConfigParam('imp_contents')->getHeader()->getValue('message-id');

        $partlist = array_keys($this->_mimepart->contentTypeMap());
        $base_id = reset($partlist);
        $version_id = next($partlist);
        $data_id = Horde_Mime::mimeIdArithmetic($version_id, 'next');

        $status = new IMP_Mime_Status();
        $status->icon('mime/encryption.png', 'PGP');

        self::$_cache[$mid][$base_id] = array(
            'status' => array($status),
            'other' => array(
                $version_id => null,
                $data_id => null
            ),
            'wrap' => ''
        );

        /* Is PGP active? */
        if (empty($GLOBALS['conf']['gnupg']['path']) ||
            !$GLOBALS['prefs']->getValue('use_pgp')) {
            $status->addText(_("The data in this part has been encrypted via PGP, however, PGP support is disabled so the message cannot be decrypted."));
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
                    $status->addText(_("The data in this part has been encrypted via PGP."));

                    /* Ask for the correct passphrase if this is encrypted
                     * symmetrically. */
                    $imple = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Imple')->create('IMP_Ajax_Imple_PassphraseDialog', array(
                        'params' => array(
                            'symmetricid' => $symmetric_id
                        ),
                        'type' => 'pgpSymmetric'
                    ));
                    $status->addText(Horde::link('#', '', '', '', '', '', '', array('id' => $imple->getDomId())) . _("You must enter the passphrase used to encrypt this message to view it.") . '</a>');
                    return null;
                }
            }
        } catch (Horde_Exception $e) {
            Horde::log($e, 'INFO');
            return null;
        }

        /* Check if this is a literal compressed message. */
        try {
            $info = $imp_pgp->pgpPacketInformation($encrypted_data);
        } catch (Horde_Exception $e) {
            Horde::log($e, 'INFO');
            return null;
        }

        $literal = !empty($info['literal']);
        if ($literal) {
            $status->addText(_("The data in this part has been compressed via PGP."));
        } else {
            $status->addText(_("The data in this part has been encrypted via PGP."));

            if (!$symmetric) {
                if ($imp_pgp->getPersonalPrivateKey()) {
                    $personal_pass = $imp_pgp->getPassphrase('personal');
                    if (is_null($personal_pass)) {
                        /* Ask for the private key's passphrase if this is
                         * encrypted asymmetrically. */
                        $imple = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Imple')->create('IMP_Ajax_Imple_PassphraseDialog', array(
                            'type' => 'pgpPersonal'
                        ));
                        $status->addText(Horde::link('#', '', '', '', '', '', '', array('id' => $imple->getDomId())) . _("You must enter the passphrase for your PGP private key to view this message.") . '</a>');
                        return null;
                    }
                } else {
                    /* Output if there is no personal private key to decrypt
                     * with. */
                    $status->addText(_("However, no personal private key exists so the message cannot be decrypted."));
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
            $status->addText(_("The data in this part does not appear to be a valid PGP encrypted message. Error: ") . $e->getMessage());
            if (!is_null($symmetric_pass)) {
                $imp_pgp->unsetPassphrase('symmetric', $this->_getSymmetricID());
                return $this->_getEmbeddedMimeParts();
            }
            return null;
        }

        self::$_cache[$mid][$base_id]['wrap'] = 'mimePartWrapValid';

        /* Check for combined encryption/signature data. */
        if ($decrypted_data->result) {
            $sig_text = is_bool($decrypted_data->result)
                ? _("The data in this part has been digitally signed via PGP.")
                : $this->_textFilter($decrypted_data->result, 'text2html', array('parselevel' => Horde_Text_Filter_Text2html::NOHTML));

            $status2 = new IMP_Mime_Status($sig_text);
            $status2->action(IMP_Mime_Status::SUCCESS);

            self::$_cache[$mid][$base_id]['status'][] = $status2;
        }

        /* Force armor data as text/plain data. */
        if ($this->_mimepart->getMetadata(self::PGP_ARMOR)) {
            $decrypted_data->message = "Content-Type: text/plain\n\n" .
                                       $decrypted_data->message;
        }

        return Horde_Mime_Part::parseMessage($decrypted_data->message, array(
            'forcemime' => true
        ));
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
        $status = new IMP_Mime_Status(_("A PGP Public Key is attached to the message."));
        $status->icon('mime/encryption.png', 'PGP');

        $mime_id = $this->_mimepart->getMimeId();

        if ($GLOBALS['prefs']->getValue('use_pgp') &&
            $GLOBALS['prefs']->getValue('add_source') &&
            $GLOBALS['registry']->hasMethod('contacts/addField')) {
            // TODO: Check for key existence.
            $imp_contents = $this->getConfigParam('imp_contents');
            $imple = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Imple')->create('IMP_Ajax_Imple_ImportEncryptKey', array(
                'mailbox' => $imp_contents->getMailbox(),
                'mime_id' => $mime_id,
                'type' => 'pgp',
                'uid' => $imp_contents->getUid()
            ));
            $status->addText(Horde::link('#', '', '', '', '', '', '', array('id' => $imple->getDomId())) . _("Save the key to your address book.") . '</a>');
        }
        $status->addText($this->getConfigParam('imp_contents')->linkViewJS($this->_mimepart, 'view_attach', _("View key details."), array('params' => array('mode' => IMP_Contents::RENDER_FULL, 'pgp_view_key' => 1))));

        return array(
            $mime_id => array(
                'data' => '',
                'status' => $status,
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

        $status = new IMP_Mime_Status();
        $status->icon('mime/encryption.png', 'PGP');

        $ret = array(
            $base_id => array(
                'data' => '',
                'nosummary' => true,
                'status' => array($status),
                'type' => 'text/html; charset=' . $this->getConfigParam('charset'),
                'wrap' => 'mimePartWrap'
            ),
            $sig_id => null
        );

        if (!$GLOBALS['prefs']->getValue('use_pgp') ||
            empty($GLOBALS['conf']['gnupg']['path'])) {
            /* If PGP not active, hide signature data and output status
             * information. */
            $status->addText(_("The data in this part has been digitally signed via PGP, but the signature cannot be verified."));
            return $ret;
        }

        $status->addText(_("The data in this part has been digitally signed via PGP."));

        if ($GLOBALS['prefs']->getValue('pgp_verify') ||
            $GLOBALS['injector']->getInstance('Horde_Variables')->pgp_verify_msg) {
            $sig_part = $this->getConfigParam('imp_contents')->getMIMEPart($sig_id);

            $status2 = new IMP_Mime_Status();

            try {
                $imp_pgp = $GLOBALS['injector']->getInstance('IMP_Crypt_Pgp');
                $sig_result = $sig_part->getMetadata(self::PGP_SIG)
                    ? $imp_pgp->verifySignature($sig_part->getContents(array('canonical' => true)), $this->_address, null, $sig_part->getMetadata(self::PGP_CHARSET))
                    : $imp_pgp->verifySignature($sig_part->replaceEOL($this->getConfigParam('imp_contents')->getBodyPart($signed_id, array('mimeheaders' => true)), Horde_Mime_Part::RFC_EOL), $this->_address, $sig_part->getContents());

                $status2->action(IMP_Mime_Status::SUCCESS);
                $sig_text = $sig_result->message;
                $ret[$base_id]['wrap'] = 'mimePartWrapValid';
            } catch (Horde_Exception $e) {
                $status2->action(IMP_Mime_Status::ERROR);
                $sig_text = $e->getMessage();
                $ret[$base_id]['wrap'] = 'mimePartWrapInvalid';
            }

            $status2->addText($this->_textFilter($sig_text, 'text2html', array(
                'parselevel' => Horde_Text_Filter_Text2html::NOHTML
            )));
            $ret[$base_id]['status'][] = $status2;
        } else {
            switch ($GLOBALS['registry']->getView()) {
            case Horde_Registry::VIEW_BASIC:
                $status->addText(Horde::link(IMP::selfUrl()->add(array('pgp_verify_msg' => 1))) . _("Click HERE to verify the message.") . '</a>');
                break;

            case Horde_Registry::VIEW_DYNAMIC:
                $status->addText(Horde::link('#', '', 'pgpVerifyMsg') . _("Click HERE to verify the message.") . '</a>');
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
        switch ($mode) {
        case 'full':
            if ($this->_mimepart->getType() == 'application/pgp-keys') {
                return true;
            }
            break;

        case 'raw':
            if (($this->_mimepart->getType() == 'application/pgp-signature') &&
                $this->_mimepart->getMetadata(self::PGP_SIG)) {
                return true;
            }
            break;
        }

        return parent::canRender($mode);
    }

}
