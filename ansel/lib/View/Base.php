<?php
/**
 * The Ansel_View_Abstract:: Parent class for the various Ansel_View classes
 *
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
abstract class Ansel_View_Base
{
    protected $_params = array();

    /**
     * Const'r
     *
     * Any javascript files needed by the (non-api) view should be included
     * within this method. Additionally, any redirects need to be done in the
     * cont'r since when ::html() is called, headers will have already been
     * sent.
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
        $this->_params = $params;
    }

    public function __get($property)
    {
        if (isset($this->_params[$property])) {
            return $this->_params[$property];
        }

        return null;
    }

    public function __set($property, $value)
    {
        $this->_params[$property] = $value;
    }

    public function __isset($property)
    {
        return isset($this->_params[$property]);
    }

    /**
     * Getter for the view parameters.
     *
     * @return unknown_type
     */
    public function getParams()
    {
        return $this->_params;
    }

    /**
     * Todo
     *
     * @param integer $galleryId  The gallery id
     * @param string $slug        The gallery slug
     *
     * @return Ansel_Gallery  The requested Ansel_Gallery object
     * @throws Horde_Exception
     * @throws InvalidArgumentException
     *
     */
    protected function _getGallery($galleryId = null, $slug = '')
    {
        if (is_null($galleryId) && empty($slug)) {
            $galleryId = !empty($this->_params['gallery_id']) ? $this->_params['gallery_id'] : null;
            $slug = !empty($this->_params['gallery_slug']) ? $this->_params['gallery_slug'] : null;
        }

        if (empty($galleryId) && empty($slug)) {
            throw new Ansel_Exception(_("No gallery specified"));
        }

        // If we have a slug, use it.
        try {
            if (!empty($slug)) {
                $gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getGalleryBySlug($slug);
            } else {
                $gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getGallery($galleryId);
            }
        } catch (Ansel_Exception $e) {
            throw new Horde_Exception_NotFound($e->getmessage());
        }

        if (!$gallery->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::READ)) {
            throw new Horde_Exception(sprintf(_("Access denied to gallery \"%s\"."), $gallery->get('name')));
        }

        /* Set any date info we might have */
        if (!empty($this->_params['year'])) {
            $date = Ansel::getDateParameter(
                array('year' => $this->_params['year'],
                      'month' => $this->_params['month'],
                      'day' => $this->_params['day']));
        } else {
            $date = array();
        }
        $gallery->setDate($date);

        return $gallery;
    }


   /**
     * JSON representation of this gallery's images. We don't use
     * Ansel_Gallery::toJson() on purpose since that is a general jsonification
     * of the gallery data. This method is specific to the view, paging, links
     * etc...
     *
     * @param Ansel_Gallery $gallery  The gallery to represent in this view
     * @param array $params           An array of parameters for this method:
     *   <pre>
     *      full       - Should a full URL be generated? [false]
     *      from       - Starting image count [0]
     *      count      - The number of images to include (starting at from) [0]
     *      image_view - The type of ImageGenerator to obtain the src url for. [screen]
     *      view_links - Should the JSON include links to the Image and/or Gallery View? [false]
     *      perpage    - Number of images per page [from user prefs]
     *   </pre>
     *
     * @return string  A serialized JSON array.
     */
    static public function json(Ansel_Gallery $gallery, $params = array())
    {
        global $conf, $prefs;

        $default = array(
            'full' => false,
            'from' => 0,
            'count' => 0,
            'image_view' => 'screen',
            'view_links' => false,
            'perpage' => $prefs->getValue('tilesperpage', $conf['thumbnail']['perpage'])
        );

        $params = array_merge($default, $params);
        $json = array();
        $curimage = 0;
        $curpage =  0;
        if (empty($params['images'])) {
            $images = $gallery->getImages($params['from'], $params['count']);
        }
        $style = $gallery->getStyle();
        if ($params['image_view'] == 'thumb' && !empty($params['generator'])) {
            $style->thumbstyle = $params['generator'];
        }
        foreach ($images as $image) {
            // Calculate the page this image will appear on in the gallery view.
            if (++$curimage > $params['perpage']) {
                ++$curpage;
                $curimage = 0;
            }

            $data = array(
                (string)Ansel::getImageUrl($image->id, $params['image_view'], $params['full'], $style),
                htmlspecialchars($image->filename),
                $GLOBALS['injector']->getInstance('Horde_Core_Factory_TextFilter')->filter($image->caption, 'text2html', array('parselevel' => Horde_Text_Filter_Text2html::MICRO_LINKURL)),
                $image->id,
                $curpage
            );
            if ($params['view_links']) {
                $data[] = (string)Ansel::getUrlFor('view',
                    array('gallery' => $gallery->id,
                          'slug' => $gallery->get('slug'),
                          'image' => $image->id,
                          'view' => 'Image',
                          'page' => $curpage),
                    true);
                $data[] = (string)Ansel::getUrlFor('view',
                    array('gallery' => $image->gallery,
                          'slug' => $gallery->get('slug'),
                          'view' => 'Gallery'),
                    true);
            }
            // Source, Width, Height, Name, Caption, Image Id, Gallery Page
            $json[] = $data;
        }

        return Horde_Serialize::serialize($json, Horde_Serialize::JSON);
    }

}
