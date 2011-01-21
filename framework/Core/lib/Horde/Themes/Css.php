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
     * The theme cache ID.
     *
     * @var string
     */
    protected $_cacheid;

    /**
     * A list of additional stylesheet files to add to the output.
     *
     * @var array
     */
    protected $_cssFiles = array();

    /**
     * A list of additional themed stylesheet files to add to the output.
     *
     * @var array
     */
    protected $_cssThemeFiles = array();

    /**
     * Adds an external stylesheet to the output.
     *
     * @param string $file  The CSS filepath.
     * @param string $url   The CSS URL.
     */
    public function addStylesheet($file, $url)
    {
        $this->_cssFiles[$file] = $url;
    }

    /**
     * Adds a themed stylesheet to the output.
     *
     * @param string $file  The stylesheet name.
     */
    public function addThemeStylesheet($file)
    {
        $this->_cssThemeFiles[$file] = true;
    }

    /**
     * Generate the stylesheet URLs needed to display the current page.
     * Honors configuration choices as to stylesheet caching.
     *
     * @param array $opts  Additional options:
     * <pre>
     * 'app' - (string) The current application.
     * 'nobase' - (boolean) If true, don't load base stylesheets.
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
    public function getStylesheetUrls(array $opts = array())
    {
        global $conf, $injector, $prefs, $registry;

        $themesfs = $registry->get('themesfs');
        $themesuri = $registry->get('themesuri');

        $theme = isset($opts['theme'])
            ? $opts['theme']
            : $prefs->getValue('theme');
        $css = $this->getStylesheets($theme, $opts);

        $cache_type = empty($conf['cachecss'])
            ? 'none'
            : $conf['cachecssparams']['driver'];

        if ($cache_type == 'none') {
            $css_out = array();
            foreach ($css as $file) {
                $css_out[] = $file['uri'];
            }
            return $css_out;
        }

        $out = '';
        $sig = hash('md5', serialize($css) . $this->_cacheid);

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

        return array($css_url);
    }

    /**
     * Return the list of base stylesheets to display.
     *
     * @param mixed $theme  The theme to use; specify an empty value to
     *                      retrieve the theme from user preferences, and
     *                      false for no theme.
     * @param array $opts   Additional options:
     * <pre>
     * 'app' - (string) The current application.
     * 'nobase' - (boolean) If true, don't load base stylesheets.
     * 'nohorde' - (boolean) If true, don't load files from Horde.
     * 'sub' - (string) A subdirectory containing additional CSS files to
     *         load as an overlay to the base CSS files.
     * 'subonly' - (boolean) If true, only load the files in 'sub', not
     *             the default theme files.
     * 'themeonly' - (boolean) If true, only load the theme files.
     * </pre>
     *
     * @return array  An array of 2-element array arrays containing 2 keys:
     * <pre>
     * fs - (string) Filesystem location of stylesheet.
     * uri - (string) URI of stylesheet.
     * </pre>
     */
    public function getStylesheets($theme = '', array $opts = array())
    {
        if (($theme === '') && isset($GLOBALS['prefs'])) {
            $theme = $GLOBALS['prefs']->getValue('theme');
        }

        $add_css = $css_out = array();
        $css_list = empty($opts['nobase'])
            ? $this->getBaseStylesheetList()
            : array();

        $css_list = array_unique(array_merge($css_list, array_keys($this->_cssThemeFiles)));

        $curr_app = empty($opts['app'])
            ? $GLOBALS['registry']->getApp()
            : $opts['app'];
        $mask = empty($opts['nohorde'])
            ? 0
            : Horde_Themes_Cache::APP_DEFAULT | Horde_Themes_Cache::APP_THEME;
        $sub = empty($opts['sub'])
            ? null
            : $opts['sub'];

        $cache = $GLOBALS['injector']->getInstance('Horde_Core_Factory_ThemesCache')->create($curr_app, $theme);
        $this->_cacheid = $cache->getCacheId();

        foreach ($css_list as $css_name) {
            if (empty($opts['subonly'])) {
                $css_out = array_merge($css_out, array_reverse($cache->getAll($css_name, $mask)));
            }

            if ($sub) {
                $css_out = array_merge($css_out, array_reverse($cache->getAll($sub . '/' . $css_name, $mask)));
            }
        }

        /* Add additional stylesheets added by code. */
        foreach ($this->_cssFiles as $f => $u) {
            if (file_exists($f)) {
                $add_css[$f] = $u;
            }
        }

        /* Add user-defined additional stylesheets. */
        try {
            $add_css = array_merge($add_css, Horde::callHook('cssfiles', array($theme), 'horde'));
        } catch (Horde_Exception_HookNotSet $e) {
        }

        if ($curr_app != 'horde') {
            try {
                $add_css = array_merge($add_css, Horde::callHook('cssfiles', array($theme), $curr_app));
            } catch (Horde_Exception_HookNotSet $e) {
            }
        }

        foreach ($add_css as $f => $u) {
            $css_out[] = array(
                'fs' => $f,
                'uri' => $u
            );
        }

        return $css_out;
    }

    /**
     * Returns the list of base stylesheets, based on the current language
     * and browser settings.
     *
     * @return array  A list of base CSS files to load.
     */
    public function getBaseStylesheetList()
    {
        $css_list = array('screen.css');

        if ($GLOBALS['registry']->nlsconfig->curr_rtl) {
            $css_list[] = 'rtl.css';
        }

        /* Collect browser specific stylesheets if needed. */
        switch ($GLOBALS['browser']->getBrowser()) {
        case 'msie':
            $ie_major = $GLOBALS['browser']->getMajor();
            if ($ie_major == 8) {
                $css_list[] = 'ie8.css';
            } elseif ($ie_major == 7) {
                $css_list[] = 'ie7.css';
            } elseif ($ie_major < 7) {
                $css_list[] = 'ie6_or_less.css';
            }
            break;

        case 'opera':
            $css_list[] = 'opera.css';
            break;

        case 'mozilla':
            $css_list[] = 'mozilla.css';
            break;

        case 'webkit':
            $css_list[] = 'webkit.css';
        }

        return $css_list;
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
            $path = substr($file['uri'], 0, strrpos($file['uri'], '/') + 1);

            // Fix relative URLs, convert graphics URLs to data URLs
            // (if possible), remove multiple whitespaces, and strip
            // comments.
            $tmp = preg_replace(array('/(url\(["\']?)([^\/])/i', '/\s+/', '/\/\*.*?\*\//'), array('$1' . $path . '$2', ' ', ''), implode('', file($file['fs'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)));
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
