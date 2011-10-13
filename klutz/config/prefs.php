<?php
/**
 * See horde/config/prefs.php for documentation on the structure of this file.
 */

$prefGroups['display'] = array(
    'column' => _("Other Options"),
    'label' => _("Display Options"),
    'desc' => _("Change display options such as what comics you see, and how comics are sorted."),
    'members' => array('comic_select', 'show_unselected', 'summ_showall', 'comicgallery'),
);


// user display
$_prefs['comic_select'] = array('type' => 'special');

$_prefs['viewcomics'] = array(
    'value' => '',
    'locked' => false,
    'shared' => false,
    'type' => 'implicit',
);

$_prefs['datesvisited'] = array(
    'value' => '',
    'locked' => false,
    'shared' => false,
    'type' => 'implicit',
);

$_prefs['show_unselected'] = array(
    'value' => 1,
    'locked' => false,
    'shared' => false,
    'type' => 'checkbox',
    'desc' => _("Show comics you haven't selected on the browse page?"),
);

$_prefs['summ_showall'] = array(
    'value' => 0,
    'locked' => false,
    'shared' => false,
    'type' => 'checkbox',
    'desc' => _("Show all of your selected comics on the summary page?"),
);
$_prefs['comicgallery'] = array(
    'value' => '',
    'locked' => !$registry->hasMethod('images/listGalleries'),
    'shared' => false,
    'type' => 'select',
    'desc' => _("Default gallery to use when saving comics:"),
);
