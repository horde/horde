<?php
/**
 * Class to abstract the creation of various image views.
 *
 * Copyright 2007-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * Copyright 2007-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_ImageView {

    /**
     * Ansel_Image object that this view is created from.
     *
     * @var Ansel_Image
     */
    var $_image = null;

    /**
     * Parameters for this view
     *
     * @var array
     */
    var $_params = array();

    /**
     * Image dimensions
     *
     * @var array
     */
    var $_dimensions = array();

    var $_style = array();

    var $need = array();

    /**
     * Constructor
     */
    function Ansel_ImageView($params)
    {
        $this->_params = $params;
        if (!empty($params['image'])) {
            $this->_image = $params['image'];
        }
        $this->_style = $params['style'];
    }

    /**
     * Function to actually create and cache the view.
     */
    function create()
    {
        if (!empty($this->_image)) {
            $this->_dimensions = $this->_image->_image->getDimensions();
        }

        return $this->_create();
    }

    function factory($type, $params = array())
    {
        $type = basename($type);
        $class = 'Ansel_ImageView_' . $type;
        if (!class_exists($class)) {
            include dirname(__FILE__) . '/ImageView/' . $type . '.php';
        }
        if (class_exists($class)) {
            $view = new $class($params);
            // Check that the image object supports what we need for the
            // requested effect.
            foreach ($view->need as $need) {
                if (!Ansel::isAvailable($need)) {
                    $err = PEAR::raiseError(_("This install does not support the %s feature. Please contact your administrator."), $need);
                    Horde::logMessage($err, __FILE__, __LINE__, PEAR_LOG_ERR);
                    return $err;
                }
            }
            return $view;
        } else {
            $err = PEAR::raiseError(sprintf(_("Unable to load the definition of %s."), $class));
            Horde::logMessage($err, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $err;
        }

    }

    /**
     * Utility function to make every effort to find a subgallery that
     * contains images.
     *
     * @param Ansel_Gallery $parent  The gallery to start looking in
     *
     * @return An Ansel_Gallery object that has images, or the original $parent
     */
    function _getGalleryWithImages($parent)
    {
       $galleries = $GLOBALS['ansel_storage']->listGalleries(
                                                    PERMS_SHOW,
                                                    null,
                                                    $parent,
                                                    false);

        foreach ($galleries as $gallery) {
            if ($gallery->countImages()) {
                return $gallery;
            }
            $result = $this->_getGalleryWithImages($gallery);
            if ($result->countImages()) {
                return $result;
            }
        }
        return $parent;
    }

   /**
     * Utility function to return an array of Ansel_Images to use
     * in building a polaroid stack. Returns a random set of 5 images from
     * the gallery, or the explicitly set default image plus 4 others.
     */
    function _getStackImages()
    {
        $images = array();
        $gallery = $this->_params['gallery'];

        // Make sure we have images.
        if (!$gallery->countImages() && $gallery->hasSubGalleries()) {
            $gallery = $this->_getGalleryWithImages($gallery);
        }

        $cnt = min(5, $gallery->countImages());
        $default = $gallery->get('default');
        if (!empty($default) && $default > 0) {
            $img = &$gallery->getImage($default);
            if (!is_a($img, 'PEAR_Error')) {
                $images[] = &$gallery->getImage($default);
                $cnt--;
            }
        }

        for ($i = 0; $i < $cnt; $i++) {
            $rnd = mt_rand(0, $cnt);
            $temp = $gallery->getImages($rnd, 1);
            if (!is_a($temp, 'PEAR_Error') && count($temp)) {
                $images[] = array_shift($temp);
            }
        }

        // Reverse the array to ensure the requested default image
        // is the last in the array (so it will appear on the top of
        // the stack.
        return array_reverse($images);
    }

}
