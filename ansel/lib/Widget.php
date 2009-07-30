<?php
/**
 * Ansel_Widget:: class wraps the display of widgets to be displayed in various
 * Ansel_Views.
 *
 * $Horde: ansel/lib/Widget.php,v 1.10 2009/06/19 22:32:18 mrubinsk Exp $
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_Widget {

    /**
     * Any parameters this widget will need..
     *
     * @var array
     */
    var $_params = array();

    /**
     * Reference to the Ansel_View we are attaching to
     *
     * @var Ansel_View
     */
    var $_view;

    /**
     * Holds the style definition for the gallery this view is for
     * (or the image's parent gallery if this is for an image view).
     *
     * @var array
     */
    var $_style;

    /**
     * Title for this widget.
     *
     * @var string
     */
    var $_title;

    /**
     * Determine if this widget will be automatically rendered, or if it is
     * the calling code's responsibility to render it.
     *
     * @var string
     */
    var $_render = 'auto';

    /**
     * Factory method for creating Ansel_Widgets
     *
     * @param string $type   The type of widget to create.
     * @param array $params  Any parameters the widget needs.
     *
     * @return mixed Ansel_Widget object | PEAR_Error
     */
    function factory($type, $params = array())
    {
        $type = basename($type);
        $class = 'Ansel_Widget_' . $type;
        if (!class_exists($class)) {
            include dirname(__FILE__) . '/Widget/' . $type . '.php';
        }
        if (class_exists($class)) {
            $widget = new $class($params);
            return $widget;
        }

        return PEAR::raiseError(sprintf(_("Unable to load the definition of %s."), $class));
    }

    /**
     * Constructor
     *
     * @param array $params
     * @return Ansel_Widget
     */
    function Ansel_Widget($params)
    {
        $this->_params = array_merge($params, $this->_params);
        if (!empty($params['render'])) {
            $this->_render = $params['render'];
        }
    }

    /**
     * Attach this widget to the passed in view. Normally called
     * by the Ansel_View once this widget is added.
     *
     * @param Ansel_View $view  The view to attach to
     */
    function attach($view)
    {
        $this->_view = $view;

        if (!empty($this->_params['style'])) {
            $this->_style = Ansel::getStyleDefinition($this->_params['style']);
        } else {
            $this->_style = $view->gallery->getStyle();
        }

        return true;
    }

    /**
     * Get the HTML for this widget
     *
     * @abstract
     */
    function html()
    {
    }

    /**
     * Default HTML for the beginning of the widget.
     *
     * @return string
     */
    function _htmlBegin()
    {
        $html = '<div class="anselWidget" style="background-color:' . $this->_style['background'] .   ';">';
        $html .= '<h2 class="header tagTitle">' . $this->_title . '</h2>';
        return $html;
    }

    /**
     * Default HTML for the end of the widget.
     *
     * @return string
     */
    function _htmlEnd()
    {
        return '</div>';
    }


    /**
     * Determine if a particular view (Image, Gallery etc..) is supported
     * by this widget.
     *
     * @TODO
     * @param string $view  The view to check
     *
     * @return boolean
     */
    function isSupported($view)
    {
        return true;
    }

}
