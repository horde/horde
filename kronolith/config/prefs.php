<?php
/**
 * See horde/config/prefs.php for documentation on the structure of this file.
 *
 * IMPORTANT: Local overrides should be placed in prefs.local.php, or
 * prefs-servername.php if the 'vhosts' setting has been enabled in Horde's
 * configuration.
 */

$prefGroups['view'] = array(
    'column' => _("Display Preferences"),
    'label' => _("User Interface"),
    'desc' => _("Select confirmation preferences, how to display the different views and choose default view."),
    'members' => array(
        'dynamic_view', 'confirm_delete', 'defaultview', 'max_events',
        'time_between_days', 'week_start_monday', 'day_hour_start',
        'day_hour_end', 'day_hour_force', 'slots_per_hour', 'show_icons',
        'show_time', 'show_location', 'show_fb_legend',
        'show_shared_side_by_side'
    ),
);

$prefGroups['share'] = array(
    'column' => _("Calendars"),
    'label' => _("Default Calendar"),
    'desc' => _("Choose your default calendar."),
    'members' => array('default_share'),
);

$prefGroups['event_options'] = array(
    'column' => _("Events"),
    'label' => _("Event Defaults"),
    'desc' => _("Set default values for new events."),
    'members' => array('default_alarm_management'),
);

$prefGroups['logintasks'] = array(
    'column' => _("Events"),
    'label' => _("Login Tasks"),
    'desc' => sprintf(_("Customize tasks to run upon logon to %s."), $GLOBALS['registry']->get('name')),
    'members' => array(
        'purge_events', 'purge_events_interval', 'purge_events_keep'
    )
);

$prefGroups['notification'] = array(
    'column' => _("Events"),
    'label' => _("Notifications"),
    'desc' => _("Choose how you want to be notified about event changes, event alarms and upcoming events."),
    'members' => array(
        'event_notification', 'event_notification_exclude_self',
        'daily_agenda', 'event_reminder', 'event_alarms_select'
    )
);

$prefGroups['freebusy'] = array(
    'column' => _("Calendars"),
    'label' => _("Free/Busy Information"),
    'desc' => _("Set your Free/Busy calendars and your own and other users' Free/Busy preferences."),
    'members' => array('fb_url', 'fb_cals', 'freebusy_days'),
);

$prefGroups['addressbooks'] = array(
    'column' => _("Other Preferences"),
    'label' => _("Address Books"),
    'desc' => _("Select address book sources for adding and searching for addresses."),
    'members' => array('display_contact', 'sourceselect'),
);

// show dynamic view?
$_prefs['dynamic_view'] = array(
    'value' => 1,
    'type' => 'checkbox',
    'desc' => _("Show the dynamic view by default, if the browser supports it?")
);

// confirm deletion of events which don't recur?
$_prefs['confirm_delete'] = array(
    'value' => 1,
    'type' => 'checkbox',
    'desc' => _("Confirm deletion of events?")
);

// default view
$_prefs['defaultview'] = array(
    'value' => 'month',
    'type' => 'enum',
    'enum' => array(
        'day' => _("Day"),
        'week' => _("Week"),
        'workweek' => _("Work Week"),
        'month' => _("Month")
    ),
    'desc' => _("Select the view to display on startup:")
);

$_prefs['max_events'] = array(
    'value' => 3,
    'type' => 'number',
    'desc' => _("How many events should be displayed per day in the month view? Set to 0 to always show all events."),
);

// Display the timeslots between each day column, in week view.
$_prefs['time_between_days'] = array(
    'value' => 0,
    'type' => 'checkbox',
    'desc' => _("Show time of day between each day in week views?")
);

// what day does the week start with
$_prefs['week_start_monday'] = array(
    'value' => '0',
    'type' => 'enum',
    'desc' => _("Select the first weekday:"),
    'enum' => array(
        '0' => _("Sunday"),
        '1' => _("Monday")
    )
);

