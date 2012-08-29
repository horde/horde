<?php
/**
 *
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
                $locale = preg_replace_callback('/([A-Z][a-z]*)([A-Z].*)?/', create_function('$m', 'if (!isset($m[2])) { return strtolower($m[1]); } else { return strtolower($m[1]) . "_" . strtoupper($m[2]); }'), $locale);
                $locales[] = $locale;
            }
        }

        return $locales;
    }

}
