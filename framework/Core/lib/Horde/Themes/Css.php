<?php
/**
 * This class provides an interface to handling CSS stylesheets for Horde
 * applications.
 *
 * Copyright 2010-2013 Horde LLC (http://www.horde.org/)
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
    /* @since Horde 2.3.0 */
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
     * Temp data used in loadCssFiles() callback.
     *
     * @var array
     */
    protected $_tmp;

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
     *
     * @return array  The list of URLs to display (Horde_Url objects).
     */
    public function getStylesheetUrls(array $opts = array())
    {
        global $conf, $injector, $prefs, $registry;

        $theme = isset($opts['theme'])
            ? $opts['theme']
            : $prefs->getValue('theme');
        $css = $this->getStylesheets($theme, $opts);
        if (!count($css)) {
            return array();
        }

        $cache_type = !empty($opts['nocache']) || empty($conf['cachecss'])
            ? 'none'
            : $conf['cachecssparams']['driver'];

        if ($cache_type == 'none') {
            $css_out = array();
            foreach ($css as $file) {
                $url = Horde::url($file['uri'], true, -1);
                $css_out[] = (is_null($file['app']) || empty($conf['cachecssparams']['url_version_param']))
                    ? $url
                    : $url->add('v', hash('md5', $registry->getVersion($file['app'])));
            }
            return $css_out;
        }

        $out = '';
        $sig = hash('md5', serialize($css) . $this->_cacheid);

        switch ($cache_type) {
        case 'filesystem':
            $css_filename = '/static/' . $sig . '.css';
            $css_path = $registry->get('fileroot', 'horde') . $css_filename;
            $css_url = Horde::url($registry->get('webroot', 'horde') . $css_filename, true, array('append_session' => -1));
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
     *   - app: (string) The current application.
     *   - nobase: (boolean) If true, don't load base stylesheets.
     *   - nohorde: (boolean) If true, don't load files from Horde.
     *   - sub: (string) A subdirectory containing additional CSS files to
     *          load as an overlay to the base CSS files.
     *   - subonly: (boolean) If true, only load the files in 'sub', not
     *              the default theme files.
     *   - themeonly: (boolean) If true, only load the theme files.
     *
     * @return array  An array of 2-element array arrays containing 2 keys:
     *   - app: (string) App of the CSS file.
     *   - fs: (string) Filesystem location of stylesheet.
     *   - uri: (string) URI of stylesheet.
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
        try {
            $add_css = array_merge($add_css, Horde::callHook('cssfiles', array($theme), 'horde'));
        } catch (Horde_Exception_HookNotSet $e) {}

        if ($curr_app != 'horde') {
            try {
                $add_css = array_merge($add_css, Horde::callHook('cssfiles', array($theme), $curr_app));
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
     * @param array $files  List of CSS files as returned from
     *                      getStylesheets().
     *
     * @return string  CSS data.
     */
    public function loadCssFiles($files)
    {
        global $browser, $injector;

        $this->_tmp = array(
            'dataurl' => $browser->hasFeature('dataurl')
        );
        $out = '';

        foreach ($files as $file) {
            $data = file_get_contents($file['fs']);
            $this->_tmp['path'] = substr($file['uri'], 0, strrpos($file['uri'], '/') + 1);

            try {
                $css = $injector->getInstance('Horde_Core_Factory_TextFilter')->filter($data, 'csstidy', array(
                    'ob' => true
                ));

                foreach ($css->tokens as $k => $v) {
                    if (stripos($v[1], 'url(') !== false) {
                        $this->_tmp['type'] = $css->tokens[$k - 1][1];
                        $css->tokens[$k][1] = preg_replace_callback(self::CSS_URL_REGEX, array($this, '_loadCssFilesCallback'), $v[1]);
                    }
                }

                foreach ($css->import as $val) {
                    if (preg_match_all(self::CSS_URL_REGEX, $val, $matches)) {
                        foreach ($matches[1] as $val2) {
                            $ob = Horde_Themes_Element::fromUri($this->_tmp['path'] . $val2);
                            $out .= $this->loadCssFiles(array(array(
                                'app' => null,
                                'fs' => $ob->fs,
                                'uri' => $ob->uri
                            )));
                        }
                    }
                }
                $css->import = array();

                $out .= $css->print->plain();
            } catch (Horde_Exception $e) {
                $out .= $data;
            }
        }

        return $out;
    }

    /**
     * Callback for loadCssFiles() to convert URL data.
     *
     * @param array $matches  The list of matches from preg_replace_callback.
     *
     * @return string  The converted string.
     */
    protected function _loadCssFilesCallback($matches)
    {
        $url = $this->_tmp['path'] . $matches[1];

        if ($this->_tmp['dataurl'] &&
            (stripos(ltrim($this->_tmp['type']), 'background') === 0)) {
            /* Limit data to 16 KB in stylesheets. */
            return 'url(' . Horde::base64ImgData($url, 16384) . ')';
        }

        return 'url(\'' . $url . '\')';
    }

}
