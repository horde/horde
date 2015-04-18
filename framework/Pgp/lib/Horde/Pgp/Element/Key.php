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
 * @property-read integer $creation  Creation date (UNIX timestamp).
 * @property-read string $fingerprint  Key fingerprint.
 * @property-read string $id  Key ID.
 */
abstract class Horde_Pgp_Element_Key
extends Horde_Pgp_Element
{
    /**
     */
    public function __get($name)
    {
        $mapping = array(
            'creation' => 'timestamp',
            'fingerprint' => 'fingerprint',
            'id' => 'key_id'
        );

        switch ($name) {
        case 'creation':
        case 'fingerprint':
        case 'id':
            foreach ($this->message as $val) {
                if ($val instanceof OpenPGP_PublicKeyPacket) {
                    return $val->{$mapping[$name]};
                }
            }
            break;
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
        $out = array();
        $topkey = $userid = $userid_p = null;

        /* Internal function used to verify sigs. */
        $pgp = new Horde_Pgp_Backend_Openpgp();
        $self = $this;
        $verify = function ($topkey, $userid, $sig) use ($pgp, $self) {
            if (!$topkey || !$userid) {
                return false;
            }
            $v = $pgp->verify(
                new Horde_Pgp_Element_Message(
                    new OpenPGP_Message(array($topkey, $userid, $sig))
                ),
                $self
            );
            return isset($v[0][2][0]);
        };

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
                    if ($verify($topkey, $userid_p, $val)) {
                        $userid->key = $topkey;
                        $userid->sig = $val;
                    }
                    break;

                case 0x30:
                    /* Revocation of User ID. */
                    if ($verify($topkey, $userid_p, $val)) {
                        $userid = $userid_p = null;
                    }
                    break;
                }
            }
        }

        if ($userid && isset($userid->key)) {
            $out[] = $userid;
        }

        return $out;
    }

    /**
     * Does this key contain the e-mail address?
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
     * Return a list of key/subkey packets contained in this key.
     *
     * @return array  List of keys, each represented by an object with these
     *                properties:
     *   - key: (OpenPGP_PublicKeyPacket) Key packet object.
     *   - signature: (OpenPGP_SignaturePacket) Signature packet object.
     *   - userid: (OpenPGP_UserIDPacket) ID packet object.
     */
    public function getKeyList()
    {
        $key = $sig = $userid = null;
        $out = array();

        foreach ($this->message as $val) {
            if ($val instanceof OpenPGP_PublicKeyPacket) {
                $key = $val;
            } elseif ($val instanceof OpenPGP_SignaturePacket) {
                $sig = $val;
            } elseif ($val instanceof OpenPGP_UserIDPacket) {
                $userid = $val;
            }

            if (!is_null($key) && !is_null($sig)) {
                $tmp = new stdClass;
                $tmp->key = $key;
                $tmp->signature = $sig;
                $tmp->userid = $userid;
                $out[] = $tmp;

                $key = $sig = null;
            }
        }

        return $out;
    }

    /**
     * Return the public key.
     *
     * @return Horde_Pgp_Element_PublicKey  Public key.
     */
    abstract public function getPublicKey();

}
