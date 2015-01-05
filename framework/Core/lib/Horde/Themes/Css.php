<?php
/**
 * This class provides an interface to handling CSS stylesheets for Horde
 * applications.
 *
 * Copyright 2010-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */
class Horde_Themes_Css
{
    /**
     * @deprecated
     * @since Horde 2.3.0
     */
    const CSS_URL_REGEX = '/url\s*\(["\']?(.*?)["\']?\)/i';

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
     *   - app: (string) The current application.
     *   - nobase: (boolean) If true, don't load base stylesheets.
     *   - nocache: (boolean) If true, don't load files from cache.
     *   - nohorde: (boolean) If true, don't load files from Horde.
     *   - sub: (string) A subdirectory containing additional CSS files to
     *          load as an overlay to the base CSS files.
     *   - subonly: (boolean) If true, only load the files in 'sub', not
     *              the default theme files.
     *   - theme: (string) Use this theme instead of the default.
     *   - themeonly: (boolean) If true, only load the theme files.
     * </pre>
     *
     * @return array  The list of URLs to display (Horde_Url objects).
     */
    public function getStylesheetUrls(array $opts = array())
    {
        global $conf, $injector, $prefs;

        $theme = isset($opts['theme'])
            ? $opts['theme']
            : $prefs->getValue('theme');
        $css = $this->getStylesheets($theme, $opts);
        if (!count($css)) {
            return array();
        }

        $cache_ob = empty($opts['nocache'])
            ? $injector->getInstance('Horde_Core_CssCache')
            : new Horde_Themes_Css_Cache_Null();

        return $cache_ob->process($css, $this->_cacheid);
    }

    /**
     * Return the list of base stylesheets to display.
     *
     * @param mixed $theme  The theme to use; specify an empty value to
     *                      retrieve the theme from user preferences, and
     *                      false for no theme.
     * @param array $opts   Additional options:
     * <pre>
     *   - app: (string) The current application.
     *   - nobase: (boolean) If true, don't load base stylesheets.
     *   - nohorde: (boolean) If true, don't load files from Horde.
     *   - sub: (string) A subdirectory containing additional CSS files to
     *          load as an overlay to the base CSS files.
     *   - subonly: (boolean) If true, only load the files in 'sub', not
     *              the default theme files.
     *   - themeonly: (boolean) If true, only load the theme files.
     * </pre>
     *
     * @return array  An array of 2-element array arrays containing 2 keys:
     * <pre>
     *   - app: (string) App of the CSS file.
     *   - fs: (string) Filesystem location of stylesheet.
     *   - uri: (string) URI of stylesheet.
     * </pre>
     */
    public function getStylesheets($theme = '', array $opts = array())
    {
        global $injector, $prefs, $registry;

        if (($theme === '') && isset($prefs)) {
            $theme = $prefs->getValue('theme');
        }

        $add_css = $css_out = array();
        $css_list = empty($opts['nobase'])
            ? $this->getBaseStylesheetList()
            : array();

        $css_list = array_unique(array_merge($css_list, array_keys($this->_cssThemeFiles)));

        $curr_app = empty($opts['app'])
            ? $registry->getApp()
            : $opts['app'];
        $mask = empty($opts['nohorde'])
            ? 0
            : Horde_Themes_Cache::APP_DEFAULT | Horde_Themes_Cache::APP_THEME;
        $sub = empty($opts['sub'])
            ? null
            : $opts['sub'];

        $cache = $injector->getInstance('Horde_Core_Factory_ThemesCache')->create($curr_app, $theme);
        $this->_cacheid = $cache->getCacheId();

        /* Add external stylesheets first, since they are ALWAYS overwritable
         * by Horde code. */
        foreach ($this->_cssFiles as $f => $u) {
            if (file_exists($f)) {
                $css_out[] = array(
                    'app' => null,
                    'fs' => $f,
                    'uri' => $u
                );
            }
        }

        /* Add theme stylesheets. */
        foreach ($css_list as $css_name) {
            if (empty($opts['subonly'])) {
                $css_out = array_merge($css_out, array_reverse($cache->getAll($css_name, $mask)));
            }

            if ($sub) {
                $css_out = array_merge($css_out, array_reverse($cache->getAll($sub . '/' . $css_name, $mask)));
            }
        }

        /* Add user-defined additional stylesheets. */
        $hooks = $injector->getInstance('Horde_Core_Hooks');
        try {
            $add_css = array_merge($add_css, $hooks->callHook('cssfiles', 'horde', array($theme)));
        } catch (Horde_Exception_HookNotSet $e) {}

        if ($curr_app != 'horde') {
            try {
                $add_css = array_merge($add_css, $hooks->callHook('cssfiles', $curr_app, array($theme)));
            } catch (Horde_Exception_HookNotSet $e) {}
        }

        foreach ($add_css as $f => $u) {
            $css_out[] = array(
                'app' => $curr_app,
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
     * @deprecated  Use Horde_Themes_Css_Compress instead.
     *
     * @param array $files  List of CSS files as returned from
     *                      getStylesheets().
     *
     * @return string  CSS data.
     */
    public function loadCssFiles($files)
    {
        $compress = new Horde_Themes_Css_Compress();
        return $compress->compress($files);
    }

}
