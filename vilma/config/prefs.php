<?php
/**
 * See horde/config/prefs.php for documentation on the structure of this file.
 *
 * IMPORTANT: Local overrides should be placed in pref.local.php, or
 * prefs-servername.php if the 'vhosts' setting has been enabled in Horde's
 * configuration.
 */

$prefGroups['display'] = array(
    'column' => _("Display listings"),
    'label' => _("Display details"),
    'desc' => _("Set default display parameters."),
    'members' => array('addresses_perpage'));

// listing
$_prefs['addresses_perpage'] = array(
     'value' => 20,
     'locked' => false,
     'type' => 'number',
     'desc' => _("How many domain to display per page."));

/* Local overrides. */
if (file_exists(dirname(__FILE__) . '/prefs.local.php')) {
    include dirname(__FILE__) . '/prefs.local.php';
}
