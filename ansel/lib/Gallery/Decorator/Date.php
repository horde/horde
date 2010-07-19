<?php
/**
 * A wrapper/decorator around an Ansel_Gallery to allow multiple date groupings
 * to access the same Ansel_Gallery instance. This is not a full Ansel_Gallery
 * implementation.
 *
 * TODO: For PHP5, this should be rewritten to get rid of all these gosh-darn
 * pass through functions.
 *
 * @package Ansel
 */
class Ansel_Gallery_Decorator_Date
{
    /**
     * The gallery mode helper
     *
     * @var Ansel_GalleryMode_Base object
     */
    protected $_modeHelper;

    /**
     *  The gallery we are decorating
     *
     * @var Ansel_Gallery
     */
    protected $_gallery;

    /**
     * An array of image ids that this gallery contains
     *
     * @var array
     */
    protected $_images;

    /**
     * The Ansel_Gallery_Date constructor.
     *
     * The client
     * code (Ansel_GalleryMode_Date) needs to call the setDate() method on the
     * new GalleryMode_Date object before it's used.
     *
     * @param Ansel_Gallery $gallery  The gallery we are decorating.
     * @param array $images           An array of image ids that this grouping
     *                                contains.
     */
    public function __construct(Ansel_Gallery $gallery, $images = array())
    {
        $this->_gallery = $gallery;
        $this->_modeHelper = new Ansel_GalleryMode_Date($this);
        $this->data = $this->_gallery->data;
        $this->_images = $images;
    }

    /**
     * Magic method - pass thru methods to the wrapped Ansel_Gallery:: or to
     * the Ansel_GalleryMode_Base:: handler.
     *
     * @param string $method
     * @param array $args
     *
     * @return mixed
     */
    public function __call($method, $args)
    {
        switch ($method) {
        case 'getGalleryChildren':
        case 'countGalleryChildren':
        case 'listImages':
        case 'getImages':
        case 'hasSubGalleries':
        case 'getDate':
        case 'setDate':
            return call_user_func_array(array($this->_modeHelper, $method), $args);
        default:
            return call_user_func_array(array($this->_gallery, $method), $args);
        }
    }

    public function __get($property)
    {
        switch ($property) {
        case 'id':
            return $this->_gallery->id;
        }
    }
    /**
     * Output the HTML for this gallery's tile.
     *
     * @param Ansel_Gallery $parent  The parent Ansel_Gallery object
     * @param string $style          A named gallery style to use.
     * @param boolean $mini          Force the use of a mini thumbnail?
     * @param array $params          Any additional parameters the Ansel_Tile
     *                               object may need.
     */
    public function getTile($parent = null, $style = null, $mini = false,
                     $params = array())
    {
        if (!is_null($parent) && is_null($style)) {
            $style = $parent->getStyle();
        } else {
            $style = Ansel::getStyleDefinition($style);
        }

        return Ansel_Tile_DateGallery::getTile($this, $style, $mini, $params);
    }

    /**
     * Return the most recently added images in this gallery.
     *
     * @param integer $limit  The maximum number of images to return.
     *
     * @return mixed  An array of Ansel_Image objects | PEAR_Error
     */
    public function getRecentImages($limit = 10)
    {
        return $GLOBALS['injector']->getInstance('Ansel_Storage')
                ->getScope()
                ->getRecentImages(array($this->_gallery->id), $limit);
    }

    /**
     * Returns the image in this gallery corresponding to the given id.
     *
     * @param integer $id  The ID of the image to retrieve.
     *
     * @return Ansel_Image  The image object corresponding to the given id.
     */
    public function &getImage($id)
    {
        return $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getImage($id);
    }

    /**
     * Returns the number of images in this gallery and, optionally, all
     * sub-galleries.
     *
     * @param boolean $subgalleries  Determines whether subgalleries should
     *                               be counted or not.
     *
     * @return integer number of images in this gallery
     */
    public function countImages($subgalleries = false)
    {
        return count($this->_images);
    }

    /**
     * Returns the default image for this gallery.
     *
     * @param string $style  Force the use of this style, if it's available
     *                       otherwise use whatever style is choosen for this
     *                       gallery. If prettythumbs are not available then
     *                       we always use ansel_default style.
     *
     * @return mixed  The image_id of the default image or false.
     */
    public function getDefaultImage($style = null)
    {
        if (count($this->_images)) {
            return reset($this->_images);
        } else {
            return 0;
        }
    }

    /**
     * Return a count of the number of children this share has
     *
     * @param integer $perm  A Horde_Perms::* constant
     * @param boolean $allLevels  Count grandchildren or just children
     *
     * @return mixed  The number of child shares || PEAR_Error
     */
    public function countChildren($perm = Horde_Perms::SHOW, $allLevels = true)
    {
        return $this->_gallery->getShareOb()->countShares($GLOBALS['registry']->getAuth(), $perm, null, $this, $allLevels);
    }

    /**
     * Returns a child's direct parent
     *
     * @return mixed  The direct parent Horde_Share_Object or PEAR_Error
     */
    public function getParent()
    {
        return $this->_gallery->getShareOb()->getParent($this);
    }

    /**
     * Returns all image ids that this grouping contains.
     *
     * @array
     */
    public function getImagesByGrouping()
    {
        return $this->_images;
    }

}
