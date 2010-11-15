<?php
/**
 * The nonce handler.
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
 * The nonce handler.
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
class Horde_Nonce
{
    /**
     * The nonce generator.
     *
     * @var Horde_Nonce_Generator
     */
    private $_generator;

    /**
     * Hashes the random part of a nonce for storage in the Bloom filter.
     *
     * @var Horde_Nonce_Hash
     */
    private $_hash;

    /**
     * Constructor.
     *
     * @param Horde_Nonce_Hash $hash Hashes the random part of a nonce for
     *                               storage in the Bloom filter.
     * @param int              $size Size of the random part of the generated
     *                               nonces.
     */
    public function __construct(
        Horde_Nonce_Generator $generator,
        Horde_Nonce_Hash $hash
    ) {
        $this->_generator = $generator;
        $this->_hash = $hash;
    }

    /**
     * Return a nonce.
     *
     * @return string The nonce.
     */
    public function create()
    {
        return $this->_generator->create();
    }

    /**
     * Validate a nonce.
     *
     * @param string $nonce   The nonce that should be validate.
     * @param float  $timeout The nonce should be invalid after this amount of time.
     *
     * @return boolean True if the nonce is still valid.
     */
    public function isValid($nonce, $timeout = -1)
    {
        list($timestamp, $random) = $this->_generator->split($nonce);
        if ($timeout > 0 && $timestamp < (time() - $timeout)) {
            return false;
        }
        
        return true;
    }
}
