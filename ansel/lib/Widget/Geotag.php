<?php
/**
 * Ansel_Widget_Geotag:: class to wrap the display of a Google map showing
 * images with geolocation data.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
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
    protected $_supported_views = array('Image', 'Gallery');

    /**
     * Default params
     *
     * @var array
     */
    protected $_params = array('default_zoom' => 15,
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
        // Don't even try if we don't have an api key
        if (empty($GLOBALS['conf']['api']['googlemaps'])) {
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
        $ansel_storage = $GLOBALS['injector']->getInstance('Ansel_Injector_Factory_Storage')->create();
        $geodata = $ansel_storage->getImagesGeodata($this->_params['images']);
        $url = Horde::url('map_edit.php', true);
        $rtext = _("Relocate this image");
        $dtext = _("Delete geotag");

        $imple = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Imple')->create(array('ansel', 'ImageSaveGeotag'));
        $impleUrl = $imple->getUrl();

        $permsEdit = $this->_view->gallery->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT);
        $viewType = $this->_view->viewType();

        if (count($geodata) == 0 && $viewType != 'Image') {
            return '';
        } elseif (count($geodata) == 0) {
            $noGeotag = true;
        }

        // Bring in googlemap.js now that we know we need it.
        Horde::addScriptFile('http://maps.google.com/maps?file=api&v=2&sensor=false&key=' . $GLOBALS['conf']['api']['googlemaps'], 'ansel', array('external' => true));
        Horde::addScriptFile('http://gmaps-utility-library.googlecode.com/svn/trunk/markermanager/1.1/src/markermanager.js', 'ansel', array('external' => true));
        Horde::addScriptFile('googlemap.js');
        Horde::addScriptFile('popup.js', 'horde');

        $html = $this->_htmlBegin() . "\n";
        $content = '<div id="ansel_geo_widget">';

        // Add extra information to the JSON data to be sent:
        foreach ($geodata as $id => $data) {
            $geodata[$id]['icon'] = (string)Ansel::getImageUrl($geodata[$id]['image_id'], 'mini', true);
            $geodata[$id]['markerOnly'] = ($viewType == 'Image');
            $geodata[$id]['link'] = (string)Ansel::getUrlFor(
                    'view',
                     array('view' => 'Image',
                           'gallery' => $this->_view->gallery->id,
                           'image' => $geodata[$id]['image_id']), true);
        }

        // If this is an image view, get the other gallery images
        if ($viewType == 'Image') {
            $image_id = $this->_view->resource->id;
            $others = $this->_getGalleryImagesWithGeodata();
            foreach ($others as $id => $data) {
                if ($id != $image_id) {
                    $others[$id]['icon'] = (string)Ansel::getImageUrl($others[$id]['image_id'], 'mini', true);
                    $others[$id]['link'] = (string)Ansel::getUrlFor(
                            'view',
                            array('view' => 'Image',
                                  'gallery' => $this->_view->gallery->id,
                                  'image' => $others[$id]['image_id']), true);
                } else {
                    unset($others[$id]);
                }
            }
            $geodata = array_values(array_merge($geodata, $others));

            if (empty($noGeotag)) {
                $content .= '<div id="ansel_map"></div>';
                $content .= '<div class="ansel_geolocation">';
                $content .= '<div id="ansel_locationtext"></div>';
                $content .= '<div id="ansel_latlng"></div>';
                $content .= '<div id="ansel_relocate"></div><div id="ansel_deleteGeotag"></div></div>';
                $content .= '<div id="ansel_map_small"></div>';

            } elseif ($permsEdit) {
                // Image view, but no geotags, provide ability to add it.
                $addurl = Horde::url('map_edit.php')->add('image', $this->_params['images'][0]);
                $addLink = $addurl->link(array('onclick' => Horde::popupJs(Horde::url('map_edit.php'), array('params' => array('image' => $this->_params['images'][0]), 'urlencode' => true, 'width' => '750', 'height' => '600')) . 'return false;'));
                $imgs = $ansel_storage->getRecentImagesGeodata($GLOBALS['registry']->getAuth());
                    if (count($imgs) > 0) {
                        $imgsrc = '<div class="ansel_location_sameas">';
                        foreach ($imgs as $id => $data) {
                            if (!empty($data['image_location'])) {
                                $title = $data['image_location'];
                            } else {
                                $title = $this->_point2Deg($data['image_latitude'], true) . ' ' . $this->_point2Deg($data['image_longitude']);
                            }
                            $imgsrc .= $addurl->link(
                                        array('title' => $title,
                                              'onclick' => "Ansel.widgets.geotag.setLocation('" . $data['image_latitude'] . "', '" . $data['image_longitude'] . "');return false"))
                                    . '<img src="' . Ansel::getImageUrl($id, 'mini', true) . '" alt="[image]" /></a>';
                        }
                        $imgsrc .= '</div>';
                        $content .= sprintf(_("No location data present. Place using %s map %s or click on image to place at the same location."), $addLink, '</a>') . $imgsrc;
                    } else {
                        $content .= _("No location data present. You may add some ") . $addLink . _("here") . '</a>';
                    }
            } else {
                // For now, just put up a notice. In future, maybe provide a link
                // to suggest a location using the Report API?
                $content .= _("No location data present.");
            }

        } else {
            // Gallery view-------------
            // Avoids undefined error when we build the js function below.
            $image_id = 0;
            $content .= '<div id="ansel_map"></div><div id="ansel_locationtext" style="min-height: 20px;"></div><div id="ansel_map_small"></div>';
        }

        $content .= '</div>';
        $json = Horde_Serialize::serialize(array_values($geodata), Horde_Serialize::JSON);
        $html .= <<<EOT
        <script type="text/javascript">
        Ansel.widgets = Ansel.widgets || {};
        Ansel.widgets.geotag = {
            map: {},
            images: {$json},
            options: {
                smallMap: 'ansel_map_small',
                mainMap:  'ansel_map',
                viewType: '{$viewType}',
                relocateUrl: '{$url}',
                relocateText: '{$rtext}',
                deleteGeotagText: '{$dtext}',
                hasEdit: {$permsEdit},
                calculateMaxZoom: true,
                updateEndpoint: '{$impleUrl}',
                deleteGeotagCallback: function() { Ansel.widgets.geotag.deleteLocation(); }.bind(this)
            },

            setLocation: function(lat, lng)  {
                var params = { "values": "img={$image_id}/lat=" + lat + "/lng=" + lng };

                var url = "{$impleUrl}";
                new Ajax.Request(url + "/action=geotag/post=values", {
                    method: 'post',
                    parameters: params,
                    onComplete: function(transport) {
                         if (typeof Horde_ToolTips != 'undefined') {
                             Horde_ToolTips.out();
                         }
                         if (transport.responseJSON.response == 1) {
                            var w = new Element('div');
                            w.appendChild(new Element('div', {id: 'ansel_map'}));
                            var ag = new Element('div', {'class': 'ansel_geolocation'});
                            ag.appendChild(new Element('div', {id: 'ansel_locationtext'}));
                            ag.appendChild(new Element('div', {id: 'ansel_latlng'}));
                            ag.appendChild(new Element('div', {id: 'ansel_relocate'}));
                            ag.appendChild(new Element('div', {id: 'ansel_deleteGeotag'}));
                            w.appendChild(ag);
                            w.appendChild(new Element('div', {id: 'ansel_map_small'}));
                            $('ansel_geo_widget').update(w);
                            this.images.unshift({image_id: {$image_id}, image_latitude: lat, image_longitude: lng, image_location:'', markerOnly:true});
                            this.doMap();
                         }
                     }.bind(this)
                });
            },

            deleteLocation: function() {
                var params = {"values": "img={$image_id}" };
                var url = "{$impleUrl}";
                new Ajax.Request(url + "/action=untag/post=values", {
                    method: 'post',
                    parameters: params,
                    onComplete: function(transport) {
                        if (transport.responseJSON.response == 1) {
                            $('ansel_geo_widget').update(transport.responseJSON.message);
                        }
                    }
                });

            },

            doMap: function() {
                this.map = new Ansel_GMap(this.options);
                this.map.getLocationCallback_ = this.map.getLocationCallback;
                this.map.getLocationCallback = function(points, marker) {
                    this.map.getLocationCallback_(points, marker, (typeof points.NoUpdate == 'undefined'));
                }.bind(this);
                this.map.addPoints(this.images);
                this.map.display();
            }
        };
EOT;
        if (empty($noGeotag)) {
            $html .= "\n" . 'Event.observe(window, "load", function() {Ansel.widgets.geotag.doMap();});' . "\n";
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
        return $GLOBALS['injector']->getInstance('Ansel_Injector_Factory_Storage')->create()->getImagesGeodata(array(), $this->_view->gallery->id);
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
