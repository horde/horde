<?php
/**
 * Ansel_Tile_DateGallery:: class wraps display of thumbnail tile for the
 * DateGallery psuedo gallery.
 *
 * @author   Michael Rubinsky <mrubinsk@horde.org>
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @category Horde
 * @package  Ansel
 */
class Ansel_Tile_DateGallery
{
    /**
     * Outputs the html for a DateGallery tile.
     *
     * @param Ansel_DateGallery $dgallery  The Ansel_Gallery_Date we are
     *                                     displaying.
     * @param Ansel_Style $style           A style object.
     * @param boolean $mini                Force the use of a mini thumbail?
     * @param array $params                An array containing additional
     *                                     parameters. Currently,
     *                                     gallery_view_url and
     *                                     image_view_url are used to override
     *                                     the respective urls. %g and %i are
     *                                     replaced with image id and gallery id,
     *                                     respectively
     *
     * @return  Outputs the HTML for the tile.
     */
    public function getTile($dgallery, $style = null, $mini = false, $params = array())
    {
        /* User's preferred date format */
        $date_format = $GLOBALS['prefs']->getValue('date_format');

        /* Easier to work with a Horde_Date object */
        $date_array = $dgallery->getDate();
        if (empty($date_array['month'])) {
            $date_array['month'] = 1;
        }
        if (empty($date_array['day'])) {
            $date_array['day'] = 1;
        }

        $full_date = new Horde_Date($date_array);

        /* Need the unaltered date part array */
        $date_array = $dgallery->getDate();

        /* Figure out the needed link for the next drill down level. We *must*
         * have at least a year since we are in a date tile. */
        if (empty($date_array['month'])) {
            // unit == year
            $caption = $full_date->format('Y');
            $next_date = array('year' => (int)$caption);
        } elseif (empty($date_array['day'])) {
            // unit == month
            $caption = $full_date->format('F Y');
            $next_date = array('year' => date('Y', $full_date->timestamp()),
                               'month' => date('n', $full_date->timestamp()));
        } else {
            // unit == day ... hopefully ;)
            $caption = $full_date->strftime($date_format);
            $next_date = array('year' => date('Y', $full_date->timestamp()),
                               'month' => date('n', $full_date->timestamp()),
                               'day' => date('j', $full_date->timestamp()));
        }

        /* Check permissions on the gallery and get appropriate tile image */
        if ($dgallery->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::READ)) {
            if (is_null($style)) {
                $style = $dgallery->getStyle();
            }

            $thumbstyle = $mini ? 'mini' : $style->thumbstyle;
            $gallery_image = Ansel::getImageUrl(
                $dgallery->getKeyImage(),
                $thumbstyle,
                true,
                $style);

            $gallery_image = '<img src="' . $gallery_image . '" alt="' . $caption . '" />' ;
        } else {
            $gallery_image = Horde::img('thumb-error.png');
        }

        /* Check for being called via the api and generate correct view links */
        if (!isset($params['gallery_view_url'])) {
            if (empty($params['style'])) {
                $gstyle = $dgallery->getStyle();
            } else {
                $gstyle = $params['style'];
            }
            $params = array('gallery' => $dgallery->id,
                            'view' => 'Gallery',
                            'slug' => $dgallery->get('slug'));
            $params = array_merge($params, $next_date);
            $view_link = Ansel::getUrlFor('view', $params)->link();
        } else {
            $view_link = new Horde_Url(str_replace(array('%g', '%s'),
                                                   array($dgallery->id, $dgallery->get('slug')),
                                                   urldecode($params['gallery_view_url'])));
            $view_link = $view_link->add($next_date)->link();
        }

        /* Variables used in the template file */
        $image_link = $view_link . $gallery_image . '</a>';
        $text_link = $view_link . htmlspecialchars($caption, ENT_COMPAT, $GLOBALS['registry']->getCharset()) . '</a>';
        $gallery_count = $dgallery->countImages(true);

        /* Background color is needed if displaying a mini tile */
        $background_color = $style->background;

        Horde::startBuffer();
        include ANSEL_TEMPLATES . '/tile/dategallery' . ($mini ? 'mini' : '') . '.inc';
        return Horde::endBuffer();
    }
}
