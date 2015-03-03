<?php
/**
 * Copyright 2014-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Idna
 */

/**
 * Provide normalized encoding/decoding support for IDNA strings.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
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
        return static::_getBackend()->encode($data);
    }

    /**
     * @throws Horde_Idna_Exception
     */
    public static function decode($data)
    {
        return static::_getBackend()->decode($data);
    }

    /**
     * Return the IDNA backend.
     *
     * @return mixed  IDNA backend (false if none available).
     */
    protected static function _getBackend()
    {
        if (!isset(self::$_backend)) {
            self::$_backend = new Horde_Idna_Punycode();
        }

        return self::$_backend;
    }

}
