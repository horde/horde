<?php
/**
 * Horde_Kolab_Cli_Translation is the translation wrapper class for Horde_Kolab_Cli.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Cli
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Cli
 */

/**
 * Horde_Kolab_Cli_Translation is the translation wrapper class for Horde_Kolab_Cli.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Cli
 * @author   Jan Schneider <jan@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Cli
 */
class Horde_Kolab_Cli_Translation extends Horde_Translation
{
    /**
     * Returns the translation of a message.
     *
     * @var string $message  The string to translate.
     *
     * @return string  The string translation, or the original string if no
     *                 translation exists.
     */
    static public function t($message)
    {
        self::$_domain = 'Horde_Kolab_Cli';
        self::$_directory = '@data_dir@' == '@'.'data_dir'.'@' ? __DIR__ . '/../../../../locale' : '@data_dir@/Horde_Kolab_Cli/locale';
        return parent::t($message);
    }

    /**
     * Returns the plural translation of a message.
     *
     * @param string $singular  The singular version to translate.
     * @param string $plural    The plural version to translate.
     * @param integer $number   The number that determines singular vs. plural.
     *
     * @return string  The string translation, or the original string if no
     *                 translation exists.
     */
    static public function ngettext($singular, $plural, $number)
    {
        self::$_domain = 'Horde_Kolab_Cli';
        self::$_directory = '@data_dir@' == '@'.'data_dir'.'@' ? __DIR__ . '/../../../../locale' : '@data_dir@/Horde_Kolab_Cli/locale';
        return parent::ngettext($singular, $plural, $number);
    }
}
