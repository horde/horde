<?php
/**
 * Defines the AJAX actions used in Ansel.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Ansel
 */
class Ansel_Ajax_Application_Helper extends Horde_Core_Ajax_Application_Helper
{
    /**
     * Obtain a gallery
     *
     * @return mixed  False on failure, object representing the gallery with
     *                the following structure:
     * @see Ansel_Gallery::toJson()
     */
    public function getGallery()
    {
        $id = $this->vars->id;
        try {
            return $GLOBALS['injector']
                ->getInstance('Ansel_Storage')
                ->getGallery($id)
                ->toJson(true);
        } catch (Exception $e) {
            Horde::logMessage($e, 'ERR');
            return false;
        }
    }

    /**
     */
    public function uploadNotification()
    {
        global $conf, $injector, $prefs, $registry;

        $gallery = $injector->getInstance('Ansel_Storage')->getGallery($this->vars->g);

        switch ($this->vars->s) {
        case 'twitter':
            $url = Ansel::getUrlFor(
                'view',
                array('view' => 'Gallery', 'gallery' => $gallery->id),
                true);

            if (!empty($conf['urlshortener'])) {
                try {
                    $url = $injector
                        ->getInstance('Horde_Service_UrlShortener')
                        ->shorten($url->setRaw(true));
                } catch (Horde_Service_UrlShortener_Exception $e) {
                    Horde::logMessage($e, 'ERR');
                    header('HTTP/1.1 500');
                }
            }
            $text = sprintf(_("New images uploaded to %s. %s"), $gallery->get('name'), $url);

            $token = unserialize($prefs->getValue('twitter'));
            if (empty($token['key']) && empty($token['secret'])) {
                $pref_link = $registry->getServiceLink('prefs', 'horde')->add('group', 'twitter')->link();
                throw new Horde_Exception(sprintf(_("You have not properly connected your Twitter account with Horde. You should check your Twitter settings in your %s."), $pref_link . _("preferences") . '</a>'));
            }

            $twitter = $injector->getInstance('Horde_Service_Twitter');
            $auth_token = new Horde_Oauth_Token($token['key'], $token['secret']);
            $twitter->auth->setToken($auth_token);

            try {
                return $twitter->statuses->update($text);
            } catch (Horde_Service_Twitter_Exception $e) {
                Horde::logMessage($e, 'ERR');
                header('HTTP/1.1 500');
            }
        }
    }

    /**
     * Variables used:
     *   - slug
     *
     * @return boolean  True if slug is valid.
     */
    public function checkSlug()
    {
        $slug = $this->vars->slug;

        if (!strlen($slug)) {
            return true;
        }

        return preg_match('/^[a-zA-Z0-9_-]*$/', $slug)
            ? (bool)$GLOBALS['injector']->getInstance('Ansel_Storage')->galleryExists(null, $slug)
            : false;
    }

