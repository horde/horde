<?php
/**
 * Hermes wrapper for the base AJAX framework handler.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl21 GPL
 * @package  Hermes
 */
class Hermes_Ajax extends Horde_Core_Ajax
{
    /**
     * Javascript variables to output to the page.
     *
     * @var array
     */
    protected $_jsvars = array();

    /**
     */
    public function init(array $opts = array())
    {
        parent::init(array_merge($opts, array('app' => 'hermes')));
    }

    /**
     */
    public function header()
    {
        $this->init(array(
            'growler_log' => true
        ));

        $this->_addBaseVars();

        $datejs = str_replace('_', '-', $GLOBALS['language']) . '.js';
        if (!file_exists($GLOBALS['registry']->get('jsfs', 'horde') . '/date/' . $datejs)) {
            $datejs = 'en-US.js';
        }
        $page_output = $GLOBALS['injector']->getInstance('Horde_PageOutput');
        $page_output->addScriptFile('effects.js', 'horde');
        $page_output->addScriptFile('horde.js', 'horde');
        $page_output->addScriptFile('growler.js', 'horde');
        $page_output->addScriptFile('redbox.js', 'horde');
        $page_output->addScriptFile('tooltips.js', 'horde');
        $page_output->addScriptFile('date/' . $datejs, 'horde');
        $page_output->addScriptFile('date/date.js', 'horde');
        $page_output->addScriptFile('quickfinder.js', 'horde');
        $page_output->addScriptFile('hermes.js');
        Horde_Core_Ui_JsCalendar::init(array('short_weekdays' => true));

        $page_output->addInlineJsVars(array(
            'var Hermes' => $this->_jsvars
        ), array('top' => true));

        parent::header(array(
            'bodyid' => 'hermesAjax'
        ));
    }

    protected function _addBaseVars()
    {
        global $prefs, $injector, $conf, $registry;

        $app_urls = array();
        if (isset($conf['menu']['apps']) &&
            is_array($conf['menu']['apps'])) {
            foreach ($conf['menu']['apps'] as $app) {
                $app_urls[$app] = (string)Horde::url($registry->getInitialPage($app), true)->add('ajaxui', 1);
            }
        }
        $identity = $injector->getInstance('Horde_Core_Factory_Identity')->create();

        /* Variables used in core javascript files. */
        $this->_jsvars['conf'] = array(
            'URI_HOME' => empty($conf['logo']['link']) ? null : $conf['logo']['link'],
            'images' => array(
                'timerlog' => (string)Horde_Themes::img('log.png'),
                'timerplay' => (string)Horde_Themes::img('play.png'),
                'timerpause' => (string)Horde_Themes::img('pause.png')
            ),
            'user' => $registry->convertUsername($registry->getAuth(), false),
            'prefs_url' => (string)Horde::getServiceLink('prefs', 'hermes')->setRaw(true)->add('ajaxui', 1),
            'app_urls' => $app_urls,
            'name' => $identity->getName(),
            'use_iframe' => intval(!empty($conf['menu']['apps_iframe'])),
            'login_view' => 'time',
            'date_format' => str_replace(
                array('%e', '%d', '%a', '%A', '%m', '%h', '%b', '%B', '%y', '%Y'),
                array('d', 'dd', 'ddd', 'dddd', 'MM', 'MMM', 'MMM', 'MMMM', 'yy', 'yyyy'),
                Horde_Nls::getLangInfo(D_FMT)),
            'client_name_field' => $conf['client']['field']
        );

        /* Gettext strings used in core javascript files. */
        $this->_jsvars['text'] = array(
            'noalerts' => _("No Notifications"),
            'alerts' => sprintf(_("%s notifications"), '#{count}'),
            'hidelog' => _("Hide Notifications"),
            'more' => _("more..."),
            'prefs' => _("Preferences"),
            'fix_form_values' => _("Please enter correct values in the form first."),
        );
    }

}
