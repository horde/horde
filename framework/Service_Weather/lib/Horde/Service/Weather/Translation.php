<?php
/**
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 *
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package Service_Weather
 */

/**
 * Horde_Service_Weather_Translation is the translation wrapper class for
 * Horde_Service_Weather
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Service_Weather
 */
class Horde_Service_Weather_Translation extends Horde_Translation
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
        self::$_domain = 'Horde_Service_Weather';
        self::$_directory = '@data_dir@' == '@'.'data_dir'.'@' ? __DIR__ . '/../../../../locale' : '@data_dir@/Horde_Service_Weather/locale';
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
        self::$_domain = 'Horde_Service_Weather';
        self::$_directory = '@data_dir@' == '@'.'data_dir'.'@' ? __DIR__ . '/../../../../locale' : '@data_dir@/Horde_Service_Weather/locale';
        return parent::ngettext($singular, $plural, $number);
    }

}