<?php
/**
 *
 */
class Horde_Date_Parser
{
    public static function factory($args = array())
    {
        $locale = isset($args['locale']) ? $args['locale'] : null;
        if ($locale && strtolower($locale) != 'base') {
            $locale = str_replace(' ', '_', ucwords(str_replace('_', ' ', strtolower($locale))));
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

    public static function componentFactory($component, $args = array())
    {
        $locale = isset($args['locale']) ? $args['locale'] : null;
        if ($locale && strtolower($locale) != 'base') {
            $locale = str_replace(' ', '_', ucwords(str_replace('_', ' ', strtolower($locale))));
            $class = 'Horde_Date_Parser_Locale_' . $locale . '_' . $component;
            if (class_exists($class)) {
                return new $class($args);
            }

            $language = array_shift(explode('_', $locale));
            if ($language != $locale) {
                $class = 'Horde_Date_Parser_Locale_' . $language . '_' . $component;
                if (class_exists($class)) {
                    return new $class($args);
                }
            }
        }

        $class = 'Horde_Date_Parser_Locale_Base_' . $component;
        return new $class($args);
    }

}
