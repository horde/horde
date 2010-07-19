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
     * Outputs the necessary style tags, honoring configuration choices as
     * to stylesheet caching.
     *
     * @param array $options  Additional options:
     * <pre>
     * 'additional' - (array) TODO
     * 'nohorde' - (boolean) If true, don't load files from Horde.
     * 'sub' - (string) A subdirectory containing additional CSS files to
     *         load as an overlay to the base CSS files.
     * 'subonly' - (boolean) If true, only load the files in 'sub', not
     *             the default theme files.
     * 'theme' - (string) Use this theme instead of the default.
     * 'themeonly' - (boolean) If true, only load the theme files.
     * </pre>
     */
    static public function includeStylesheetFiles($options = array())
    {
        global $conf, $prefs, $registry;

        $themesfs = $registry->get('themesfs');
        $themesuri = $registry->get('themesuri');

        $css = self::getStylesheets(isset($options['theme']) ? $options['theme'] : $prefs->getValue('theme'), $options);
        $css_out = array();

        if (!empty($options['additional'])) {
            $css = array_merge($css, $options['additional']);
        }

        $cache_type = empty($conf['cachecss'])
            ? 'none'
            : $conf['cachecssparams']['driver'];

        if ($cache_type == 'none') {
            $css_out = $css;
        } else {
            $mtime = array(0);
            $out = '';

            foreach ($css as $file) {
                $mtime[] = filemtime($file['f']);
            }

            $sig = hash('md5', serialize($css) . max($mtime));

            switch ($cache_type) {
            case 'filesystem':
                $css_filename = '/static/' . $sig . '.css';
                $css_path = $registry->get('fileroot', 'horde') . $css_filename;
                $css_url = $registry->get('webroot', 'horde') . $css_filename;
                $exists = file_exists($css_path);
                break;

            case 'horde_cache':
                $cache = $GLOBALS['injector']->getInstance('Horde_Cache');

                // Do lifetime checking here, not on cache display page.
                $exists = $cache->exists($sig, empty($GLOBALS['conf']['cachecssparams']['lifetime']) ? 0 : $GLOBALS['conf']['cachecssparams']['lifetime']);
                $css_url = Horde::getCacheUrl('css', array('cid' => $sig));
                break;
            }

            if (!$exists) {
                $out = self::loadCssFiles($css);

                /* Use CSS tidy to clean up file. */
                if ($conf['cachecssparams']['compress'] == 'php') {
                    try {
                        $out = $GLOBALS['injector']->getInstance('Horde_Text_Filter')->filter($out, 'csstidy');
                    } catch (Horde_Exception $e) {}
                }

                switch ($cache_type) {
                case 'filesystem':
                    if (!file_put_contents($css_path, $out)) {
                        throw new Horde_Exception('Could not write cached CSS file to disk.');
                    }
                    break;

                case 'horde_cache':
                    $cache->set($sig, $out);
                    break;
                }
            }

            $css_out = array(array('u' => $css_url));
        }

        foreach ($css_out as $file) {
            echo '<link href="' . $file['u'] . "\" rel=\"stylesheet\" type=\"text/css\" />\n";
        }
    }

    /**
     * Callback for includeStylesheetFiles() to convert images to base64
     * data strings.
     *
     * @param array $matches  The list of matches from preg_replace_callback.
     *
     * @return string  The image string.
     */
    static public function stylesheetCallback($matches)
    {
        /* Limit data to 16 KB in stylesheets. */
        return $matches[1] . Horde::base64ImgData($matches[2], 16384) . $matches[3];
    }

    /**
     * Return the list of base stylesheets to display.
     * Callback for includeStylesheetFiles() to convert images to base64
     * data strings.
     *
     * @param mixed $theme    The theme to use; specify an empty value to
     *                        retrieve the theme from user preferences, and
     *                        false for no theme.
     * @param array $options  Additional options:
     * <pre>
     * 'app' - (string) The current application.
     * 'nohorde' - (boolean) If true, don't load files from Horde.
     * 'sub' - (string) A subdirectory containing additional CSS files to
     *         load as an overlay to the base CSS files.
     * 'subonly' - (boolean) If true, only load the files in 'sub', not
     *             the default theme files.
     * 'themeonly' - (boolean) If true, only load the theme files.
     * </pre>
     *
     * @return array  TODO
     */
    static public function getStylesheets($theme = '', $options = array())
    {
        if (($theme === '') && isset($GLOBALS['prefs'])) {
            $theme = $GLOBALS['prefs']->getValue('theme');
        }

        $css = array();

        $css_list = array('screen');
        if (isset($GLOBALS['registry']->nlsconfig['rtl'][$GLOBALS['language']])) {
            $css_list[] = 'rtl';
        }

        /* Collect browser specific stylesheets if needed. */
        switch ($GLOBALS['browser']->getBrowser()) {
        case 'msie':
            $ie_major = $GLOBALS['browser']->getMajor();
            if ($ie_major == 8) {
                $css_list[] = 'ie8';
            } elseif ($ie_major == 7) {
                $css_list[] = 'ie7';
            } elseif ($ie_major < 7) {
                $css_list[] = 'ie6_or_less';
            }
            break;


        case 'opera':
            $css_list[] = 'opera';
            break;

        case 'mozilla':
            $css_list[] = 'mozilla';
            break;

        case 'webkit':
            $css_list[] = 'webkit';
        }

        $curr_app = empty($options['app'])
            ? $GLOBALS['registry']->getApp()
            : $options['app'];
        if (empty($options['nohorde'])) {
            $apps = array_unique(array('horde', $curr_app));
        } else {
            $apps = ($curr_app == 'horde') ? array() : array($curr_app);
        }
        $sub = empty($options['sub']) ? null : $options['sub'];

        foreach ($apps as $app) {
            $themes_fs = $GLOBALS['registry']->get('themesfs', $app) . '/';
            $themes_uri = Horde::url($GLOBALS['registry']->get('themesuri', $app), false, -1) . '/';

            foreach ($css_list as $css_name) {
                if (empty($options['subonly'])) {
                    $css[$themes_fs . $css_name . '.css'] = $themes_uri . $css_name . '.css';
                }

                if ($sub && ($app == $curr_app)) {
                    $css[$themes_fs . $sub . '/' . $css_name . '.css'] = $themes_uri . $sub . '/' . $css_name . '.css';
                }

                if (!empty($theme)) {
                    if (empty($options['subonly'])) {
                        $css[$themes_fs . $theme . '/' . $css_name . '.css'] = $themes_uri . $theme . '/' . $css_name . '.css';
                    }

                    if ($sub && ($app == $curr_app)) {
                        $css[$themes_fs . $theme . '/' . $sub . '/' . $css_name . '.css'] = $themes_uri . $theme . '/' . $sub . '/' . $css_name . '.css';
                    }
                }
            }
        }

        /* Add user-defined additional stylesheets. */
        try {
            $css = array_merge($css, Horde::callHook('cssfiles', array($theme), 'horde'));
        } catch (Horde_Exception_HookNotSet $e) {}
        if ($curr_app != 'horde') {
            try {
                $css = array_merge($css, Horde::callHook('cssfiles', array($theme), $curr_app));
            } catch (Horde_Exception_HookNotSet $e) {}
        }

        $css_out = array();
        foreach ($css as $f => $u) {
            if (file_exists($f)) {
                $css_out[] = array('f' => $f, 'u' => $u);
            }
        }

        return $css_out;
    }

    /**
     * Loads CSS files, cleans up the input, and concatenates to a string.
     *
     * @param array $files  List of CSS files as returned from
     *                      getStylesheets().
     *
     * @return string  CSS data.
     */
    static public function loadCssFiles($files)
    {
        $flags = defined('FILE_IGNORE_NEW_LINES')
            ? (FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)
            : 0;
        $out = '';

        foreach ($files as $file) {
            $path = substr($file['u'], 0, strrpos($file['u'], '/') + 1);

            // Fix relative URLs, convert graphics URLs to data URLs
            // (if possible), remove multiple whitespaces, and strip
            // comments.
            $tmp = preg_replace(array('/(url\(["\']?)([^\/])/i', '/\s+/', '/\/\*.*?\*\//'), array('$1' . $path . '$2', ' ', ''), implode('', file($file['f'], $flags)));
            if ($GLOBALS['browser']->hasFeature('dataurl')) {
                $tmp = preg_replace_callback('/(background(?:-image)?:[^;}]*(?:url\(["\']?))(.*?)((?:["\']?\)))/i', array(__CLASS__, 'stylesheetCallback'), $tmp);
            }
            $out .= $tmp;
        }

        return $out;
    }

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
     * 'notheme' - (boolean) If true, do not use themed data.
     * 'theme' - (string) Use this theme instead of the Horde default.
     * </pre>
     *
     * @return Horde_Themes_Image  An object which contains the URI
     *                             and filesystem location of the image.
     */
    static public function img($name = null, $options = array())
    {
        return self::_getObject('graphics', $name, $options);
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
     * 'notheme' - (boolean) If true, do not use themed data.
     * 'theme' - (string) Use this theme instead of the Horde default.
     * </pre>
     *
     * @return Horde_Themes_Image  An object which contains the URI
     *                             and filesystem location of the sound.
     */
    static public function sound($name = null, $options = array())
    {
        return self::_getObject('sounds', $name, $options);
    }

    /**
     * Return the path to a themes element, using the default element if the
     * image does not exist in the current theme.
     *
     * @param string $type    The element type ('graphics', 'sound').
     * @param string $name    The element name. If null, will return the
     *                        element directory.
     * @param mixed $options  Additional options. If a string, is taken to be
     *                        the 'app' parameter. If an array, the following
     *                        options are available:
     * <pre>
     * 'app' - (string) Use this application instead of the current app.
     * 'nohorde' - (boolean) If true, do not fallback to horde for element.
     * 'notheme' - (boolean) If true, do not use themed data.
     * 'theme' - (string) Use this theme instead of the Horde default.
     * </pre>
     *
     * @return Horde_Themes_Element  An object which contains the URI and
     *                               filesystem location of the element.
     */
    static protected function _getObject($type, $name, $options)
    {
        if (is_string($options)) {
            $app = $options;
            $options = array();
        } else {
            $app = empty($options['app'])
                ? $GLOBALS['registry']->getApp()
                : $options['app'];
        }
        if ($GLOBALS['registry']->get('status', $app) == 'heading') {
            $app = 'horde';
        }

        $app_list = array($app);
        if ($app != 'horde' && empty($options['nohorde'])) {
            $app_list[] = 'horde';
        }
        $path = '/' . $type . (is_null($name) ? '' : '/' . $name);

        $classname = ($type == 'graphics')
            ? 'Horde_Themes_Image'
            : 'Horde_Themes_Sound';

        /* Check themes first. */
        if (empty($options['notheme']) &&
            isset($GLOBALS['prefs']) &&
            (($theme = $GLOBALS['prefs']->getValue('theme')) ||
             (!empty($options['theme']) && ($theme = $options['theme'])))) {
            $tpath = '/' . $theme . $path;
            foreach ($app_list as $app) {
                $filepath = $GLOBALS['registry']->get('themesfs', $app) . $tpath;
                if (is_null($name) || file_exists($filepath)) {
                    return new $classname($GLOBALS['registry']->get('themesuri', $app) . $tpath, $filepath);
                }
            }
        }

        /* Fall back to app/horde defaults. */
        foreach ($app_list as $app) {
            $filepath = $GLOBALS['registry']->get('themesfs', $app) . $path;
            if (file_exists($filepath)) {
                return new $classname($GLOBALS['registry']->get('themesuri', $app) . $path, $filepath);
            }
        }

        return '';
    }

    /**
     * Returns a list of available sounds for a theme.
     *
     * @return array  An array of Horde_Themes_Sound objects. Keys are the
     *                base filenames.
     */
    static public function soundList()
    {
        $app = $GLOBALS['registry']->getApp();

        /* Do search in reverse order - app + theme sounds have the highest
         * priority and will overwrite previous sound definitions. */
        $locations = array(
            self::sound(null, array('app' => 'horde', 'notheme' => true)),
            // Placeholder for app
            null,
            self::sound(null, 'horde')
        );

        if ($app != 'horde') {
            $locations[1] = self::sound(null, array('app' => $app, 'notheme' => true));
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

}
