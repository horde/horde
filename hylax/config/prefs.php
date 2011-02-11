<?php
/**
 * See horde/config/prefs.php for documentation on the structure of this file.
 *
 * IMPORTANT: Local overrides should be placed in prefs.local.php, or
 * prefs-servername.php if the 'vhosts' setting has been enabled in Horde's
 * configuration.
 */

$prefGroups['display'] = array(
    'column' => _("Other Preferences"),
    'label' => _("Display Preferences"),
    'desc' => _("Change display preferences such as how search results are sorted."),
    'members' => array('hylax_default_view')
);

// default view
$_prefs['hylax_default_view'] = array(
    'value' => 'summary',
    'locked' => false,
    'type' => 'enum',
    'enum' => array('summmary' => _("Summary"),
                    'folder' => _("Folders"),
                    'compose' => _("Compose")),
    'desc' => _("Select the view to display after login:")
);

/* Local overrides. */
if (file_exists(dirname(__FILE__) . '/prefs.local.php')) {
    include dirname(__FILE__) . '/prefs.local.php';
}
