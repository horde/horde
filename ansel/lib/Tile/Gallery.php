<?php
/**
 * Ansel_Tile_Gallery:: class wraps display of thumbnail 'tiles' displayed
 * for a gallery on the Ansel_View_Gallery view.
 *
 * @copyright 2007-2015 Horde LLC (http://www.horde.org)
 * @author Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Ansel
 */
class Ansel_Tile_Gallery
{
    /**
     * Outputs the html for a gallery tile.
     *
     * @param Ansel_Gallery $gallery  The Ansel_Gallery we are displaying.
     * @param Ansel_Style $style      A style object.
     * @param boolean $mini           Force the use of a mini thumbail?
     * @param array $params           An array containing additional parameters.
     *                                Currently, gallery_view_url and
     *                                image_view_url are used to override the
     *                                respective urls. %g and %i are replaced
     *                                with image id and gallery id, respectively
     *
     *
     * @return  Outputs the HTML for the tile.
     */
    public static function getTile(
        Ansel_Gallery $gallery, Ansel_Style $style = null, $mini = false, array $params = array())
    {
        global $prefs, $registry, $injector;

        // Create view
        $view = $injector->createInstance('Horde_View');
        $view->addTemplatePath(ANSEL_TEMPLATES . '/tile');
        $view->gallery = $gallery;

        $view_type = Horde_Util::getFormData('view', 'Gallery');
        $haveSearch = ($view_type == 'Results') ? 1 : 0;
        if (($view_type == 'Results' || $view_type == 'List') ||
            (basename($_SERVER['PHP_SELF']) == 'index.php' &&
             $prefs->getValue('defaultview') == 'galleries')) {
            $showOwner = true;
        } else {
            $showOwner = false;
        }

        // Use the galleries style if not explicitly passed.
        if (is_null($style)) {
            $style = $gallery->getStyle();
        }

        // If the gallery has subgalleries, and no images, use one of the
        // subgalleries' stack image. hasSubGalleries already takes
        // permissions into account.
        if ($gallery->hasPermission($registry->getAuth(), Horde_Perms::READ) &&
            !$gallery->countImages() && $gallery->hasSubGalleries()) {

            try {
                $galleries = $injector
                    ->getInstance('Ansel_Storage')
                    ->listGalleries(array('parent' => $gallery->id, 'all_levels' => false, 'perm' => Horde_Perms::READ));

                foreach ($galleries as $sgallery) {
                    if ($default_img = $sgallery->getKeyImage($style)) {
                        $view->gallery_image = Ansel::getImageUrl(
                            $default_img, ($mini ? 'mini' : 'thumb'), true, $style);
                    }
                }
            } catch (Ansel_Exception $e) {}

        } elseif ($gallery->hasPermission($registry->getAuth(), Horde_Perms::READ) &&
                  $gallery->countImages()) {

            $thumbstyle = $mini ? 'mini' : 'thumb';
            if ($gallery->hasPasswd()) {
                $view->gallery_image = Horde_Themes::img('gallery-locked.png');
            } else {
                $view->gallery_image = Ansel::getImageUrl(
                    $gallery->getKeyImage($style),
                    $thumbstyle,
                    true,
                    $style);
            }

        }

        // If no image at this point, we can't get one.
        if (empty($view->gallery_image)) {
            $view->gallery_image = Horde_Themes::img('thumb-error.png');
        }

        // Check for being called via the api and generate correct view links
        if (!isset($params['gallery_view_url'])) {
            $view->view_link = Ansel::getUrlFor(
                'view',
                array(
                    'gallery' => $gallery->id,
                    'view' => 'Gallery',
                    'havesearch' => $haveSearch,
                    'slug' => $gallery->get('slug')));
        } else {
            $view->view_link = new Horde_Url(
                str_replace(
                    array('%g', '%s'),
                    array($gallery->id, $gallery->get('slug')),
                    urldecode($params['gallery_view_url'])));
        }

        if ($gallery->hasPermission($registry->getAuth(), Horde_Perms::EDIT) && !$mini) {
            $view->properties_link = Horde::url('gallery.php', true)->add(
                array('gallery' => $gallery->id,
                      'actionID' => 'modify',
                      'havesearch' => $haveSearch,
                      'url' => Horde::selfUrl(true, false, true)));
        }

        if ($showOwner && !$mini &&
            $registry->getAuth() != $gallery->get('owner')) {
            $view->owner_link = Ansel::getUrlFor(
                'view',
                array(
                    'view' => 'List',
                    'owner' => $gallery->get('owner'),
                    'groupby' => 'owner'),
                true);
            $view->owner_string = $gallery->getIdentity()->getValue('fullname');
            if (empty($view->owner_string)) {
                $view->owner_string = $gallery->get('owner');
            }
        }

        $view->background_color = $style->background;
        $view->gallery_count = $gallery->countImages(true);
        $view->date_format = $prefs->getValue('date_format');

        return $view->render('gallery' . ($mini ? 'mini' : ''));
    }

}
