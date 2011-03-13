<?php
/**
 * See horde/config/prefs.php for documentation on the structure of this file.
 *
 * IMPORTANT: Local overrides should be placed in pref.local.php, or
 * prefs-servername.php if the 'vhosts' setting has been enabled in Horde's
 * configuration.
 */

$prefGroups['display'] = array(
    'column' => _("Other Preferences"),
    'label' => _("Display Preferences"),
    'desc' => _("Set how to display bookmark listings and how to open links."),
    'members' => array('sortby', 'sortdir', 'show_in_new_window')
);

// bookmark sort order
$_prefs['sortby'] = array(
    'value' => 'title',
    'locked' => false,
    'type' => 'enum',
    'enum' => array('title' => _("Title"),
                    'rating' => _("Highest Rated"),
                    'clicks' => _("Most Clicked")),
    'desc' => _("Sort bookmarks by:")
);

// user preferred sorting direction
$_prefs['sortdir'] = array(
    'value' => 0,
    'locked' => false,
    'type' => 'enum',
    'enum' => array(0 => _("Ascending (A to Z)"),
                    1 => _("Descending (9 to 1)")),
    'desc' => _("Sort direction:")
);

// Open links in new windows?
$_prefs['show_in_new_window'] = array(
    'value' => 1,
    'locked' => false,
    'type' => 'checkbox',
    'desc' => _("Open links in a new window?")
);
