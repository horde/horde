<?php
/**
 * Display most recently geotagged images.
 *
 * Copyright 2007-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Michael Rubinsky <mrubinsk@horde.org>
 */
class Ansel_Block_RecentlyAddedGeodata extends Horde_Core_Block
{
    /**
     */
    public function __construct($app, $params = array())
    {
        parent::__construct($app, $params);

        $this->_name = _("Recently Geotagged Photos");
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
    protected function _content()
    {
        Horde::initMap();
        $GLOBALS['page_output']->addScriptFile('map.js');
        $GLOBALS['page_output']->addScriptFile('blocks/geotag.js');

        try {
            $images = $GLOBALS['injector']
                ->getInstance('Ansel_Storage')
                ->getRecentImagesGeodata(null, 0, min($this->_params['limit'], 100));
        } catch (Ansel_Exception $e) {
            return $e->getMessage();
        }
        $images = array_reverse($images);
        foreach ($images as $key => $image) {
            $id = $image['image_id'];
            $gallery = $GLOBALS['injector']
                ->getInstance('Ansel_Storage')
                ->getGallery($image['gallery_id']);

            // Don't show locked galleries in the block.
            if (!$gallery->isOldEnough() || $gallery->hasPasswd()) {
                continue;
            }
            $style = $gallery->getStyle();

            // Generate the image view url
            $url = Ansel::getUrlFor(
                'view',
                array('view' => 'Image',
                      'slug' => $gallery->get('slug'),
                      'gallery' => $gallery->id,
                      'image' => $id,
                      'gallery_view' => $style->gallery_view), true);
            $images[$key]['icon'] = (string)Ansel::getImageUrl($images[$key]['image_id'], 'mini', true);
            $images[$key]['link'] = (string)$url;
            $images[$key]['markerOnly'] = false;
        }

        // URL for updating selected layer
        $layerUrl = $GLOBALS['registry']->getServiceLink('ajax', 'ansel')->setRaw(true);
        $layerUrl->url .= 'setPrefValue';

        // And the current defaultLayer, if any.
        $defaultLayer = $GLOBALS['prefs']->getValue('current_maplayer');

        $json = Horde_Serialize::serialize(array_values($images), Horde_Serialize::JSON);
        $html = '<div id="ansel_map" style="height:' . $this->_params['height'] . 'px;"></div>';
        $html .= <<<EOT
        <script type="text/javascript">
            var opts = {
                layerUpdateEndpoint: '{$layerUrl}',
                layerUpdatePref: 'current_maplayer',
                defaultBaseLayer: '{$defaultLayer}'
            }
            document.observe('dom:loaded', function() { new AnselBlockGeoTag({$json}, opts); });
        </script>
EOT;
        return $html;
    }

}
