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
 */
abstract class Horde_Pgp_Element_Key
extends Horde_Pgp_Element
{
    /** Revocation reasons. */
    const REVOKE_UNKNOWN = 0;
    const REVOKE_SUPERSEDED = 1;
    const REVOKE_COMPROMISED = 2;
    const REVOKE_RETIRED = 3;
    const REVOKE_NOTUSED = 4;

    /**
     * Cached data.
     *
     * @var array
     */
    protected $_cache;

    /**
     * Return the list of key fingerprints.
     *
     * @return array  Keys are key IDs; values are fingerprints.
     */
    public function getFingerprints()
    {
        $keys = array_merge($this->getSignKeys(), $this->getEncryptKeys());
        $out = array();

        foreach ($keys as $val) {
            $out[$val->id] = $val->fingerprint;
        }

        return $out;
    }

    /**
     * Returns the list of signing subkeys within this key.
     *
     * @return array  An array of objects, with these keys:
     *   - created: (DateTime) Creation time.
     *   - fingerprint: (string) Key fingerprint.
     *   - id: (string) Key ID.
     *   - key: (OpenPGP_PublicKeyPacket) Key packet.
     *   - revoke: (object) Revocation information. Elements:
     *     - created: (DateTime) Creation time.
     *     - info: (string) Human readable reason string.
     *     - reason: (integer) Revocation reason.
     */
    public function getSignKeys()
    {
        $this->_parse();

        return $this->_cache['sign'];
    }

    /**
     * Returns the list of user ID information associated with this key.
     *
     * @return array  An array of objects, with these keys:
     *   - comment: (string) Comment.
     *   - created: (DateTime) Creation time.
     *   - email: (Horde_Mail_Rfc822_Address) E-mail address.
     *   - key: (OpenPGP_PublicKeyPacket) Key packet.
     *   - revoke: (object) Revocation information. Elements:
     *     - created: (DateTime) Creation time.
     *     - info: (string) Human readable reason string.
     *     - reason: (integer) Revocation reason.
     *   - sig: (OpenPGP_SignaturePacket) Signature packet.
     */
    public function getUserIds()
    {
        $this->_parse();

        return $this->_cache['userid'];
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
     * Return the list of verified encryption subkeys in this key.
     *
     * @return array  An array of objects, with these keys:
     *   - created: (DateTime) Creation time.
     *   - fingerprint: (string) Key fingerprint.
     *   - id: (string) Key ID.
     *   - key: (OpenPGP_PublicKeyPacket) Key packet.
     *   - revoke: (object) Revocation information. Elements:
     *     - created: (DateTime) Creation time.
     *     - info: (string) Human readable reason string.
     *     - reason: (integer) Revocation reason.
     */
    public function getEncryptKeys()
    {
        $this->_parse();

        return $this->_cache['encrypt'];
    }

    /**
     * Return the public key.
     *
     * @return Horde_Pgp_Element_PublicKey  Public key.
     */
    abstract public function getPublicKey();

    /**
     * Parse the message data, verifying the key contents.
     */
    protected function _parse()
    {
        if (isset($this->_cache)) {
            return;
        }

        $this->_cache = array(
            'encrypt' => array(),
            'userid' => array()
        );

        $fallback = $p = $topkey = $userid = $userid_p = null;
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
                    $topkey = new stdClass;
                    $topkey->created = new DateTime('@' . $val->timestamp);
                    $topkey->fingerprint = $val->fingerprint;
                    $topkey->id = $val->key_id;
                    $topkey->key = $val;
                    $this->_cache['sign'][] = $topkey;
                }
                $p = $val;
                $p_revoke = null;
            } elseif ($val instanceof OpenPGP_UserIDPacket) {
                if ($userid && isset($userid->key)) {
                    $this->_cache['userid'][] = $userid;
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
                    if ($topkey &&
                        $userid_p &&
                        $this->_parseVerify($topkey->key, $userid_p, $val)) {
                        $userid->key = $topkey->key;
                        $userid->created = $this->_parseCreation($val);
                        $userid->sig = $val;
                    } else {
                        $userid_p = null;
                    }
                    break;

                case 0x18:
                    /* Verify first. */
                    if (!$p ||
                        (($topkey->key !== $p) &&
                         !$this->_parseVerify($topkey->key, $p, $val))) {
                        continue;
                    }

                    $encrypt = new stdClass;
                    if (!is_null($p_revoke)) {
                        $encrypt->revoke = $p_revoke;
                    }

                    /* Check for explicit key flag subpacket. Require
                     * this information to be in hashed subpackets. */
                    if ($val->version === 4) {
                        $encrypt->created = $this->_parseCreation($val);

                        foreach ($val->hashed_subpackets as $val2) {
                            if ($val2 instanceof OpenPGP_SignaturePacket_KeyFlagsPacket) {
                                foreach ($val2->flags as $val3) {
                                    if ($val3 & 0x04) {
                                        $encrypt->key = $create_out($p, $val);
                                        $encrypt->fingerprint = $p->fingerprint;
                                        $encrypt->id = $p->key_id;
                                        $this->_cache['encrypt'][] = $encrypt;
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
                        $encrypt->key = $create_out($p, $val);
                        $fallback = $encrypt;
                    } elseif (!$sub &&
                              ($p instanceof OpenPGP_PublicSubkeyPacket) ||
                              ($p instanceof OpenPGP_SecretSubkeyPacket)) {
                        $encrypt->key = $create_out($p, $val);
                        $fallback = $encrypt;
                        $sub = true;
                    }
                    break;

                case 0x20:
                    /* Key revocation. */
                    if ($this->_parseVerify($topkey->key, false, $val)) {
                        $this->_cache = array(
                            'encrypt' => array(),
                            'userid' => array()
                        );
                        return;
                    }
                    break;

                case 0x28:
                    /* Subkey revocation. */
                    if ($this->_parseVerify($topkey->key, $p, $val)) {
                        $p_revoke = $this->_parseRevokePacket($val);
                    }
                    break;

                case 0x30:
                    /* Revocation of User ID. */
                    if ($this->_parseVerify($topkey->key, $userid_p, $val)) {
                        $userid->revoke = $this->_parseRevokePacket($val);
                    }
                    break;
                }
            }
        }

        if ($userid && isset($userid->key)) {
            $this->_cache['userid'][] = $userid;
        }

        if ($fallback && empty($this->_cache['encrypt'])) {
            $fallback->fingerprint = $fallback->key->fingerprint;
            $fallback->id = $fallback->key->key_id;
            $this->_cache['encrypt'][] = $fallback;
        }
    }

    /**
     */
    protected function _parseVerify($key, $data, $sig)
    {
        if (is_null($key) || is_null($data)) {
            return false;
        }

        $pgp = new Horde_Pgp_Backend_Openpgp();
        $v = $pgp->verify(
            new Horde_Pgp_Element_Message(
                new OpenPGP_Message(
                    ($data === false)
                        ? array($key, $sig)
                        : array($key, $data, $sig)
                )
            ),
            $this
        );

        return isset($v[0][($data === false) ? 1 : 2][0]);
    }

    /**
     */
    protected function _parseCreation($p)
    {
        /* Creation time is a MUST hashed subpacket (RFC 4880 [5.2.3.4]). */
        foreach ($p->hashed_subpackets as $val) {
            if ($val instanceof OpenPGP_SignaturePacket_SignatureCreationTimePacket) {
                return new DateTime('@' . $val->data);
            }
        }

        return new DateTime('@0');
    }

    /**
     */
    protected function _parseRevokePacket($p)
    {
        $revoke = new stdClass;
        $revoke->created = $this->_parseCreation($p);
        $revoke->reason = self::REVOKE_UNKNOWN;

        foreach ($p->hashed_subpackets as $val) {
            if ($val instanceof OpenPGP_SignaturePacket_ReasonForRevocationPacket) {
                switch ($val->code) {
                case 0x00:
                    $revoke->reason = self::REVOKE_UNKNOWN;
                    break;

                case 0x01:
                    $revoke->reason = self::REVOKE_SUPERSEDED;
                    break;

                case 0x02:
                    $revoke->reason = self::REVOKE_COMPROMISED;
                    break;

                case 0x03:
                    $revoke->reason = self::REVOKE_RETIRED;
                    break;

                case 0x20:
                    $revoke->reason = self::REVOKE_NOTUSED;
                    break;
                }

                $revoke->info = $val->data;
            }
        }

        return $revoke;
    }

}
