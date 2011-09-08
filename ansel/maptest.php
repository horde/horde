<?php
/**
 * Strictly a temporary bootstrap for testing Ansel's HordeMap integration
 *
 */
require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('ansel');

$image_id = Horde_Util::getFormData('image');

/* Sanity checks, perms etc... */
if (empty($image_id)) {
    throw new Ansel_Exception(
        _("An error has occured retrieving the image. Details have been logged."));
}
$image = $GLOBALS['injector']
    ->getInstance('Ansel_Storage')
    ->getImage($image_id);
$gallery = $GLOBALS['injector']
    ->getInstance('Ansel_Storage')
    ->getGallery($image->gallery);
if (!$gallery->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
    throw new Horde_Exception_PermissionDenied(
        _("Not Authorized. Details have been logged for the server administrator."));
}

$geodata = array(
    'image_id' => $image->id,
    'image_latitude' => $image->lat,
    'image_longitude' => $image->lng,
    'image_location' => $image->location,
    'icon' => Ansel::getImageUrl($image->id, 'mini', true),
    'markerOnly' => true,
    'draggable' => true);

/* Gettext strings */
$save = _("Save");
$returnText = _("Return to Image View");
$findText = _("Find");
$fetchingText = _("Fetching location");
$locateText = _("Locate image at:");
$errorText = _("Unable to find location. Error code:");

/* Links, img src etc...  */
$returnLink = Ansel::getUrlFor(
    'view',
    array(
        'view' => 'Image',
       'image' => $image_id,
       'gallery' => $gallery->id));


$loadingImg = Horde::img('loading.gif', _("Loading..."));

/* Start the output */

include $registry->get('templates', 'horde') . '/common-header.inc';
Ansel::initJSVariables();
Ansel::initHordeMap($conf['maps']);
echo '<div class="header">' . sprintf(_("Update position of %s"), $image->filename) . '</div>';
echo '<div id="anselmap" style="height:300px;width:700px;"></div>';
?>
<script type="text/javascript">
    document.observe('dom:loaded', function() {
        AnselMap.ensureMap('anselmap',
        {
            'panzoom': true,
            'layerswitcher': true
        });
        AnselMap.placeMapMarker(
            'anselmap',
            {
                'lat': <?php echo $geodata['image_latitude']?>,
                'lon': <?php echo $geodata['image_longitude']?>
            },
            null,
            null,
            '<?php echo $geodata["icon"]?>'
        );
    });
</script>
<?php
require $registry->get('templates', 'horde') . '/common-footer.inc';

// // Helper function for displaying Lat/Lng values
// function _point2Deg($value, $lat = false)
// {
//     $letter = $lat ? ($value > 0 ? "N" : "S") : ($value > 0 ? "E" : "W");
//     $value = abs($value);
//     $deg = floor($value);
//     $min = floor(($value - $deg) * 60);
//     $sec = ($value - $deg - $min / 60) * 3600;
//     return $deg . "&deg; " . $min . '\' ' . round($sec, 2) . '" ' . $letter;
// }
