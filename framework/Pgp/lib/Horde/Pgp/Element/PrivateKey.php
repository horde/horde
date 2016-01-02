<?php
/**
 * Copyright 2015-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2015-2016 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Pgp
 */

/**
 * PGP element: private key.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2015-2016 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Pgp
 *
 * @property boolean $encrypted  Returns true if the private key is encrypted.
 */
class Horde_Pgp_Element_PrivateKey
extends Horde_Pgp_Element_Key
{
    /**
     */
    protected $_armor = 'PRIVATE KEY BLOCK';

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'encrypted':
            $p = $this->_getSecretKeyPackets();
            return (strlen(reset($p)->encrypted_data) > 0);

        default:
            return parent::__get($name);
        }
    }

    /**
     * Return the unencrypted version of the private key.
     *
     * @param string $passphrase  The passphrase used to encrypt the key.
     *
     * @return Horde_Pgp_Element_PrivateKey  Unencrypted key.
     * @throws Horde_Pgp_Exception
     */
    public function getUnencryptedKey($passphrase = null)
    {
        $out = null;

        foreach ($this->_getSecretKeyPackets() as $k => $v) {
            if (!strlen($v->encrypted_data)) {
                continue;
            }

            if (is_null($out)) {
                $out = clone $this->message;
            }

            $out[$k] = OpenPGP_Crypt_Symmetric::decryptSecretKey(
                $passphrase,
                $v
            );

            if (is_null($out[$k])) {
                throw new Horde_Pgp_Exception(
                    Horde_Pgp_Translation::t("Could not unencrypt private key.")
                );
            }
        }

        return new Horde_Pgp_Element_PrivateKey($out);
    }

    /**
     */
    public function getPublicKey()
    {
        $pubkey = clone $this->message;

        foreach ($pubkey as $key => $val) {
            if ($val instanceof OpenPGP_SecretKeyPacket) {
                $ob = ($val instanceof OpenPGP_SecretSubkeyPacket)
                    ? new OpenPGP_PublicSubkeyPacket()
                    : new OpenPGP_PublicKeyPacket();
                foreach (array_keys(get_object_vars($ob)) as $key2) {
                    if ($key2 !== 'tag') {
                        $ob->$key2 = $val->$key2;
                    }
                }
                $pubkey[$key] = $ob;
            }
        }

        return new Horde_Pgp_Element_PublicKey($pubkey);
    }

    /**
     * Return the secret key packet.
     *
     * @return array  Packet ID as keys, OpenPGP_SecretKeyPacket objects as
     *                values.
     */
    protected function _getSecretKeyPackets()
    {
        $out = array();

        foreach ($this->message as $key => $val) {
            if ($val instanceof OpenPGP_SecretKeyPacket) {
                $out[$key] = $val;
            }
        }

        return $out;
    }

}
