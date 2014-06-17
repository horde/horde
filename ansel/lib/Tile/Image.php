<?php
/**
 *
 * @author Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Ansel
 */
/**
 * Ansel_Tile_Image:: class wraps display of thumbnails displayed
 * for a image on the Ansel_View_Gallery view.
 *
 * @author Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Ansel
 */
class Ansel_Tile_Image
{
    /**
     * Outputs the HTML for an image thumbnail tile.
     *
     * @param Ansel_Image $image  The Ansel_Image we are displaying.
     * @param Ansel_Style $style  A sytle definiition array.
     * @param boolean $mini       Force the use of a mini thumbnail?
     * @param array $params       Any other paramaters needed by this tile
     *
     * @return  Outputs the HTML for the image tile.
     */
    public static function getTile(
        Ansel_Image $image, Ansel_Style $style = null, $mini = false, array $params = array())
    {
        global $conf, $registry, $injector, $storage;

        $page = isset($params['page']) ? $params['page'] : 0;
        $view_type = isset($params['view']) ? $params['view'] : 'Gallery';

        $view = $injector->createInstance('Horde_View');
        $view->addTemplatePath(ANSEL_TEMPLATES . '/tile');
        $view->image = $image;
        $view->view_type = $view_type;
        try {
            $view->parent = $storage->getGallery($image->gallery);
        } catch (Ansel_Exception $e) {
            // @TODO: Short circuit here and return a generic error tile.
        } catch (Horde_Exception_NotFound $e) {
            // @TODO: Ditto above.
        }
        if (is_null($style)) {
            $style = $view->parent->getStyle();
        }

        $date = $view->parent->getDate();

        if ($view_type == 'Results') {
            $haveSearch = 1;
        } else {
            $haveSearch = 0;
        }

        // Override the thumbnail to mini or use style default?
        $thumbstyle = $mini ? 'mini' : 'thumb';

        // URL for image properties/actions
        $view->image_url = Horde::url('image.php')->add(
             array_merge(
               array('gallery' => $image->gallery,
                     'page' => $page,
                     'image' => $image->id,
                     'havesearch' => $haveSearch),
               $date));

        // URL to view the image. This is the link for the Tile.
        // $view_url is the link for the thumbnail and since this might not
        // always point to the image view page, we set $img_view_url to link to
        // the image view
        $view->img_view_url = Ansel::getUrlFor('view', array_merge(
            array('gallery' => $image->gallery,
                  'slug' => $view->parent->get('slug'),
                  'page' => $page,
                  'view' => 'Image',
                  'image'=> $image->id,
                  'havesearch' => $haveSearch),
            $date));

        if (!empty($params['image_view_src'])) {
            $view->view_url = Ansel::getImageUrl($image->id, 'screen', true);
        } elseif (empty($params['image_view_url'])) {
            $view->view_url = new Horde_Url($view->img_view_url);
        } else {
            $view->view_url = new Horde_Url(
                str_replace(
                    array('%i', '%g', '%s'),
                    array($image->id, $image->gallery, $view->parent->get('slug')),
                    urldecode($params['image_view_url'])
                )
            );

            // If we override the view_url, assume we want to override this also
            $view->img_view_url = $view->view_url;
        }

        // Need the gallery URL to display the "From" link when showing
        // the image tile from somewhere other than the gallery view.
        if (!empty($view_type) || basename($_SERVER['PHP_SELF']) == 'view.php') {
            $view->gallery_url = Ansel::getUrlFor(
                'view',
                array_merge(
                    array(
                        'gallery' => $view->parent->id,
                        'slug' => $view->parent->get('slug'),
                        'view' => 'Gallery',
                        'havesearch' => $haveSearch),
                    $date)
            );
        }

        $view->thumb_url = Ansel::getImageUrl($image->id, $thumbstyle, true, $style);
        $view->option_select = $view->parent->hasPermission($registry->getAuth(), Horde_Perms::DELETE);
        $view->option_edit = $view->parent->hasPermission($registry->getAuth(), Horde_Perms::EDIT);
        $view->imgAttributes = (!empty($params['image_view_attributes']) ? $params['image_view_attributes'] : array());
        $view->option_comments = ($conf['comments']['allow'] == 'all' || ($conf['comments']['allow'] == 'authenticated' && $registry->getAuth())) && empty($params['hide_comments']);

        $view->imgOnClick = (!empty($params['image_onclick'])
                ? str_replace('%i', $image->id, $params['image_onclick'])
                : '');
        $view->title = $image->title;

        // In-line caption editing if we have Horde_Perms::EDIT
        if ($view->option_edit) {
            try {
                $geometry = $image->getDimensions($thumbstyle);
                $injector->createInstance('Horde_Core_Factory_Imple')->create(
                    'Ansel_Ajax_Imple_EditCaption',
                    array(
                        'dataid' => $image->id,
                        'id' => $image->id . 'caption',
                        'width' => $geometry['width']));
            } catch (Ansel_Exception $e) {
            }
        }

        return $view->render('image');
    }

}
