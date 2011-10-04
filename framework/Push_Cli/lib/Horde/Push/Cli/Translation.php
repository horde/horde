<?php
/**
 * Horde_Push_Cli_Translation is the translation wrapper class for Horde_Push_Cli.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Push_Cli
 * @author   Jan Schneider <jan@horde.org>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/gpl GPL-2.0
 * @link     http://www.horde.org/components/Horde_Push_Cli
 */

/**
 * Horde_Push_Cli_Translation is the translation wrapper class for Horde_Push_Cli.
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL-2.0). If you did
 * not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category Horde
 * @package  Push_Cli
 * @author   Jan Schneider <jan@horde.org>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/gpl GPL-2.0
 * @link     http://www.horde.org/components/Horde_Push_Cli
 */
class Horde_Push_Cli_Translation extends Horde_Translation
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
        self::$_domain = 'Horde_Push_Cli';
        self::$_directory = '@data_dir@' == '@'.'data_dir'.'@' ? dirname(__FILE__) . '/../../../../locale' : '@data_dir@/Horde_Push_Cli/locale';
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
        self::$_domain = 'Horde_Push_Cli';
        self::$_directory = '@data_dir@' == '@'.'data_dir'.'@' ? dirname(__FILE__) . '/../../../../locale' : '@data_dir@/Horde_Push_Cli/locale';
        return parent::ngettext($singular, $plural, $number);
    }
}
