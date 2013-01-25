<?php
/**
 * Ansel_Tile_DateGallery:: class wraps display of thumbnail tile for the
 * DateGallery psuedo gallery.
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/gpl GPL
 * @category Horde
 * @package  Ansel
 */
class Ansel_Tile_DateGallery
{
    /**
     * Outputs the html for a DateGallery tile.
     *
     * @param Ansel_Gallery_Decorator_Date $dgallery  The Ansel_Gallery_Date we are
     *                                     displaying.
     * @param Ansel_Style $style  A style object.
     * @param boolean $mini       Force the use of a mini thumbail?
     * @param array $params       An array containing additional parameters.
     *                            Currently, gallery_view_url and image_view_url
     *                            are used to override the respective urls.
     *                            %g and %i are replaced with image id and
     *                            gallery id, respectively.
     *
     * @return string  The HTML for the tile.
     */
    public function getTile(
        Ansel_Gallery_Decorator_Date $dgallery, Ansel_Style $style = null, $mini = false, array $params = array())
    {
         $view = $GLOBALS['injector']->createInstance('Horde_View');
         $view->addTemplatePath(ANSEL_TEMPLATES . '/tile');

        // User's preferred date format
        $date_format = $GLOBALS['prefs']->getValue('date_format');
        $date_array = $dgallery->getDate();
        if (empty($date_array['month'])) {
            $date_array['month'] = 1;
        }
        if (empty($date_array['day'])) {
            $date_array['day'] = 1;
        }
        $full_date = new Horde_Date($date_array);

        // Need the unaltered date part array
        $date_array = $dgallery->getDate();

        // Figure out the needed link for the next drill down level. We *must*
        // have at least a year since we are in a date tile.
        if (empty($date_array['month'])) {
            // unit == year
            $view->caption = $full_date->strftime('%Y');
            $next_date = array('year' => (int)$view->caption);
        } elseif (empty($date_array['day'])) {
            // unit == month
            $view->caption = $full_date->strftime('%B %Y');
            $next_date = array(
                'year' => date('Y', $full_date->timestamp()),
                'month' => date('n', $full_date->timestamp()));
        } else {
            // unit == day
            $view->caption = $full_date->strftime($date_format);
            $next_date = array(
                'year' => date('Y', $full_date->timestamp()),
                'month' => date('n', $full_date->timestamp()),
                'day' => date('j', $full_date->timestamp()));
        }

        // Check permissions on the gallery and get appropriate tile image
        if ($dgallery->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::READ)) {
            if (is_null($style)) {
                $style = $dgallery->getStyle();
            }

            $thumbstyle = $mini ? 'mini' : 'thumb';
            $view->gallery_image = Ansel::getImageUrl(
                $dgallery->getKeyImage(),
                $thumbstyle,
                true,
                $style);
        } else {
            $view->gallery_image = Horde_Themes::img('thumb-error.png');
        }

        /* Check for being called via the api and generate correct view links */
        if (!isset($params['gallery_view_url'])) {
            if (empty($params['style'])) {
                $gstyle = $dgallery->getStyle();
            } else {
                $gstyle = $params['style'];
            }
            $params = array(
                'gallery' => $dgallery->id,
                'view' => 'Gallery',
                'slug' => $dgallery->get('slug'));
            $view->view_link = Ansel::getUrlFor('view', array_merge($params, $next_date));
        } else {
            $view->view_link = new Horde_Url(
                str_replace(array('%g', '%s'),
                array($dgallery->id, $dgallery->get('slug')),
                urldecode($params['gallery_view_url'])));
        }
        $view->gallery_count = $dgallery->countImages(true);

        return $view->render('dategallery');
    }
}
