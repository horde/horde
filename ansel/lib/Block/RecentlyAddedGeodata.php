<?php
/**
 * Display most recently geotagged images.
 *
 * Copyright 2007-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Rubinsky <mrubinsk@horde.org>
 */
class Ansel_Block_RecentlyAddedGeodata extends Horde_Block
{
   /**
    */
    public function getName()
    {
        return _("Recently Geotagged Photos");
    }

    /**
     */
    protected function _params()
    {
        return array(
            'limit' => array(
                'name' => _("Maximum number of photos"),
                'type' => 'int',
                'default' => 10
            ),
            'height' => array(
                'name' => _("Height of map (width automatically adjusts to block)"),
                'type' => 'int',
                'default' => 250
            ),
        );
    }

    /**
     */
    protected function _title()
    {
        return $this->getName();
    }

    /**
     */
    protected function _content()
    {
        try {
            $images = $GLOBALS['injector']->getInstance('Ansel_Storage')->getRecentImagesGeodata(null, 0, min($this->_params['limit'], 100));
        } catch (Ansel_Exception $e) {
            return $e->getMessage();
        }
        $images = array_reverse($images);
        foreach ($images as $key => $image) {
            $id = $image['image_id'];
            $gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getGallery($image['gallery_id']);

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
                      'gallery_view' => $style->gallery_view), true);
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

        Horde::addScriptFile('http://maps.google.com/maps?file=api&v=2&sensor=false&key=' . $GLOBALS['conf']['api']['googlemaps'], 'ansel', array('external' => true));
        Horde::addScriptFile('http://gmaps-utility-library.googlecode.com/svn/trunk/markermanager/1.1/src/markermanager.js', 'ansel', array('external' => true));
        Horde::addScriptFile('googlemap.js');
        return $html;
    }

}
