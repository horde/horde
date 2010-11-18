<?php
global $prefs, $registry;

$kronolith_webroot = $registry->get('webroot');
$horde_webroot = $registry->get('webroot', 'horde');
$has_tasks = $registry->hasInterface('tasks');
$tagger = self::getTagger();

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
    'prefs_url' => (string)Horde::getServiceLink('prefs', 'kronolith')->setRaw(true)->add('ajaxui', 1),
    'name' => $registry->get('name'),
    'has_tasks' => $has_tasks,
    'login_view' => $prefs->getValue('defaultview') == 'workweek' ? 'week' : $prefs->getValue('defaultview'),
    'default_calendar' => 'internal|' . self::getDefaultCalendar(Horde_Perms::EDIT),
    'week_start' => (int)$prefs->getValue('week_start_monday'),
    'max_events' => (int)$prefs->getValue('max_events'),
    'date_format' => str_replace(array('%e', '%d', '%a', '%A', '%m', '%h', '%b', '%B', '%y', '%Y'),
                                 array('d', 'dd', 'ddd', 'dddd', 'MM', 'MMM', 'MMM', 'MMMM', 'yy', 'yyyy'),
                                 Horde_Nls::getLangInfo(D_FMT)),
    'time_format' => $prefs->getValue('twentyFour') ? 'HH:mm' : 'hh:mm tt',
    'status' => array('tentative' => self::STATUS_TENTATIVE,
                      'confirmed' => self::STATUS_CONFIRMED,
                      'cancelled' => self::STATUS_CANCELLED,
                      'free' => self::STATUS_FREE),
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
                     'delegate' => self::PERMS_DELEGATE),
    'snooze' => array('0' => _("select..."),
                      '5' => _("5 minutes"),
                      '15' => _("15 minutes"),
                      '60' => _("1 hour"),
                      '360' => _("6 hours"),
                      '1440' => _("1 day")),
);
if (!empty($GLOBALS['conf']['logo']['link'])) {
    $code['conf']['URI_HOME'] = $GLOBALS['conf']['logo']['link'];
}

if ($has_tasks) {
    $code['conf']['tasks'] = $registry->tasks->ajaxDefaults();
}

$subscriptionCals = Horde::url($registry->get('webroot', 'horde') . ($GLOBALS['conf']['urls']['pretty'] == 'rewrite' ? '/rpc/kronolith/' : '/rpc.php/kronolith/'), true, -1);
$subscriptionTasks = Horde::url($registry->get('webroot', 'horde') . ($GLOBALS['conf']['urls']['pretty'] == 'rewrite' ? '/rpc/nag/' : '/rpc.php/nag/'), true, -1);

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
                'sub' => $subscriptionCals . ($calendar->owner() ? $calendar->owner() : '-system-') . '/' . $id . '.ics',
                'feed' => (string)Kronolith::feedUrl($id),
                'embed' => self::embedCode($id),
                'tg' => array_values($tagger->getTags($id, 'calendar')));
            if ($owner) {
                $code['conf']['calendars']['internal'][$id]['perms'] = self::permissionToJson($calendar->share()->getPermission());
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
                'fg' => self::foregroundColor($tasklist),
                'bg' => self::backgroundColor($tasklist),
                'show' => in_array('tasks/' . $id, $GLOBALS['display_external_calendars']),
                'edit' => $tasklist->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT),
                'sub' => $subscriptionTasks . ($tasklist->get('owner') ? $tasklist->get('owner') : '-system-') . '/' . $tasklist->getName() . '.ics');
            if ($owner) {
                $code['conf']['calendars']['tasklists']['tasks/' . $id]['perms'] = self::permissionToJson($tasklist->getPermission());
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
    'ajax_timeout' => _("There has been no contact with the server for several minutes. The server may be temporarily unavailable or network problems may be interrupting your session. You will not see any updates until the connection is restored."),
    'ajax_recover' => _("The connection to the server has been restored."),
    'alarm' => _("Alarm:"),
    'snooze' => sprintf(_("You can snooze it for %s or %s dismiss %s it entirely"), '#{time}', '#{dismiss_start}', '#{dismiss_end}'),
    'noalerts' => _("No Notifications"),
    'alerts' => sprintf(_("%s notifications"), '#{count}'),
    'hidelog' => _("Hide Notifications"),
    'growlerinfo' => _("This is the notification backlog"),
    'agenda' => _("Agenda"),
    'searching' => sprintf(_("Events matching \"%s\""), '#{term}'),
    'allday' => _("All day"),
    'more' => _("more..."),
    'prefs' => _("Preferences"),
    'shared' => _("Shared"),
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
    'noevents' => _("No events to display"),
);
for ($i = 1; $i <= 12; ++$i) {
    $code['text']['month'][$i - 1] = Horde_Nls::getLangInfo(constant('MON_' . $i));
}
for ($i = 1; $i <= 7; ++$i) {
    $code['text']['weekday'][$i] = Horde_Nls::getLangInfo(constant('DAY_' . $i));
}
foreach (array(Horde_Date_Recurrence::RECUR_DAILY,
               Horde_Date_Recurrence::RECUR_WEEKLY,
               Horde_Date_Recurrence::RECUR_MONTHLY_DATE,
               Horde_Date_Recurrence::RECUR_MONTHLY_WEEKDAY,
               Horde_Date_Recurrence::RECUR_YEARLY_DATE,
               Horde_Date_Recurrence::RECUR_YEARLY_DAY,
               Horde_Date_Recurrence::RECUR_YEARLY_WEEKDAY) as $recurType) {
    $code['text']['recur'][$recurType] = self::recurToString($recurType);
}

$code['text']['recur']['desc'] = array(
    Horde_Date_Recurrence::RECUR_WEEKLY => array(sprintf(_("Recurs weekly on every %s"), "#{weekday}"),
                                                 sprintf(_("Recurs every %s weeks on %s"), "#{interval}", "#{weekday}")),
    Horde_Date_Recurrence::RECUR_MONTHLY_DATE => array(sprintf(_("Recurs on the %s of every month"), "#{date}"),
                                                       sprintf(_("Recurs every %s months on the %s"), "#{interval}", "#{date}")),
    Horde_Date_Recurrence::RECUR_MONTHLY_WEEKDAY => array(_("Recurs every month on the same weekday"),
                                                       sprintf(_("Recurs every %s months on the same weekday"), "#{interval}"))
);
$code['text']['recur']['exception'] = _("Exception");

// Maps
$code['conf']['maps'] = $GLOBALS['conf']['maps'];

return Horde::addInlineJsVars(array(
    'var Kronolith' => $code
), array('ret_vars' => true));