    /**
     * Save/update image geotag.
     *
     * @return object  Object with 2 parameters:
     *   - message
     *   - response
     */
    public function imageSaveGeotag()
    {
        global $injector, $registry;

        $type = $this->vars->action;
        $location = $this->vars->location;
        $lat = $this->vars->lat;
        $lng = $this->vars->lng;
        $img = $this->vars->img;

        $result = new stdClass;
        $result->response = 0;

        if (empty($img) ||
            ($type == 'location' && empty($location)) ||
            ((empty($type) || $type == 'all') &&
             ($type == 'all' && empty($lat)))) {
            return new Horde_Core_Ajax_Response_Prototypejs($result);
        }

        // Get the image and gallery to check perms
        try {
            $ansel_storage = $injector->getInstance('Ansel_Storage');
            $image = $ansel_storage->getImage((int)$img);
            $gallery = $ansel_storage->getGallery($image->gallery);
        } catch (Ansel_Exception $e) {
            return new Horde_Core_Ajax_Response_Prototypejs($result);
        }

        // Bail out if no perms on the image.
        if (!$gallery->hasPermission($registry->getAuth(), Horde_Perms::EDIT)) {
            return new Horde_Core_Ajax_Response_Prototypejs($result);
        }

        switch ($type) {
        case 'geotag':
            $image->geotag($lat, $lng, !empty($location) ? $location : '');
            $result->response = 1;
            break;

        case 'location':
            $image->location = !empty($location) ? urldecode($location) : '';
            $image->save();
            $result->response = 1;
            $result->message = htmlentities($image->location);
            break;

        case 'untag':
            $image->geotag('', '', '');
            // Now get the "add geotag" stuff
            $addurl = Horde::url('map_edit.php')->add('image', $img);
            $addLink = $addurl->link(array(
                'onclick' => Horde::popupJs(Horde::url('map_edit.php'), array('params' => array('image' => $img), 'urlencode' => true, 'width' => '750', 'height' => '600')) . 'return false;'
            ));
            $imgs = $ansel_storage->getRecentImagesGeodata($registry->getAuth());
            if (count($imgs) > 0) {
                $imgsrc = '<div class="ansel_location_sameas">';
                foreach ($imgs as $id => $data) {
                    $title = empty($data['image_location'])
                        ? $this->_point2Deg($data['image_latitude'], true) . ' ' . $this->_point2Deg($data['image_longitude'])
                        : $data['image_location'];
                    $imgsrc .= $addurl->link(array(
                        'title' => $title,
                        'onclick' => "Ansel.widgets.geotag.setLocation('" . $data['image_latitude'] . "', '" . $data['image_longitude'] . "');return false"
                    )) . '<img src="' . Ansel::getImageUrl($id, 'mini', true) . '" alt="[image]" /></a>';
                }

                $imgsrc .= '</div>';
                $result->message = sprintf(_("No location data present. Place using %smap%s or click on image to place at the same location."), $addLink, '</a>') . $imgsrc;
            } else {
                $result->message = sprintf(_("No location data present. You may add some %s."), $addLink . _("here") . '</a>');
            }

            $result->response = 1;
            break;
        }

        return new Horde_Core_Ajax_Response_Prototypejs($result);
    }

    /**
     */
    protected function _point2Deg($value, $lat = false)
    {
        $letter = $lat
            ? ($value > 0 ? "N" : "S")
            : ($value > 0 ? "E" : "W");
        $value = abs($value);
        $deg = floor($value);
        $min = floor(($value - $deg) * 60);
        $sec = ($value - $deg - $min / 60) * 3600;

        return $deg . "&deg; " . $min . '\' ' . round($sec, 2) . '" ' . $letter;
    }

    /**
     * Delete a face from an image.
     */
    public function deleteFaces()
    {
        global $injector, $registry;

        $face_id = intval($this->vars->face_id);
        $image_id = intval($this->vars->image_id);
        $storage = $injector->getInstance('Ansel_Storage');

        $image = $storage->getImage($image_id);
        $gallery = $storage->getGallery($image->gallery);
        if (!$gallery->hasPermission($registry->getAuth(), Horde_Perms::EDIT)) {
            throw new Ansel_Exception('Access denied editing the photo.');
        }

        Ansel_Faces::delete($image, $face_id);

        return true;
    }

    /**
     * Sets a name in an image.
     */
    public function setFaceName()
    {
        global $injector, $registry;

        $face_id = intval($this->vars->face_id);
        $image_id = intval($this->vars->image_id);
        $name = $this->vars->face_name;
        $storage = $injector->getInstance('Ansel_Storage');

        $image = $storage->getImage($image_id);
        $gallery = $storage->getGallery($image->gallery);

        if (!$gallery->hasPermission($registry->getAuth(), Horde_Perms::EDIT)) {
            throw new Ansel_Exception('You are not allowed to edit this photo');
        }

        Ansel_Faces::setName($face_id, $name);

        return Horde_Core_Ajax_Response_Raw(Ansel_Faces::getFaceTile($face_id), 'text/html');
    }

