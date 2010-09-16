<?php
/**
 * The Ansel_Style:: class is responsible for holding information about a
 * single Ansel style.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 *
 * @package Ansel
 */
class Ansel_Style
{
    /**
     * Holds the style definition
     *
     * @var array
     */
    protected $_properties;

    public function __construct($properties)
    {
        $this->_properties = array_merge(array('gallery_view' => 'Gallery',
                                               'background' => 'none'),
                                        $properties);
    }

    /**
     * Return if this style requires PNG support in the browser.
     *
     * @return boolean
     */
    public function requiresPng()
    {
        return true;
    }

    public function getHash($view)
    {
        if ($view != 'screen' && $view != 'thumb' && $view != 'mini' &&
            $view != 'full') {

            $view = md5($this->thumbstyle . '.' . $this->background);
        }

        return $view;
    }

    public function &__get($property)
    {
        if ($property == 'keyimage_type') {
            // Force the same type of effect for key image/stacks if available
            $class = $this->_properties['thumbstyle'] . 'Stack';
            if (!class_exists('Ansel_ImageGenerator_' . $class)) {
                $class = 'Thumb';
            }

            return $class;
        }

        return $this->_properties[$property];
    }

    public function __isset($property)
    {
        return !empty($property);
    }

}

