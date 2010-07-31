<?php
/**
 * Horde_Widget_SimilarPhotos:: class to display a widget containing mini
 * thumbnails of images that are similar, based on tags.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_Widget_SimilarPhotos extends Ansel_Widget_Base
{
    /**
     * Array of views that this widget may appear in.
     *
     * @var unknown_type
     */
    protected $_supported_views = array('Image');

    /**
     * Constructor
     *
     * @param array $params  Any parameters for this widget
     * @return Ansel_Widget_SimilarPhotos
     */
    public function __construct($params)
    {
        parent::__construct($params);
        $this->_title = _("Similar Photos");
    }

    /**
     * Return the HTML representing this widget.
     *
     * @return string  The HTML for this widget.
     */
    public function html()
    {
        $html = $this->_htmlBegin();
        $html .= '<div id="similar">' . $this->_getRelatedImages() . '</div>';
        $html .= $this->_htmlEnd();

        return $html;
    }

    /**
     * Helper function for generating a widget of images related to this one.
     *
     * @TODO Rethink the way we determine if an image is related. This one is
     *       not ideal, as it just pops tags off the tag list until all the tags
     *       match. This could miss many related images. Maybe make this random?
     *
     * @return string  The HTML
     */
    public function _getRelatedImages()
    {
        $ansel_storage = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope();

        $html = '';
        $tags = array_values($this->_view->resource->getTags());
        $imgs = $GLOBALS['injector']->getInstance('Ansel_Tagger')->search($tags);
        while (count($imgs['images']) <= 5 && count($tags)) {
            array_pop($tags);
            $newImgs =$GLOBALS['injector']->getInstance('Ansel_Tagger')->search($tags);
            $imgs['images'] = array_merge($imgs['images'], $newImgs['images']);
        }
        if (count($imgs['images'])) {
            $i = 0;
            foreach ($imgs['images'] as $imgId) {
                if ($i >= min(count($imgs['images']), 5)) {
                    break;
                }
                if ($imgId != $this->_view->resource->id) {
                    try {
                        $rImg = $ansel_storage->getImage($imgId);
                        $rGal = $ansel_storage->getGallery($rImg->gallery);
                    } catch (Ansel_Exception $e) {
                        continue;
                    }

                    $html .= Ansel::getUrlFor(
                            'view',
                             array('image' => $imgId,
                                   'view' => 'Image',
                                   'gallery' => $rImg->gallery,
                                   'slug' => $rGal->get('slug')),
                             true)->link(array('title' =>  sprintf(_("%s from %s"), $rImg->filename, $rGal->get('name'))))
                        . '<img src="'. Ansel::getImageUrl($imgId, 'mini', true) . '" alt="' . htmlspecialchars($rImg->filename) . '" /></a>';
                    $i++;
                }
            }
        }

        return $html;
    }
}
