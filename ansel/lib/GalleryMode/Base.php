<?php
/**
 * Ansel_GalleryMode_Base:: Class for encapsulating gallery methods that
 * depend on the current display mode of the gallery.
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
abstract class Ansel_GalleryMode_Base
{
    /**
     * @var Ansel_Gallery
     */
    protected $_gallery;

    /**
     *
     * @var array
     */
    protected $_features = array();

    /**
     * Constructor
     *
     * @param Ansel_Gallery $gallery  The gallery to bind to.
     *
     * @return Ansel_GalleryMode_Base
     */
    public function __construct($gallery)
    {
        $this->_gallery = $gallery;
    }

    public function hasFeature($feature)
    {
        return in_array($feature, $this->_features);
    }

    /**
     * @TODO: Figure out if we can get rid of this and only include it in the
     *        objects that actually need it.
     * @param array $date   Date parts array
     */
    public function setDate($date = array())
    {
    }

    /**
     *
     * @return array  Date parts array.
     */
    public function getDate()
    {
        return array();
    }

    /**
     * Get the children of this gallery.
     *
     * @param integer $perm   The permissions to limit to.
     * @param integer $from   The child to start at.
     * @param integer $count  The number of children to return.
     *
     * @return array  A mixed array of Ansel_Gallery and Ansel_Image objects
     *                that are children of this gallery.
     */
    abstract public function getGalleryChildren($perm = Horde_Perms::SHOW, $from = 0, $to = 0);

    /**
     * Return the count this gallery's children
     *
     * @param integer $perm            The permissions to require.
     * @param boolean $galleries_only  Only include galleries, no images.
     *
     * @return integer The count of this gallery's children.
     */
    abstract public function countGalleryChildren($perm = Horde_Perms::SHOW, $galleries_only = false);

    /**
     * Get an array describing where this gallery is in a breadcrumb trail.
     *
     * @return  An array of 'title' and 'navdata' hashes with the [0] element
     *          being the deepest part.
     */
    abstract public function getGalleryCrumbData();

    /**
     * List a slice of the image ids in this gallery.
     *
     * @param integer $from  The image to start listing.
     * @param integer $count The numer of images to list.
     *
     * @return array  An array of image_ids
     */
    abstract public function listImages($from = 0, $count = 0);

    /**
     * Gets a slice of the images in this gallery.
     *
     * @param integer $from  The image to start fetching.
     * @param integer $count The numer of images to return.
     *
     * @param array An array of Ansel_Image objects
     */
    abstract public function getImages($from = 0, $count = 0);

    /**
     * Move images from this gallery to another.
     *
     * @param array $images           The image ids to move.
     * @param Ansel_Gallery $gallery  The gallery to move images into.
     *
     * @return boolean
     * @throws Ansel_Exception
     * @throws Horde_Exception_PermissionDenied
     */
    abstract public function moveImagesTo($images, $gallery);

    /**
     * Remove an image from Ansel.
     *
     * @param integer | Ansel_Image $image  The image id or object
     * @param boolean $isStack              This represents a stack image
     *
     * @return boolean
     */
    abstract public function removeImage($image, $isStack);

    /**
     * Checks if the gallery has any subgallery
     *
     * @return boolean
     */
    abstract public function hasSubGalleries();

    /**
     * Returns the number of images in this gallery and, optionally, all
     * sub-galleries.
     *
     * @param boolean $subgalleries  Determine whether subgalleries should
     *                               be counted or not.
     *
     * @return integer  The number of images in this gallery
     */
    abstract public function countImages($subgalleries = false);
}
