<?php
/**
 * Horde_Kolab_Format_Translation is the translation wrapper class for
 * Horde_Kolab_Format.

 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Jan Schneider <jan@horde.org>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Format
 */

/**
 * Horde_Kolab_Format_Translation is the translation wrapper class for
 * Horde_Kolab_Format.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Jan Schneider <jan@horde.org>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Format
 */
class Horde_Kolab_Format_Translation extends Horde_Translation
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
        self::$_domain = 'Horde_Kolab_Format';
        self::$_directory = '@data_dir@' == '@'.'data_dir'.'@' ? dirname(__FILE__) . '/../../../../locale' : '@data_dir@/Horde_Kolab_Format/locale';
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
        self::$_domain = 'Horde_Kolab_Format';
        self::$_directory = '@data_dir@' == '@'.'data_dir'.'@' ? dirname(__FILE__) . '/../../../../locale' : '@data_dir@/Horde_Kolab_Format/locale';
        return parent::ngettext($singular, $plural, $number);
    }
}
