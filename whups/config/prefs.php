<?php
/**
 * $Horde: whups/config/prefs.php.dist,v 1.29 2008/06/06 15:09:44 jan Exp $
 *
 * See horde/config/prefs.php for documentation on the structure of this file.
 */

$prefGroups['display'] = array(
    'column' => _("General Options"),
    'label' => _("Display Options"),
    'desc' => _("Change display options such as how search results are sorted."),
    'members' => array('sortby', 'sortdir', 'comment_sort_dir', 'whups_default_view', 'summary_show_requested', 'summary_show_ticket_numbers', 'report_time_format', 'autolink_tickets')
);

$prefGroups['notification'] = array(
    'column' => _("General Options"),
    'label' => _("Notification Options"),
    'desc' => _("Change options for email notifications of ticket activity."),
    'members' => array('email_others_only', 'email_comments_only'));

if ($GLOBALS['registry']->hasMethod('contacts/sources')) {
    $prefGroups['addressbooks'] = array(
        'column' => _("General Options"),
        'label' => _("Address Books"),
        'desc' => _("Select address book sources for adding and searching for addresses."),
        'members' => array('sourceselect'),
    );
}


// the layout of the bugs portal.
$_prefs['mybugs_layout'] = array(
    'value' => 'a:0:{}',
    'locked' => false,
    'shared' => false,
    'type' => 'implicit'
);

// user preferred sorting column
$_prefs['sortby'] = array(
    'value' => 'id',
    'locked' => false,
    'shared' => false,
    'type' => 'enum',
    'enum' => array('id' => _("Id"),
                    'summary' => _("Summary"),
                    'state_name' => _("State"),
                    'type_name'     => _("Type"),
                    'priority_name' => _("Priority"),
                    'queue_name' => _("Queue"),
                    'version_name' => _("Version"),
                    'timestamp' => _("Created"),
                    'date_assigned' => _("Assigned"),
                    'date_resolved' => _("Resolved")),
    'desc' => _("Default sorting criteria:")
);

// user preferred sorting direction
$_prefs['sortdir'] = array(
    'value' => 0,
    'locked' => false,
    'shared' => false,
    'type' => 'enum',
    'enum' => array(0 => _("Ascending"),
                    1 => _("Descending")),
    'desc' => _("Default sorting direction:")
);

// default view
$_prefs['whups_default_view'] = array(
    'value' => 'mybugs',
    'locked' => false,
    'shared' => false,
    'type' => 'enum',
    'enum' => array('mybugs' => _("My Tickets"),
                    'search' => _("Search Tickets"),
                    'ticket/create' => _("Create Ticket")),
    'desc' => _("Select the view to display after login:")
);

// show requested tickets in the horde summary?
$_prefs['summary_show_requested'] = array(
    'value' => 1,
    'locked' => false,
    'shared' => false,
    'type' => 'checkbox',
    'desc' => _("Show tickets you have requested in the summary view?"));

// show ticket ids in the horde summary?
$_prefs['summary_show_ticket_numbers'] = array(
    'value' => 1,
    'locked' => false,
    'shared' => false,
    'type' => 'checkbox',
    'desc' => _("Show ticket IDs in the summary view?")
);

// Allow custom time/date formats in reports
$_prefs['report_time_format'] = array(
    'value' => '%m/%d/%y',
    'locked' => false,
    'shared' => false,
    'type' => 'enum',
    'enum' => array('%a %d %B' => _("Weekday Day Month"),
                    '%c' => _("Weekday Day Month HH:MM:SS TZ"),
                    '%m/%d/%y' => _("MM/DD/YY"),
                    '%m/%d/%y %H:%M:%S' => _("MM/DD/YY HH:MM:SS")),
    'desc' => _("Date/Time format for search results")
);

// Skip notification of changes you added?
$_prefs['email_others_only'] = array(
    'value' => 1,
    'locked' => false,
    'shared' => false,
    'type' => 'checkbox',
    'desc' => _("Only notify me of ticket changes from other users?")
);

// Skip notification without comments?
$_prefs['email_comments_only'] = array(
    'value' => 0,
    'locked' => false,
    'shared' => false,
    'type' => 'checkbox',
    'desc' => _("Only notify me of ticket changes with comments?")
);

// AutoLink to tickets references in comments
$_prefs['autolink_tickets'] = array(
    'value' => 1,
    'locked' => false,
    'shared' => false,
    'type' => 'checkbox',
    'desc' => _("Autolink to other tickets in comments?")
);

// Show ticket comments in ascending or descending order?
$_prefs['comment_sort_dir'] = array(
    'value' => 1,
    'locked' => false,
    'shared' => false,
    'type' => 'enum',
    'desc' => _("Show comments in chronological order, or most recent first?"),
    'enum' => array(0 => _("Chronological (oldest first)"),
                    1 => _("Most recent first"))
);

// address book selection widget
$_prefs['sourceselect'] = array('type' => 'special');

// address book(s) to use when expanding addresses
// You can provide default values this way (note the \t and the double quotes):
// 'value' => "source_one\tsource_two"
// refer to turba/config/sources.php for possible source values
$_prefs['search_sources'] = array(
    'value' => "",
    'locked' => false,
    'shared' => false,
    'type' => 'implicit',
);

// field(s) to use when expanding addresses
// This depends on the search_sources preference if you want to provide
// default values:
// 'value' => "source_one\tfield_one\tfield_two\nsource_two\tfield_three"
// will search the fields 'field_one' and 'field_two' in source_one and
// 'field_three' in source_two.
// refer to turba/config/sources.php for possible source and field values
$_prefs['search_fields'] = array(
    'value' => "",
    'locked' => false,
    'shared' => false,
    'type' => 'implicit',
);
