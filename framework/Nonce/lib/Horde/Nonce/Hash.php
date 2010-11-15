<?php
/**
 * Hashes the random part of a nonce so that it can be stored in the Bloom
 * filter.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Nonce
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Nonce
 */

/**
 * Hashes the random part of a nonce so that it can be stored in the Bloom
 * filter.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Nonce
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Nonce
 */
class Horde_Nonce_Hash
{
    /**
     * Number of hash functions / resulting hash keys.
     *
     * @var int
     */
    private $_keys;

    /**
     * Bit length of the hash keys.
     *
     * @var int
     */
    private $_size;

    /**
     * Constructor.
     *
     * @param int $keys Number of resulting hash keys.
     * @param int $size Size of the resulting hash keys.
     */
    public function __construct($keys = 3, $size = 196)
    {
        $this->_keys = $keys;
        $this->_size = $size;
    }

    /**
     * Hash the random part of a nonce.
     *
     * @param array $random The random part of the nonce splitted into two byte segments.
     *
     * @return array The resulting hash key array.
     */
    public function hash(array $random)
    {
        /**
         * Use only 31 bit of randomness as this is sufficient for the hashing
         * and avoids troubles with signed integers.
         */
        $start = array_pop($random);
        $start |= (array_pop($random) & (pow(2, 15) - 1)) << 16;

        $hash = array();
        $hash[0] = $start % 197;
        $start = (int) $start / 197;
        $hash[1] = $start % 197;
        $start = (int) $start / 197;
        $hash[2] = $start % 197;

        return $hash;
    }
}
