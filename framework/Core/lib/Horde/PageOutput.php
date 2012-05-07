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
     * Script files object.
     *
     * @var Horde_Script_Files
     */
    public $hsf;

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
     * Constructor.
     */
    public function __construct()
    {
        $this->css = new Horde_Themes_Css();
        $this->hsf = new Horde_Script_Files();
    }

    /**
     * Adds the javascript script to the output (if output has already
     * started), or to the list of script files to include via
     * includeScriptFiles().
     *
     * As long as one script file is added, 'prototype.js' will be
     * automatically added, if the prototypejs property of Horde_Script_Files
     * is true (it is true by default).
     *
     * @param string $file  The full javascript file name.
     * @param string $app   The application name. Defaults to the current
     *                      application.
     * @param array $opts   Additional options:
     *   - external: (boolean) Treat $file as an external URL.
     *               DEFAULT: $file is located in the app's js/ directory.
     *   - full: (boolean) Output a full URL
     *           DEFAULT: false
     *
     * @throws Horde_Exception
     */
    public function addScriptFile($file, $app = null, array $opts = array())
    {
        if (empty($opts['external'])) {
            $this->hsf->add($file, array(
                'app' => $app,
                'full' => !empty($opts['full']),
                'no_output' => $this->deferScripts
            ));
        } else {
            $this->hsf->addExternal($file, $app);
        }
    }

    /**
     * Outputs the necessary script tags, honoring configuration choices as
     * to script caching.
     *
     * @throws Horde_Exception
     */
    public function includeScriptFiles()
    {
        global $conf, $injector, $registry;

        $driver = empty($conf['cachejs'])
            ? 'none'
            : strtolower($conf['cachejsparams']['driver']);

        if ($driver == 'none') {
            $this->hsf->includeFiles();
            return;
        }

        $js = array(
            'force' => array(),
            'external' => array(),
            'tocache' => array()
        );
        $mtime = array(
            'force' => array(),
            'tocache' => array()
        );

        $s_list = $this->hsf->listFiles();
        if (empty($s_list)) {
            return;
        }

        if ($driver == 'horde_cache') {
            $cache = $injector->getInstance('Horde_Cache');
            $cache_lifetime = empty($conf['cachejsparams']['lifetime'])
                ? 0
                : $conf['cachejsparams']['lifetime'];
        }

        /* Output prototype.js separately from the other files. */
        if ($s_list['horde'][0]['f'] == 'prototype.js') {
            $js['force'][] = $s_list['horde'][0]['p'] . $s_list['horde'][0]['f'];
            $mtime['force'][] = filemtime($s_list['horde'][0]['p'] . $s_list['horde'][0]['f']);
            unset($s_list['horde'][0]);
        }

        foreach ($s_list as $files) {
            foreach ($files as $file) {
                if ($file['d'] && ($file['f'][0] != '/') && empty($file['e'])) {
                    $js['tocache'][] = $file['p'] . $file['f'];
                    $mtime['tocache'][] = filemtime($file['p'] . $file['f']);
                } elseif (!empty($file['e'])) {
                    $js['external'][] = $file['u'];
                } else {
                    $js['force'][] = $file['p'] . $file['f'];
                    $mtime['force'][] = filemtime($file['p'] . $file['f']);
                }
            }
        }

        $jsmin_params = null;
        foreach ($js as $key => $files) {
            if (!count($files)) {
                continue;
            }

            if ($key == 'external') {
                foreach ($files as $val) {
                    $this->hsf->outputTag($val);
                }
                continue;
            }

            $sig_files = $files;
            sort($sig_files);
            $sig = hash('md5', serialize($sig_files) . max($mtime[$key]));

            switch ($driver) {
            case 'filesystem':
                $js_filename = '/static/' . $sig . '.js';
                $js_path = $registry->get('fileroot', 'horde') . $js_filename;
                $js_url = $registry->get('webroot', 'horde') . $js_filename;
                $exists = file_exists($js_path);
                break;

            case 'horde_cache':
                // Do lifetime checking here, not on cache display page.
                $exists = $cache->exists($sig, $cache_lifetime);
                $js_url = Horde::getCacheUrl('js', array('cid' => $sig));
                break;
            }

            if (!$exists) {
                $out = '';
                foreach ($files as $val) {
                    $js_text = file_get_contents($val);

                    if ($conf['cachejsparams']['compress'] == 'none') {
                        $out .= $js_text . "\n";
                    } else {
                        if (is_null($jsmin_params)) {
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
                            }
                        }

                        /* Separate JS files with a newline since some
                         * compressors may strip trailing terminators. */
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

            $this->hsf->outputTag($js_url);
        }

        $this->hsf->clear();
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
     * Add the popup script to the page output.
     */
    public function addPopupJs()
    {
        if (!$this->hsf->isIncluded('popup.js', 'horde')) {
            $this->addScriptFile('popup.js', 'horde');
            $this->addInlineJsVars(array(
                'Horde.popup_block_text' => Horde_Core_Translation::t("A popup window could not be opened. Your browser may be blocking popups.")
            ), true);
        }
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
     * Output the page header.
     *
     * @param array $opts  Options:
     *   - body_class: (string)
     *   - body_id: (string)
     *   - growler_log: (boolean) If true, initialized the Growler log.
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

        if (empty($opts['view'])) {
            $opts['view'] = $registry->hasView($registry->getView())
                ? $registry->getView()
                : Horde_Registry::VIEW_BASIC;
        }

        switch ($opts['view']) {
        case $registry::VIEW_BASIC:
            $view->stylesheetOpts['sub'] = 'basic';
            break;

        case $registry::VIEW_DYNAMIC:
            $this->addScriptFile('horde.js', 'horde');
            $this->addScriptFile('hordecore.js', 'horde');
            $this->addScriptFile('growler.js', 'horde');
            $this->addScriptFile('popup.js', 'horde');
            $this->addScriptFile('scriptaculous/effects.js', 'horde');
            $this->addScriptFile('scriptaculous/sound.js', 'horde');

            /* Configuration used in core javascript files. */
            $js_conf = array_filter(array(
                /* URLs */
                'URI_AJAX' => Horde::getServiceLink('ajax', $registry->getApp())->url,
                'URI_LOGOUT' => strval(Horde::getServiceLink('logout')),
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
                'popup_block' => _("A popup window could not be opened. Your browser may be blocking popups."),
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
                'var HordeCoreConf' => $js_conf,
                'var HordeCoreText' => $js_text
            ), array('top' => true));

            $view->stylesheetOpts['sub'] = 'dynamic';
            break;

        case $registry::VIEW_MINIMAL:
            $view->stylesheetOpts['sub'] = 'minimal';
            $view->stylesheetOpts['subonly'] = true;

            $view->minimalView = true;
            break;

        case $registry::VIEW_SMARTMOBILE:
            $this->hsf->prototypejs = false;

            $this->addScriptFile('jquery.mobile/jquery.min.js', 'horde');
            $this->addScriptFile('growler-jquery.js', 'horde');
            $this->addScriptFile('horde-jquery.js', 'horde');
            $this->addScriptFile('smartmobile.js', 'horde');

            $init_js = implode('', array_merge(array(
                '$.mobile.page.prototype.options.backBtnText = "' . _("Back") .'";',
                '$.mobile.dialog.prototype.options.closeBtnText = "' . _("Close") .'";',
                '$.mobile.loadingMessage = "' . _("loading") . '";'
            ), isset($opts['smartmobileinit']) ? $opts['smartmobileinit'] : array()));

            $this->addInlineJsVars(array(
                'HordeMobile.conf' => array(
                    'ajax_url' => Horde::getServiceLink('ajax', $registry->getApp())->url,
                    'logout_url' => strval(Horde::getServiceLink('logout')),
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
            break;
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
     * Output files needed for smartmobile mode.
     */
    public function outputSmartmobileFiles()
    {
        $this->addScriptFile('jquery.mobile/jquery.mobile.min.js', 'horde');
    }

    /**
     * @param array $opts  Options:
     *   - view: (integer)
     */
    public function footer(array $opts = array())
    {
        global $browser, $notification, $registry;

        if (!isset($opts['view'])) {
            $opts['view'] = $registry->hasView($registry->getView())
                ? $registry->getView()
                : Horde_Registry::VIEW_BASIC;
        }

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

        switch ($opts['view']) {
        case $registry::VIEW_SMARTMOBILE:
            $view->smartmobileView = true;
            break;
        }

        echo $view->render('footer');
    }

}
