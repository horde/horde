<?php
/**
 * Copyright 2008-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @package  Date_Parser
 */

/**
 *
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2008-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL
 * @package   Date_Parser
 */
class Horde_Date_Parser
{
    public static function parse($text, $args = array())
    {
        $factoryArgs = $args;
        unset($args['locale']);

        return self::factory($factoryArgs)->parse($text, $args);
    }

    public static function factory($args = array())
    {
        $locale = isset($args['locale']) ? $args['locale'] : null;
        if ($locale && Horde_String::lower($locale) != 'base') {
            $locale = str_replace(' ', '_', Horde_String::ucwords(str_replace('_', ' ', Horde_String::lower($locale))));
            $class = 'Horde_Date_Parser_Locale_' . $locale;
            if (class_exists($class)) {
                return new $class($args);
            }

            $language = array_shift(explode('_', $locale));
            if ($language != $locale) {
                $class = 'Horde_Date_Parser_Locale_' . $language;
                if (class_exists($class)) {
                    return new $class($args);
                }
            }
        }

        return new Horde_Date_Parser_Locale_Base($args);
    }

    /**
     * Return a list of available locales
     */
    public static function getLocales()
    {
        $dir = __DIR__ . '/Parser/Locale';
        $locales = array();
        foreach (new DirectoryIterator($dir) as $f) {
            if ($f->isFile()) {
                $locale = str_replace('.php', '', $f->getFilename());
                $locale = preg_replace_callback(
                    '/([A-Z][a-z]*)([A-Z].*)?/',
                    function ($m) {
                        if (!isset($m[2])) {
                            return Horde_String::lower($m[1]);
                        } else {
                            return Horde_String::lower($m[1])
                                . '_' . Horde_String::upper($m[2]);
                        }
                    },
                    $locale
                );
                $locales[] = $locale;
            }
        }

        return $locales;
    }

}
