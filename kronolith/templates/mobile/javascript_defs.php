<?php
global $prefs, $registry;

$kronolith_webroot = $registry->get('webroot');
$horde_webroot = $registry->get('webroot', 'horde');
$has_tasks = $registry->hasInterface('tasks');

/* Variables used in core javascript files. */
$code['conf'] = array(
    'URI_AJAX' => (string)Horde::getServiceLink('ajax', 'kronolith'),
    'URI_SNOOZE' => (string)Horde::url($registry->get('webroot', 'horde') . '/services/snooze.php', true, -1),
    'SESSION_ID' => defined('SID') ? SID : '',
    'images' => array(
        'attendees' => (string)Horde_Themes::img('attendees-fff.png'),
        'alarm'     => (string)Horde_Themes::img('alarm-fff.png'),
        'recur'     => (string)Horde_Themes::img('recur-fff.png'),
        'exception' => (string)Horde_Themes::img('exception-fff.png'),
    ),
    'user' => $GLOBALS['registry']->convertUsername($GLOBALS['registry']->getAuth(), false),
    //'prefs_url' => (string)Horde::getServiceLink('prefs', 'kronolith')->setRaw(true)->add('ajaxui', 1),
    'name' => $registry->get('name'),
    'has_tasks' => $has_tasks,
    'default_calendar' => 'internal|' . Kronolith::getDefaultCalendar(Horde_Perms::EDIT),
    'week_start' => (int)$prefs->getValue('week_start_monday'),
    'max_events' => (int)$prefs->getValue('max_events'),
    'date_format' => str_replace(array('%e', '%d', '%a', '%A', '%m', '%h', '%b', '%B', '%y', '%Y'),
                                 array('d', 'dd', 'ddd', 'dddd', 'MM', 'MMM', 'MMM', 'MMMM', 'yy', 'yyyy'),
                                 Horde_Nls::getLangInfo(D_FMT)),
    'time_format' => $prefs->getValue('twentyFour') ? 'HH:mm' : 'hh:mm tt',
    'status' => array('tentative' => Kronolith::STATUS_TENTATIVE,
                      'confirmed' => Kronolith::STATUS_CONFIRMED,
                      'cancelled' => Kronolith::STATUS_CANCELLED,
                      'free' => Kronolith::STATUS_FREE),
    'recur' => array(Horde_Date_Recurrence::RECUR_NONE => 'None',
                     Horde_Date_Recurrence::RECUR_DAILY => 'Daily',
                     Horde_Date_Recurrence::RECUR_WEEKLY => 'Weekly',
                     Horde_Date_Recurrence::RECUR_MONTHLY_DATE => 'Monthly',
                     Horde_Date_Recurrence::RECUR_MONTHLY_WEEKDAY => 'Monthly',
                     Horde_Date_Recurrence::RECUR_YEARLY_DATE => 'Yearly',
                     Horde_Date_Recurrence::RECUR_YEARLY_DAY => 'Yearly',
                     Horde_Date_Recurrence::RECUR_YEARLY_WEEKDAY => 'Yearly'),
    'perms' => array('all' => Horde_Perms::ALL,
                     'show' => Horde_Perms::SHOW,
                     'read' => Horde_Perms::READ,
                     'edit' => Horde_Perms::EDIT,
                     'delete' => Horde_Perms::DELETE,
                     'delegate' => Kronolith::PERMS_DELEGATE),
    'snooze' => array('0' => _("select..."),
                      '5' => _("5 minutes"),
                      '15' => _("15 minutes"),
                      '60' => _("1 hour"),
                      '360' => _("6 hours"),
                      '1440' => _("1 day")),
);
if ($has_tasks) {
    $code['conf']['tasks'] = $registry->tasks->ajaxDefaults();
}
// Calendars
foreach (array(true, false) as $my) {
    foreach ($GLOBALS['all_calendars'] as $id => $calendar) {
        if ($calendar->owner() != $GLOBALS['registry']->getAuth() &&
            !empty($GLOBALS['conf']['share']['hidden']) &&
            !in_array($id, $GLOBALS['display_calendars'])) {
            continue;
        }
        $owner = $GLOBALS['registry']->getAuth() &&
            $calendar->owner() == $GLOBALS['registry']->getAuth();
        if (($my && $owner) || (!$my && !$owner)) {
            $code['conf']['calendars']['internal'][$id] = array(
                'name' => ($owner || !$calendar->owner() ? '' : '[' . $GLOBALS['registry']->convertUsername($calendar->owner(), false) . '] ')
                    . $calendar->name(),
                'desc' => $calendar->description(),
                'owner' => $owner,
                'fg' => $calendar->foreground(),
                'bg' => $calendar->background(),
                'show' => in_array($id, $GLOBALS['display_calendars']),
                'edit' => $calendar->hasPermission(Horde_Perms::EDIT),
                'feed' => (string)Kronolith::feedUrl($id),
                'embed' => Kronolith::embedCode($id));
            if ($owner) {
                $code['conf']['calendars']['internal'][$id]['perms'] = Kronolith::permissionToJson($calendar->share()->getPermission());
            }
        }
    }

    // Tasklists
    if (!$has_tasks) {
        continue;
    }
    foreach ($registry->tasks->listTasklists($my, Horde_Perms::SHOW) as $id => $tasklist) {
        if ($tasklist->get('owner') != $GLOBALS['registry']->getAuth() &&
            !empty($GLOBALS['conf']['share']['hidden']) &&
            !in_array('tasks/' . $id, $GLOBALS['display_external_calendars'])) {
            continue;
        }
        $owner = $GLOBALS['registry']->getAuth() &&
            $tasklist->get('owner') == $GLOBALS['registry']->getAuth();
        if (($my && $owner) || (!$my && !$owner)) {
            $code['conf']['calendars']['tasklists']['tasks/' . $id] = array(
                'name' => ($owner || !$tasklist->get('owner') ? '' : '[' . $GLOBALS['registry']->convertUsername($tasklist->get('owner'), false) . '] ')
                    . $tasklist->get('name'),
                'desc' => $tasklist->get('desc'),
                'owner' => $owner,
                'fg' => Kronolith::foregroundColor($tasklist),
                'bg' => Kronolith::backgroundColor($tasklist),
                'show' => in_array('tasks/' . $id, $GLOBALS['display_external_calendars']),
                'edit' => $tasklist->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT));
            if ($owner) {
                $code['conf']['calendars']['tasklists']['tasks/' . $id]['perms'] = Kronolith::permissionToJson($tasklist->getPermission());
            }
        }
    }
}

