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
    /**
     * const'r
     *
     */
    public function __construct($params = array())
    {
        parent::__construct($params);
        Horde::addScriptFile('slideshow.js', 'ansel', true);
    }

    protected function _html()
    {
        global $registry, $prefs;
        $imageIndex = $this->_revList[$this->resource->id];
        ob_start();
        require ANSEL_TEMPLATES . '/view/slideshow.inc';
        return ob_get_clean();

    }

    public function viewType()
    {
        return 'Slideshow';
    }

}
