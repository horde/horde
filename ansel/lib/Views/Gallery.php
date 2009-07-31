<?php
/**
 * @package Ansel
 */

/** Ansel_View_Abstract */
require_once ANSEL_BASE . '/lib/Views/Abstract.php';

/**
 * The Ansel_View_Gallery:: class wraps display of individual images.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Ansel
 */
class Ansel_View_Gallery extends Ansel_View_Abstract {

    /** Holds the object that does the actual rendering **/
    var $_renderer;

    /**
     * @static
     *
     * @param array $params  Any parameters that the view might need.
     * <pre>
     * gallery_id              The gallery id this view is for. If omitted, it
     *                         looks for a query parameter called 'gallery'
     *
     * gallery_slug            Same as above, but a slug
     *
     * gallery_view_url        If set, this is used as the link to a gallery
     *                         view. %g is replaced by the gallery_id and %s is
     *                         replaced by the gallery_slug.
     *
     * gallery_view            The specific Renderer to use, if needed.
     *                         (GalleryLightbox, Gallery etc...).
     *
     * image_view_url          If this is set, the image tiles will use this url
     *                         for the image view link. %i and %g will be
     *                         replaced by image_id and gallery_id respectively.
     *                         %s will be replaced by the gallery_slug
     *
     * image_view_src          If this is set to true, the image view link will go
     *                         directly to the actual image. This overrides any
     *                         setting of image_view_url.
     *
     * image_view_attributes   An optional array of attribute => value pairs
     *                         that are used as attributes of the image view
     *                         link.
     *
     * image_view_title        Specifies which property of the image object
     *                         to use as the image caption.
     *
     * image_onclick           Specifies a onclick handler for the image tile
     *                         links.
     *
     * style                   Force the use of this named style.
     *
     * api                     If set, we are being called from the external api
     *
     * page                    The gallery page number to display if not the
     *                         default value of the first page (page = 0)
     *
     * day, month, year        Numeric date part values to describe the gallery
     *                         date grouping to view in date mode.
     *
     * force_date_grouping     Do not auto navigate to the first date grouping
     *                         with more then one resource. Used from the api
     *                         when clicking on breadcrumb links, for example.
     * </pre>
     *
     * @TODO use exceptions from the constructor instead of static
     * instance-getting.
     */
    function makeView($params = array())
    {
        $view = new Ansel_View_Gallery();

        if (count($params)) {
            $view->_params = $params;
        }

        if (!empty($params['gallery_slug'])) {
            $view->gallery = $view->getGallery(null, $params['gallery_slug']);
        } elseif (!empty($params['gallery_id'])) {
            $view->gallery = $view->getGallery($params['gallery_id']);
        } else {
            $view->gallery = $view->getGallery();
        }

        if (is_a($view->gallery, 'PEAR_Error')) {
            return $view->gallery;
        }

        // Check user age
        if (!$view->gallery->isOldEnough()) {
            if (!empty($params['api'])) {
                return PEAR::raiseError(_("Locked galleries are not viewable via the api."));
            }
            $date = Ansel::getDateParameter(
                array('year' => isset($view->_params['year']) ? $view->_params['year'] : 0,
                      'month' => isset($view->_params['month']) ? $view->_params['month'] : 0,
                      'day' => isset($view->_params['day']) ? $view->_params['day'] : 0));

                $galleryurl = Ansel::getUrlFor('view', array_merge(
                                   array('gallery' => $view->gallery->id,
                                         'slug' => empty($params['slug']) ? '' : $params['slug'],
                                         'page' => empty($params['page']) ? 0 : $params['page'],
                                         'view' => 'Gallery'),
                                   $date),
                                   true);

            $params = array('gallery' => $view->gallery->id, 'url' => $galleryurl);
            header('Location: ' . Horde_Util::addParameter(Horde::applicationUrl('disclamer.php'), $params, null, false));
            exit;
        }

        if ($view->gallery->hasPasswd()) {
            if (!empty($params['api'])) {
                return PEAR::raiseError(_("Locked galleries are not viewable via the api."));
            }
            $date = Ansel::getDateParameter(
                array('year' => isset($view->_params['year']) ? $view->_params['year'] : 0,
                      'month' => isset($view->_params['month']) ? $view->_params['month'] : 0,
                      'day' => isset($view->_params['day']) ? $view->_params['day'] : 0));

                $galleryurl = Ansel::getUrlFor('view', array_merge(
                                   array('gallery' => $view->gallery->id,
                                         'slug' => empty($params['slug']) ? '' : $params['slug'],
                                         'page' => empty($params['page']) ? 0 : $params['page'],
                                         'view' => 'Gallery'),
                                   $date),
                                   true);

            $params = array('gallery' => $view->gallery->id, 'url' => $galleryurl);
            header('Location: ' . Horde_Util::addParameter(Horde::applicationUrl('protect.php'), $params, null, false));
            exit;
        }

        if (!$view->gallery->hasPermission(Horde_Auth::getAuth(), PERMS_READ)) {
            return PEAR::raiseError(_("Access denied viewing this gallery."));
        }

        // Since this is a gallery view, the resource is just a reference to the
        // gallery. We keep both instance variables becuase both gallery and
        // image views are assumed to have a gallery object.
        $view->resource = &$view->gallery;

        /* Do we have an explicit style set? If not, use the gallery's */
        if (!empty($view->_params['style'])) {
            $style = Ansel::getStyleDefinition($view->_params['style']);
        } else {
            $style = $view->gallery->getStyle();
        }

        if (!empty($view->_params['gallery_view'])) {
            $renderer = $view->_params['gallery_view'];
        } else {
            $renderer = (!empty($style['gallery_view'])) ? $style['gallery_view'] : 'Gallery';
        }
        /* Load the helper */
        $classname = 'Ansel_View_GalleryRenderer_' . basename($renderer);
        $view->_renderer = new $classname($view);
        $view->_renderer->init();

        return $view;
    }

    function getGalleryCrumbData()
    {
        return $this->gallery->getGalleryCrumbData();
    }

    /**
     * Get this gallery's title.
     *
     * @return string  The gallery's title.
     */
    function getTitle()
    {
        if (is_a($this->gallery, 'PEAR_Error')) {
            return $this->gallery->getMessage();
        }
        return $this->gallery->get('name');
    }

    /**
     * Return the HTML representing this view.
     *
     * @return string  The HTML.
     *
     */
    function html()
    {
        return $this->_renderer->html();
    }

    function viewType()
    {
        return 'Gallery';
    }

}
