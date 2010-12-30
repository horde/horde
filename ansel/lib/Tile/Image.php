<?php
/**
 * Ansel_Tile_Image:: class wraps display of thumbnails displayed
 * for a image on the Ansel_View_Gallery view.
 *
 * @author Michael Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  Ansel
 */
class Ansel_Tile_Image
{
    /**
     * Outputs the HTML for an image thumbnail 'tile'.
     *
     * @param Ansel_Image $image     The Ansel_Image we are displaying.
     * @param Ansel_Gallery $parent  The parent Ansel_Gallery for this image.
     *                               If null, will create a new instance of
     *                               the Ansel_Gallery
     * @param Ansel_Style $style     A sytle definiition array.
     * @param boolean $mini          Force the use of a mini thumbnail?
     * @param string $params         Any other paramaters needed by this tile
     *
     * @return  Outputs the HTML for the image tile.
     */
    public function getTile($image, $style = null, $mini = false, $params = array())
    {
        global $conf, $registry;

        $parent = $GLOBALS['injector']->getInstance('Ansel_Storage')->getGallery($image->gallery);
        if (is_null($style)) {
            $style = $parent->getStyle();
        }

        $page = isset($params['page']) ? $params['page'] : 0;
        $view = isset($params['view']) ? $params['view'] : 'Gallery';
        $date = $parent->getDate();

        if ($view == 'Results') {
            $haveSearch = 1;
        } else {
            $haveSearch = 0;
        }

        /* Override the thumbnail to mini or use style default? */
        $thumbstyle = $mini ? 'mini' : 'thumb';

        /* URL for image properties/actions etc... */
        $image_url = Horde::url('image.php')->add(
             array_merge(
               array('gallery' => $image->gallery,
                     'page' => $page,
                     'image' => $image->id,
                     'havesearch' => $haveSearch),
               $date));

        /* URL to view the image. This is the link for the Tile.
         * $view_url is the link for the thumbnail and since this might not
         * always point to the image view page, we set $img_view_url to link to
         * the image view
         */
        $img_view_url = Ansel::getUrlFor('view', array_merge(
            array('gallery' => $image->gallery,
                  'slug' => $parent->get('slug'),
                  'page' => $page,
                  'view' => 'Image',
                  'image'=> $image->id,
                  'havesearch' => $haveSearch),
            $date));

        if (!empty($params['image_view_src'])) {
            $view_url = Ansel::getImageUrl($image->id, 'screen', true);
        } elseif (empty($params['image_view_url'])) {
            $view_url = new Horde_Url($img_view_url);
        } else {
            $view_url = new Horde_Url(
                str_replace(array('%i', '%g', '%s'),
                            array($image->id, $image->gallery, $parent->get('slug')),
                            urldecode($params['image_view_url'])));

            // If we override the view_url, assume we want to override this also
            $img_view_url = $view_url;
        }

        // Need the gallery URL to display the "From" link when showing
        // the image tile from somewhere other than the gallery view.
        if (!empty($view) || basename($_SERVER['PHP_SELF']) == 'view.php') {
            $gallery_url = Ansel::getUrlFor('view', array_merge(
                array('gallery' => $parent->id,
                      'slug' => $parent->get('slug'),
                      'view' => 'Gallery',
                      'havesearch' => $haveSearch),
                $date));
        }

        $thumb_url = Ansel::getImageUrl($image->id, $thumbstyle, true, $style);

        $option_select = $parent->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::DELETE);
        $option_edit = $parent->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT);
        $imgAttributes = (!empty($params['image_view_attributes']) ? $params['image_view_attributes'] : array());

        $imgOnClick = (!empty($params['image_onclick'])
                ? str_replace('%i', $image->id, $params['image_onclick'])
                : '');

        $imageCaption = $GLOBALS['injector']->getInstance('Horde_Core_Factory_TextFilter')->filter(
            $image->caption, 'text2html',
            array('parselevel' => Horde_Text_Filter_Text2html::MICRO));

        if (!empty($params['image_view_title']) &&
            !empty($image->_data[$params['image_view_title']])) {
            $title = $image->_data[$params['image_view_title']];
        } else {
            $title = $image->filename;
        }

        Horde::startBuffer();
        // In-line caption editing if we have Horde_Perms::EDIT
        if ($option_edit) {
            // @TODO: passing thumbstyle here doesn't look right to me.
            $geometry = $image->getDimensions($thumbstyle);
            $GLOBALS['injector']->createInstance('Horde_Core_Factory_Imple')->create(
                array('ansel', 'EditCaption'),
                array('domid' => $image->id . 'caption',
                      'id' => $image->id,
                      'width' => $geometry['width']));
        }
        include ANSEL_BASE . '/templates/tile/image.inc';

        return Horde::endBuffer();
    }

}
