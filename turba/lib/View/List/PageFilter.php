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
  * Skips objects which are not on the current page
  */
class Turba_View_List_PageFilter
{
    protected $_min;
    protected $_max;
    protected $_count = 0;

    public function __construct($min, $max)
    {
        $this->_min = $min;
        $this->_max = $max;
    }

    public function skip($ob)
    {
        if ($this->_count++ < $this->_min) {
            return true;
        }

        return ($this->_count > $this->_max);
    }

}