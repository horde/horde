<?php
/**
 * This object consolidates the elements needed to output a page to the
 * browser.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */
class Horde_PageOutput
{
    /**
     * Output code necessary to perform AJAX operations?
     *
     * @var boolean
     */
    public $ajax = false;

    /**
     * Stylesheet object.
     *
     * @var Horde_Themes_Css
     */
    public $css;

    /**
     * Defer loading of scripts until end of page?
     *
     * @var boolean
     */
    public $deferScripts = true;

    /**
     * Output code necessary to display growler notifications?
     *
     * @var boolean
     */
    public $growler = false;

    /**
     * Script list.
     *
     * @var Horde_Script_List
     */
    public $hsl;

    /**
     * List of inline scripts.
     *
     * @var array
     */
    public $inlineScript = array();

    /**
     * List of LINK tags to output.
     *
     * @var array
     */
    public $linkTags = array();

    /**
     * List of META tags to output.
     *
     * @var array
     */
    public $metaTags = array();

    /**
     * Has the sidebar been loaded in this page?
     *
     * @var boolean
     */
    public $sidebarLoaded = false;

    /**
     * Has PHP userspace page compression been started?
     *
     * @var boolean
     */
    protected $_compress = false;

    /**
     * View mode.
     *
     * @var integer
     */
    protected $_view = 0;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->css = new Horde_Themes_Css();
        $this->hsl = new Horde_Script_List();
    }

    /**
     * Adds a single javascript script to the output (if output has already
     * started), or to the list of script files to include in the output.
     *
     * @param mixed $file  Either a Horde_Script_File object, or the full
     *                     javascript file name.
     * @param string $app  If $file is a file name, this is the application
     *                     where the file is located. Defaults to the current
     *                     registry application.
     *
     * @return Horde_Script_File  Script file object.
     */
    public function addScriptFile($file, $app = null)
    {
        $ob = is_object($file)
            ? $file
            : new Horde_Script_File_JsDir($file, $app);

        return $this->hsl->add($ob);
    }

    /**
     * Adds a javascript package to the browser output.
     *
     * @param mixed $package  Either a classname, basename of a
     *                        Horde_Core_Script_Package class, or a
     *                        Horde_Script_Package object.
     *
     * @return Horde_Script_Package  Package object.
     * @throws Horde_Exception
     */
    public function addScriptPackage($package)
    {
        if (!is_object($package)) {
            if (!class_exists($package)) {
                $package = 'Horde_Core_Script_Package_' . $package;
                if (!class_exists($package)) {
                    throw new Horde_Exception('Invalid package name provided.');
                }
            }
            $package = new $package();
        }

        foreach ($package as $ob) {
            $this->hsl->add($ob);
        }

        return $package;
    }

    /**
     * Outputs the necessary script tags, honoring configuration choices as
     * to script caching.
     *
     * @throws Horde_Exception
     */
    public function includeScriptFiles()
    {
        global $browser, $conf;

        if (!$browser->hasFeature('javascript')) {
            return;
        }

        $driver = empty($conf['cachejs'])
            ? 'none'
            : strtolower($conf['cachejsparams']['driver']);
        $last_cache = null;
        $jsvars = $tmp = array();

        foreach ($this->hsl as $val) {
            if ($driver == 'none') {
                echo $val;
            } elseif (is_null($val->cache)) {
                if (!empty($tmp)) {
                    $this->_outputCachedScripts($tmp);
                    $tmp = array();
                }
                echo $val;
            } else {
                if (!is_null($last_cache) && ($last_cache != $val->cache)) {
                    $this->_outputCachedScripts($tmp);
                    $tmp = array();
                }
                $tmp[$val->hash] = $val;
            }

            $last_cache = $val->cache;

            if (!empty($val->jsvars)) {
                $jsvars = array_merge($jsvars, $val->jsvars);
            }
        }

        $this->_outputCachedScripts($tmp);
        $this->hsl->clear();
        $this->addInlineJsVars($jsvars);
    }

    /**
     */
    protected function _outputCachedScripts($scripts)
    {
        global $conf, $injector, $registry;

        if (empty($scripts)) {
            return;
        }

        $mtime = 0;
        foreach ($scripts as $val) {
            if (($tmp = $val->modified) > $mtime) {
                $mtime = $tmp;
            }
        }

        $hashes = array_keys($scripts);
        sort($hashes);

        $sig = hash('sha1', serialize($hashes) . $mtime);

        $driver = empty($conf['cachejs'])
            ? 'none'
            : strtolower($conf['cachejsparams']['driver']);

        switch ($driver) {
        case 'filesystem':
            $js_filename = '/static/' . $sig . '.js';
            $js_path = $registry->get('fileroot', 'horde') . $js_filename;
            $js_url = $registry->get('webroot', 'horde') . $js_filename;
            $exists = file_exists($js_path);
            break;

        case 'horde_cache':
            $cache = $injector->getInstance('Horde_Cache');
            $cache_lifetime = empty($conf['cachejsparams']['lifetime'])
                ? 0
                : $conf['cachejsparams']['lifetime'];

            // Do lifetime checking here, not on cache display page.
            $exists = $cache->exists($sig, $cache_lifetime);
            $js_url = Horde::getCacheUrl('js', array('cid' => $sig));
            break;
        }

        echo '<script type="text/javascript" src="' . $js_url . '"></script>';

        if ($exists) {
            return;
        }

        $out = '';
        foreach ($scripts as $val) {
            $js_text = file_get_contents($val->full_path);

            if ($conf['cachejsparams']['compress'] == 'none') {
                $out .= $js_text . "\n";
            } else {
                switch ($conf['cachejsparams']['compress']) {
                case 'closure':
                    $jsmin_params = array(
                        'closure' => $conf['cachejsparams']['closurepath'],
                        'java' => $conf['cachejsparams']['javapath']
                    );
                    break;

                case 'yui':
                    $jsmin_params = array(
                        'java' => $conf['cachejsparams']['javapath'],
                        'yui' => $conf['cachejsparams']['yuipath']
                    );
                    break;

                default:
                    $jsmin_params = array();
                    break;
                }

                /* Separate JS files with a newline since some compressors may
                 * strip trailing terminators. */
                try {
                    $out .= $injector->getInstance('Horde_Core_Factory_TextFilter')->filter($js_text, 'JavascriptMinify', $jsmin_params) . "\n";
                } catch (Horde_Exception $e) {
                    $out .= $js_text . "\n";
                }
            }
        }

        switch ($driver) {
        case 'filesystem':
            if (!file_put_contents($js_path, $out)) {
                throw new Horde_Exception('Could not write cached JS file to disk.');
            }
            break;

        case 'horde_cache':
            $cache->set($sig, $out);
            break;
        }
    }

    /**
     * Add inline javascript to the output buffer.
     *
     * @param mixed $script    The script text to add (can be stored in an
     *                         array).
     * @param boolean $onload  Load the script after the page (DOM) has
     *                         loaded?
     * @param boolean $top     Add script to top of stack?
     */
    public function addInlineScript($script, $onload = false, $top = false)
    {
        $script = is_array($script)
            ? implode(';', array_map('trim', $script))
            : trim($script);
        if (!strlen($script)) {
            return;
        }

        $onload = intval($onload);
        $script = rtrim($script, ';') . ';';

        if ($top && isset($this->inlineScript[$onload])) {
            array_unshift($this->inlineScript[$onload], $script);
        } else {
            $this->inlineScript[$onload][] = $script;
        }

        // If headers have already been sent, we need to output a
        // <script> tag directly.
        if (!$this->deferScripts && Horde::contentSent()) {
            $this->outputInlineScript();
        }
    }

    /**
     * Add inline javascript variable definitions to the output buffer.
     *
     * @param array $data  Keys are the variable names, values are the data
     *                     to JSON encode.  If the key begins with a '-',
     *                     the data will be added to the output as-is.
     * @param mixed $opts  If boolean true, equivalent to setting the 'onload'
     *                     option to true. Other options:
     *   - onload: (boolean) Wrap the definition in an onload handler?
     *             DEFAULT: false
     *   - ret_vars: (boolean) If true, will return the list of variable
     *               definitions instead of outputting to page.
     *               DEFAULT: false
     *   - top: (boolean) Add definitions to top of stack?
     *          DEFAULT: false
     *
     * @return array  Returns the variable list of 'ret_vars' option is true.
     */
    public function addInlineJsVars($data, $opts = array())
    {
        $out = array();

        if ($opts === true) {
            $opts = array('onload' => true);
        }
        $opts = array_merge(array(
            'onload' => false,
            'ret_vars' => false,
            'top' => false
        ), $opts);

        foreach ($data as $key => $val) {
            if ($key[0] == '-') {
                $key = substr($key, 1);
            } else {
                $val = Horde_Serialize::serialize($val, Horde_Serialize::JSON);
            }

            $out[] = $key . '=' . $val;
        }

        if ($opts['ret_vars']) {
            return $out;
        }

        $this->addInlineScript($out, $opts['onload'], $opts['top']);
    }

    /**
     * Print pending inline javascript to the output buffer.
     *
     * @param boolean $raw  Return the raw script (not wrapped in CDATA tags
     *                      or observe wrappers)?
     */
    public function outputInlineScript($raw = false)
    {
        if (empty($this->inlineScript)) {
            return;
        }

        $script = array();

        foreach ($this->inlineScript as $key => $val) {
            $val = implode('', $val);

            $script[] = (!$raw && $key)
                ? 'document.observe("dom:loaded",function(){' . $val . '});'
                : $val;
        }

        echo $raw
            ? implode('', $script)
            : Horde::wrapInlineScript($script);

        $this->inlineScript = array();
    }

    /**
     * Generate and output the favicon tag for the current application.
     */
    public function includeFavicon()
    {
        $img = strval(Horde_Themes::img('favicon.ico', array(
            'nohorde' => true
        )));

        if (!$img) {
            $img = strval(Horde_Themes::img('favicon.ico', array(
                'app' => 'horde'
            )));
        }

        echo '<link href="' . $img . '" rel="SHORTCUT ICON" />';
    }

    /**
     * Adds a META tag to the page output.
     *
     * @param string $name         The name value.
     * @param string $content      The content of the META tag.
     * @param boolean $http_equiv  Output http-equiv instead of name?
     */
    public function addMetaTag($type, $content, $http_equiv = true)
    {
        $this->metaTags[$type] = array(
            'c' => $content,
            'h' => $http_equiv
        );
    }

    /**
     * Adds a META refresh tag.
     *
     * @param integer $time  Refresh time.
     * @param string $url    Refresh URL
     */
    public function metaRefresh($time, $url)
    {
        if (!empty($time) && !empty($url)) {
            $this->addMetaTag('refresh', $time . ';url=' . $url);
        }
    }

    /**
     * Adds a META tag to disable DNS prefetching.
     * See Horde Bug #8836.
     */
    public function noDnsPrefetch()
    {
        $this->addMetaTag('x-dns-prefetch-control', 'off');
    }

    /**
     * Output META tags to page.
     */
    public function outputMetaTags()
    {
        foreach ($this->metaTags as $key => $val) {
            echo '<meta content="' . $val['c'] . '" ' .
                ($val['h'] ? 'http-equiv' : 'name') .
                '="' . $key . "\" />\n";
        }

        $this->metaTags = array();
    }

    /**
     * Add a LINK tag.
     *
     * @param array $opts  Non-default tag elements.
     */
    public function addLinkTag(array $opts = array())
    {
        $opts = array_merge(array(
            'rel' => 'alternate',
            'type' => 'application/rss+xml'
        ), $opts);

        $out = '<link';

        foreach ($opts as $key => $val) {
            if (!is_null($val)) {
                $out .= ' ' . $key . '="' . htmlspecialchars($val) . '"';
            }
        }

        $this->linkTags[] = $out . ' />';
    }

    /**
     * Output LINK tags.
     */
    public function outputLinkTags()
    {
        echo implode("\n", $this->linkTags);
        $this->linkTags = array();
    }

    /**
     * Adds an external stylesheet to the output.
     *
     * @param string $file  The CSS filepath.
     * @param string $url   The CSS URL.
     */
    public function addStylesheet($file, $url)
    {
        $this->css->addStylesheet($file, $url);
    }

    /**
     * Adds a themed stylesheet to the output.
     *
     * @param string $file  The stylesheet name.
     */
    public function addThemeStylesheet($file)
    {
        $this->css->addThemeStylesheet($file);
    }

    /**
     * Generate the stylesheet tags for the current application.
     *
     * @param array $opts  Options to pass to
     *                     Horde_Themes_Css::getStylesheetUrls().
     * @param array $full  Return a full URL?
     */
    public function includeStylesheetFiles(array $opts = array(),
                                           $full = false)
    {
        foreach ($this->css->getStylesheetUrls($opts) as $val) {
            echo '<link href="' . $val->toString(false, $full) . '" rel="stylesheet" type="text/css" />';
        }
    }

    /**
     * Activates output compression.
     */
    public function startCompression()
    {
        if ($this->_compress) {
            return;
        }

        /* Compress output if requested and possible. */
        if ($GLOBALS['conf']['compress_pages'] &&
            !$GLOBALS['browser']->hasQuirk('buggy_compression') &&
            !(bool)ini_get('zlib.output_compression') &&
            !(bool)ini_get('zend_accelerator.compress_all') &&
            ini_get('output_handler') != 'ob_gzhandler') {
            if (ob_get_level()) {
                ob_end_clean();
            }
            ob_start('ob_gzhandler');
        }

        $this->_compress = true;
    }

    /**
     * Disables output compression. If successful, throws out all data
     * currently in the output buffer. Must be called before any data is sent
     * to the browser.
     */
    public function disableCompression()
    {
        if ($this->_compress && (reset(ob_list_handlers()) == 'ob_gzhandler')) {
            ob_end_clean();
            $this->_compress = false;
        }
    }

    /**
     * Output the page header.
     *
     * @param array $opts  Options:
     *   - body_class: (string)
     *   - body_id: (string)
     *   - growler_log: (boolean) If true, initialize Growler log.
     *                  DEFAULT: false
     *   - html_id: (string)
     *   - smartmobileinit: (string)
     *   - stylesheet_opts: (array)
     *   - title: (string)
     *   - view: (integer)
     */
    public function header(array $opts = array())
    {
        global $language, $registry, $session;

        $view = new Horde_View(array(
            'templatePath' => $registry->get('templates', 'horde') . '/common'
        ));

        $view->outputJs = !$this->deferScripts;
        $view->stylesheetOpts = array();

        $this->_view = empty($opts['view'])
            ? ($registry->hasView($registry->getView()) ? $registry->getView() : Horde_Registry::VIEW_BASIC)
            : $opts['view'];

        switch ($this->_view) {
        case $registry::VIEW_BASIC:
            $this->_addBasicScripts($opts);
            $view->stylesheetOpts['sub'] = 'basic';
            break;

        case $registry::VIEW_DYNAMIC:
            $this->ajax = true;
            $this->growler = true;

            $this->_addBasicScripts($opts);
            $this->addScriptPackage('Popup');

            $view->stylesheetOpts['sub'] = 'dynamic';
            break;

        case $registry::VIEW_MINIMAL:
            $view->stylesheetOpts['sub'] = 'minimal';
            $view->stylesheetOpts['subonly'] = true;

            $view->minimalView = true;
            break;

        case $registry::VIEW_SMARTMOBILE:
            $smobile_files = array(
                'jquery.mobile/jquery.min.js',
                'growler-jquery.js',
                'horde-jquery.js',
                'smartmobile.js'
            );
            foreach ($smobile_files as $val) {
                $this->addScriptFile(new Horde_Script_File_JsFramework($val, 'horde'));
            }

            $init_js = implode('', array_merge(array(
                '$.mobile.page.prototype.options.backBtnText = "' . _("Back") .'";',
                '$.mobile.dialog.prototype.options.closeBtnText = "' . _("Close") .'";',
                '$.mobile.loadingMessage = "' . _("loading") . '";'
            ), isset($opts['smartmobileinit']) ? $opts['smartmobileinit'] : array()));

            $this->addInlineJsVars(array(
                'HordeMobile.conf' => array(
                    'ajax_url' => $registry->getServiceLink('ajax', $registry->getApp())->url,
                    'logout_url' => strval($registry->getServiceLink('logout')),
                    'token' => $session->getToken()
                )
            ));
            $this->addInlineScript('$(window.document).bind("mobileinit", function() {' . $init_js . '});');

            $this->addMetaTag('viewport', 'width=device-width, initial-scale=1', false);

            $view->stylesheetOpts['nocache'] = true;
            $view->stylesheetOpts['sub'] = 'smartmobile';
            $view->stylesheetOpts['subonly'] = true;

            $this->addStylesheet(
                $registry->get('jsfs', 'horde') . '/jquery.mobile/jquery.mobile.min.css',
                $registry->get('jsuri', 'horde') . '/jquery.mobile/jquery.mobile.min.css'
            );

            $view->smartmobileView = true;

            // Force JS to load at top of page, so we don't see flicker when
            // mobile styles are applied.
            $view->outputJs = true;
            break;
        }

        if ($this->ajax || $this->growler) {
            $this->addScriptFile(new Horde_Script_File_JsFramework('hordecore.js', 'horde'));

            /* Configuration used in core javascript files. */
            $js_conf = array_filter(array(
                /* URLs */
                'URI_AJAX' => $registry->getServiceLink('ajax', $registry->getApp())->url,
                'URI_DLOAD' => $registry->getServiceLink('download', $registry->getApp())->url,
                'URI_LOGOUT' => strval($registry->getServiceLink('logout')),
                'URI_SNOOZE' => strval(Horde::url($registry->get('webroot', 'horde') . '/services/snooze.php', true, -1)),

                /* Other constants */
                'SID' => defined('SID') ? SID : '',
                'TOKEN' => $session->getToken(),

                /* Other config. */
                'growler_log' => !empty($opts['growler_log']),
                'popup_height' => 610,
                'popup_width' => 820
            ));

            /* Gettext strings used in core javascript files. */
            $js_text = array(
                'ajax_error' => _("Error when communicating with the server."),
                'ajax_recover' => _("The connection to the server has been restored."),
                'ajax_timeout' => _("There has been no contact with the server for several minutes. The server may be temporarily unavailable or network problems may be interrupting your session. You will not see any updates until the connection is restored."),
                'snooze' => sprintf(_("You can snooze it for %s or %s dismiss %s it entirely"), '#{time}', '#{dismiss_start}', '#{dismiss_end}'),
                'snooze_select' => array(
                    '0' => _("Select..."),
                    '5' => _("5 minutes"),
                    '15' => _("15 minutes"),
                    '60' => _("1 hour"),
                    '360' => _("6 hours"),
                    '1440' => _("1 day")
                )
            );

            if (!empty($opts['growler_log'])) {
                $js_text['growlerinfo'] = _("This is the notification log.");
                $js_text['growlernoalerts'] = _("No Alerts");
            }

            $this->addInlineJsVars(array(
                'HordeCore.conf' => $js_conf,
                'HordeCore.text' => $js_text
            ), array('top' => true));
        }

        if ($this->growler) {
            $this->addScriptFile('growler.js', 'horde');
            $this->addScriptFile('scriptaculous/effects.js', 'horde');
            $this->addScriptFile('scriptaculous/sound.js', 'horde');
        }

        if (isset($opts['stylesheet_opts'])) {
            $view->stylesheetOpts = array_merge($view->stylesheetOpts, $opts['stylesheet_opts']);
        }

        $html = '';
        if (isset($language)) {
            $html .= ' lang="' . htmlspecialchars(strtr($language, '_', '-')) . '"';
        }
        if (isset($opts['html_id'])) {
            $html .= ' id="' . htmlspecialchars($opts['html_id']) . '"';
        }
        $view->htmlAttr = $html;

        $body = '';
        if (isset($opts['body_class'])) {
            $body .= ' class="' . htmlspecialchars($opts['body_class']) . '"';
        }
        if (isset($opts['body_id'])) {
            $body .= ' id="' . htmlspecialchars($opts['body_id']) . '"';
        }
        $view->bodyAttr = $body;

        $page_title = $registry->get('name');
        if (isset($opts['title'])) {
            $page_title .= ' :: ' . $opts['title'];
        }
        $view->pageTitle = htmlspecialchars($page_title);

        $view->pageOutput = $this;

        header('Content-type: text/html; charset=UTF-8');
        if (isset($language)) {
            header('Vary: Accept-Language');
        }

        echo $view->render('header');

        // Send what we have currently output so the browser can start
        // loading CSS/JS. See:
        // http://developer.yahoo.com/performance/rules.html#flush
        echo Horde::endBuffer();
        flush();
    }

    /**
     * Add basic framework scripts to the output.
     */
    protected function _addBasicScripts($opts)
    {
        global $prefs, $registry, $session;

        $base_js = array(
            'prototype.js',
            'horde.js'
        );

        foreach ($base_js as $val) {
            $ob = $this->addScriptFile(new Horde_Script_File_JsFramework($val, 'horde'));
            $ob->cache = 'package_basic';
        }

        if ($prefs->getValue('widget_accesskey')) {
            $this->addScriptFile('accesskeys.js', 'horde');
        }
    }

    /**
     * Output files needed for smartmobile mode.
     */
    public function outputSmartmobileFiles()
    {
        $this->addScriptFile('jquery.mobile/jquery.mobile.min.js', 'horde');
        $this->includeScriptFiles();
    }

    /**
     * Output page footer.
     *
     * @param array $opts  Options:
     *   - NONE currently
     */
    public function footer(array $opts = array())
    {
        global $browser, $notification, $registry;

        $view = new Horde_View(array(
            'templatePath' => $registry->get('templates', 'horde') . '/common'
        ));

        $view->notifications = $browser->isMobile()
            ? ''
            : $notification->notify(array('listeners' => array('audio')));
        $view->outputJs = $this->deferScripts;
        $view->pageOutput = $this;
        $view->sidebarLoaded = $this->sidebarLoaded;

        $this->deferScripts = false;

        switch ($this->_view) {
        case $registry::VIEW_MINIMAL:
            $view->minimalView = true;
            break;

        case $registry::VIEW_SMARTMOBILE:
            $view->smartmobileView = true;
            break;
        }

        echo $view->render('footer');
    }

}
