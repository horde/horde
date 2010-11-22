<?php
/**
 * The Horde_Themes:: class provides an interface to handling Horde themes.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @package  Core
 */
class Horde_Themes
{
    /**
     * Return the path to an image, using the default image if the image does
     * not exist in the current theme.
     *
     * @param string $name    The image name. If null, will return the image
     *                        directory.
     * @param mixed $options  Additional options. If a string, is taken to be
     *                        the 'app' parameter. If an array, the following
     *                        options are available:
     * <pre>
     * 'app' - (string) Use this application instead of the current app.
     * 'nohorde' - (boolean) If true, do not fallback to horde for image.
     * 'theme' - (string) Use this theme instead of the Horde default.
     * </pre>
     *
     * @return Horde_Themes_Image  An object which contains the URI
     *                             and filesystem location of the image.
     */
    static public function img($name = null, $options = array())
    {
        if (is_string($options)) {
            $options = array('app' => $options);
        }

        return new Horde_Themes_Image($name, $options);
    }

    /**
     * Return the path to a sound, using the default sound if the sound does
     * not exist in the current theme.
     *
     * @param string $name    The sound name. If null, will return the sound
     *                        directory.
     * @param mixed $options  Additional options. If a string, is taken to be
     *                        the 'app' parameter. If an array, the following
     *                        options are available:
     * <pre>
     * 'app' - (string) Use this application instead of the current app.
     * 'nohorde' - (boolean) If true, do not fallback to horde for sound.
     * 'theme' - (string) Use this theme instead of the Horde default.
     * </pre>
     *
     * @return Horde_Themes_Sound  An object which contains the URI
     *                             and filesystem location of the sound.
     */
    static public function sound($name = null, $options = array())
    {
        if (is_string($options)) {
            $options = array('app' => $options);
        }

        return new Horde_Themes_Sound($name, $options);
    }

    /**
     * Returns a list of available themes.
     *
     * @return array  Keys are theme names, values are theme descriptions.
     * @throws UnexpectedValueException
     */
    static public function themeList()
    {
        $out = array();

        // Throws UnexpectedValueException
        $di = new DirectoryIterator($GLOBALS['registry']->get('themesfs', 'horde'));

        foreach ($di as $val) {
            $theme_name = null;

            if ($val->isDir() &&
                !$di->isDot() &&
                (@include $val->getPathname() . '/info.php')) {
                $out[strval($val)] = $theme_name;
            }
        }

        asort($out);

        return $out;
    }

    /**
     * Returns a list of available sounds for a theme.
     *
     * @param string $app  The app to search in.
     *
     * @return array  An array of Horde_Themes_Sound objects. Keys are the
     *                base filenames.
     */
    static public function soundList($app = null)
    {
        if (is_null($app)) {
            $app = $GLOBALS['registry']->getApp();
        }

        /* Do search in reverse order - app + theme sounds have the highest
         * priority and will overwrite previous sound definitions. */
        $locations = array(
            self::sound(null, array('app' => 'horde', 'theme' => null)),
            // Placeholder for app
            null,
            self::sound(null, 'horde')
        );

        if ($app != 'horde') {
            $locations[1] = self::sound(null, array('app' => $app, 'theme' => null));
            $locations[3] = self::sound(null, $app);
        }

        $sounds = array();
        foreach ($locations as $val) {
            if ($val) {
                foreach (glob($val->fs . '/*.wav') as $file) {
                    $file = basename($file);
                    if (!isset($sounds[$file])) {
                        $sounds[$file] = self::sound($file);
                    }
                }
            }
        }

        ksort($sounds);
        return $sounds;
    }

    /**
     * Return the location of the feed XSL file.
     *
     * As of now, this file MUST live in horde/themes/default/feed-rss.xsl.
     *
     * @return string  Path to the feed file.
     */
    static public function getFeedXsl()
    {
         return $GLOBALS['registry']->get('themesuri', 'horde') . '/default/feed-rss.xsl';
    }

}
