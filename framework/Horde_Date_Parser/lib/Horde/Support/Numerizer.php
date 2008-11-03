<?php
/**
 *
 */
class Horde_Support_Numerizer
{
    public static function factory($locale = null, $args = array())
    {
        if ($locale) {
            $locale = ucfirst($locale);
            $class = 'Horde_Support_Numerizer_Locale_' . $locale;
            if (class_exists($class)) {
                return new $class($args);
            }

            $country = array_shift(explode('_', $locale));
            if ($country != $locale) {
                $class = 'Horde_Support_Numerizer_Locale_' . $country;
                if (class_exists($class)) {
                    return new $class($args);
                }
            }
        }

        return new Horde_Support_Numerizer_Locale_Base($args);
    }

}
