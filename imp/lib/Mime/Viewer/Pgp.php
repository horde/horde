<?php
/**
 * Copyright 2002-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2002-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Renderer to allow viewing/decrypting of PGP formatted messages (RFC 3156).
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
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2002-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Mime_Viewer_Pgp extends Horde_Mime_Viewer_Base
{
    /* Metadata constants. */
    const PGP_SIGN_ENC = 'imp-pgp-signed-encrypted';

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
     * @var Horde_Mail_Rfc822_Address
     */
    protected $_sender = null;

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
                        'data' => '<html><body><tt>' . nl2br(str_replace(' ', '&nbsp;', $GLOBALS['injector']->getInstance('IMP_Pgp')->pgpPrettyKey($this->_mimepart->getContents()))) . '</tt></body></html>',
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
            $parse = new Horde_Crypt_Pgp_Parse();
            $parts = $parse->parse($this->_mimepart->getContents());
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

        switch ($this->_mimepart->getType()) {
        case 'application/pgp-keys':
            return $this->_outputPGPKey();

        case 'multipart/signed':
            return $this->_outputPGPSigned();

        case 'multipart/encrypted':
            $cache = $this->getConfigParam('imp_contents')->getViewCache();

            if (isset($cache->pgp[$id])) {
                return array_merge(array(
                    $id => array(
                        'data' => null,
                        'status' => $cache->pgp[$id]['status'],
                        'type' => 'text/plain; charset=' . $this->getConfigParam('charset'),
                        'wrap' => $cache->pgp[$id]['wrap']
                    )
                ), $cache->pgp[$id]['other']);
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

        $imp_contents = $this->getConfigParam('imp_contents');
        $iterator = $this->_mimepart->partIterator();
        $iterator->rewind();
        $base_id = $iterator->current()->getMimeId();
        $iterator->next();
        $version_id = $iterator->current()->getMimeId();

        $id_ob = new Horde_Mime_Id($version_id);
        $data_id = $id_ob->idArithmetic($id_ob::ID_NEXT);

        $status = new IMP_Mime_Status($this->_mimepart);
        $status->icon('mime/encryption.png', 'PGP');

        $cache = $imp_contents->getViewCache();
        $cache->pgp[$base_id] = array(
            'status' => array($status),
            'other' => array(
                $version_id => null,
                $data_id => null
            ),
            'wrap' => ''
        );

        /* Is PGP active? */
        if (!IMP_Pgp::enabled()) {
            $status->addText(
                _("The data in this part has been encrypted via PGP, however, PGP support is disabled so the message cannot be decrypted.")
            );
            return null;
        }

        /* PGP version information appears in the first MIME subpart. We
         * don't currently need to do anything with this information. The
         * encrypted data appears in the second MIME subpart. */
        if (!($encrypted_part = $imp_contents->getMimePart($data_id))) {
            return null;
        }

        $encrypted_data = $encrypted_part->getContents();
        $symmetric_pass = $personal_pass = null;

        /* Check if this a symmetrically encrypted message. */
        try {
            $imp_pgp = $GLOBALS['injector']->getInstance('IMP_Pgp');
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
                $decrypted_data = $imp_pgp->decryptMessage($encrypted_data, 'symmetric', array(
                    'passphrase' => $symmetric_pass,
                    'sender' => $this->_getSender()->bare_address
                ));
            } elseif (!is_null($personal_pass)) {
                $decrypted_data = $imp_pgp->decryptMessage($encrypted_data, 'personal', array(
                    'passphrase' => $personal_pass,
                    'sender' => $this->_getSender()->bare_address
                ));
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

        $cache->pgp[$base_id]['wrap'] = 'mimePartWrapValid';

        /* Check for combined encryption/signature data. */
        if ($decrypted_data->result) {
            $sig_text = is_bool($decrypted_data->result)
                ? _("The data in this part has been digitally signed via PGP.")
                : $this->_textFilter($decrypted_data->result, 'text2html', array('parselevel' => Horde_Text_Filter_Text2html::NOHTML));

            $status2 = new IMP_Mime_Status($this->_mimepart, $sig_text);
            $status2->action(IMP_Mime_Status::SUCCESS);

            $cache->pgp[$base_id]['status'][] = $status2;
        }

        /* Force armor data as text/plain data. */
        if ($this->_mimepart->getMetadata(Horde_Crypt_Pgp_Parse::PGP_ARMOR)) {
            $decrypted_data->message = "Content-Type: text/plain\n\n" .
                                       $decrypted_data->message;
        }

        $new_part = Horde_Mime_Part::parseMessage($decrypted_data->message, array(
            'forcemime' => true
        ));

        if ($new_part->getType() == 'multipart/signed') {
            $data = new Horde_Stream_Temp();
            try {
                $data->add(Horde_Mime_Part::getRawPartText($decrypted_data->message, 'header', '1'));
                $data->add("\n\n");
                $data->add(Horde_Mime_Part::getRawPartText($decrypted_data->message, 'body', '1'));
            } catch (Horde_Mime_Exception $e) {}

            $new_part->setMetadata(self::PGP_SIGN_ENC, $data->stream);
            $new_part->setContents($decrypted_data->message, array(
                'encoding' => 'binary'
            ));
        }

        return $new_part;
    }

    /**
     * Generates output for 'application/pgp-keys' MIME_Parts.
     *
     * @return string  The HTML output.
     */
    protected function _outputPGPKey()
    {
        /* Is PGP active? */
        if (!IMP_Pgp::enabled()) {
            return array();
        }

        /* Initialize status message. */
        $status = new IMP_Mime_Status(
            $this->_mimepart,
            _("A PGP Public Key is attached to the message.")
        );
        $status->icon('mime/encryption.png', 'PGP');

        $imp_contents = $this->getConfigParam('imp_contents');
        $mime_id = $this->_mimepart->getMimeId();

        if ($GLOBALS['prefs']->getValue('add_source') &&
            $GLOBALS['registry']->hasMethod('contacts/addField')) {
            // TODO: Check for key existence.
            $imple = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Imple')->create('IMP_Ajax_Imple_ImportEncryptKey', array(
                'mime_id' => $mime_id,
                'muid' => strval($imp_contents->getIndicesOb()),
                'type' => 'pgp'
            ));
            $status->addText(Horde::link('#', '', '', '', '', '', '', array('id' => $imple->getDomId())) . _("Save the key to your address book.") . '</a>');
        }
        $status->addText($imp_contents->linkViewJS($this->_mimepart, 'view_attach', _("View key details."), array('params' => array('mode' => IMP_Contents::RENDER_FULL, 'pgp_view_key' => 1))));

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
        global $injector, $prefs, $session;

        $iterator = $this->_mimepart->partIterator();
        $iterator->rewind();
        $base_id = $iterator->current()->getMimeId();
        $iterator->next();
        $signed_id = $iterator->current()->getMimeId();

        $id_ob = new Horde_Mime_Id($signed_id);
        $sig_id = $id_ob->idArithmetic($id_ob::ID_NEXT);

        if (!IMP_Pgp::enabled()) {
            return array(
                $sig_id => null
            );
        }

        $status = new IMP_Mime_Status($this->_mimepart);
        $status->addText(_("The data in this part has been digitally signed via PGP."));
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

        if ($prefs->getValue('pgp_verify') ||
            $injector->getInstance('Horde_Variables')->pgp_verify_msg) {
            $imp_contents = $this->getConfigParam('imp_contents');
            $sig_part = $imp_contents->getMimePart($sig_id);

            $status2 = new IMP_Mime_Status($this->_mimepart);

            if (!$sig_part) {
                $status2->action(IMP_Mime_Status::ERROR);
                $sig_text = _("This digitally signed message is broken.");
                $ret[$base_id]['wrap'] = 'mimePartWrapInvalid';
            } else {
                /* Close session, since this may be a long-running
                 * operation. */
                $session->close();

                try {
                    $imp_pgp = $injector->getInstance('IMP_Pgp');
                    if ($sig_raw = $sig_part->getMetadata(Horde_Crypt_Pgp_Parse::SIG_RAW)) {
                        $sig_result = $imp_pgp->verifySignature($sig_raw, $this->_getSender()->bare_address, null, $sig_part->getMetadata(Horde_Crypt_Pgp_Parse::SIG_CHARSET));
                    } else {
                        $stream = $imp_contents->isEmbedded($signed_id)
                            ? $this->_mimepart->getMetadata(self::PGP_SIGN_ENC)
                            : $imp_contents->getBodyPart($signed_id, array('mimeheaders' => true, 'stream' => true))->data;

                        rewind($stream);
                        stream_filter_register('horde_eol', 'Horde_Stream_Filter_Eol');
                        stream_filter_append($stream, 'horde_eol', STREAM_FILTER_READ, array(
                            'eol' => Horde_Mime_Part::RFC_EOL
                        ));

                        $sig_result = $imp_pgp->verifySignature(stream_get_contents($stream), $this->_getSender()->bare_address, $sig_part->getContents());
                    }

                    $status2->action(IMP_Mime_Status::SUCCESS);
                    $sig_text = $sig_result->message;
                    $ret[$base_id]['wrap'] = 'mimePartWrapValid';
                } catch (Horde_Exception $e) {
                    $status2->action(IMP_Mime_Status::ERROR);
                    $sig_text = $e->getMessage();
                    $ret[$base_id]['wrap'] = 'mimePartWrapInvalid';
                }
            }

            $status2->addText($this->_textFilter($sig_text, 'text2html', array(
                'parselevel' => Horde_Text_Filter_Text2html::NOHTML
            )));
            $ret[$base_id]['status'][] = $status2;
        } else {
            $status->addMimeAction(
                'pgpVerifyMsg',
                _("Click to verify the message.")
            );
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
        return $GLOBALS['injector']->getInstance('IMP_Pgp')->getSymmetricID($this->getConfigParam('imp_contents')->getMailbox(), $this->getConfigParam('imp_contents')->getUid(), $this->_mimepart->getMimeId());
    }

    /**
     * Determine the address of the sender.
     *
     * @return Horde_Mail_Rfc822_Address  The from address.
     */
    protected function _getSender()
    {
        if (is_null($this->_sender)) {
            $from = $this->getConfigParam('imp_contents')->getHeader()->getHeader('from');
            $this->_sender = $from
                ? $from->getAddressList(true)->first()
                : new Horde_Mail_Rfc822_Address();
        }

        return $this->_sender;
    }

    /**
     * Can this driver render the data?
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
                $this->_mimepart->getMetadata(Horde_Crypt_Pgp_Parse::SIG_RAW)) {
                return true;
            }
            break;
        }

        return parent::canRender($mode);
    }

}
