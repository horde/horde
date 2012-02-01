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
class Kronolith_Ajax extends Horde_Core_Ajax
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
        parent::init(array_merge($opts, array('app' => 'kronolith')));
    }

    /**
     */
    public function header()
    {
        $this->init(array(
            'growler_log' => true
        ));

        $this->_addBaseVars();

        Horde::addScriptFile('dragdrop2.js', 'kronolith');
        Horde::addScriptFile('redbox.js', 'horde');
        Horde::addScriptFile('tooltips.js', 'horde');
        Horde::addScriptFile('colorpicker.js', 'horde');

        $datejs = str_replace('_', '-', $GLOBALS['language']) . '.js';
        if (!file_exists($GLOBALS['registry']->get('jsfs', 'horde') . '/date/' . $datejs)) {
            $datejs = 'en-US.js';
        }
        Horde::addScriptFile('date/' . $datejs, 'horde');
        Horde::addScriptFile('date/date.js', 'horde');
        Horde::addScriptFile('kronolith.js', 'kronolith');
        Horde_Core_Ui_JsCalendar::init(array('short_weekdays' => true));

        Horde::addInlineJsVars(array(
            'var Kronolith' => $this->_jsvars
        ), array('top' => true));

        parent::header(array(
            'bodyid' => 'kronolithAjax'
        ));
    }

    /**
     * Add base javascript variables to the page.
     */
    protected function _addBaseVars()
    {
        global $conf, $injector, $prefs, $registry;
        global $all_calendars, $all_external_calendars, $all_holidays, $all_remote_calendars, $display_external_calendars;

        $auth_name = $registry->getAuth();
        $has_tasks = Kronolith::hasApiPermission('tasks');
        $identity = $injector->getInstance('Horde_Core_Factory_Identity')->create();

        $app_urls = array();
        if (isset($conf['menu']['apps']) &&
            is_array($conf['menu']['apps'])) {
            foreach ($conf['menu']['apps'] as $app) {
                $app_urls[$app] = strval(Horde::url($registry->getInitialPage($app), true)->add('ajaxui', 1));
            }
        }

        /* Variables used in core javascript files. */
        $this->_jsvars['conf'] = array_filter(array(
            'URI_CALENDAR_EXPORT' => strval(Horde::url('data.php', true)->add(array('actionID' => 'export', 'all_events' => 1, 'exportID' => Horde_Data::EXPORT_ICALENDAR, 'exportCal' => 'internal_'))),
            'URI_EVENT_EXPORT' => str_replace(array('%23', '%7B', '%7D'), array('#', '{', '}'), Horde::url('event.php', true)->add(array('view' => 'ExportEvent', 'eventID' => '#{id}', 'calendar' => '#{calendar}', 'type' => '#{type}'))),
            'URI_HOME' => empty($conf['logo']['link']) ? null : $conf['logo']['link'],

            'images' => array(
                'attendees' => strval(Horde_Themes::img('attendees-fff.png')),
                'alarm'     => strval(Horde_Themes::img('alarm-fff.png')),
                'recur'     => strval(Horde_Themes::img('recur-fff.png')),
                'exception' => strval(Horde_Themes::img('exception-fff.png')),
            ),
            'user' => $registry->convertUsername($auth_name, false),
            'name' => $identity->getName(),
            'email' => $identity->getDefaultFromAddress(),
            'prefs_url' => strval(Horde::getServiceLink('prefs', 'kronolith')->setRaw(true)->add('ajaxui', 1)),
            'app_urls' => $app_urls,
            'use_iframe' => intval(!empty($conf['menu']['apps_iframe'])),
            'name' => $registry->get('name'),
            'has_tasks' => intval($has_tasks),
            'login_view' => ($prefs->getValue('defaultview') == 'workweek') ? 'week' : $prefs->getValue('defaultview'),
            'default_calendar' => 'internal|' . Kronolith::getDefaultCalendar(Horde_Perms::EDIT),
            'max_events' => intval($prefs->getValue('max_events')),
            'date_format' => str_replace(
                array('%e', '%d', '%a', '%A', '%m', '%h', '%b', '%B', '%y', '%Y'),
                array('d', 'dd', 'ddd', 'dddd', 'MM', 'MMM', 'MMM', 'MMMM', 'yy', 'yyyy'),
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
        $this->_jsvars['conf']['week_start'] = intval($prefs->getValue('week_start_monday'));

        // Calendars. Do some twisting to sort own calendar before shared
        // calendars.
        foreach (array(true, false) as $my) {
            foreach ($all_calendars as $id => $calendar) {
                $owner = ($auth_name && ($calendar->owner() == $auth_name));
                if (($my && $owner) || (!$my && !$owner)) {
                    $this->_jsvars['conf']['calendars']['internal'][$id] = $calendar->toHash();
                }
            }

            // Tasklists
            if ($has_tasks) {
                foreach ($registry->tasks->listTasklists($my, Horde_Perms::SHOW) as $id => $tasklist) {
                    if (isset($all_external_calendars['tasks/' . $id])) {
                        $owner = ($auth_name &&
                                  ($tasklist->get('owner') == $auth_name));
                        if (($my && $owner) || (!$my && !$owner)) {
                            $this->_jsvars['conf']['calendars']['tasklists']['tasks/' . $id] = $all_external_calendars['tasks/' . $id]->toHash();
                        }
                    }
                }
            }
        }

        // Resources
        foreach (Kronolith::getDriver('Resource')->listResources() as $resource) {
            if ($resource->get('type') != Kronolith_Resource::TYPE_GROUP) {
                $rcal = new Kronolith_Calendar_Resource(array(
                    'resource' => $resource
                ));
                $this->_jsvars['conf']['calendars']['resource'][$resource->get('calendar')] = $rcal->toHash();
            }
        }

        // Timeobjects
        foreach ($all_external_calendars as $id => $calendar) {
            if (($calendar->api() != 'tasks') &&
                (empty($conf['share']['hidden']) ||
                 in_array($id, $display_external_calendars))) {
                $this->_jsvars['conf']['calendars']['external'][$id] = $calendar->toHash();
            }
        }

        // Remote calendars
        foreach ($all_remote_calendars as $url => $calendar) {
            $this->_jsvars['conf']['calendars']['remote'][$url] = $calendar->toHash();
        }

        // Holidays
        foreach ($all_holidays as $id => $calendar) {
            $this->_jsvars['conf']['calendars']['holiday'][$id] = $calendar->toHash();
        }

        /* Gettext strings. */
        $this->_jsvars['text'] = array(
            'alarm' => _("Alarm:"),
            'alerts' => _("Notifications"),
            'hidelog' => _("Hide Notifications"),
            'agenda' => _("Agenda"),
            'tasks' => _("Tasks"),
            'searching' => sprintf(_("Events matching \"%s\""), '#{term}'),
            'allday' => _("All day"),
            'more' => _("more..."),
            'prefs' => _("Preferences"),
            'shared' => _("Shared"),
            'external_category' => _("Other events"),
            'no_url' => _("You must specify a URL."),
            'no_calendar_title' => _("The calendar title must not be empty."),
            'no_tasklist_title' => _("The task list title must not be empty."),
            'delete_calendar' => _("Are you sure you want to delete this calendar and all the events in it?"),
            'delete_tasklist' => _("Are you sure you want to delete this task list and all the tasks in it?"),
            'wrong_auth' => _("The authentication information you specified wasn't accepted."),
            'geocode_error' => _("Unable to locate requested address"),
            'wrong_date_format' => sprintf(_("You used an unknown date format \"%s\". Please try something like \"%s\"."), '#{wrong}', '#{right}'),
            'wrong_time_format' => sprintf(_("You used an unknown time format \"%s\". Please try something like \"%s\"."), '#{wrong}', '#{right}'),
            'fix_form_values' => _("Please enter correct values in the form first."),
            'unknown_resource' => _("Unknown resource."),
        );

        for ($i = 1; $i <= 12; ++$i) {
            $this->_jsvars['text']['month'][$i - 1] = Horde_Nls::getLangInfo(constant('MON_' . $i));
        }

        for ($i = 1; $i <= 7; ++$i) {
            $this->_jsvars['text']['weekday'][$i] = Horde_Nls::getLangInfo(constant('DAY_' . $i));
        }

        foreach (array_diff(array_keys($this->_jsvars['conf']['recur']), array(Horde_Date_Recurrence::RECUR_NONE)) as $recurType) {
            $this->_jsvars['text']['recur'][$recurType] = Kronolith::recurToString($recurType);
        }
        $this->_jsvars['text']['recur']['exception'] = _("Exception");

        // Maps
        $this->_jsvars['conf']['maps'] = $conf['maps'];
    }

}
