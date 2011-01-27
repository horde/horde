<?php
/**
 * The Turba_View_List:: class provides an interface for objects that
 * visualize Turba_List objects.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Jon Parise <jon@csh.rit.edu>
 * @package Turba
 */
/**
 * Skips objects whose name does not start with the specified letter
 */
class Turba_View_List_AlphaFilter
{
    protected $_alpha;
    protected $_format;

    public function __construct($alpha)
    {
        $this->_alpha = Horde_String::lower($alpha);
        $this->_format = $GLOBALS['prefs']->getValue('name_sort');
    }

    public function skip($ob)
    {
        $name = Turba::formatName($ob, $this->_format);
        if ($this->_alpha != '*' &&
            Horde_String::lower(substr($name, 0, 1)) != $this->_alpha) {
            return true;
        }

        return false;
    }

}