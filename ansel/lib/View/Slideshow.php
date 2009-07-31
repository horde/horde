<?php
/**
 * The Ansel_View_Slideshow:: class wraps display of the gallery slideshow.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */

/** Ansel_View_Abstract */
require_once ANSEL_BASE . '/lib/Views/Abstract.php';

class Ansel_View_Slideshow extends Ansel_View_Abstract {

    /**
     * The Ansel_Image object representing the first image selected for view.
     *
     * @var Ansel_Image
     */
    var $image;

    /**
     * @static
     *
     * @return Ansel_View_Slidshow  The view object.
     *
     * @TODO use exceptions from the constructor instead of static
     * instance-getting.
     */
    function makeView($params = array())
    {
        if (empty($params['image_id'])) {
            $image_id = Horde_Util::getFormData('image');
        } else {
            $image_id = $params['image_id'];
        }
        $image = $GLOBALS['ansel_storage']->getImage($image_id);
        if (is_a($image, 'PEAR_Error')) {
            return $image;
        }

        $view = new Ansel_View_Slideshow();
        if (count($params)) {
            $view->_params = $params;
        }
        $view->gallery = $view->getGallery($image->gallery);
        if (is_a($view->gallery, 'PEAR_Error')) {
            return $view->gallery;
        }
        $view->image = $image;

        // Check user age
        if (!$view->gallery->isOldEnough()) {
           $date = Ansel::getDateParameter(
                array('year' => isset($view->_params['year']) ? $view->_params['year'] : 0,
                      'month' => isset($view->_params['month']) ? $view->_params['month'] : 0,
                      'day' => isset($view->_params['day']) ? $view->_params['day'] : 0));

                $url = Ansel::getUrlFor('view', array_merge(
                    array('gallery' => $view->gallery->id,
                          'slug' => empty($params['slug']) ? '' : $params['slug'],
                          'page' => empty($params['page']) ? 0 : $params['page'],
                          'view' => 'Slideshow',
                          'image' => $image->id),
                    $date),
                    true);

            $params = array('gallery' => $view->gallery->id, 'url' => $url);

            header('Location: ' . Horde_Util::addParameter(Horde::applicationUrl('disclamer.php'), $params, null, false));
            exit;
        }

       // Check password
        if ($view->gallery->hasPasswd()) {
           $date = Ansel::getDateParameter(
                array('year' => isset($view->_params['year']) ? $view->_params['year'] : 0,
                      'month' => isset($view->_params['month']) ? $view->_params['month'] : 0,
                      'day' => isset($view->_params['day']) ? $view->_params['day'] : 0));

                $url = Ansel::getUrlFor('view', array_merge(
                    array('gallery' => $view->gallery->id,
                          'slug' => empty($params['slug']) ? '' : $params['slug'],
                          'page' => empty($params['page']) ? 0 : $params['page'],
                          'view' => 'Slideshow',
                          'image' => $image->id),
                    $date),
                    true);

            $params = array('gallery' => $view->gallery->id, 'url' => $url);

            header('Location: ' . Horde_Util::addParameter(Horde::applicationUrl('protect.php'), $params, null, false));
            exit;
        }


        Horde::addScriptFile('effects.js', 'horde', true);
        Horde::addScriptFile('stripe.js', 'horde', true);
        Horde::addScriptFile('slideshow.js', 'ansel', true);

        return $view;
    }

    /**
     * Get the title for this view.
     *
     * @return string  The title.
     */
    function getTitle()
    {
        return $this->image->filename;
    }

    /**
     * Get the HTML representing this view.
     *
     * @return string  The HTML.
     */
    function html()
    {
        global $browser, $conf, $prefs, $registry;

        if (is_a($this->gallery, 'PEAR_Error')) {
            echo htmlspecialchars($this->gallery->getMessage());
            return;
        }
        $page = Horde_Util::getFormData('page', 0);
        $galleryId = $this->gallery->id;
        $imageId = $this->image->id;
        $galleryOwner = $this->gallery->get('owner');
        $style = $this->gallery->getStyle();

        /* Get date info to pass along the links */
        if (!empty($this->_params['year'])) {
            $date = Ansel::getDateParameter(
                array('year' => $this->_params['year'],
                      'month' => $this->_params['month'],
                      'day' => $this->_params['day']));
        } else {
            $date = array();
        }

        /* Get the index of the starting image */
        $imageList = $this->gallery->listImages();

        $style = $this->gallery->getStyle();
        $revList = array_flip($imageList);
        $imageIndex = $revList[$imageId];
        if (isset($imageList[$imageIndex - 1])) {
            $prev = $imageList[$imageIndex - 1];
        } else {
            $prev = $imageList[count($imageList) - 1];
        }

        $ecardurl = Horde_Util::addParameter('img/ecard.php',
                                       array('gallery' => $galleryId,
                                             'image' => $imageId));
        $galleryurl = Horde_Util::addParameter('view.php', array_merge(
            array('gallery' => $galleryId,
                  'page' => $page),
            $date));
        $imageActionUrl = Horde_Util::addParameter('image.php', array_merge(
            array('gallery' => $galleryId,
                  'image' => $imageId,
                  'page' => $page),
            $date));
        $imageUrl = Ansel::getImageUrl($imageId, 'screen', false, $style['name']);

        ob_start();
        require ANSEL_TEMPLATES . '/view/slideshow.inc';
        return ob_get_clean();
    }

    function viewType()
    {
        return 'Slideshow';
    }

}
