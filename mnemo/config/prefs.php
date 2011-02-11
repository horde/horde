<?php
/**
 * See horde/config/prefs.php for documentation on the structure of this file.
 *
 * IMPORTANT: Local overrides should be placed in prefs.local.php, or
 * prefs-servername.php if the 'vhosts' setting has been enabled in Horde's
 * configuration.
 */

$prefGroups['display'] = array(
    'column' => _("General Preferences"),
    'label' => _("Display Preferences"),
    'desc' => _("Change your note sorting and display preferences."),
    'members' => array('show_notepad', 'sortby', 'sortdir')
);

$prefGroups['share'] = array(
    'column' => _("General Preferences"),
    'label' => _("Default Notepad"),
    'desc' => _("Choose your default Notepad."),
    'members' => array('default_notepad')
);

$prefGroups['deletion'] = array(
    'column' => _("General Preferences"),
    'label' => _("Delete Confirmation"),
    'desc' => _("Delete button behaviour"),
    'members' => array('delete_opt')
);


// show a notepad column in the list view?
$_prefs['show_notepad'] = array(
    'value' => 0,
    'type' => 'checkbox',
    'desc' => _("Should the Notepad be shown in its own column in the List view?")
);

// show the notepad options panel?
// a value of 0 = no, 1 = yes
$_prefs['show_panel'] = array(
    'value' => 1
);

// user preferred sorting column
$_prefs['sortby'] = array(
    'value' => Mnemo::SORT_DESC,
    'type' => 'enum',
    'enum' => array(
        Mnemo::SORT_DESC => _("Note Text"),
        Mnemo::SORT_CATEGORY => _("Note Category"),
        Mnemo::SORT_NOTEPAD => _("Notepad")
    ),
    'desc' => _("Default sorting criteria:")
);

// user preferred sorting direction
$_prefs['sortdir'] = array(
    'value' => 0,
    'type' => 'enum',
    'enum' => array(
        Mnemo::SORT_ASCEND => _("Ascending"),
        Mnemo::SORT_DESCEND => _("Descending")
    ),
    'desc' => _("Default sorting direction:")
);

// user note categories
$_prefs['memo_categories'] = array(
    'value' => ''
);

// category highlight colors
$_prefs['memo_colors'] = array(
    'value' => ''
);

// default notepad
// Set locked to true if you don't want users to have multiple notepads.
$_prefs['default_notepad'] = array(
    'value' => $GLOBALS['registry']->getAuth() ? $GLOBALS['registry']->getAuth() : 0,
    'type' => 'enum',
    'desc' => _("Your default notepad:")
);

// store the notepads to diplay
$_prefs['display_notepads'] = array(
    'value' => 'a:0:{}'
);

// preference for delete confirmation dialog.
$_prefs['delete_opt'] = array(
    'value' => 1,
    'type' => 'checkbox',
    'desc' => _("Do you want to confirm deleting entries?")
);

/* Local overrides. */
if (file_exists(dirname(__FILE__) . '/prefs.local.php')) {
    include dirname(__FILE__) . '/prefs.local.php';
}
