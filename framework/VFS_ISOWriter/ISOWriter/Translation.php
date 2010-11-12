<?php
/**
 * @package VFS_ISOWriter
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

/**
 * Horde_VFS_ISOWriter_Translation is the translation wrapper class for Horde_VFS_ISOWriter.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package VFS_ISOWriter
 */
class Horde_VFS_ISOWriter_Translation extends Horde_Translation
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
        self::$_domain = 'Horde_VFS_ISOWriter';
        self::$_directory = '@data_dir@' == '@'.'data_dir'.'@' ? dirname(__FILE__) . '/../locale' : '@data_dir@/VFS_ISOWriter/locale';
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
        self::$_domain = 'Horde_VFS_ISOWriter';
        self::$_directory = '@data_dir@' == '@'.'data_dir'.'@' ? dirname(__FILE__) . '/../locale' : '@data_dir@/VFS_ISOWriter/locale';
        return parent::ngettext($singular, $plural, $number);
    }
}
