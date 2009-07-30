<?php
/**
 * Ansel_Ajax_Imple_ImageSaveGeotag:: class for saving/updating image geotag
 * data.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_Ajax_Imple_ImageSaveGeotag extends Horde_Ajax_Imple_Base
{
    // Noop since this isn't attached to any UI Element
    public function attach() {}

    public function getUrl()
    {
        return $this->_getUrl('ImageSaveGeotag', 'ansel');
    }

    public function handle($args)
    {
        include_once dirname(__FILE__) . '/../../base.php';

        /* Require type, location, img to be from POST */
        $type = Horde_Util::getPost('type');
        $location = Horde_Util::getPost('location');
        $img = Horde_Util::getPost('img');
        $lat = Horde_Util::getPost('lat');
        $lng = Horde_Util::getPost('lng');

        if (empty($img) ||
            ($type == 'location' && empty($location)) ||
            ((empty($type) || $type == 'all') &&
             ($type == 'all' && empty($lat)))) {

            return array('response' => 0);
        }

        // Get the image and gallery to check perms
        $image = $GLOBALS['ansel_storage']->getImage((int)$img);
        if (is_a($image, 'PEAR_Error')) {
            return array('response' => 0);
        }
        $gallery = $GLOBALS['ansel_storage']->getGallery($image->gallery);
        if (is_a($gallery, 'PEAR_Error')) {
            return array('response' => 0);
        }
        // Bail out if no perms on the image.
        if (!$gallery->hasPermission(Horde_Auth::getAuth(), PERMS_EDIT)) {
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
            $addurl = Horde_Util::addParameter(Horde::applicationUrl('map_edit.php'), 'image', $img);
            $addLink = Horde::link($addurl, '', '', '', 'popup(\'' . Horde_Util::addParameter(Horde::applicationUrl('map_edit.php'), 'image', $img) . '\'); return false;');
            $imgs = $GLOBALS['ansel_storage']->getRecentImagesGeodata(Horde_Auth::getAuth());
            if (count($imgs) > 0) {
                $imgsrc = '<div class="ansel_location_sameas">';
                foreach ($imgs as $id => $data) {
                    if (!empty($data['image_location'])) {
                        $title = $data['image_location'];
                    } else {
                        $title = $this->_point2Deg($data['image_latitude'], true) . ' ' . $this->_point2Deg($data['image_longitude']);
                    }
                    $imgsrc .= Horde::link($addurl, $title, '', '', "setLocation('" . $data['image_latitude'] . "', '" . $data['image_longitude'] . "');return false") . '<img src="' . Ansel::getImageUrl($id, 'mini', true) . '" alt="[image]" /></a>';
                }
                $imgsrc .= '</div>';
                $content = sprintf(_("No location data present. Place using %s map %s or click on image to place at the same location."), $addLink, '</a>') . $imgsrc;
            } else {
                $content = _("No location data present. You may add some ") . $addLink . _("here") . '</a>';
            }

            return array('response' => 1, 'message' => $content);
        }
    }

}
