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
     *
     * @return string  The HTML
     */
    public function _getRelatedImages()
    {
        $ansel_storage = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope();

        $html = '';
        $args = array('typeId' => 'image',
                      'userId' => $this->_view->gallery->get('owner'));

        $results = $GLOBALS['injector']->getInstance('Ansel_Tagger')->listRelatedImages($this->_view->resource);
        if (count($results)) {
            $i = 0;
            foreach ($results as $result) {
                $img = $result['image'];
                $rGal = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getGallery($img->gallery);
                if ($rGal->hasPermission())
                $html .= Ansel::getUrlFor(
                        'view',
                         array('image' => $img->id,
                               'view' => 'Image',
                               'gallery' => $img->gallery,
                               'slug' => $rGal->get('slug')),
                         true)->link(array('title' =>  sprintf(_("%s from %s"), $img->filename, $rGal->get('name'))))
                    . '<img src="'. Ansel::getImageUrl($img->id, 'mini', true) . '" alt="' . htmlspecialchars($img->filename) . '" /></a>';
                $i++;
            }
        }

        return $html;
    }

}
