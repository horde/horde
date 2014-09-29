<?php
/**
 * Copyright 2011-2014 Horde LLC (http://www.horde.org/)
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
class Horde_Service_Weather_Translation extends Horde_Translation_Autodetect
{
    /**
     * The translation domain
     *
     * @var string
     */
    protected static $_domain = 'Horde_Service_Weather';

    /**
     * The absolute PEAR path to the translations for the default gettext handler.
     *
     * @var string
     */
    protected static $_pearDirectory = '@data_dir@';
}
