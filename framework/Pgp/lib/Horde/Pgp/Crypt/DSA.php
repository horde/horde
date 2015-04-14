<?php
/**
 * Copyright 2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * Original file (version 0.0.4) released under BSD license:
 *
 * Copyright (c) 2004-2006, TSURUOKA Naoya
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   - Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *   - Redistributions in binary form must reproduce the above
 *     copyright notice, this list of conditions and the following
 *     disclaimer in the documentation and/or other materials provided
 *     with the distribution.
 *   - Neither the name of the author nor the names of its contributors
 *     may be used to endorse or promote products derived from this
 *     software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

 * @category  Horde
 * @copyright 2006 TSURUOKA Naoya
 * @copyright 2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Pgp
 */

/**
 * DSA (Digital Signature Algorithm) implementation.
 *
 * @author    TSURUOKA Naoya <tsuruoka@labs.cybozu.co.jp>
 * @author    Benjamin Krämer <benjamin.kraemer@alien-scripts.de>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2006 TSURUOKA Naoya
 * @copyright 2015 Horde LLC
 * @internal
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Pgp
 */
class Horde_Pgp_Crypt_DSA
{
    /**
     * Generate a number that lies between 0 and q-1.
     *
     * @param Math_BigInteger $q  Max number.
     *
     * @return Math_BigInteger  Generated number.
     */
    static public function randomNumber($q)
    {
        $bytes = strlen($q->toBytes()) + 8;
        $ints = ($bytes + 1) >> 2;
        $cstring = crypt_random_string($ints);

        $random = '';
        for ($i = 0; $i < $ints; ++$i) {
            $random .= pack('N', $cstring[$i]);
        }

        $c = new Math_BigInteger(
            substr($random, 0, $bytes),
            256
        );
        $one = new Math_BigInteger(1);
        $result_base = $c->divide($q->subtract($one));

        return $result_base[1]->add($one);
    }

    /**
     * Constructor.
     */
    public function __construct()
    {
        Horde_Pgp_Backend_Openpgp::autoload();
    }

    /**
     * DSA keypair creation.
     *
     * @param Math_BigInteger $p  p
     * @param Math_BigInteger $q  q
     * @param Math_BigInteger $g  g
     *
     * @return array  x = private key, y = public key.
     */
    public function generate($p, $q, $g)
    {
        $x = self::randomNumber($q);
        $y = $g->modPow($x, $p);

        return array('x' => $x, 'y' => $y);
    }

    /**
     * DSA sign
     *
     * @param string $message     Message.
     * @param string $hash_alg    Hash algorithm.
     * @param Math_BigInteger $p  p
     * @param Math_BigInteger $q  q
     * @param Math_BigInteger $g  g
     * @param Math_BigInteger $x  Private key.
     *
     * @return array  r,s key
     */
    public function sign($message, $hash_alg, $p, $q, $g, $x)
    {
        $hash = new Crypt_Hash($hash_alg);
        $zero = new Math_BigInteger();

        while (true) {
            $k = self::randomNumber($q);
            $r_base = $g->modPow($k, $p)->divide($q);
            $r = $r_base[1];

            if ($r->compare($zero) == 0) {
                continue;
            }

            $bigint_hash = new Math_BigInteger($hash->hash($message), 256);

            // compute H(m) + (x*r)
            $x_mul_r_base = $x->multiply($r)->divide($q);
            $x_mul_r = $x_mul_r_base[1];
            $message_dep_base = $bigint_hash->add($x_mul_r)->divide($q);
            $message_dep = $message_dep_base[1];

            // compute s
            $k_modInv = $k->modInverse($q);
            $k_modInv_mul = $k_modInv->multiply($message_dep);
            $s_base = $k_modInv_mul->divide($q);
            $s = $s_base[1];

            if ($s->compare($zero) != 0) {
                // r and s are non-zero, we can continue
                break;
            }
        }

        return array('r' => $r->toBytes(), 's' => $s->toBytes());
    }

    /**
     * DSA verify
     *
     * @param string $message     Message
     * @param string $hash_alg    Hash algorithm
     * @param Math_BigInteger $r  r
     * @param Math_BigInteger $s  s
     * @param Math_BigInteger $p  p
     * @param Math_BigInteger $q  q
     * @param Math_BigInteger $g  g
     * @param Math_BigInteger $y  Public key.
     *
     * @return bool  True if verified.
     */
    public function verify($message, $hash_alg, $r, $s, $p, $q, $g, $y)
    {
        $hash = new Crypt_Hash($hash_alg);
        $hash_m = new Math_BigInteger($hash->hash($message), 256);

        $w = $s->modInverse($q);

        $hash_m_mul = $hash_m->multiply($w);
        $u1_base = $hash_m_mul->divide($q);
        $u1 = $u1_base[1];

        $r_mul = $r->multiply($w);
        $u2_base = $r_mul->divide($q);
        $u2 = $u2_base[1];

        $g_pow = $g->modPow($u1, $p);
        $y_pow = $y->modPow($u2, $p);
        $g_pow_mul = $g_pow->multiply($y_pow);
        $g_pow_mul_mod_base = $g_pow_mul->divide($p);
        $g_pow_mul_mod = $g_pow_mul_mod_base[1];

        $v_base = $g_pow_mul_mod->divide($q);
        $v = $v_base[1];

        return ($v->compare($r) == 0);
    }
}
