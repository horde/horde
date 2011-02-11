<?php
/**
 * See horde/config/prefs.php for documentation on the structure of this file.
 *
 * IMPORTANT: Local overrides should be placed in prefs.local.php, or
 * prefs-servername.php if the 'vhosts' setting has been enabled in Horde's
 * configuration.
 */

$prefGroups['timer'] = array(
    'column' => _("General Preferences"),
    'label' => _("Stop watch Preferences"),
    'desc' => _("Set preferences on the stop watch timer."),
    'members' => array('add_description')
);

// should we add the stop watch name to the description?
$_prefs['add_description'] = array(
    'value' => true,
    'locked' => false,
    'type' => 'checkbox',
    'desc' => _("Add stop watch name and start and end time to the description of the time entry?")
);

// preference for holding any running timers.
$_prefs['running_timers'] = array(
    'value' => '',
    'locked' => false,
    'type' => 'implicit'
);

/* Local overrides. */
if (file_exists(dirname(__FILE__) . '/prefs.local.php')) {
    include dirname(__FILE__) . '/prefs.local.php';
}
