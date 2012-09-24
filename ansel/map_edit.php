<?php
/**
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
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

// Determine if we already have a geotag or are we tagging it for the 1st time
if (empty($image->lat)) {
    $geodata = array('image_id' => $image->id,
                     'image_latitude' => "20",
                     'image_longitude' => "40",
                     'image_location' => '',
                     'icon' => Ansel::getImageUrl($image->id, 'mini', true),
                     'markerOnly' => true,
                     'draggable' => true);
    $isNew = 1;
} else {
    $geodata = array('image_id' => $image->id,
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

/* Gettext strings */
$save = _("Save");
$returnText = _("Return to Image View");
$findText = _("Find");
$fetchingText = _("Fetching location");
$locateText = _("Locate image at:");
$errorText = _("Unable to find location. Error code:");

// Links, img src
$returnLink = Ansel::getUrlFor(
    'view', array(
        'view' => 'Image',
        'image' => $image_id,
        'gallery' => $gallery->id));
$image_tag = '<img src="' . Ansel::getImageUrl($image_id, 'thumb', true) . '" alt="[thumbnail]" />';

// Url for geotag ajax helper
$gtUrl = $registry->getServiceLink('ajax', 'ansel')->setRaw(true);
$gtUrl->url .= 'imageSaveGeotag';

$loadingImg = Horde::img('loading.gif', _("Loading..."));

// Obtain other geotagged images to possibly locate this image at
$imgs = $injector->getInstance('Ansel_Storage')
    ->getRecentImagesGeodata($GLOBALS['registry']->getAuth());
if (count($imgs) > 0) {
    $other_images = '<div class="ansel_location_sameas">'
        . _("Click on a thumbnail to locate at the same point.") . '<br />';
    foreach ($imgs as $id => $data) {
        if ($id != $image_id) {
            if (!empty($data['image_location'])) {
                $iTitle = $data['image_location'];
            } else {
                $iTitle = _point2Deg($data['image_latitude'], true)
                    . ' ' . _point2Deg($data['image_longitude']);
            }
            $tempurl = new Horde_Url('#');
            $other_images .= $tempurl->link(array('title' => $iTitle, 'onclick' => "mapEdit.setLocation('" . $data['image_latitude'] . "', '" . $data['image_longitude'] . "', '" . $data['image_location'] . "');return false")) . '<img src="' . Ansel::getImageUrl($id, 'mini', true) . '" alt="[thumbnail]" /></a>';
        }
    }
    $other_images .= '</div>';
} else {
    $other_images = '';
}

/* Build the HTML */
$html = <<<EOT
<div id="status">&nbsp;</div>
<div style="width:450px;float:left;">
  <div id="ansel_map" style="width:450px;height:450px;"></div>
</div>
<div style="float:left;width:250px;min-height:450px;">
 <div class="control">
  <h4>{$locateText}</h4>
  <form>
   <input type="text" id="locationInput" name="locationInput" />
   <span id="locationInput_loading_img" style="display:none;">{$loadingImg}</span>
   <input id="locationAction" value="{$findText}" class="button" type="submit" />
  </form>
 </div>
 {$other_images}
 <div class="control" style="vertical-align:bottom;">
  <div style="text-align:center;margin-top:6px;">{$image_tag}</div>
  <div class="ansel_geolocation">
   <div id="ansel_locationtext">&nbsp;</div>
   <div id="ansel_latlng">&nbsp;</div>
  </div>
 </div>
</div>
<div class="clear"></div>
<div class="control">
 <input class="button" id="saveButton" type="submit" value="{$save}" /><input class="button" type="submit" onclick="window.close();" value="{$returnText}" />
</div>
EOT;

$page_output->addInlineScript(
    "var mapEdit = new AnselMapEdit({$json}, {
        'geocoder': Ansel.conf.maps.geocoder,
        'image_id': {$image_id},
        'ajaxuri': '{$gtUrl}' });
    $('saveButton').observe('click', mapEdit.save.bind(mapEdit));
    $('locationAction').observe('click', function(e) { mapEdit.geocode($('locationInput').value); e.stop(); });",
    true);
$page_output->topbar = $page_output->sidebar = false;

// Start the output
$page_output->header(array(
    'title' => $title
));
echo '<div class="header">' . sprintf(_("Update position of %s"), $image->filename) . '</div>';
echo $html;
$page_output->footer();

// Helper function for displaying Lat/Lng values
function _point2Deg($value, $lat = false)
{
    $letter = $lat ? ($value > 0 ? "N" : "S") : ($value > 0 ? "E" : "W");
    $value = abs($value);
    $deg = floor($value);
    $min = floor(($value - $deg) * 60);
    $sec = ($value - $deg - $min / 60) * 3600;
    return $deg . "&deg; " . $min . '\' ' . round($sec, 2) . '" ' . $letter;
}
