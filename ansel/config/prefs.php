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
    'desc' => _("Change display preferences such as which view to display by default, how many photos to display on a page, and the default gallery style to use."),
    'members' => array(
        'grouptitle', 'defaultview', 'tilesperrow', 'tilesperpage',
        'facesperpage', 'groupby', 'groupsperpage',
        'default_gallerystyle_select', 'default_category_select'
    )
);

$prefGroups['metadata'] = array(
    'column' => _("General Preferences"),
    'label' => _("Tags and Metadata Preferences"),
    'desc' => _("Change preferences dealing with tags and image metadata."),
    'members' => array('showexif', 'exif_tags', 'exif_title')
);

$prefGroups['perms'] = array(
    'column' => _("General Preferences"),
    'label' => _("Permission Preferences"),
    'desc' => _("Change your user permission preferences such as who can download original photos, and what permissions newly created galleries should have by default."),
    'members' => array(
        'default_download', 'default_permissions', 'group_permissions',
        'guest_permissions'
    )
);

$prefGroups['watermark'] = array(
    'column' => _("General Preferences"),
    'label' => _("Watermark Preferences"),
    'desc' => _("Change your watermark preferences."),
    'members' => array(
        'watermark_text', 'watermark_vertical', 'watermark_horizontal',
        'watermark_font', 'watermark_auto'
    )
);

/* Note that for the following to work, your pref backend must support
   retrieving prefs for other users (such as the SQL backend) */
$_prefs['grouptitle'] = array(
    'value' => '',
    'type' => 'text',
    'desc' => _("Custom text to display describing your galleries. This will be displayed in place of your username when grouping galleries by username.")
);

$_prefs['defaultview'] = array(
    'value' => 'galleries',
    'type' => 'enum',
    'enum' => array(
        'browse' => _("Browse"),
        'galleries' => _("Galleries"),
        'mygalleries' => _("My Galleries")
    ),
    'desc' => _("View to display by default")
);

$_prefs['groupby'] = array(
    'value' => 'none',
    'type' => 'enum',
    'enum' => array(
        'owner' => _("Owner"),
        'category' => _("Category"),
        'none' => _("None")
    ),
    'desc' => _("Group galleries by")
);

// number of photos on each row in the gallery view
$_prefs['tilesperrow'] = array(
    'value' => 3,
    'type' => 'number',
    'desc' => _("Number of tiles per row")
);

$_prefs['tilesperpage'] = array(
    'value' => 9,
    'type' => 'number',
    'desc' => _("Number of tiles per page")
);

$_prefs['facesperpage'] = array(
    'value' => '20',
    'locked' => !$GLOBALS['conf']['faces']['driver'],
    'type' => 'number',
    'desc' => _("Number of faces per page")
);

$_prefs['groupsperpage'] = array(
    'value' => 9,
    'type' => 'number',
    'desc' => _("Number of groups per page")
);

$_prefs['showexif'] = array(
    'value' => false,
    'type' => 'checkbox',
    'desc' => _("Show EXIF data")
);

$_prefs['watermark'] = array(
    'value' => '',
    'type' => 'text',
    'desc' => _("Custom watermark to use for photos")
);

$_prefs['myansel_layout'] = array(
    'value' => 'a:1:{i:0;a:3:{i:0;a:4:{s:3:"app";s:5:"ansel";s:6:"height";i:1;s:5:"width";i:1;s:6:"params";a:2:{s:4:"type";s:5:"cloud";s:6:"params";a:1:{s:5:"count";s:2:"20";}}}i:1;a:4:{s:3:"app";s:5:"ansel";s:6:"height";i:1;s:5:"width";i:1;s:6:"params";a:2:{s:4:"type";s:12:"my_galleries";s:6:"params";a:0:{}}}i:2;a:4:{s:3:"app";s:5:"ansel";s:6:"height";i:1;s:5:"width";i:1;s:6:"params";a:2:{s:4:"type";s:14:"recently_added";s:6:"params";a:2:{s:7:"gallery";s:3:"all";s:5:"limit";s:2:"10";}}}}}'
);

$_prefs['default_gallerystyle'] = array(
    'value' => 'ansel_default'
);
$_prefs['default_gallerystyle_select'] = array(
    'type' => 'special'
);

// Default category
$_prefs['default_category'] = array(
    'value' => ''
);

// Default category
$_prefs['default_category_select'] = array(
    'type' => 'special'
);

$_prefs['show_actions'] = array(
    'value' => 0
);

$_prefs['show_othergalleries'] = array(
    'value' => 0
);

$_prefs['watermark_text'] = array(
    'value' => '',
    'type' => 'text',
    'desc' => _("Custom watermark to use for photos")
);

$_prefs['watermark_horizontal'] = array(
    'value' => 'left',
    'type' => 'enum',
    'enum' => array(
        'left' => _("Left"),
        'center' => _("Center"),
        'right' => _("Right")
    ),
    'desc' => _("Horizontal Alignment")
);

$_prefs['watermark_vertical'] = array(
    'value' => 'bottom',
    'type' => 'enum',
    'enum' => array(
        'top' => _("Top"),
        'center' => _("Center"),
        'bottom' => _("Bottom")
    ),
    'desc' => _("Vertical Alignment")
);

$_prefs['watermark_font'] = array(
    'value' => 'bottom',
    'type' => 'enum',
    'enum' => array(
        'tiny' => _("Tiny"),
        'small' => _("Small"),
        'medium' => _("Medium"),
        'large' => _("Large"),
        'giant' => _("Giant")
    ),
    'desc' => _("Vertical Alignment")
);

$_prefs['watermark_auto'] = array(
    'value' => 0,
    'type' => 'checkbox',
    'desc' => _("Automatically watermark photos?")
);

$_prefs['default_download'] = array(
    'value' => 'edit',
    'type' => 'enum',
    'enum' => array(
        'all' => _("Anyone"),
        'edit' => _("Authenticated users"),
        'authenticated' => _("Users with edit permissions")
    ),
    'desc' => _("Who should be allowed to download original photos")
);

$_prefs['default_permissions'] = array(
    'value' => 'read',
    'type' => 'enum',
    'enum' => array(
        'none' => _("None"),
        'read' => _("Read-only"),
        'edit' => _("Read and write")
    ),
    'desc' => _("When a new gallery is created, what permissions should be given to authenticated users by default?")
);

$_prefs['guest_permissions'] = array(
    'value' => 'read',
    'type' => 'enum',
    'enum' => array(
        'none' => _("None (Owner only)"),
        'read' => _("Read-only")
    ),
    'desc' => _("When a new gallery is created, what permissions should be given to guests by default?")
);

$_prefs['group_permissions'] = array(
    'value' => 'none',
    'type' => 'enum',
    'enum' => array(
        'none' => _("None"),
        'read' => _("Read-only"),
        'edit' => _("Read and write"),
        'delete' => _("Read, write, and delete")
    ),
    'desc' => _("When a new gallery is created, what default permissions should be given to groups that the user is a member of?")
);

$_prefs['exif_tags'] = array(
    'value' => 'a:0:{}',
    'type' => 'multienum',
    'desc' => _("Which metadata fields should we automatically add as image tags during upload?"),
);

$_prefs['exif_title'] = array(
    'value' => '',
    'type' => 'enum',
    'desc' => _("Should we automatically set the image title on upload if able? If so, choose the field to obtain the title from.")
);

/* Local overrides. */
if (file_exists(dirname(__FILE__) . '/prefs.local.php')) {
    include dirname(__FILE__) . '/prefs.local.php';
}
