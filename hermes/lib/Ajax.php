<?php
/**
 * Hermes wrapper for the base AJAX framework handler.
 *
 * Copyright 2012-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Hermes
 */
class Hermes_Ajax
{
    /**
     */
    public function init()
    {
        global $page_output;

        $page_output->addScriptFile('redbox.js', 'horde');
        $page_output->addScriptFile('tooltips.js', 'horde');
        $page_output->addScriptPackage('Datejs');
        $page_output->addScriptFile('quickfinder.js', 'horde');
        $page_output->addScriptFile('hermes.js');
        Horde_Core_Ui_JsCalendar::init(array('short_weekdays' => true));

        $page_output->addInlineJsVars(array(
            'var Hermes' => $this->_addBaseVars()
        ), array('top' => true));

        $page_output->header(array(
            'body_class' => 'horde-ajax',
            'growler_log' => true
        ));
    }

    /**
     */
    protected function _addBaseVars()
    {
        global $prefs, $injector, $conf, $registry;

        $app_urls = $js_vars = array();

        if (isset($conf['menu']['apps']) &&
            is_array($conf['menu']['apps'])) {
            foreach ($conf['menu']['apps'] as $app) {
                $app_urls[$app] = strval(Horde::url($registry->getInitialPage($app), true));
            }
        }
        $identity = $injector->getInstance('Horde_Core_Factory_Identity')->create();
        $format_date = str_replace(array('%x', '%X'), array(Horde_Nls::getLangInfo(D_FMT, Horde_Nls::getLangInfo(D_T_FMT))), $prefs->getValue('date_format_mini'));

        /* Variables used in core javascript files. */
        $js_vars['conf'] = array(
            'URI_EXPORT' => (string)$registry->downloadUrl(
                'time.csv',
                array('actionID' => 'export'))->setRaw(true),
            'images' => array(
                'timerlog' => (string)Horde_Themes::img('log.png'),
                'timerplay' => (string)Horde_Themes::img('play.png'),
                'timerpause' => (string)Horde_Themes::img('pause.png')
            ),
            'user' => $registry->convertUsername($registry->getAuth(), false),
            'prefs_url' => strval($registry->getServiceLink('prefs', 'hermes')->setRaw(true)),
            'app_urls' => $app_urls,
            'name' => $identity->getName(),
            'login_view' => 'time',
            'date_format' => str_replace(
                array('%e', '%d', '%a', '%A', '%m', '%h', '%b', '%B', '%y', '%Y'),
                array('d', 'dd', 'ddd', 'dddd', 'MM', 'MMM', 'MMM', 'MMMM', 'yy', 'yyyy'),
                $format_date),
            'client_name_field' => $conf['client']['field'],
            'has_review_edit' => $injector->getInstance('Horde_Perms')->hasPermission('hermes:review', $GLOBALS['registry']->getAuth(), Horde_Perms::EDIT),
            'has_review' => $registry->isAdmin(array('permission' => 'hermes:review')),
            'has_timeadmin' => $registry->isAdmin(array('permission' => 'hermes:timeadmin'))
        );

        /* Gettext strings used in core javascript files. */
        $js_vars['text'] = array(
            'noalerts' => _("No Notifications"),
            'alerts' => sprintf(_("%s notifications"), '#{count}'),
            'hidelog' => _("Hide Notifications"),
            'more' => _("more..."),
            'prefs' => _("Preferences"),
            'fix_form_values' => _("Please enter correct values in the form first."),
            'wrong_date_format' => sprintf(_("You used an unknown date format \"%s\". Please try something like \"%s\"."), '#{wrong}', '#{right}'),
            'timeentry' => _("Time Entry"),
            'edittime' => _("Editing Time Entry"),
            'select_jobtype' => _("Select a Job Type")
        );

        return $js_vars;
    }

}
