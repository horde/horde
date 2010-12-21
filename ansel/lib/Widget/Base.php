<?php
/**
 * Ansel_Widget:: class wraps the display of widgets to be displayed in various
 * Ansel_Views.
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license http://www.fsf.org/copyleft/gpl.html GPL
 * @package Ansel
 */
abstract class Ansel_Widget_Base
{
    /**
     * Any parameters this widget will need..
     *
     * @var array
     */
    protected $_params = array();

    /**
     * Reference to the Ansel_View we are attaching to
     *
     * @var Ansel_View
     */
    protected $_view;

    /**
     * Holds the style definition for the gallery this view is for
     * (or the image's parent gallery if this is for an image view).
     *
     * @var array
     */
    protected $_style;

    /**
     * Title for this widget.
     *
     * @var string
     */
    protected $_title;

    /**
     * Determine if this widget will be automatically rendered, or if it is
     * the calling code's responsibility to render it.
     *
     * @var string
     */
    protected $_autoRender = true;

    /**
     * Constructor
     *   render
     *   style
     * @param array $params
     * @return Ansel_Widget
     */
    public function __construct($params)
    {
        $this->_params = $params;
        if (!empty($params['render'])) {
            $this->_autoRender = ($params['render'] == 'auto');
        }
    }

    /**
     * Attach this widget to the passed in view. Normally called
     * by the Ansel_View once this widget is added.
     *
     * @param Ansel_View $view  The view to attach to
     */
    public function attach($view)
    {
        $this->_view = $view;
        if (!empty($this->_params['style'])) {
            $this->_style = $this->_params['style'];
        } else {
            $this->_style = $view->gallery->getStyle();
        }

        return true;
    }

    public function __get($property)
    {
        switch ($property) {
        case 'autoRender':
            return $this->_autoRender;
        }
    }

    /**
     * Get the HTML for this widget
     */
    abstract public function html();

    /**
     * Default HTML for the beginning of the widget.
     *
     * @return string
     */
    protected function _htmlBegin()
    {
        $html = '<div class="anselWidget" style="background-color:' . $this->_style->background .   ';">';
        $html .= '<h2 class="header tagTitle">' . $this->_title . '</h2>';
        return $html;
    }

    /**
     * Default HTML for the end of the widget.
     *
     * @return string
     */
    protected function _htmlEnd()
    {
        return '</div>';
    }


    /**
     * Determine if a particular view (Image, Gallery etc..) is supported
     * by this widget.
     *
     * @param string $view  The view to check
     *
     * @return boolean
     */
    protected function isSupported($view)
    {
        return true;
    }

}
