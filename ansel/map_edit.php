<?php
/**
 * Copyright 2009-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Michael J Rubinsky <mrubinsk@horde.org>
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('ansel');

// Init the map
Ansel::initJSVariables();
Horde::initMap();
$page_output->addScriptFile('map.js');
$page_output->addScriptFile('map_edit.js');

// Get the image id to (re)locate.
$image_id = Horde_Util::getFormData('image');

// Sanity checks, perms etc...
if (empty($image_id)) {
    throw new Ansel_Exception(
        _("An error has occured retrieving the image. Details have been logged."));
}

$image = $injector
    ->getInstance('Ansel_Storage')
    ->getImage($image_id);
$gallery = $injector->
    getInstance('Ansel_Storage')
    ->getGallery($image->gallery);

if (!$gallery->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
    throw new Horde_Exception_PermissionDenied(
        _("Not Authorized. Details have been logged for the server administrator."));
}

$view = $injector->getInstance('Horde_View');
$view->image_id = $image_id;
$view->loadingImg = Horde::img('loading.gif', _("Loading..."));
$view->filename = $image->filename;

// Determine if we already have a geotag or are we tagging it for the 1st time
if (empty($image->lat)) {
    $geodata = array(
        'image_id' => $image->id,
        'image_latitude' => '20',
        'image_longitude' => '40',
        'image_location' => '',
        'icon' => Ansel::getImageUrl($image->id, 'mini', true),
        'markerOnly' => true,
        'draggable' => true);
    $isNew = 1;
} else {
    $geodata = array(
        'image_id' => $image->id,
        'image_latitude' => $image->lat,
        'image_longitude' => $image->lng,
        'image_location' => $image->location,
        'icon' => Ansel::getImageUrl($image->id, 'mini', true),
        'markerOnly' => true,
        'draggable' => true);
    $isNew = 0;
}

// JSON representation of the image's geotag
$json = Horde_Serialize::serialize(array($geodata), Horde_Serialize::JSON);

// Url for geotag ajax helper
$gtUrl = $registry->getServiceLink('ajax', 'ansel')->setRaw(true);
$gtUrl->url .= 'imageSaveGeotag';

// Obtain other geotagged images to possibly locate this image at
$view->imgs = $injector
    ->getInstance('Ansel_Storage')
    ->getRecentImagesGeodata($GLOBALS['registry']->getAuth());

if (count($view->imgs) > 0) {
    $js = array();
    foreach ($view->imgs as $id => $data) {
        if ($id != $image_id) {
            $js[] = '$("geo_' . $id . '").observe("click", function() { mapEdit.setLocation("' . $data['image_latitude'] . '", "' . $data['image_longitude'] . '", "' . $data['image_location'] . '");return false; } )';
        }
    }
    $page_output->addInlineScript($js, true);
}

$page_output->addInlineScript(
    "var mapEdit = new AnselMapEdit({$json}, {
        'geocoder': Ansel.conf.maps.geocoder,
        'image_id': {$image_id},
        'ajaxuri': '{$gtUrl}' });
    $('saveButton').observe('click', mapEdit.save.bind(mapEdit));
    $('locationAction').observe('click', function(e) { mapEdit.geocode($('locationInput').value); e.stop(); });",
    true);

$page_output->topbar = $page_output->sidebar = false;
$page_output->header(array(
    'title' => $title
));
echo $view->render('map_edit');
$page_output->footer();
