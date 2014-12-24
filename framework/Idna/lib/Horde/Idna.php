<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Idna
 */

/**
 * Provide normalized encoding/decoding support for IDNA strings.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Idna
 */
class Horde_Idna
{
    /**
     * The backend object to use.
     *
     * @var mixed
     */
    protected static $_backend;

    /**
     * @throws Horde_Idna_Exception
     */
    public static function encode($data)
    {
        if (($backend = self::_getBackend()) === false) {
            throw new Horde_Idna_Exception('No IDNA backend available.');
        }

        return $backend->encode($data);
    }

    /**
     * @throws Horde_Idna_Exception
     */
    public static function decode($data)
    {
        if (($backend = self::_getBackend()) === false) {
            throw new Horde_Idna_Exception('No IDNA backend available.');
        }

        return $backend->decode($data);
    }

    /**
     * Return the IDNA backend.
     *
     * @return mixed  IDNA backend (false if none available).
     */
    protected static function _getBackend()
    {
        if (!isset(self::$_backend)) {
            if (extension_loaded('mbstring')) {
                if (file_exists(__DIR__ . '/vendor/autoload.php')) {
                    require_once __DIR__ . '/vendor/autoload.php';
                } else {
                    require_once __DIR__ . '/../../bundle/vendor/autoload.php';
                }
                self::$_backend = new True\Punycode();
                mb_internal_encoding('UTF-8');
            } elseif (class_exists('Net_IDNA2')) {
                self::$_backend = new Net_IDNA2();
            } elseif (class_exists('Net_IDNA')) {
                self::$_backend = new Net_IDNA();
            } else {
                self::$_backend = false;
            }
        }

        return self::$_backend;
    }

}