// Timeobjects
foreach ($GLOBALS['all_external_calendars'] as $id => $calendar) {
    if ($calendar->api() == 'tasks') {
        continue;
    }
    if (!empty($GLOBALS['conf']['share']['hidden']) &&
        !in_array($id, $GLOBALS['display_external_calendars'])) {
        continue;
    }
    $code['conf']['calendars']['external'][$id] = array(
        'name' => $calendar->name(),
        'fg' => $calendar->foreground(),
        'bg' => $calendar->background(),
        'api' => $registry->get('name', $registry->hasInterface($calendar->api())),
        'show' => in_array($id, $GLOBALS['display_external_calendars']));
}

// Remote calendars
foreach ($GLOBALS['all_remote_calendars'] as $url => $calendar) {
    $code['conf']['calendars']['remote'][$url] = array_merge(
        array('name' => $calendar->name(),
              'desc' => $calendar->description(),
              'owner' => true,
              'fg' => $calendar->foreground(),
              'bg' => $calendar->background(),
              'show' => in_array($url, $GLOBALS['display_remote_calendars'])),
        $calendar->credentials());
}

// Holidays
foreach ($GLOBALS['all_holidays'] as $id => $calendar) {
    $code['conf']['calendars']['holiday'][$id] = array(
        'name' => $calendar->name(),
        'fg' => $calendar->foreground(),
        'bg' => $calendar->background(),
        'show' => in_array($id, $GLOBALS['display_holidays']));
}

/* Gettext strings used in core javascript files. */
$code['text'] = array(
    'ajax_error' => _("Error when communicating with the server."),
    //'alarm' => _("Alarm:"),
    //'snooze' => sprintf(_("You can snooze it for %s or %s dismiss %s it entirely"), '#{time}', '#{dismiss_start}', '#{dismiss_end}'),
    //'agenda' => _("Agenda"),
    'allday' => _("All day"),
    //'more' => _("more..."),
    //'prefs' => _("Preferences"),
    //'shared' => _("Shared"),
    //'fix_form_values' => _("Please enter correct values in the form first."),
    'noevents' => _("No events to display"),
    'yesterday' => _("Yesterday"),
    'today' => _("Today"),
    'tomorrow' => _("Tomorrow")
);
for ($i = 1; $i <= 12; ++$i) {
    $code['text']['month'][$i - 1] = Horde_Nls::getLangInfo(constant('MON_' . $i));
}
for ($i = 1; $i <= 7; ++$i) {
    $code['text']['weekday'][$i] = Horde_Nls::getLangInfo(constant('DAY_' . $i));
}
//foreach (array(Horde_Date_Recurrence::RECUR_DAILY,
//               Horde_Date_Recurrence::RECUR_WEEKLY,
//               Horde_Date_Recurrence::RECUR_MONTHLY_DATE,
//               Horde_Date_Recurrence::RECUR_MONTHLY_WEEKDAY,
//               Horde_Date_Recurrence::RECUR_YEARLY_DATE,
//               Horde_Date_Recurrence::RECUR_YEARLY_DAY,
//               Horde_Date_Recurrence::RECUR_YEARLY_WEEKDAY) as $recurType) {
//    $code['text']['recur'][$recurType] = Kronolith::recurToString($recurType);
//}
$code['text']['recur']['desc'] = array(
    Horde_Date_Recurrence::RECUR_WEEKLY => array(sprintf(_("Recurs weekly on every %s"), "#{weekday}"),
                                                 sprintf(_("Recurs every %s weeks on %s"), "#{interval}", "#{weekday}")),
    Horde_Date_Recurrence::RECUR_MONTHLY_DATE => array(sprintf(_("Recurs on the %s of every month"), "#{date}"),
                                                       sprintf(_("Recurs every %s months on the %s"), "#{interval}", "#{date}")),
    Horde_Date_Recurrence::RECUR_MONTHLY_WEEKDAY => array(_("Recurs every month on the same weekday"),
                                                       sprintf(_("Recurs every %s months on the same weekday"), "#{interval}"))
);
$code['text']['recur']['exception'] = _("Exception");

echo Horde::addInlineJsVars(array(
    'var Kronolith' => $code
), array('top' => true));