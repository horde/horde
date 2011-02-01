<?php
/**
 * Abstract Ansel_View class for Ansel UI specific views.
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
abstract class Ansel_View_Ansel extends Ansel_View_Base
{
    /**
     * The ansel resource this view is for.
     * @TODO: visibility protected
     * @var mixed  Either an Ansel_Gallery or Ansel_Image
     */
    public $resource;

    /**
     * The gallery object (will be eq to $resource in a gallery view
     *
     * @TODO: visibility protected
     * @var Ansel_Gallery
     */
    public $gallery;

    /**
     * Collection of Ansel_Widgets to display in this view.
     *
     * @var array
     */
    protected $_widgets = array();

    /**
     * Add an Ansel_Widget to be displayed in this view.
     *
     * @param Ansel_Widget $widget  The Ansel_Widget to display
     */
    public function addWidget($widget)
    {
        $result = $widget->attach($this);
        if (!empty($result)) {
            $this->_widgets[] = $widget;
        }
    }

    /**
     * Output any widgets associated with this view.
     *
     */
    public function renderWidgets()
    {
        $this->_renderWidgets();
    }

    /**
     * Count the number of widgets we have attached.
     *
     * @return integer  The number of widgets attached to this view.
     */
    public function countWidgets()
    {
        return count($this->_widgets);
    }

    /**
     * Default widget rendering, can be overridden by any subclass.
     *
     */
    protected function _renderWidgets()
    {
        echo '<div class="anselWidgets">';
        foreach ($this->_widgets as $widget) {
            if ($widget->autoRender) {
                echo $widget->html();
                echo '<br />';
            }
        }
        echo '</div>';
    }

    abstract public function viewType();
    abstract public function getGalleryCrumbData();
    abstract public function getTitle();
    abstract public function html();
}