<?php
/**
 * The Ansel_Style:: class is responsible for holding information about a
 * single Ansel style.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 *
 * @package Ansel
 */
class Ansel_Style
{
    /**
     * Holds the style definition. Currently supported properties are:
     * <pre>
     *   'thumbstyle'    -  The ImageGenerator to use for thumbnails,
     *                      e.g. PolaroidThumb or RoundedThumb
     *   'background'    -  The background color of the view area. If needed,
     *                      generated images will contain this as their
     *                      background color.
     *   'gallery_view'  -  The GalleryRenderer type to use for the gallery
     *                      view, e.g. GalleryLightbox or Gallery.
     *   'widgets'       -  An array of widgets and their configuration values
     *                      to display on the gallery view.
     *                      e.g. Array('Geotag' => array(),
     *                                 'Tags' => array('view' => 'gallery'))
     *   'width'         - Optional width of generated thumbnails.
     *   'height'        - Option height of generated thumbnails.
     *   'image_widgets' - @TODO: not yet implemented.
     * </pre>
     *
     * @var array
     */
    protected $_properties;

    /**
     * Work around issue with arrays and __get
     */
    public $widgets = array();

    public function __construct($properties)
    {
        $widgets = !empty($properties['widgets']) ? $properties['widgets'] : array();
        unset($properties['widgets']);
        $properties['widgets'] = null;
        $this->widgets = array_merge(array('Actions' => array()), $widgets);

        $this->_properties = array_merge(
            array(
                'gallery_view' => 'Gallery',
                'background' => 'none'),
            $properties);
    }

    /**
     * Return if this style requires PNG support in the browser. Assumes that
     * any thumbstyle other than the traditional "Thumb", withOUT a background
     * is considered to requre PNG support in the browser.
     *
     * @return boolean
     */
    public function requiresPng()
    {
        return ($this->_properties['thumbstyle'] != 'Thumb' && $this->_properties['background'] == 'none');
    }

    public function getHash($view)
    {
        if ($view != 'screen' && $view != 'mini' && $view != 'full') {
            $view = md5($this->thumbstyle . '.' . $this->background . (!empty($this->width) ? $this->width : '') . (!empty($this->height) ? $this->height : ''));
        }

        return $view;
    }

    public function __get($property)
    {
        if ($property == 'keyimage_type') {
            // Force the same type of effect for key image/stacks if available
            $class = $this->_properties['thumbstyle'] . 'Stack';
            if (!class_exists('Ansel_ImageGenerator_' . $class)) {
                $class = 'Thumb';
            }

            return $class;
        }

        return !empty($this->_properties[$property]) ? $this->_properties[$property] : null;
    }

    public function __set($property, $value)
    {
        switch ($property) {
        case 'thumbstyle':
        case 'background':
        case 'width':
        case 'height':
            $this->_properties[$property] = $value;
            break;
        default:
            throw new Ansel_Exception('Invalid property');
        }
    }

    public function __isset($property)
    {
        return !empty($property);
    }

    /**
     * HACK - not sure how to upgrade the serialized Ansel_Style objects in the
     * stored galleries... the data is protected, so unserializing it wouldn't
     * give access to the needed information to move it.
     */
    public function __wakeup()
    {
       if (!empty($this->_properties['widgets'])) {
           $this->widgets = $this->_properties['widgets'];
           unset($this->_properties['widgets']);
       }
    }

}

