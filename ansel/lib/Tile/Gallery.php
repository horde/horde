<?php
/**
 * Ansel_Tile_Gallery:: class wraps display of thumbnail 'tiles' displayed
 * for a gallery on the Ansel_View_Gallery view.
 *
 * @author Michael Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
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
    public function getTile($gallery, $style = null, $mini = false, $params = array())
    {
        /*
         * See what view we are being displayed in to see if we need to show
         * the owner info or not.
         */
        $view = Horde_Util::getFormData('view', 'Gallery');
        $haveSearch = ($view == 'Results') ? 1 : 0;
        if (($view == 'Results' || $view == 'List') ||
            (basename($_SERVER['PHP_SELF']) == 'index.php' &&
             $GLOBALS['prefs']->getValue('defaultview') == 'galleries')) {
            $showOwner = true;
        } else {
            $showOwner = false;
        }

        /* Check gallery permissions and get appropriate tile image */
        if ($gallery->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::READ)) {
            if (is_null($style)) {
                $style = $gallery->getStyle();
            }
            $thumbstyle = $mini ? 'mini' : $style->thumbstyle;
            if ($gallery->hasPasswd()) {
                $gallery_image = Horde::img('gallery-locked.png');
            } else {
                $gallery_image = Ansel::getImageUrl(
                    $gallery->getKeyImage($style),
                    $thumbstyle,
                    true,
                    $style);
                $gallery_image = '<img src="' . $gallery_image . '" alt="' . htmlspecialchars($gallery->get('name')) . '" />';
            }
        } else {
            $gallery_image = Horde::img('thumb-error.png');
        }

        /* Check for being called via the api and generate correct view links */
        if (!isset($params['gallery_view_url'])) {
            $view_link = Ansel::getUrlFor('view',
                                          array('gallery' => $gallery->id,
                                                'view' => 'Gallery',
                                                'havesearch' => $haveSearch,
                                                'slug' => $gallery->get('slug')))->link();
        } else {
            $view_link = new Horde_Url(
                str_replace(array('%g', '%s'),
                            array($gallery->id, $gallery->get('slug')),
                            urldecode($params['gallery_view_url'])));
            $view_link = $view_link->link();
        }

        $image_link = $view_link . $gallery_image . '</a>';
        $text_link = $view_link . htmlspecialchars($gallery->get('name'))
                     . '</a>';

        if ($gallery->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT) && !$mini) {
            $properties_link = Horde::url('gallery.php', true)->add(
                        array('gallery' => $gallery->id,
                              'actionID' => 'modify',
                              'havesearch' => $haveSearch,
                              'url' => Horde::selfUrl(true, false, true)));
            $properties_link = $properties_link->link() . _("Gallery Properties") . '</a>';
        }

        if ($showOwner && !$mini &&
            $GLOBALS['registry']->getAuth() != $gallery->get('owner')) {
            $owner_link = Ansel::getUrlFor('view',
                                            array('view' => 'List',
                                                  'owner' => $gallery->get('owner'),
                                                  'groupby' => 'owner'),
                                            true)->link();
            $gallery_owner = $gallery->getIdentity();
            $owner_string = $gallery_owner->getValue('fullname');
            if (empty($owner_string)) {
                $owner_string = $gallery->get('owner');
            }
            $owner_link .= htmlspecialchars($owner_string) . '</a>';
        }

        $gallery_count = $gallery->countImages(true);
        $background_color = $style->background;

        $date_format = $GLOBALS['prefs']->getValue('date_format');
        $created = _("Created:") . ' '
                   . strftime($date_format, (int)$gallery->get('date_created'));
        $modified = _("Modified") . ' '
                   . strftime($date_format, (int)$gallery->get('last_modified'));

        Horde::startBuffer();
        include ANSEL_TEMPLATES . '/tile/gallery' . ($mini ? 'mini' : '') . '.inc';

        return Horde::endBuffer();
    }

}
