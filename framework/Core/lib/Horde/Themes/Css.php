<?php
/**
 * This class provides an interface to handling CSS stylesheets for Horde
 * applications.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Core
 */
class Horde_Themes_Css
{
    /**
     * A list of additional stylesheet files to add to the output.
     *
     * @var array
     */
    protected $_cssFiles = array();

    /**
     * Adds an external stylesheet to the output.
     *
     * @param string $file
     */
    public function addStylesheet($file, $url)
    {
        $this->_cssFiles[$file] = $url;
    }

    /**
     * Generate the stylesheet URLs needed to display the current page.
     * Honors configuration choices as to stylesheet caching.
     *
     * @param array $options  Additional options:
     * <pre>
     * 'nohorde' - (boolean) If true, don't load files from Horde.
     * 'sub' - (string) A subdirectory containing additional CSS files to
     *         load as an overlay to the base CSS files.
     * 'subonly' - (boolean) If true, only load the files in 'sub', not
     *             the default theme files.
     * 'theme' - (string) Use this theme instead of the default.
     * 'themeonly' - (boolean) If true, only load the theme files.
     * </pre>
     *
     * @return array  The list of URLs to display.
     */
    public function getStylesheetUrls($options = array())
    {
        global $conf, $injector, $prefs, $registry;

        $themesfs = $registry->get('themesfs');
        $themesuri = $registry->get('themesuri');

        $css = $this->getStylesheets(isset($options['theme']) ? $options['theme'] : $prefs->getValue('theme'), $options);
        $css_out = array();

        $cache_type = empty($conf['cachecss'])
            ? 'none'
            : $conf['cachecssparams']['driver'];

        if ($cache_type == 'none') {
            $css_out = array();
            foreach ($css as $file) {
                $css_out[] = $file['u'];
            }
            return $css_out;
        }

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
            $cache = $injector->getInstance('Horde_Cache');

            // Do lifetime checking here, not on cache display page.
            $exists = $cache->exists($sig, empty($conf['cachecssparams']['lifetime']) ? 0 : $conf['cachecssparams']['lifetime']);
            $css_url = Horde::getCacheUrl('css', array('cid' => $sig));
            break;
        }

        if (!$exists) {
            $out = $this->loadCssFiles($css);

            /* Use CSS tidy to clean up file. */
            if ($conf['cachecssparams']['compress'] == 'php') {
                try {
                    $out = $injector->getInstance('Horde_Core_Factory_TextFilter')->filter($out, 'csstidy');
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

        return $css_url;
    }

    /**
     * Return the list of base stylesheets to display.
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
    public function getStylesheets($theme = '', $options = array())
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

        /* Add additional stylesheets added by code. */
        $css = array_merge($css, $this->_cssFiles);

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
    public function loadCssFiles($files)
    {
        $dataurl = $GLOBALS['browser']->hasFeature('dataurl');
        $out = '';

        foreach ($files as $file) {
            $path = substr($file['u'], 0, strrpos($file['u'], '/') + 1);

            // Fix relative URLs, convert graphics URLs to data URLs
            // (if possible), remove multiple whitespaces, and strip
            // comments.
            $tmp = preg_replace(array('/(url\(["\']?)([^\/])/i', '/\s+/', '/\/\*.*?\*\//'), array('$1' . $path . '$2', ' ', ''), implode('', file($file['f'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)));
            if ($dataurl) {
                $tmp = preg_replace_callback('/(background(?:-image)?:[^;}]*(?:url\(["\']?))(.*?)((?:["\']?\)))/i', array($this, '_stylesheetCallback'), $tmp);
            }
            $out .= $tmp;
        }

        return $out;
    }

    /**
     * Callback for loadCssFiles() to convert images to base64 data
     * strings.
     *
     * @param array $matches  The list of matches from preg_replace_callback.
     *
     * @return string  The image string.
     */
    protected function _stylesheetCallback($matches)
    {
        /* Limit data to 16 KB in stylesheets. */
        return $matches[1] . Horde::base64ImgData($matches[2], 16384) . $matches[3];
    }


}
