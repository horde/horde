<?php
/**
 * $Horde: hermes/config/prefs.php.dist,v 1.9 2008/06/25 16:05:34 jan Exp $
 *
 * See horde/config/prefs.php for documentation on the structure of this file.
 */

$prefGroups['timer'] = array(
    'column' => _("General Options"),
    'label' => _("Timer Options"),
    'desc' => _("Set preferences on the stop watch timer."),
    'members' => array('add_description')
);

// should we add the stop watch name to the description?
$_prefs['add_description'] = array(
    'value' => true,
    'locked' => false,
    'shared' => false,
    'type' => 'checkbox',
    'desc' => _("Add stop watch name and start and end time to the description of the time entry?")
);

// preference for holding any running timers.
$_prefs['running_timers'] = array(
    'value' => '',
    'locked' => false,
    'shared' => false,
    'type' => 'implicit'
);
