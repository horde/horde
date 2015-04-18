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
 * Abstract class representing a PGP key.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Pgp
 *
 * @property-read OpenPGP_PublicKeyPacket $base  Base key packet.
 * @property-read integer $creation  Creation date (UNIX timestamp).
 * @property-read string $fingerprint  Key fingerprint.
 * @property-read string $id  Key ID.
 */
abstract class Horde_Pgp_Element_Key
extends Horde_Pgp_Element
{
    /**
     * Cached data.
     *
     * @var array
     */
    protected $_cache = array();

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'base':
            foreach ($this->message as $val) {
                if ($val instanceof OpenPGP_PublicKeyPacket) {
                    return $val;
                }
            }
            break;

        case 'creation':
        case 'fingerprint':
        case 'id':
            $mapping = array(
                'creation' => 'timestamp',
                'fingerprint' => 'fingerprint',
                'id' => 'key_id'
            );
            return $this->base->{$mapping[$name]};
        }
    }

    /**
     * Return the list of key fingerprints.
     *
     * @return array  Keys are key IDs; values are fingerprints.
     */
    public function getFingerprints()
    {
        $out = array();

        foreach ($this->message as $val) {
            if ($val instanceof OpenPGP_PublicKeyPacket) {
                $out[$val->key_id] = $val->fingerprint;
            }
        }

        return $out;
    }

    /**
     * Returns the list of user ID information associated with this key.
     *
     * @return array  An array of objects, with these keys:
     *   - comment: (string) Comment.
     *   - email: (Horde_Mail_Rfc822_Address) E-mail address.
     *   - key: (OpenPGP_PublicKeyPacket) Key packet.
     *   - sig: (OpenPGP_SignaturePacket) Signature packet.
     */
    public function getUserIds()
    {
        if (isset($this->_cache['getUserIds'])) {
            return $this->_cache['getUserIds'];
        }

        $out = array();
        $topkey = $userid = $userid_p = null;

        foreach ($this->message as $val) {
            if ($val instanceof OpenPGP_PublicKeyPacket) {
                $topkey = $val;
            } elseif ($val instanceof OpenPGP_UserIDPacket) {
                if ($userid && isset($userid->key)) {
                    $out[] = $userid;
                }

                $userid = new stdClass;
                $userid->email = new Horde_Mail_Rfc822_Address($val->email);
                $userid->email->personal = $val->name;
                $userid->comment = $val->comment;

                $userid_p = $val;
            } elseif ($val instanceof OpenPGP_SignaturePacket) {
                /* Signature types: RFC 4880 [5.2.1] */
                switch ($val->signature_type) {
                case 0x10:
                case 0x11:
                case 0x12:
                case 0x13:
                    /* Certification of User ID. */
                    if ($this->_verifyKeyData($topkey, $userid_p, $val)) {
                        $userid->key = $topkey;
                        $userid->sig = $val;
                    }
                    break;

                case 0x30:
                    /* Revocation of User ID. */
                    if ($this->_verifyKeyData($topkey, $userid_p, $val)) {
                        $userid = $userid_p = null;
                    }
                    break;
                }
            }
        }

        if ($userid && isset($userid->key)) {
            $out[] = $userid;
        }

        $this->_cache['getUserIds'] = $out;

        return $out;
    }

    /**
     * Does this key contain an e-mail address?
     *
     * @return boolean  True if the key contains the e-mail address.
     */
    public function containsEmail($email)
    {
        foreach ($this->getUserIds() as $val) {
            if ($val->email->match($email)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return the list of verified encryption packets in this key.
     *
     * @return array  Array of OpenPGP_PublicKeyPacket objects.
     */
    public function getEncryptPackets()
    {
        if (isset($this->_cache['getEncryptPackets'])) {
            return $this->_cache['getEncryptPackets'];
        }

        $fallback = $topkey = null;
        $out = array();
        $sub = false;

        $create_out = function ($p, $s) {
            $out = new stdClass;
            $out->key = $p;
            $out->sig = $s;
            return $out;
        };

        /* Search for the key flag indicating that a key may be used to
         * encrypt communications (RFC 4880 [5.2.3.21]). In the absence
         * of finding this flag (i.e. v3 packets), use the first subkey and,
         * in the absence of that, use the main key. */
        foreach ($this->message as $val) {
            if ($val instanceof OpenPGP_PublicKeyPacket) {
                if (is_null($topkey)) {
                    $topkey = $val;
                }
                $p = $val;
            } elseif ($val instanceof OpenPGP_SignaturePacket) {
                switch ($val->signature_type) {
                case 0x10:
                case 0x11:
                case 0x12:
                case 0x18:
                    /* Verify first. */
                    if (($topkey !== $p) &&
                        !$this->_verifyKeyData($topkey, $p, $val)) {
                        continue;
                    }

                    /* Check for explicit key flag subpacket. Require
                     * this information to be in hashed subpackets. */
                    if ($val->version === 4) {
                        foreach ($val->hashed_subpackets as $val2) {
                            if ($val2 instanceof OpenPGP_SignaturePacket_KeyFlagsPacket) {
                                foreach ($val2->flags as $val3) {
                                    if ($val3 & 0x04) {
                                        $out[] = $create_out($p, $val);
                                        continue 3;
                                    }
                                }

                                /* If the flag wasn't set, we know explicitly
                                 * that this is not an encrypting key. */
                                continue 2;
                            }
                        }
                    }

                    if (is_null($fallback)) {
                        $fallback = $create_out($p, $val);
                    } elseif (!$sub &&
                              ($p instanceof OpenPGP_PublicSubkeyPacket) ||
                              ($p instanceof OpenPGP_SecretSubkeyPacket)) {
                        $fallback = $create_out($p, $val);
                        $sub = true;
                    }
                    break;
                }
            }
        }

        $out = (empty($out) && $fallback)
            ? array($fallback)
            : $out;

        $this->_cache['getEncryptPackets'] = $out;

        return $out;
    }

    /**
     * Return the public key.
     *
     * @return Horde_Pgp_Element_PublicKey  Public key.
     */
    abstract public function getPublicKey();

    /**
     * Verify key data.
     *
     * @param OpenPGP_PublicKeyPacket $topkey  Top key packet.
     * @param OpenPGP_Packet                   Data to verify.
     * @param OpenPGP_SignaturePacket          Signature data.
     *
     * @return boolean  True if verified.
     */
    protected function _verifyKeyData($topkey, $data, $sig)
    {
        if (!$topkey || !$data) {
            return false;
        }

        $pgp = new Horde_Pgp_Backend_Openpgp();
        $v = $pgp->verify(
            new Horde_Pgp_Element_Message(
                new OpenPGP_Message(array($topkey, $data, $sig))
            ),
            $this
        );

        return isset($v[0][2][0]);
    }

}
