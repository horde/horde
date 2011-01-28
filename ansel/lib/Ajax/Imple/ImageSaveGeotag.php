<?php
/**
 * Ansel_Ajax_Imple_ImageSaveGeotag:: class for saving/updating image geotag
 * data.
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_Ajax_Imple_ImageSaveGeotag extends Horde_Core_Ajax_Imple
{
    // Noop since this isn't attached to any UI Element
    public function attach() {}

    public function getUrl()
    {
        return $this->_getUrl('ImageSaveGeotag', 'ansel');
    }

    public function handle($args, $post)
    {
        $type = $args['action'];
        $location = empty($post['location']) ? null : $post['location'];
        $lat = empty($post['lat']) ? null : $post['lat'];
        $lng = empty($post['lng']) ? null : $post['lng'];
        $img = $post['img'];

        if (empty($img) ||
            ($type == 'location' && empty($location)) ||
            ((empty($type) || $type == 'all') &&
             ($type == 'all' && empty($lat)))) {

            return array('response' => 0);
        }

        // Get the image and gallery to check perms
        try {
            $image = $GLOBALS['injector']->getInstance('Ansel_Storage')->getImage((int)$img);
            $gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getGallery($image->gallery);
        } catch (Ansel_Exception $e) {
            return array('response' => 0);
        }

        // Bail out if no perms on the image.
        if (!$gallery->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
            return array('response' => 0);
        }
        switch ($type) {
        case 'geotag':
            $image->geotag($lat, $lng, !empty($location) ? $location : '');
            return array('response' => 1);

        case 'location':
            $image->location = !empty($location) ? urldecode($location) : '';
            $image->save();
            return array('response' => 1, 'message' => htmlentities($image->location));

        case 'untag':
            $image->geotag('', '', '');
            // Now get the "add geotag" stuff
            $addurl = Horde::url('map_edit.php')->add('image', $img);
            $addLink = $addurl->link(array('onclick' => Horde::popupJs(Horde::url('map_edit.php'), array('params' => array('image' => $img), 'urlencode' => true, 'width' => '750', 'height' => '600')) . 'return false;'));
            $imgs = $GLOBALS['injector']->getInstance('Ansel_Storage')->getRecentImagesGeodata($GLOBALS['registry']->getAuth());
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
                $content = sprintf(_("No location data present. Place using %smap%s or click on image to place at the same location."), $addLink, '</a>') . $imgsrc;
            } else {
                $content = sprintf(_("No location data present. You may add some %s."), $addLink . _("here") . '</a>');
            }

            return array('response' => 1, 'message' => $content);
        }
    }

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
