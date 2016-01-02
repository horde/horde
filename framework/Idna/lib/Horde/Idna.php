<?php
/**
 * Copyright 2014-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @category  Horde
 * @copyright 2014-2016 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Idna
 */

/**
 * Provide normalized encoding/decoding support for IDNA strings.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014-2016 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Idna
 */
class Horde_Idna
{
    /**
     * The backend to use.
     *
     * @var mixed
     */
    protected static $_backend;

    /**
     * @throws Horde_Idna_Exception
     */
    public static function encode($data)
    {
        switch ($backend = static::_getBackend()) {
        case 'INTL':
            return idn_to_ascii($data);

        case 'INTL_UTS46':
            return idn_to_ascii($data, 0, INTL_IDNA_VARIANT_UTS46);

        default:
            return $backend->encode($data);
        }
    }

    /**
     * @throws Horde_Idna_Exception
     */
    public static function decode($data)
    {
        switch ($backend = static::_getBackend()) {
        case 'INTL':
        case 'INTL_UTS46':
            $parts = explode('.', $data);
            foreach ($parts as &$part) {
                if (strpos($part, 'xn--') === 0) {
                    switch ($backend) {
                    case 'INTL':
                        $part = idn_to_utf8($part);
                        break;

                    case 'INTL_UTS46':
                        $part = idn_to_utf8($part, 0, INTL_IDNA_VARIANT_UTS46);
                        break;
                    }
                }
            }
            return implode('.', $parts);

        default:
            return $backend->decode($data);
        }
    }

    /**
     * Return the IDNA backend.
     *
     * @return mixed  IDNA backend (false if none available).
     */
    protected static function _getBackend()
    {
        if (!isset(self::$_backend)) {
            if (extension_loaded('intl')) {
                /* Only available in PHP > 5.4.0 */
                self::$_backend = defined('INTL_IDNA_VARIANT_UTS46')
                    ? 'INTL_UTS46'
                    : 'INTL';
            } else {
                self::$_backend = new Horde_Idna_Punycode();
            }
        }

        return self::$_backend;
    }

}
