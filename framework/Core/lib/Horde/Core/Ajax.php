<?php
/**
 * Provides common AJAX features for use by all applications.
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
 * @since    2.0.0
 */
class Horde_Core_Ajax
{
    /**
     * Has the browser environment been initialized?
     *
     * @var boolean
     */
    static protected $_init = false;

    /**
     * Initialize the HordeCore browser framework.
     *
     * @param array $opts  Configuration parameters:
     *   - app: (string) The application name.
     *          DEFAULT: Registry app
     *   - growler_log: (boolean) If true, initialized the Growler log.
     *                  DEFAULT: false
     */
    public function init(array $opts = array())
    {
        global $registry;

        if (self::$_init) {
            return;
        }

        if (empty($opts['app'])) {
            $opts['app'] = $registry->getApp();
        }

        Horde::addScriptFile('horde.js', 'horde');
        Horde::addScriptFile('hordecore.js', 'horde');
        Horde::addScriptFile('effects.js', 'horde');
        Horde::addScriptFile('growler.js', 'horde');
        Horde::addScriptFile('popup.js', 'horde');
        Horde::addScriptFile('sound.js', 'horde');

        /* Configuration used in core javascript files. */
        $js_conf = array_filter(array(
            /* URLs */
            'URI_AJAX' => Horde::getServiceLink('ajax', $opts['app'])->url,
            'URI_SNOOZE' => strval(Horde::url($registry->get('webroot', 'horde') . '/services/snooze.php', true, -1)),

            /* Other constants */
            'SID' => defined('SID') ? SID : '',

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

        Horde::addInlineJsVars(array(
            'var HordeCoreConf' => $js_conf,
            'var HordeCoreText' => $js_text
        ), array('top' => true));

        self::$_init = true;
    }

    /**
     * Initialize the JS browser environment and output everything up to, and
     * including, the <body> tag.
     *
     * @param array $opts  Configuration options:
     *   - bodyid: (string) An ID to use for the BODY tag.
     *   - css: (array) Arguments to pass to Horde::includeStylesheetFiles().
     *   - inlinescript: (boolean) Output inline scripts?
     *   - title: (string) The title of the page.
     */
    public function header(array $opts = array())
    {
        $this->init();

        if (isset($GLOBALS['language'])) {
            header('Content-type: text/html; charset=UTF-8');
            header('Vary: Accept-Language');
        }

        print '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">' . "\n";

        if (empty($GLOBALS['language'])) {
            print '<html>';
        } else {
            print '<html lang="' . strtr($GLOBALS['language'], '_', '-') . '">';
        }

        print '<head>';

        Horde::outputMetaTags();
        Horde::includeStylesheetFiles(isset($opts['css']) ? $opts['css'] : array());
        if (!empty($opts['inlinescript'])) {
            Horde::includeScriptFiles();
            Horde::outputInlineScript();
        }
        Horde::includeFavicon();

        $page_title = $GLOBALS['registry']->get('name');
        if (!empty($opts['title'])) {
            $page_title .= ' :: ' . $opts['title'];
        }

        print '<title>' . htmlspecialchars($page_title) . '</title>' .
            '</head><body' .
            (empty($opts['bodyid']) ? '' : ' id="' . $opts['bodyid'] . '"') .
            '>';

        // Send what we have currently output so the browser can start
        // loading CSS/JS. See:
        // http://developer.yahoo.com/performance/rules.html#flush
        echo Horde::endBuffer();
        flush();
    }

}
