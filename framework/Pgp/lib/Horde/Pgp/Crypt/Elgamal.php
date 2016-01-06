<?php
/**
 * Copyright 2015-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.

 * @category  Horde
 * @copyright 2015-2016 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Pgp
 */

use phpseclib\Crypt\Random as crypt_random;
use phpseclib\Math\BigInteger as Math_BigInteger;

/**
 * Elgamal encryption implementation (w/EME-PKCS1-v1_5 block encoding).
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2015-2016 Horde LLC
 * @internal
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Pgp
 */
class Horde_Pgp_Crypt_Elgamal
{
    /**
     * Public/private key packet.
     *
     * @var OpenPGP_PublicKeyPacket
     */
    private $_key;

    /**
     * Constructor.
     *
     * @param OpenPGP_PublicKeyPacket $key  Key data.
     */
    public function __construct(OpenPGP_PublicKeyPacket $key)
    {
        $this->_key = $key;
    }

    /**
     * Encrypt data.
     *
     * @param string $text  Plaintext.
     *
     * @return array  Array of MPI values (c1, c2).
     */
    public function encrypt($text)
    {
        $p_len = strlen($this->_key->key['p']);
        $length = $p_len - 11;
        if ($length <= 0) {
            return false;
        }

        $g = new Math_BigInteger($this->_key->key['g'], 256);
        $p = new Math_BigInteger($this->_key->key['p'], 256);
        $y = new Math_BigInteger($this->_key->key['y'], 256);
        $out = array();

        foreach (str_split($text, $length) as $m) {
            // EME-PKCS1-v1_5 encoding
            $psLen = $p_len - strlen($m) - 3;
            $ps = '';

            while (($psLen2 = strlen($ps)) != $psLen) {
                $tmp = crypt_random::String($psLen - $psLen2);
                $ps .= str_replace("\x00", '', $tmp);
            }

            $em = new Math_BigInteger(
                chr(0) . chr(2) . $ps . chr(0) . $m,
                256
            );
            // End EME-PKCS1-v1_5 encoding

            $k = Horde_Pgp_Crypt_DSA::randomNumber($p);
            $c1 = $g->modPow($k, $p);
            $c2_base = $y->modPow($k, $p)->multiply($em)->divide($p);
            $c2 = $c2_base[1];

            $out[] = str_pad($c1->toBytes(), $p_len, chr(0), STR_PAD_LEFT);
            $out[] = str_pad($c2->toBytes(), $p_len, chr(0), STR_PAD_LEFT);
        }

        return $out;
    }

    /**
     * Decrypt data.
     *
     * @param string $text  PKCS1-v1_5 encoded text.
     *
     * @return string  Plaintext.
     */
    public function decrypt($text)
    {
        $out = '';
        $p_len = strlen($this->_key->key['p']);

        $text = str_split($text, $p_len);
        $text[count($text) - 1] = str_pad(
            $text[count($text) - 1],
            $p_len,
            chr(0),
            STR_PAD_LEFT
        );

        $p = new Math_BigInteger($this->_key->key['p'], 256);
        $x = new Math_BigInteger($this->_key->key['x'], 256);

        for ($i = 0, $j = count($text); $i < $j; $i += 2) {
            $c1 = new Math_BigInteger($text[$i], 256);
            $c2 = new Math_BigInteger($text[$i + 1], 256);

            $s = $c1->modPow($x, $p);
            $m_prime = $s->modInverse($p)->multiply($c2)->divide($p);
            $em = str_pad(
                $m_prime[1]->toBytes(),
                $p_len,
                chr(0),
                STR_PAD_LEFT
            );

            // EME-PKCS1-v1_5 decoding
            if ((ord($em[0]) !== 0) || (ord($em[1]) !== 2)) {
                throw new RuntimeException();
            }

            $out .= substr($em, strpos($em, chr(0), 2) + 1);
        }

        return $out;
    }

}
