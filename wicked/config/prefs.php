<?php
/**
 * See horde/config/prefs.php for documentation on the structure of this file.
 *
 * IMPORTANT: Local overrides should be placed in pref.local.php, or
 * prefs-servername.php if the 'vhosts' setting has been enabled in Horde's
 * configuration.
 */

$_prefs = array();

$_prefs['sync_data'] = array(
    'value' => 'a:0:{}',
    'locked' => false,
    'type' => 'implicit'
);

/* Local overrides. */
if (file_exists(dirname(__FILE__) . '/prefs.local.php')) {
    include dirname(__FILE__) . '/prefs.local.php';
}
