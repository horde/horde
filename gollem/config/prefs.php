<?php
/**
 * See horde/config/prefs.php for documentation on the structure of this file.
 *
 * IMPORTANT: Local overrides should be placed in prefs.local.php, or
 * prefs-servername.php if the 'vhosts' setting has been enabled in Horde's
 * configuration.
 */

// *** File Display Preferences ***

$prefGroups['display'] = array(
    'column' => _("User Interface"),
    'label' => _("File Display"),
    'desc' => _("File display preferences."),
    'members' => array(
        'show_dotfiles', 'sortdirsfirst', 'columnselect', 'sortby', 'sortdir',
        'perpage'
    )
);

// show dotfiles?
$_prefs['show_dotfiles'] = array(
    'value' => 0,
    'type' => 'checkbox',
    'desc' => _("Show dotfiles?")
);

// always sort directories before files
$_prefs['sortdirsfirst'] = array(
    'value' => 1,
    'type' => 'checkbox',
    'desc' => _("List folders first?")
);

// columns selection widget
$_prefs['columnselect'] = array(
    'type' => 'special'
);

// columns to be displayed
$_prefs['columns'] = array(
    // 'value' = json_encode(array())
    'value' => '["ftp","type","name","download","modified","size","permission","owner","group"]'
);


// user preferred sorting column
$_prefs['sortby'] = array(
    'value' => Gollem::SORT_TYPE,
    'type' => 'enum',
    'enum' => array(
        Gollem::SORT_TYPE => _("File Type"),
        Gollem::SORT_DATE => _("File Name"),
        Gollem::SORT_DATE => _("File Modification Time"),
        Gollem::SORT_SIZE => _("File Size")
    ),
    'desc' => _("Default sorting criteria:")
);

// user preferred sorting direction
$_prefs['sortdir'] = array(
    'value' => 0,
    'type' => 'enum',
    'enum' => array(
        Gollem::SORT_ASCEND => _("Ascending"),
        Gollem::SORT_DESCEND => _("Descending")
    ),
    'desc' => _("Default sorting direction:")
);

// number of items per page
$_prefs['perpage'] = array(
    'value' => 30,
    'type' => 'number',
    'desc' => _("Items per page")
);



// *** File Actions Preferences ***

$prefGroups['settings'] = array(
    'column' => _("User Interface"),
    'label' => _("File Actions"),
    'desc' => _("File action settings."),
    'members' => array('recursive_deletes'));

// user preferred recursive deletes
$_prefs['recursive_deletes'] = array(
    'value' => 'disabled',
    'type' => 'enum',
    'enum' => array(
        'disabled' => _("No"),
        'enabled' => _("Yes"),
        'warn' => _("Ask")
    ),
    'desc' => _("Delete folders recursively?")
);
