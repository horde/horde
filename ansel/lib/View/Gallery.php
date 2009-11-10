<?php
/**
 * The Ansel_View_Gallery:: class wraps display of individual images.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 *
 * @package Ansel
 */
class Ansel_View_Gallery extends Ansel_View_Base
{
    /**
     *  Holds the object that does the actual rendering.
     *
     *  @var Ansel_View_GalleryRenderer
     */
    protected $_renderer;

    /**
     * Const'r
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
     */
    public function __construct($params = array())
    {
        parent::__construct($params);

        if (!empty($params['gallery_slug'])) {
            $this->gallery = $this->_getGallery(null, $params['gallery_slug']);
        } elseif (!empty($params['gallery_id'])) {
            $this->gallery = $this->_getGallery($params['gallery_id']);
        } else {
            $this->gallery = $this->_getGallery();
        }

        // Check user age
        if (!$this->gallery->isOldEnough()) {
            if (!empty($params['api'])) {
                throw new Horde_Exception('Locked galleries are not viewable via the api.');
            }
            $date = Ansel::getDateParameter(
                array('year' => isset($this->_params['year']) ? $this->_params['year'] : 0,
                      'month' => isset($this->_params['month']) ? $this->_params['month'] : 0,
                      'day' => isset($this->_params['day']) ? $this->_params['day'] : 0));

                $galleryurl = Ansel::getUrlFor('view', array_merge(
                                   array('gallery' => $this->gallery->id,
                                         'slug' => empty($params['slug']) ? '' : $params['slug'],
                                         'page' => empty($params['page']) ? 0 : $params['page'],
                                         'view' => 'Gallery'),
                                   $date),
                                   true);

            $params = array('gallery' => $this->gallery->id, 'url' => $galleryurl);
            header('Location: ' . Horde_Util::addParameter(Horde::applicationUrl('disclamer.php'), $params, null, false));
            exit;
        }

        if ($this->gallery->hasPasswd()) {
            if (!empty($params['api'])) {
                return PEAR::raiseError(_("Locked galleries are not viewable via the api."));
            }
            $date = Ansel::getDateParameter(
                array('year' => isset($this->_params['year']) ? $this->_params['year'] : 0,
                      'month' => isset($this->_params['month']) ? $this->_params['month'] : 0,
                      'day' => isset($this->_params['day']) ? $this->_params['day'] : 0));

                $galleryurl = Ansel::getUrlFor('view', array_merge(
                                   array('gallery' => $this->gallery->id,
                                         'slug' => empty($params['slug']) ? '' : $params['slug'],
                                         'page' => empty($params['page']) ? 0 : $params['page'],
                                         'view' => 'Gallery'),
                                   $date),
                                   true);

            $params = array('gallery' => $this->gallery->id, 'url' => $galleryurl);
            header('Location: ' . Horde_Util::addParameter(Horde::applicationUrl('protect.php'), $params, null, false));
            exit;
        }

        if (!$this->gallery->hasPermission(Horde_Auth::getAuth(), PERMS_READ)) {
            throw new Horde_Exception('Access denied viewing this gallery.');
        }

        // Since this is a gallery view, the resource is just a reference to the
        // gallery. We keep both instance variables becuase both gallery and
        // image views are assumed to have a gallery object.
        $this->resource = &$this->gallery;

        /* Do we have an explicit style set? If not, use the gallery's */
        if (!empty($this->_params['style'])) {
            $style = Ansel::getStyleDefinition($this->_params['style']);
        } else {
            $style = $this->gallery->getStyle();
        }

        if (!empty($this->_params['gallery_view'])) {
            $renderer = $this->_params['gallery_view'];
        } else {
            $renderer = (!empty($style['gallery_view'])) ? $style['gallery_view'] : 'Gallery';
        }
        /* Load the helper */
        $classname = 'Ansel_View_GalleryRenderer_' . basename($renderer);
        $this->_renderer = new $classname($this);
        $this->_renderer->init();
    }

    public function getGalleryCrumbData()
    {
        return $this->gallery->getGalleryCrumbData();
    }

    /**
     * Get this gallery's title.
     *
     * @return string  The gallery's title.
     */
    public function getTitle()
    {
        return $this->gallery->get('name');
    }

    /**
     * Return the HTML representing this view.
     *
     * @return string  The HTML.
     *
     */
    public function html()
    {
        return $this->_renderer->html();
    }

    public function viewType()
    {
        return 'Gallery';
    }

}
