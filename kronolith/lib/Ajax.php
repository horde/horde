<?php
/**
 * Kronolith wrapper for the base AJAX framework handler.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl21 GPL
 * @package  Kronolith
 */
class Kronolith_Ajax
{
    /**
     */
    public function init()
    {
        global $page_output;

        $page_output->addScriptFile('dragdrop2.js');
        $page_output->addScriptFile('redbox.js', 'horde');
        $page_output->addScriptFile('tooltips.js', 'horde');
        $page_output->addScriptFile('colorpicker.js', 'horde');
        $page_output->addScriptPackage('Datejs');
        $page_output->addScriptFile('kronolith.js');
        Horde_Core_Ui_JsCalendar::init(array('short_weekdays' => true));

        $page_output->addInlineJsVars(array(
            'var Kronolith' => $this->_addBaseVars()
        ), array('top' => true));

        $page_output->header(array(
            'body_class' => 'horde-ajax',
            'growler_log' => true
        ));
    }

    /**
     * Add base javascript variables to the page.
     */
    protected function _addBaseVars()
    {
        global $conf, $injector, $prefs, $registry;

        $auth_name = $registry->getAuth();
        $has_tasks = Kronolith::hasApiPermission('tasks');
        $identity = $injector->getInstance('Horde_Core_Factory_Identity')->create();

        $app_urls = $js_vars = array();
        if (isset($conf['menu']['apps']) &&
            is_array($conf['menu']['apps'])) {
            foreach ($conf['menu']['apps'] as $app) {
                $app_urls[$app] = strval(Horde::url($registry->getInitialPage($app), true));
            }
        }

        /* Variables used in core javascript files. */
        $js_vars['conf'] = array_filter(array(
            'URI_CALENDAR_EXPORT' => strval($registry->downloadUrl('', array('actionID' => 'export', 'all_events' => 1, 'exportID' => Horde_Data::EXPORT_ICALENDAR, 'exportCal' => 'internal_'))),
            'URI_EVENT_EXPORT' => str_replace(array('%23', '%7B', '%7D'), array('#', '{', '}'), Horde::url('event.php', true)->add(array('view' => 'ExportEvent', 'eventID' => '#{id}', 'calendar' => '#{calendar}', 'type' => '#{type}'))),

            'images' => array(
                'alarm'     => strval(Horde_Themes::img('alarm-fff.png')),
                'attendees' => strval(Horde_Themes::img('attendees-fff.png')),
                'exception' => strval(Horde_Themes::img('exception-fff.png')),
                'new_event' => strval(Horde_Themes::img('new.png')),
                'new_task'  => strval(Horde_Themes::img('new_task.png')),
                'recur'     => strval(Horde_Themes::img('recur-fff.png')),
            ),
            'new_event' => $injector->getInstance('Kronolith_View_Sidebar')->newLink
                . $injector->getInstance('Kronolith_View_Sidebar')->newText
                . '</a>',
            'new_task' => $injector->getInstance('Kronolith_View_SidebarTasks')->newLink
                . $injector->getInstance('Kronolith_View_SidebarTasks')->newText
                . '</a>',
            'user' => $registry->convertUsername($auth_name, false),
            'name' => $identity->getName(),
            'email' => $identity->getDefaultFromAddress(),
            'prefs_url' => strval($registry->getServiceLink('prefs', 'kronolith')->setRaw(true)),
            'app_urls' => $app_urls,
            'name' => $registry->get('name'),
            'has_tasks' => intval($has_tasks),
            'login_view' => ($prefs->getValue('defaultview') == 'workweek') ? 'week' : $prefs->getValue('defaultview'),
            'default_calendar' => 'internal|' . Kronolith::getDefaultCalendar(Horde_Perms::EDIT),
            'max_events' => intval($prefs->getValue('max_events')),
            'date_format' => Horde_Core_Script_Package_Datejs::translateFormat(
                Horde_Nls::getLangInfo(D_FMT)
            ),
            'time_format' => $prefs->getValue('twentyFour') ? 'HH:mm' : 'hh:mm tt',
            'show_time' => Kronolith::viewShowTime(),
            'default_alarm' => intval($prefs->getValue('default_alarm')),
            'status' => array(
                'cancelled' => Kronolith::STATUS_CANCELLED,
                'confirmed' => Kronolith::STATUS_CONFIRMED,
                'free' => Kronolith::STATUS_FREE,
                'tentative' => Kronolith::STATUS_TENTATIVE
            ),
            'recur' => array(
                Horde_Date_Recurrence::RECUR_NONE => 'None',
                Horde_Date_Recurrence::RECUR_DAILY => 'Daily',
                Horde_Date_Recurrence::RECUR_WEEKLY => 'Weekly',
                Horde_Date_Recurrence::RECUR_MONTHLY_DATE => 'Monthly',
                Horde_Date_Recurrence::RECUR_MONTHLY_WEEKDAY => 'Monthly',
                Horde_Date_Recurrence::RECUR_YEARLY_DATE => 'Yearly',
                Horde_Date_Recurrence::RECUR_YEARLY_DAY => 'Yearly',
                Horde_Date_Recurrence::RECUR_YEARLY_WEEKDAY => 'Yearly'
            ),
            'perms' => array(
                'all' => Horde_Perms::ALL,
                'show' => Horde_Perms::SHOW,
                'read' => Horde_Perms::READ,
                'edit' => Horde_Perms::EDIT,
                'delete' => Horde_Perms::DELETE,
                'delegate' => Kronolith::PERMS_DELEGATE
             ),
             'tasks' => $has_tasks ? $registry->tasks->ajaxDefaults() : null
         ));

        /* Make sure this value is not optimized out by array_filter(). */
        $js_vars['conf']['week_start'] = intval($prefs->getValue('week_start_monday'));

        /* Gettext strings. */
        $js_vars['text'] = array(
            'alarm' => _("Alarm:"),
            'alerts' => _("Notifications"),
            'allday' => _("All day"),
            'delete_calendar' => _("Are you sure you want to delete this calendar and all the events in it?"),
            'delete_tasklist' => _("Are you sure you want to delete this task list and all the tasks in it?"),
            'external_category' => _("Other events"),
            'fix_form_values' => _("Please enter correct values in the form first."),
            'geocode_error' => _("Unable to locate requested address"),
            'hidelog' => _("Hide Notifications"),
            'more' => _("more..."),
            'no_calendar_title' => _("The calendar title must not be empty."),
            'no_tasklist_title' => _("The task list title must not be empty."),
            'no_url' => _("You must specify a URL."),
            'prefs' => _("Preferences"),
            'searching' => sprintf(_("Events matching \"%s\""), '#{term}'),
            'shared' => _("Shared"),
            'tasks' => _("Tasks"),
            'unknown_resource' => _("Unknown resource."),
            'wrong_auth' => _("The authentication information you specified wasn't accepted."),
            'wrong_date_format' => sprintf(_("You used an unknown date format \"%s\". Please try something like \"%s\"."), '#{wrong}', '#{right}'),
            'wrong_time_format' => sprintf(_("You used an unknown time format \"%s\". Please try something like \"%s\"."), '#{wrong}', '#{right}'),
        );

        for ($i = 1; $i <= 12; ++$i) {
            $js_vars['text']['month'][$i - 1] = Horde_Nls::getLangInfo(constant('MON_' . $i));
        }

        for ($i = 1; $i <= 7; ++$i) {
            $js_vars['text']['weekday'][$i] = Horde_Nls::getLangInfo(constant('DAY_' . $i));
        }

        foreach (array_diff(array_keys($js_vars['conf']['recur']), array(Horde_Date_Recurrence::RECUR_NONE)) as $recurType) {
            $js_vars['text']['recur'][$recurType] = Kronolith::recurToString($recurType);
        }
        $js_vars['text']['recur']['exception'] = _("Exception");

        // Maps
        $js_vars['conf']['maps'] = $conf['maps'];

        return $js_vars;
    }

}