// start of the time range in day/week views:
// Time array is dynamically built when prefs screen is displayed
$_prefs['day_hour_start'] = array(
    'value' => 16,
    'type' => 'enum',
    'desc' => _("What time should day and week views start, when there are no earlier events?")
);

// end of the time range in day/week views:
// Time array is dynamically built when prefs screen is displayed
$_prefs['day_hour_end'] = array(
    'value' => 48,
    'type' => 'enum',
    'desc' => _("What time should day and week views end, when there are no later events?")
);

// enforce hour slots?
$_prefs['day_hour_force'] = array(
    'value' => 0,
    'type' => 'checkbox',
    'desc' => _("Restrict day and week views to these time slots, even if there <strong>are</strong> earlier or later events?"),
);

// number of slots in each hour:
$_prefs['slots_per_hour'] = array(
    'value' => 1,
    'type' => 'enum',
    'desc' => _("How long should the time slots on the day and week views be?"),
    'enum' => array(
        4 => _("15 minutes"),
        3 => _("20 minutes"),
        2 => _("30 minutes"),
        1 => _("1 hour")
    ),
);

// show delete/alarm icons in the calendar view?
$_prefs['show_icons'] = array(
    'value' => 1,
    'type' => 'checkbox',
    'desc' => _("Show delete, alarm, and recurrence icons in calendar views?"),
);

// show event start/end times in the calendar and/or print views?
$_prefs['show_time'] = array(
    'value' => 'a:1:{i:0;s:5:"print";}',
    'type' => 'multienum',
    'enum' => array(
        'screen' => _("Month, Week, and Day Views"),
        'print' => _("Print Views")
     ),
    'desc' => _("Choose the views to show event start and end times in:"),
);

// show event location in the calendar and/or print views?
$_prefs['show_location'] = array(
    'value' => 'a:1:{i:0;s:5:"print";}',
    'type' => 'multienum',
    'enum' => array(
        'screen' => _("Month, Week, and Day Views"),
        'print' => _("Print Views")
     ),
    'desc' => _("Choose the views to show event locations in:"),
);

// show the calendar options panel?
// a value of 0 = no, 1 = yes
$_prefs['show_panel'] = array(
    'value' => 1
);

// show Free/Busy legend?
// a value of 0 = no, 1 = yes
$_prefs['show_fb_legend'] = array(
    'value' => 1,
    'type' => 'checkbox',
    'desc' => _("Show Free/Busy legend?"),
);

// collapsed or side by side view
$_prefs['show_shared_side_by_side'] = array(
    'value' => 0,
    'type' => 'checkbox',
    'desc' => _("Show shared calendars side-by-side?"),
);

// default calendar
// Set locked to true if you don't want users to have multiple calendars.
$_prefs['default_share'] = array(
    'value' => $GLOBALS['registry']->getAuth() ? $GLOBALS['registry']->getAuth() : 0,
    'type' => 'enum',
    'desc' => _("Your default calendar:")
);

// Which drivers are we supposed to use to examine holidays?
$_prefs['holiday_drivers'] = array(
    'value' => 'a:0:{}'
);

// store the calendars to diplay
$_prefs['display_cals'] = array(
    'value' => 'a:0:{}'
);

// default alarm
$_prefs['default_alarm'] = array(
    'value' => ''
);

$_prefs['default_alarm_management'] = array('type' => 'special');

// remote calendars
$_prefs['remote_cals'] = array(
    'value' => 'a:0:{}'
);

// store the remote calendars to display
$_prefs['display_remote_cals'] = array(
    'value' => 'a:0:{}'
);

// store the external calendars to display
$_prefs['display_external_cals'] = array(
    'value' => 'a:0:{}'
);

// new event notifications
$_prefs['event_notification'] = array(
    'value' => '',
    'type' => 'enum',
    'enum' => array(
        '' => _("No"),
        'owner' => _("On my calendars only"),
        'show' => _("On all shown calendars"),
        'read' => _("On all calendars I have read access to")
    ),
    'desc' => _("Choose if you want to be notified of new, edited, and deleted events by email:")
);

