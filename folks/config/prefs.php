<?php
/**
 * See horde/config/prefs.php for documentation on the structure of this file.
 *
 * IMPORTANT: Local overrides should be placed in prefs.local.php, or
 * prefs-servername.php if the 'vhosts' setting has been enabled in Horde's
 * configuration.
 */

$prefGroups['Preview'] = array(
    'column' => _("Preview"),
    'label' => _("How to preview users"),
    'desc' => _("Set users preview paramaters"),
    'members' => array('sort_by', 'sort_dir', 'per_page')
);

$prefGroups['Settings'] = array(
    'column' => _("Settings"),
    'label' => _("Modify account preferences"),
    'desc' => _("Set account action details"),
    'members' => array('login_notify', 'friends_approval')
);

$prefGroups['Activities'] = array(
    'column' => _("Settings"),
    'label' => _("Activity log"),
    'desc' => _("Set activity preferences"),
    'members' => array('log_user_comments', 'log_account_changes', 'log_scopes', 'log_scope_comments')
);

$_prefs['sort_by'] = array(
    'value' => 'user_uid',
    'locked' => false,
    'type' => 'enum',
    'enum' => array('user_uid' => _("Username")),
    'desc' => _("Sort by")
);

$_prefs['sort_dir'] = array(
    'value' => 1,
    'locked' => false,
    'type' => 'enum',
    'enum' => array(0 => _("Descesending"),
                    1 => _("Ascesending")),
    'desc' => _("Sort by")
);

$_prefs['per_page'] = array(
    'value' => 20,
    'locked' => false,
    'type' => 'number',
    'desc' => _("Number of users perpage")
);

$_prefs['login_notify'] = array(
    'value' => 1,
    'locked' => false,
    'type' => 'enum',
    'enum' => array(0 => _("No"),
                    1 => _("Yes")),
    'desc' => _("Notify friends that I loged in")
);

$_prefs['friends_approval'] = array(
    'value' => 1,
    'locked' => false,
    'type' => 'enum',
    'enum' => array(0 => _("No"),
                    1 => _("Yes")),
    'desc' => _("Require my confirmation if someone would like to add me to his freidn list.")
);

$_prefs['log_user_comments'] = array(
    'value' => 1,
    'locked' => false,
    'type' => 'enum',
    'enum' => array(0 => _("No"),
                    1 => _("Yes")),
    'desc' => _("Log when we comment a user?")
);

$_prefs['log_account_changes'] = array(
    'value' => 1,
    'locked' => false,
    'type' => 'enum',
    'enum' => array(0 => _("No"),
                    1 => _("Yes")),
    'desc' => _("Log account changes?")
);

$apps = array();
/*
foreach ($GLOBALS['registry']->listApps() as $app) {
    $apps[$app] = $GLOBALS['registry']->get('name', $app);
}
asort($apps);
*/

$_prefs['log_scopes'] = array(
    'value' => 'a:0:{}',
    'locked' => false,
    'type' => 'multienum',
    'enum' => $apps,
    'desc' => _("Application you would like NOT to log your activitiy when you post a new PUBLIC CONTENT.")
);

foreach ($apps as $app) {
    if (!$GLOBALS['registry']->hasMethod('commentCallback', $app)) {
        unset($apps[$app]);
    }
}

$_prefs['log_scope_comments'] = array(
    'value' => 'a:0:{}',
    'locked' => false,
    'type' => 'multienum',
    'enum' => $apps,
    'desc' => _("Application you would like NOT to log activitiy when you post a new PUBLIC COMMENT")
);

/* Local overrides. */
if (file_exists(dirname(__FILE__) . '/prefs.local.php')) {
    include dirname(__FILE__) . '/prefs.local.php';
}