    /**
     */
    public function addTag()
    {
        global $injector, $registry;

        $gallery = $this->vars->gallery;
        $tags = $this->vars->tags;
        $image = $this->vars->image;
        if ($image) {
            $id = $image;
            $type = 'image';
        } else {
            $id = $gallery;
            $type = 'gallery';
        }

        if (!is_numeric($id)) {
            throw new Ansel_Exception(_("Invalid input %s"), $id);
        }

        /* Get the resource owner */
        $storage = $injector->getInstance('Ansel_Storage');
        if ($type == 'gallery') {
            $resource = $storage->getGallery($id);
            $parent = $resource;
        } else {
            $resource = $storage->getImage($id);
            $parent = $storage->getGallery($resource->gallery);
        }

        $tags = explode(',', rawurldecode($tags));
        $tagger = $injector->getInstance('Ansel_Tagger');

        $tagger->tag($id, $tags, $registry->getAuth(), $type);

        /* Get the tags again since we need the newly added tag_ids */
        $newTags = $tagger->getTags($id, $type);
        if (count($newTags)) {
            $newTags = $tagger->getTagInfo(array_keys($newTags));
        }

        return new Horde_Core_Ajax_Response_Raw($this->_getTagHtml($newTags, $parent->hasPermission($registry->getAuth(), Horde_Perms::EDIT)), 'text/html');
    }

    /**
     */
    public function removeTag()
    {
        global $injector, $registry;

        $gallery = $this->vars->gallery;
        $tags = $this->vars->tags;
        $image = $this->vars->image;
        if ($image) {
            $id = $image;
            $type = 'image';
        } else {
            $id = $gallery;
            $type = 'gallery';
        }

        if (!is_numeric($id)) {
            throw new Ansel_Exception(_("Invalid input %s"), $id);
        }

        /* Get the resource owner */
        $storage = $injector->getInstance('Ansel_Storage');
        if ($type == 'gallery') {
            $resource = $storage->getGallery($id);
            $parent = $resource;
        } else {
            $resource = $storage->getImage($id);
            $parent = $storage->getGallery($resource->gallery);
        }

        $tagger = $injector->getInstance('Ansel_Tagger');

        $tagger->untag($resource->id, (int)$tags, $type);

        $existingTags = $tagger->getTags($resource->id, $type);
        if (count($existingTags)) {
            $newTags = $tagger->getTagInfo(array_keys($existingTags));
        } else {
            $newTags = array();
        }

        return new Horde_Core_Ajax_Response_Raw($this->_getTagHtml($newTags, $parent->hasPermission($registry->getAuth(), Horde_Perms::EDIT)), 'text/html');
    }

    /**
     */
    private function _getTagHtml($tags, $hasEdit)
    {
        $links = Ansel::getTagLinks($tags, 'add');
        $html = '<ul>';

        foreach ($tags as $taginfo) {
            $tag_id = $taginfo['tag_id'];
            $html .= '<li>' . $links[$tag_id]->link(array('title' => sprintf(ngettext("%d photo", "%d photos", $taginfo['count']), $taginfo['count']))) . htmlspecialchars($taginfo['tag_name']) . '</a>' . ($hasEdit ? '<a href="#" onclick="removeTag(' . $tag_id . ');">' . Horde::img('delete-small.png', _("Remove Tag")) . '</a>' : '') . '</li>';
        }

        return $html . '</ul>';
    }

    /**
     * Javascript code needed for embedding a small gallery widget in
     * external websites.
     *
     * @throws Ansel_Exception
     */
    public function embed()
    {
        /* First, determine the type of view we are asking for */
        $class = 'Ansel_View_EmbeddedRenderer_' . basename($this->vars->get('gallery_view', 'Mini'));

        if (!class_exists($class)) {
            throw new Ansel_Exception(sprintf("Class definition for %s not found.", $class));
        }

        try {
            $view = new $class($this->vars);
            return new Horde_Core_Ajax_Response_Javascript($view->html());
        } catch (Exception $e) {}
    }

}
