<?php
/**
 * Ansel_Widget_Geotag:: class to wrap the display of a Google map showing
 * images with geolocation data.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @TODO: Refactor the JS out to a seperate file, output needed values in the
 *        GLOBAL Ansel javascript object. Rewrite for Horde_Map js.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_Widget_Geotag extends Ansel_Widget_Base
{
    /**
     * List of views this widget supports
     *
     * @var array
     */
    protected $_supported_views = array(
        'Image',
        'Gallery');

    /**
     * Default params
     *
     * @var array
     */
    protected $_params = array(
        'default_zoom' => 15,
        'max_auto_zoom' => 15);

    /**
     * Const'r
     *
     * @param array $params
     *
     * @return Ansel_Widget_Geotag
     */
    public function __construct($params)
    {
        parent::__construct($params);
        $this->_title = _("Location");
    }

    /**
     * Attach widget to supplied view.
     *
     * @param Ansel_View_Base $view
     *
     * @return boolean
     */
    public function attach(Ansel_View_Base $view)
    {
        if (empty($GLOBALS['conf']['maps']['driver'])) {
            return false;
        }
        parent::attach($view);
        return true;
    }

    /**
     * Build the HTML for the widget
     *
     * @return string
     */
    public function html()
    {
        global $page_output;

        $ansel_storage = $GLOBALS['injector']->getInstance('Ansel_Storage');
        $geodata = $ansel_storage->getImagesGeodata($this->_params['images']);
        $viewType = $this->_view->viewType();

        // Exit early?
        if (count($geodata) == 0 && $viewType != 'Image') {
            return '';
        } elseif (count($geodata) == 0) {
            $noGeotag = true;
        }

        // Setup map and javascript includes
        Ansel::initHordeMap($GLOBALS['conf']['maps']);

        $page_output->addScriptFile('popup.js', 'horde');
        $page_output->addScriptFile('widgets/geotag.js');

        // Values needed by map javascript
        $url = Horde::url('map_edit.php', true);
        $rtext = _("Relocate this image");
        $dtext = _("Delete geotag");
        $thisTitleText = _("This image");
        $otherTitleText = _("Other images in this gallery");

        $geotagUrl = $GLOBALS['registry']->getServiceLink('ajax', 'ansel')->setRaw(true);
        $geotagUrl->url .= 'imageSaveGeotag';

        $permsEdit = (integer)$this->_view->gallery->hasPermission(
            $GLOBALS['registry']->getAuth(),
            Horde_Perms::EDIT);

        // URL for updating selected layer
        $layerUrl = $GLOBALS['registry']->getServiceLink('ajax', 'ansel')->setRaw(true);
        $layerUrl->url .= 'setPrefValue';

        // And the current defaultLayer, if any.
        $defaultLayer = $GLOBALS['prefs']->getValue('current_maplayer');

        // Add extra information to the JSON data to be sent:
        foreach ($geodata as $id => $data) {
            $geodata[$id]['icon'] = (string)Ansel::getImageUrl(
                $geodata[$id]['image_id'],
                'mini',
                true);
            $geodata[$id]['markerOnly'] = ($viewType == 'Image');
            $geodata[$id]['link'] = (string)Ansel::getUrlFor(
                'view',
                 array(
                     'view' => 'Image',
                     'gallery' => $this->_view->gallery->id,
                     'image' => $geodata[$id]['image_id']),
                true);
        }

        // Start HTML building for widget
        $html = $this->_htmlBegin() . "\n";
        $content = '<div id="ansel_geo_widget">';

        // If this is an image view, get the other gallery images
        if ($viewType == 'Image') {
            $image_id = $this->_view->resource->id;
            $others = $this->_getGalleryImagesWithGeodata();
            foreach ($others as $id => $data) {
                if ($id != $image_id) {
                    $others[$id]['icon'] = (string)Ansel::getImageUrl(
                        $others[$id]['image_id'],
                        'mini',
                        true);
                    $others[$id]['link'] = (string)Ansel::getUrlFor(
                            'view',
                            array(
                                'view' => 'Image',
                                'gallery' => $this->_view->gallery->id,
                                'image' => $others[$id]['image_id']),
                            true);
                } else {
                    unset($others[$id]);
                }
            }
            $geodata = array_values(array_merge($geodata, $others));

            // If we have geo data, build dom structure for maps, otherwise
            // build HTML for "Add geodata" section.
            if (empty($noGeotag)) {
                $content .= '<div id="ansel_map"></div>';
                $content .= '<div class="ansel_geolocation">';
                $content .= '<div id="ansel_locationtext"></div>';
                $content .= '<div id="ansel_latlng"></div>';
                $content .= '<div id="ansel_relocate"></div><div id="ansel_deleteGeotag"></div></div>';
                $content .= '<div id="ansel_map_small"></div>';
            } elseif ($permsEdit) {
                // Image view, but no geotags, provide ability to add it.
                $addurl = Horde::url('map_edit.php')->add(
                    'image',
                    $this->_params['images'][0]);
                $addLink = $addurl->link(
                    array('onclick' => Horde::popupJs(
                        Horde::url('map_edit.php'),
                        array('params' => array('image' => $this->_params['images'][0]), 'urlencode' => true, 'width' => '750', 'height' => '600'))
                    . 'return false;'));

                $imgs = $ansel_storage
                    ->getRecentImagesGeodata($GLOBALS['registry']->getAuth());
                if (count($imgs) > 0) {
                    $imgsrc = '<div class="ansel_location_sameas">';
                    foreach ($imgs as $id => $data) {
                        if (!empty($data['image_location'])) {
                            $title = $data['image_location'];
                        } else {
                            $title = $this->_point2Deg($data['image_latitude'], true)
                                . ' '
                                . $this->_point2Deg($data['image_longitude']);
                        }
                        $imgsrc .= $addurl->link(
                            array('
                                title' => $title,
                                'onclick' => "Ansel.widgets.geotag.setLocation(" . $image_id . ",'" . $data['image_latitude'] . "', '" . $data['image_longitude'] . "'); return false"
                            ))
                            . '<img src="' . Ansel::getImageUrl($id, 'mini', true) . '" alt="[image]" /></a>';
                    }
                    $imgsrc .= '</div>';
                    $content .= sprintf(_("No location data present. Place using %s map %s or click on image to place at the same location."), $addLink, '</a>') . $imgsrc;
                } else {
                    $content .= sprintf(_("No location data present. You may add some %s."), $addLink . _("here") . '</a>');
                }
            } else {
                $content .= _("No location data present.");
            }

        } else {
            // Gallery view-------------
            $image_id = 0;
            $content .= '<div id="ansel_map"></div>'
                      . '<div id="ansel_locationtext" style="min-height: 20px;"></div>'
                      . '<div id="ansel_map_small"></div>';
        }
        $content .= '</div>';

        // Build the javascript to handle the map on the gallery/image views.
        $json = Horde_Serialize::serialize(array_values($geodata), Horde_Serialize::JSON);
        $html .= <<<EOT
        <script type="text/javascript">
        Ansel.widgets = Ansel.widgets || {};
        Ansel.widgets.geotag = new AnselGeoTagWidget(
            {$json},
            {
                smallMap: 'ansel_map_small',
                mainMap:  'ansel_map',
                viewType: '{$viewType}',
                relocateUrl: '{$url}',
                relocateText: '{$rtext}',
                markerLayerTitle: '{$thisTitleText}',
                imageLayerTitle: '{$otherTitleText}',
                defaultBaseLayer: '{$defaultLayer}',
                deleteGeotagText: '{$dtext}',
                hasEdit: {$permsEdit},
                updateEndpoint: '{$geotagUrl}',
                layerUpdateEndpoint: '{$layerUrl}',
                layerUpdatePref: 'current_maplayer',
                geocoder: "{$GLOBALS['conf']['maps']['geocoder']}"
            }
        );
EOT;
        if (empty($noGeotag)) {
            $html .= "\n" . 'Event.observe(window, "load", function() { Ansel.widgets.geotag.doMap(); });' . "\n";
        }
        $html .= '</script>' . "\n";
        $html .= $content. $this->_htmlEnd();

        return $html;
    }

    /**
     *
     * @return array
     */
    protected function _getGalleryImagesWithGeodata()
    {
        return $GLOBALS['injector']
            ->getInstance('Ansel_Storage')
            ->getImagesGeodata(array(), $this->_view->gallery->id);
    }

    /**
     * Helper function for converting from decimal points to degrees lat/lng
     *
     * @param float   $value  The value
     * @param boolean $lat    Does this value represent a latitude?
     *
     * @return string  The textual representation in degrees.
     */
    protected function _point2Deg($value, $lat = false)
    {
        $letter = $lat ? ($value > 0 ? "N" : "S") : ($value > 0 ? "E" : "W");
        $value = abs($value);
        $deg = floor($value);
        $min = floor(($value - $deg) * 60);
        $sec = ($value - $deg - $min / 60) * 3600;
        return $deg . "&deg; " . $min . '\' ' . round($sec, 2) . '" ' . $letter;
    }

}
