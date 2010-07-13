<?php

$block_name = _("Recently Geotagged");

/**
 * Display most recently geotagged images.
 *
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Rubinsky <mrubinsk@horde.org>
 * @package Horde_Block
 */
class Horde_Block_ansel_recently_added_geodata extends Horde_Block {

    var $_app = 'ansel';
    var $_gallery = null;

    function _params()
    {
        $params = array('gallery' => array(
                            'name' => _("Gallery"),
                            'type' => 'enum',
                            'default' => '__random',
                            'values' => array('all' => 'All')),
                        'limit' => array(
                             'name' => _("Maximum number of photos"),
                             'type' => 'int',
                             'default' => 10),
                        'height' => array(
                             'name' => _("Height of map (width automatically adjusts to block)"),
                             'type' => 'int',
                             'default' => 250),
        );

        if ($GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->countGalleries($GLOBALS['registry']->getAuth(), Horde_Perms::READ) < $GLOBALS['conf']['gallery']['listlimit']) {
            foreach ($GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->listGalleries(array('perm' => Horde_Perms::READ)) as $id => $gal) {
                if (!$gal->hasPasswd() && $gal->isOldEnough()) {
                    $params['gallery']['values'][$id] = $gal->get('name');
                }
            }
        }

        return $params;
    }

    function _title()
    {
        Horde::addScriptFile('http://maps.google.com/maps?file=api&v=2&sensor=false&key=' . $GLOBALS['conf']['api']['googlemaps'], 'ansel', array('external' => true));
        Horde::addScriptFile('http://gmaps-utility-library.googlecode.com/svn/trunk/markermanager/1.1/src/markermanager.js', 'ansel', array('external' => true));
        Horde::addScriptFile('googlemap.js');
        if ($this->_params['gallery'] != 'all') {
            try {
                $gallery = $this->_getGallery();
            } catch (Exception $e) {
                return Ansel::getUrlFor('view', array('view' => 'List'), true)->link() . _("Gallery") . '</a>';
            }

            // Build the gallery name.
            if (isset($this->_params['gallery'])) {
                $name = @htmlspecialchars($gallery->get('name'), ENT_COMPAT, $GLOBALS['registry']->getCharset());
            }

            $style = $gallery->getStyle();
            $viewurl = Ansel::getUrlFor('view',
                                        array('slug' => $gallery->get('slug'),
                                              'gallery' => $gallery->id,
                                              'view' => 'Gallery'),
                                        true);
        } else {
            $viewurl = Ansel::getUrlFor('view', array('view' => 'List'), true);
            $name = _("All Galleries");
        }
        return sprintf(_("Recently Geotagged Photos From %s"), $viewurl->link() . $name . '</a>');
    }

    function _content()
    {
        if ($this->_params['gallery'] == 'all') {
            $galleries = array();
        } elseif (!is_array($this->_params['gallery'])) {
            $galleries = array($this->_params['gallery']);
        } else {
            $galleries = $this->_params['gallery'];
        }

        try {
            $images = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getRecentImagesGeodata(null, 0, min($this->_params['limit'], 100));
        } catch (Ansel_Exception $e) {
            return $e->getMessage();
        }
        $images = array_reverse($images);
        foreach ($images as $key => $image) {
            $id = $image['image_id'];
            $gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getGallery($image['gallery_id']);

            /* Don't show locked galleries in the block. */
            if (!$gallery->isOldEnough() || $gallery->hasPasswd()) {
                continue;
            }
            $style = $gallery->getStyle();

            /* Generate the image view url */
            $url = Ansel::getUrlFor(
                'view',
                array('view' => 'Image',
                      'slug' => $gallery->get('slug'),
                      'gallery' => $gallery->id,
                      'image' => $id,
                      'gallery_view' => $style['gallery_view']), true);
            $images[$key]['icon'] = (string)Ansel::getImageUrl($images[$key]['image_id'], 'mini', true);
            $images[$key]['link'] = (string)$url;
        }

        $json = Horde_Serialize::serialize(array_values($images), Horde_Serialize::JSON);
        $html = '<div id="ansel_map" style="height:' . $this->_params['height'] . 'px;"></div>';
        $html .= <<<EOT
        <script type="text/javascript">
        var map = {};
        var pageImages = {$json};
        options = {
            mainMap:  'ansel_map',
            viewType: 'Block',
            calculateMaxZoom: false
        };
        function doMap(points) {
            map = new Ansel_GMap(options);
            map.addPoints(points);
            map.display();
        }

        Event.observe(window, "load", function() {doMap(pageImages);});
        </script>
EOT;
        return $html;
    }

    function _getGallery()
    {
        if ($this->_gallery instanceof Ansel_Gallery) {
            return $this->_gallery;
        }

        if (isset($this->_params['gallery']) &&
            $this->_params['gallery'] != '__random') {
            $this->_gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getGallery(
                $this->_params['gallery']);
        } else {
            $this->_gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getRandomGallery();
        }

        if (empty($this->_gallery)) {
           throw new Horde_Exception_NotFound(_("Gallery not found."));
        } elseif (!$this->_gallery->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::READ)) {
            throw new Horde_Exception_PermissionDenied(_("Access denied viewing this gallery."));
        }

        return $this->_gallery;
    }

}
