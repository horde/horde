<?php
global $prefs, $registry;

$ansel_webroot = $registry->get('webroot');
$horde_webroot = $registry->get('webroot', 'horde');
$has_tasks = $registry->hasInterface('tasks');

/* Variables used in core javascript files. */
$code['conf'] = array(
    'URI_AJAX' => (string)Horde::getServiceLink('ajax', 'ansel'),
    'SESSION_ID' => defined('SID') ? SID : '',
    'images' => array(
        'error' => (string)Horde_Themes::img('error-thumb.png'),
    ),
    'user' => $GLOBALS['registry']->convertUsername($GLOBALS['registry']->getAuth(), false),
    'name' => $registry->get('name'),
);

// List of top level galleries
$gallerylist = $GLOBALS['injector']->getInstance('Ansel_Storage')->listGalleries(array('all_levels' => false, 'attributes' => $registry->getAuth()));
$galleries = array();

foreach ($gallerylist as $gallery) {
    $galleries[] = $gallery->toJson();
}
$code['conf']['galleries'] = $galleries;

/* Gettext strings used in core javascript files. */
$code['text'] = array(
    'ajax_error' => _("Error when communicating with the server."),
);
echo Horde::addInlineJsVars(array(
    'var Ansel' => $code
), array('top' => true));