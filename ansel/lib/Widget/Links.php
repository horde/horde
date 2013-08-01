<?php
/**
 * Ansel_Widget_links:: class to wrap the display of various feed links etc...
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_Widget_Links extends Ansel_Widget_Base
{
    public function __construct($params)
    {
        parent::__construct($params);
    }

    public function html()
    {
        global $registry;

        $view = $GLOBALS['injector']->createInstance('Horde_View');
        $view->addTemplatePath(ANSEL_TEMPLATES . '/widgets');

        $view->owner = $this->_view->gallery->get('owner');
        $view->userfeedurl = Ansel::getUrlFor('rss_user', array('owner' => $view->owner));
        $view->slug = $this->_view->gallery->get('slug');
        $view->galleryname = $this->_view->gallery->get('name');
        $view->galleryfeedurl = Ansel::getUrlFor('rss_gallery', array('gallery' => $this->_view->gallery->id, 'slug' => $view->slug));
        $view->title = _("Links");

        /* Embed html */
        if (empty($this->_view->_params['image_id'])) {
            /* Gallery view */
            $params = array('count' => 10);
            if (!empty($slug))  {
                $params['gallery_slug'] = $slug;
            } else {
                $params['gallery_id'] = $this->_view->gallery->id;
            }
        } else {
            // This is an image view
            $params = array(
                'thumbsize' => 'screen',
                'images' => $this->_view->_params['image_id'],
                'count' => 10);
        }
        $view->embed = Ansel::embedCode($params);

        return $view->render('links');
    }
}
