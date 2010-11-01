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
     * @see Ansel_View_Base::__construct
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
            Horde::url('disclamer.php')->add($params)->setRaw(true)->redirect();
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
            Horde::url('protect.php')->add($params)->setRaw(true)->redirect();
            exit;
        }

        if (!$this->gallery->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::READ)) {
            throw new Horde_Exception('Access denied viewing this gallery.');
        }

        // Since this is a gallery view, the resource is just a reference to the
        // gallery. We keep both instance variables becuase both gallery and
        // image views are assumed to have a gallery object.
        $this->resource = $this->gallery;

        /* Do we have an explicit style set? If not, use the gallery's */
        if (!empty($this->_params['style'])) {
            $style = Ansel::getStyleDefinition($this->_params['style']);
        } else {
            $style = $this->gallery->getStyle();
        }

        if (!empty($this->_params['gallery_view'])) {
            $renderer = $this->_params['gallery_view'];
        } else {
            $renderer = (!empty($style->gallery_view)) ? $style->gallery_view : 'Gallery';
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
