<?php
/**
 * The Ansel_View_Slideshow:: class wraps display of the gallery slideshow.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_View_Slideshow extends Ansel_View_Base
{
    /**
     * @static
     *
     * @return Ansel_View_Slidshow  The view object.
     *
     * @TODO use exceptions from the constructor instead of static
     * instance-getting.
     */
    public function __construct($params = array())
    {
        parent::__construct($params);
        if (empty($params['image_id'])) {
            $image_id = Horde_Util::getFormData('image');
        } else {
            $image_id = $params['image_id'];
        }
        $image = $GLOBALS['ansel_storage']->getImage($image_id);
        $this->gallery = $this->getGallery($image->gallery);
        $this->image = $image;

        // Check user age
        if (!$this->gallery->isOldEnough()) {
           $date = Ansel::getDateParameter(
                array('year' => !empty($this->_params['year']) ? $this->_params['year'] : 0,
                      'month' => !empty($this->_params['month']) ? $this->_params['month'] : 0,
                      'day' => !empty($this->_params['day']) ? $this->_params['day'] : 0));

                $url = Ansel::getUrlFor('view', array_merge(
                    array('gallery' => $this->gallery->id,
                          'slug' => empty($params['slug']) ? '' : $params['slug'],
                          'page' => empty($params['page']) ? 0 : $params['page'],
                          'view' => 'Slideshow',
                          'image' => $image->id),
                    $date),
                    true);

            $params = array('gallery' => $this->gallery->id, 'url' => $url);

            header('Location: ' . Horde_Util::addParameter(Horde::applicationUrl('disclamer.php'), $params, null, false));
            exit;
        }

       // Check password
        if ($this->gallery->hasPasswd()) {
           $date = Ansel::getDateParameter(
                array('year' => isset($this->_params['year']) ? $this->_params['year'] : 0,
                      'month' => isset($this->_params['month']) ? $this->_params['month'] : 0,
                      'day' => isset($this->_params['day']) ? $this->_params['day'] : 0));

                $url = Ansel::getUrlFor('view', array_merge(
                    array('gallery' => $this->gallery->id,
                          'slug' => empty($params['slug']) ? '' : $params['slug'],
                          'page' => empty($params['page']) ? 0 : $params['page'],
                          'view' => 'Slideshow',
                          'image' => $image->id),
                    $date),
                    true);

            $params = array('gallery' => $this->gallery->id, 'url' => $url);

            header('Location: ' . Horde_Util::addParameter(Horde::applicationUrl('protect.php'), $params, null, false));
            exit;
        }


        Horde::addScriptFile('effects.js', 'horde', true);
        Horde::addScriptFile('stripe.js', 'horde', true);
        Horde::addScriptFile('slideshow.js', 'ansel', true);
    }

    /**
     * Get the title for this view.
     *
     * @return string  The title.
     */
    public function getTitle()
    {
        return $this->resource->filename;
    }

    /**
     * Get the HTML representing this view.
     *
     * @return string  The HTML.
     */
    public function html()
    {
        global $browser, $conf, $prefs, $registry;

        $page = Horde_Util::getFormData('page', 0);
        $galleryId = $this->gallery->id;
        $imageId = $this->resource->id;
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

    public function viewType()
    {
        return 'Slideshow';
    }

}