// daily agenda
$_prefs['daily_agenda'] = array(
    'value' => '',
    'type' => 'enum',
    'enum' => array(
        '' => _("No"),
        'owner' => _("On my calendars only"),
        'show' => _("On all shown calendars"),
        'read' => _("On all calendars I have read access to")
    ),
    'desc' => _("Choose if you want to receive daily agenda email reminders:")
);

$_prefs['event_notification_exclude_self'] = array(
    'value' => 0,
    'type' => 'checkbox',
    'desc' => _("Don't send me a notification if I've added, changed or deleted the event?")
);

// reminder notifications
$_prefs['event_reminder'] = array(
    'value' => 'owner',
    'type' => 'enum',
    'enum' => array(
        '' => _("No"),
        'owner' => _("On my calendars only"),
        'show' => _("On all shown calendars"),
        'read' => _("On all calendars I have read access to")
    ),
    'desc' => _("Choose if you want to receive reminders for events with alarms:")
);

// alarm methods
$_prefs['event_alarms_select'] = array(
    'type' => 'special'
);

$_prefs['event_alarms'] = array(
    'value' => 'a:1:{s:6:"notify";a:0:{}}'
);

// number of days to generate Free/Busy information for:
$_prefs['freebusy_days'] = array(
    'value' => 30,
    'type' => 'number',
    'desc' => _("How many days of Free/Busy information should be generated?")
);

// By default, display all contacts in the address book when loading
// the contacts screen.  If your default address book is large and
// slow to display, you may want to disable and lock this preference.
$_prefs['display_contact'] = array(
    'value' => 1,
    'type' => 'checkbox',
    'desc' => _("List all contacts when loading the contacts screen? (if disabled, you will only see contacts that you search for explicitly)"),
);

// address book selection widget
$_prefs['sourceselect'] = array('type' => 'special');

// Address book(s) to use when expanding addresses
// Refer to turba/config/sources.php for possible source values
//
// You can provide default values this way:
//   'value' => json_encode(array('source_one', 'source_two'))
$_prefs['search_sources'] = array(
    'value' => ''
);

// Field(s) to use when expanding addresses
// Refer to turba/config/sources.php for possible source and field values
//
// If you want to provide a default value, this field depends on the
// search_sources preference. For example:
//   'value' => json_encode(array(
//       'source_one' => array('field_one', 'field_two'),
//       'source_two' => array('field_three')
//   ))
// will search the fields 'field_one' and 'field_two' in source_one and
// 'field_three' in source_two.
$_prefs['search_fields'] = array(
    'value' => ''
);

$_prefs['fb_url'] = array(
    'value' => '<strong>' . _("My Free/Busy URL") . '</strong><div class="fburl"><div>' . _("Copy this URL for use wherever you need your Free/Busy URL:") . '</div><div class="fixed">' . Horde::url('fb.php', true, array('append_session' => -1))->add('u', $GLOBALS['registry']->getAuth()) . '</div></div>',
    'type' => 'rawhtml'
);

// Calendars to include in generating Free/Busy URLs.
$_prefs['fb_cals'] = array(
    'value' => 'a:0:{}',
    'type' => 'multienum',
    'desc' => _("Choose the calendars to include in the above Free/Busy URL:"),
);

// Login Tasks preferences

$_prefs['purge_events'] = array(
    'value' => 0,
    'type' => 'checkbox',
    'desc' => _("Purge old events from your calendar?"),
);

$_prefs['purge_events_interval'] = array(
    'value' => Horde_LoginTasks::MONTHLY,
    'type' => 'enum',
    'enum' => Horde_LoginTasks::getLabels(),
    'desc' => _("Purge old events how often:"),
);

$_prefs['purge_events_keep'] = array(
    'value' => 365,
    'type' => 'number',
    'desc' => _("Purge old events older than this amount of days."),
);

// End Login Tasks preferences
