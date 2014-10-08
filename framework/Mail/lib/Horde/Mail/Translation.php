<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/bsd New BSD License
 * @package   Mail
 */

/**
 * Translation wrapper for the Horde_Mail package.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/bsd New BSD License
 * @package   Mail
 * @since     2.5.0
 */
class Horde_Mail_Translation
extends Horde_Translation
{
    /**
     */
    public static function t($message)
    {
        self::$_domain = 'Horde_Mail';
        self::$_directory = '@data_dir@' == '@'.'data_dir'.'@' ? __DIR__ . '/../../../locale' : '@data_dir@/Horde_Mail/locale';
        return parent::t($message);
    }

    /**
     */
    public static function ngettext($singular, $plural, $number)
    {
        self::$_domain = 'Horde_Mail';
        self::$_directory = '@data_dir@' == '@'.'data_dir'.'@' ? __DIR__ . '/../../../locale' : '@data_dir@/Horde_Mail/locale';
        return parent::ngettext($singular, $plural, $number);
    }

}
