<?php
/**
 * The Ansel_View_Slideshow:: class wraps display of the gallery slideshow.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_View_Slideshow extends Ansel_View_Image
{

    protected function _includeViewSpecificScripts()
    {
        $GLOBALS['page_output']->addScriptFile('slideshow.js');
        $GLOBALS['page_output']->addScriptFile('views/slideshow.js');
    }

    protected function _html()
    {
        global $registry, $prefs;

        $view = $this->_getView();
        $view->hasEdit = $this->gallery->hasPermission($registry->getAuth(), Horde_Perms::EDIT);
        $view->hasDelete = $this->gallery->hasPermission($registry->getAuth(), Horde_Perms::DELETE);
        $view->urls = $this->_urls;

        $imageIndex = $this->_revList[$this->resource->id];
        $js = 'SlideController.initialize(' . self::json($this->gallery, array('view_links' => true)) . ','
            . $imageIndex . ', "' . $registry->get('webroot') . '", ' . $this->gallery->id . ', "");';
        $GLOBALS['page_output']->addInlineScript($js, true);

        return $view->render('slideshow');
    }

    public function viewType()
    {
        return 'Slideshow';
    }

}
