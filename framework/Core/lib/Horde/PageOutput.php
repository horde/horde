<?php
/**
 * Copyright 2012-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2012-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 */

/**
 * This object consolidates the elements needed to output a page to the
 * browser.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2012-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
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
     * Activate debugging output.
     *
     * @internal
     *
     * @var boolean
     */
    public $debug = false;

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
     * Load the sidebar in this page?
     *
     * @var boolean
     */
    public $sidebar = true;

    /**
     * Smartmobile init code that needs to be output before jquery.mobile.js
     * is loaded.
     *
     * @since 2.12.0
     *
     * @var array
     */
    public $smartmobileInit = array();

    /**
     * Load the topbar in this page?
     *
     * @var boolean
     */
    public $topbar = true;

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
        global $browser, $injector;

        if (!$browser->hasFeature('javascript')) {
            return;
        }

        if (!empty($this->smartmobileInit)) {
            echo Horde::wrapInlineScript(array(
                'var horde_jquerymobile_init = function() {' .
                implode('', $this->smartmobileInit) . '};'
            ));
            $this->smartmobileInit = array();
        }

        $out = $injector->getInstance('Horde_Core_JavascriptCache')->process($this->hsl);

        $this->hsl->clear();

        foreach ($out->script as $val) {
            echo '<script type="text/javascript" src="' . $val . '"></script>';
        }

        if (($this->ajax || $this->growler) && $out->all) {
            $out->jsvars['HordeCore.jsfiles'] = $out->all;
        }
        $this->addInlineJsVars($out->jsvars);
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

        echo '<link type="image/x-icon" href="' . $img . '" rel="shortcut icon" />';
    }

    /**
     * Adds a META tag to the page output.
     *
     * @param string $name         The name value.
     * @param string $content      The content of the META tag.
     * @param boolean $http_equiv  Output http-equiv instead of name?
     */
    public function addMetaTag($name, $content, $http_equiv = true)
    {
        $this->metaTags[$name] = array(
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
     * Adds a LINK tag.
     *
     * All attributes are HTML-encoded. Only pass raw, unencoded attribute
     * values to avoid double escaping.
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
     * @param Horde_Themes_Element|string $file  Either a Horde_Themes_Element
     *                                           object or the CSS filepath.
     * @param string $url                        If $file is a string, this
     *                                           must be a CSS URL.
     */
    public function addStylesheet($file, $url = null)
    {
        if ($file instanceof Horde_Themes_Element) {
            $url = $file->uri;
            $file = $file->fs;
        }

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
            /* Removing the ob_gzhandler ADDS the below headers, which breaks
             * display on the browser (as of PHP 5.3.15). */
            header_remove('content-encoding');
            header_remove('vary');
            $this->_compress = false;
        }
    }

    /**
     * Output the page header.
     *
     * @param array $opts  Options:
     *   - body_class: (string)
     *   - body_id: (string)
     *   - html_id: (string)
     *   - smartmobileinit: (string) (@deprecated; use $this->smartmobileInit
     *                      instead)
     *   - stylesheet_opts: (array)
     *   - title: (string)
     *   - view: (integer)
     */
    public function header(array $opts = array())
    {
        global $injector, $language, $registry, $session;

        $view = new Horde_View(array(
            'templatePath' => $registry->get('templates', 'horde') . '/common'
        ));

        $view->outputJs = !$this->deferScripts;
        $view->stylesheetOpts = array();

        $this->_view = empty($opts['view'])
            ? ($registry->hasView($registry->getView()) ? $registry->getView() : Horde_Registry::VIEW_BASIC)
            : $opts['view'];

        if ($session->regenerate_due) {
            $session->regenerate();
        }

        switch ($this->_view) {
        case $registry::VIEW_BASIC:
            $this->_addBasicScripts();
            break;

        case $registry::VIEW_DYNAMIC:
            $this->ajax = true;
            $this->growler = true;

            $this->_addBasicScripts();
            $this->addScriptPackage('Horde_Core_Script_Package_Popup');
            break;

        case $registry::VIEW_MINIMAL:
            $view->stylesheetOpts['subonly'] = true;

            $view->minimalView = true;

            $this->sidebar = $this->topbar = false;
            break;

        case $registry::VIEW_SMARTMOBILE:
            $smobile_files = array(
                ($this->debug ? 'jquery.mobile/jquery.js' : 'jquery.mobile/jquery.min.js'),
                'growler-jquery.js',
                'horde-jquery.js',
                'smartmobile.js',
                'horde-jquery-init.js',
                ($this->debug ? 'jquery.mobile/jquery.mobile.js' : 'jquery.mobile/jquery.mobile.min.js')
            );
            foreach ($smobile_files as $val) {
                $ob = $this->addScriptFile(new Horde_Script_File_JsFramework($val, 'horde'));
                $ob->cache = 'package_smartmobile';
            }

            $this->smartmobileInit = array_merge(array(
                '$.mobile.page.prototype.options.backBtnText = "' . Horde_Core_Translation::t("Back") .'";',
                '$.mobile.dialog.prototype.options.closeBtnText = "' . Horde_Core_Translation::t("Close") .'";',
                '$.mobile.listview.prototype.options.filterPlaceholder = "' . Horde_Core_Translation::t("Filter items...") . '";',
                '$.mobile.loader.prototype.options.text = "' . Horde_Core_Translation::t("loading") . '";'
            ),
                isset($opts['smartmobileinit']) ? $opts['smartmobileinit'] : array(),
                $this->smartmobileInit
            );

            $this->addInlineJsVars(array(
                'HordeMobile.conf' => array(
                    'ajax_url' => $registry->getServiceLink('ajax', $registry->getApp())->url,
                    'logout_url' => strval($registry->getServiceLink('logout')),
                    'sid' => SID,
                    'token' => $session->getToken()
                )
            ));

            $this->addMetaTag('viewport', 'width=device-width, initial-scale=1', false);

            $view->stylesheetOpts['subonly'] = true;

            $this->addStylesheet(
                $registry->get('jsfs', 'horde') . '/jquery.mobile/jquery.mobile.min.css',
                $registry->get('jsuri', 'horde') . '/jquery.mobile/jquery.mobile.min.css'
            );

            $view->smartmobileView = true;

            // Force JS to load at top of page, so we don't see flicker when
            // mobile styles are applied.
            $view->outputJs = true;

            $this->sidebar = $this->topbar = false;
            break;
        }

        $view->stylesheetOpts['sub'] = Horde_Themes::viewDir($this->_view);

        if ($this->ajax || $this->growler) {
            $this->addScriptFile(new Horde_Script_File_JsFramework('hordecore.js', 'horde'));

            /* Configuration used in core javascript files. */
            $js_conf = array_filter(array(
                /* URLs */
                'URI_AJAX' => $registry->getServiceLink('ajax', $registry->getApp())->url,
                'URI_DLOAD' => strval($registry->getServiceLink('download', $registry->getApp())),
                'URI_LOGOUT' => strval($registry->getServiceLink('logout')),
                'URI_SNOOZE' => strval(Horde::url($registry->get('webroot', 'horde') . '/services/snooze.php', true, -1)),

                /* Other constants */
                'SID' => SID,
                'TOKEN' => $session->getToken(),

                /* Other config. */
                'growler_log' => $this->topbar,
                'popup_height' => 610,
                'popup_width' => 820
            ));

            /* Gettext strings used in core javascript files. */
            $js_text = array(
                'ajax_error' => Horde_Core_Translation::t("Error when communicating with the server."),
                'ajax_recover' => Horde_Core_Translation::t("The connection to the server has been restored."),
                'ajax_timeout' => Horde_Core_Translation::t("There has been no contact with the server for several minutes. The server may be temporarily unavailable or network problems may be interrupting your session. You will not see any updates until the connection is restored."),
                'snooze' => sprintf(Horde_Core_Translation::t("You can snooze it for %s or %s dismiss %s it entirely"), '#{time}', '#{dismiss_start}', '#{dismiss_end}'),
                'snooze_select' => array(
                    '0' => Horde_Core_Translation::t("Select..."),
                    '5' => Horde_Core_Translation::t("5 minutes"),
                    '15' => Horde_Core_Translation::t("15 minutes"),
                    '60' => Horde_Core_Translation::t("1 hour"),
                    '360' => Horde_Core_Translation::t("6 hours"),
                    '1440' => Horde_Core_Translation::t("1 day")
                ),
                'dismissed' => Horde_Core_Translation::t("The alarm was dismissed.")
            );

            if ($this->topbar) {
                $js_text['growlerclear'] = Horde_Core_Translation::t("Clear All");
                $js_text['growlerinfo'] = Horde_Core_Translation::t("This is the notification log.");
                $js_text['growlernoalerts'] = Horde_Core_Translation::t("No Alerts");
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
        if ($this->topbar) {
            echo $injector->getInstance('Horde_View_Topbar')->render();
        }

        // Send what we have currently output so the browser can start
        // loading CSS/JS. See:
        // http://developer.yahoo.com/performance/rules.html#flush
        echo Horde::endBuffer();
        flush();
    }

    /**
     * Add basic framework scripts to the output.
     */
    protected function _addBasicScripts()
    {
        global $prefs;

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
     *
     * @deprecated
     */
    public function outputSmartmobileFiles()
    {
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

        if (!$browser->isMobile()) {
            $notification->notify(array('listeners' => array('audio')));
        }
        $view->outputJs = $this->deferScripts;
        $view->pageOutput = $this;

        switch ($this->_view) {
        case $registry::VIEW_MINIMAL:
            $view->minimalView = true;
            break;

        case $registry::VIEW_SMARTMOBILE:
            $view->smartmobileView = true;
            break;

        case $registry::VIEW_BASIC:
            $view->basicView = true;
            if ($this->sidebar) {
                $view->sidebar = Horde::sidebar();
            }
            break;
        }

        echo $view->render('footer');

        $this->deferScripts = false;
    }

}
