<?php
/**
 * Class to abstract the creation of various image views.
 *
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_ImageGenerator
{
    /**
     * Ansel_Image object that this view is created from.
     *
     * @var Ansel_Image
     */
    protected $_image = null;

    /**
     * Parameters for this view
     *
     * @var array
     */
    protected $_params = array();

    /**
     * Image dimensions
     *
     * @var array
     */
    protected $_dimensions = array();

    /**
     * Cache the style information array
     *
     * @var array
     */
    protected $_style = array();

    /**
     * Array of required, supported features for this ImageGenerator to work
     *
     * @var array
     */
    public  $need = array();

    /**
     * Const'r
     *
     * @return Horde_ImageGenerator
     */
    public function __construct($params)
    {
        $this->_params = $params;
        if (!empty($params['image'])) {
            $this->_image = $params['image'];
        }
        $this->_style = $params['style'];
    }

    /**
     * Create and cache the view.
     *
     * @return mixed  Views used as gallery key images return Horde_Image,
     *                other views return boolean
     */
    public function create()
    {
        if (!empty($this->_image)) {
            // Use Horde_Image since we don't know at this point which
            // view we have loaded.
            $img = $this->_image->getHordeImage();
            $this->_dimensions = $img->getDimensions();
        }

        return $this->_create();
    }

    /**
     * Horde_ImageGenerator factory
     *
     * @param string $type   The type of concrete instance to return.
     * @param array $params  Additional parameters needed for the instance.
     *
     * @return Ansel_ImageGenerator
     * @throws Ansel_Exception
     */
    function factory($type, $params = array())
    {
        $type = basename($type);
        $class = 'Ansel_ImageGenerator_' . $type;
        if (!class_exists($class)) {
            include dirname(__FILE__) . '/ImageGenerator/' . $type . '.php';
        }
        if (class_exists($class)) {
            $view = new $class($params);
            // Check that the image object supports what we need for the
            // requested effect.
            foreach ($view->need as $need) {
                if (!Ansel::isAvailable($need)) {
                    Horde::logMessage($err, 'ERR');
                    throw new Ansel_Exception(_("This install does not support the %s feature. Please contact your administrator."), $need);
                }
            }
            return $view;
        } else {
            Horde::logMessage($err, 'ERR');
            throw new Ansel_Exception(sprintf(_("Unable to load the definition of %s."), $class));
        }
    }

    /**
     * Utility function to make every effort to find a subgallery that
     * contains images.
     *
     * @param Ansel_Gallery $parent  The gallery to start looking in
     *
     * @return Ansel_Gallery  Gallery that has images, or the original $parent
     */
    protected function _getGalleryWithImages($parent)
    {
        $galleries = $GLOBALS['injector']
            ->getInstance('Ansel_Storage')
            ->getScope()
            ->listGalleries(array('parent' => $parent, 'allLevels' => false));

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
     * Utility function to return an array of Horde_Images to use in building a
    *  polaroid stack. Returns a random set of 5 images from the gallery, or the
    *  explicitly set key image plus 4 others.
     *
     * @return array of Horde_Images
     */
    protected function _getStackImages()
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
            try {
                $img = $gallery->getImage($default);
                $img->load('screen');
                $images[] = $img->getHordeImage();
                $cnt--;
            } catch (Exception $e) {
                Horde::logMessage($e, 'ERR');
            }
        }

        for ($i = 0; $i < $cnt; $i++) {
            $rnd = mt_rand(0, $cnt);
            try {
                $temp = $gallery->getImages($rnd, 1);
                if (count($temp)) {
                    $aimg = array_shift($temp);
                    $aimg->load('screen');
                    $images[] = $aimg->getHordeImage();
                }
            } catch (Exception $e) {
                Horde::logMessage($e, 'ERR');
            }
        }

        // Reverse the array to ensure the requested key image
        // is the last in the array (so it will appear on the top of
        // the stack.
        return array_reverse($images);
    }

}
