<?php
/**
 * Generates nonces.
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
 * Generates nonces.
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
class Horde_Nonce_Generator
{
    /**
     * Size of the random part of the nonce.
     *
     * @var int
     */
    private $_size;

    /**
     * Constructor.
     *
     * @param int $size Size of the random part of the generated nonces (16 bits
     *                  per increment).
     */
    public function __construct($size = 1)
    {
        $this->_size = $size;
    }

    /**
     * Return a nonce.
     *
     * @return string The nonce.
     */
    public function create()
    {
        return pack('N', time()) . $this->_createRandom();
    }

    /**
     * Return the random part for a nonce.
     *
     * @return string The random part.
     */
    private function _createRandom()
    {
        $random = '';
        for ($i = 0;$i < $this->_size * 2; $i++) {
            $random .= pack('n', mt_rand());
        }
        return $random;
    }

    /**
     * Split a nonce into the timestamp and the random part.
     *
     * @param string $nonce The nonce to be splitted.
     *
     * @return array A list of two elements: the timestamp and the random part.
     */
    public function split($nonce)
    {
        $timestamp = unpack('N', substr($nonce, 0, 4));
        return array(
            array_pop($timestamp),
            unpack('n' . $this->_size * 2, substr($nonce, 4))
        );
    }
}
