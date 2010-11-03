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
     * Return a nonce.
     *
     * @return string The nonce.
     */
    public function get()
    {
        return pack('Nn2', time(), mt_rand(), mt_rand());
    }

    /**
     * Validate a nonce.
     *
     * @param string $nonce   The nonce that should be validate.
     * @param float  $timeout The nonce should be invalid after this amount of time.
     *
     * @return boolean True if the nonce is still valid.
     */
    public function isValid($nonce, $timeout)
    {
        $timestamp = unpack('N', substr($nonce, 0, 4));
        if (array_pop($timestamp) < (time() - $timeout)) {
            return false;
        }
        return true;
    }
}
