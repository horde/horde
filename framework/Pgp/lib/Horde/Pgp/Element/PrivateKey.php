<?php
/**
 * Copyright 2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Pgp
 */

/**
 * PGP element: private key.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2015 Horde LLC
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
    static protected $_header = 'PRIVATE KEY BLOCK';

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'encrypted':
            $p = $this->_getSecretKeyPacket();
            return (strlen($p[1]->encrypted_data) > 0);

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
        $p = $this->_getSecretKeyPacket();

        if (!strlen($p[1]->encrypted_data)) {
            return $this;
        }

        if (!is_null($passphrase)) {
            $unencrypted = OpenPGP_Crypt_Symmetric::decryptSecretKey(
                $passphrase,
                $p[1]
            );

            if (!is_null($unencrypted)) {
                $out = $this->getMessageOb();
                $out[$p[0]] = $unencrypted;
                return Horde_Pgp_Element_PrivateKey::createFromData($out);
            }
        }

        throw new Horde_Pgp_Exception(
            Horde_Pgp_Translation::t("Could not unencrypt private key.")
        );
    }

    /**
     */
    public function getPublicKey()
    {
        $parse = $this->getMessageOb();
        $pubkey = clone $parse;

        foreach ($parse as $key => $val) {
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

        return Horde_Pgp_Element_PublicKey::createFromData($pubkey);
    }

    /**
     * Return the secret key packet.
     *
     * @return array  Packet ID and OpenPGP_SecretKeyPacket object.
     */
    protected function _getSecretKeyPacket()
    {
        foreach ($this->getMessageOb() as $key => $val) {
            if ($val instanceof OpenPGP_SecretKeyPacket) {
                return array($key, $val);
            }
        }
    }

}
