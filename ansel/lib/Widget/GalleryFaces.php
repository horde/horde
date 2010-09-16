<?php
/**
 * Horde_Widget_GalleryFaces:: class to display a widget containing mini
 * thumbnails of faces in the gallery.
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * @author Duck <duck@obala.net>
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 *
 * @package Ansel
 */
class Ansel_Widget_GalleryFaces extends Ansel_Widget_Base
{
    /**
     * Supported views for this widget
     *
     * @var array
     */
    protected $_supported_views = array('Gallery');

    /**
     * Constructor
     *
     * @param array $params  Any parameters for this widget
     * @return Ansel_Widget_ImageFaces
     */
    public function __construct($params)
    {
        parent::__construct($params);
        $this->_title = _("People in this gallery");
    }

    /**
     * Return the HTML representing this widget.
     *
     * @return string  The HTML for this widget.
     */
    public function html()
    {
        if ($GLOBALS['conf']['faces']['driver']) {
            $html = $this->_getFaceNames();
            return $this->_htmlBegin() . $html . $this->_htmlEnd();
        } else {
            return '';
        }
    }

    /**
     * Helper function for getting faces for this gallery.
     *
     * @return string  The HTML
     */
    protected function _getFaceNames()
    {
        if ($this->_view->resource->get('faces')) {
            return '<div id="faces_widget_content"><br /><em>' . _("No faces found") . '</em></div>';
        }

        $faces = $GLOBALS['injector']->getInstance('Ansel_Faces');

        // Check for existing faces for this gallery.
        $html = '<div style="display: block'
            . ';background:' . $this->_style->background
            . ';width:100%;max-height:300px;overflow:auto;" id="faces_widget_content" >';

        $images = $faces->getGalleryFaces($this->_view->resource->id);
        if ($this->_view->gallery->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
            $link_text = (empty($images) ? _("Find faces") : _("Edit faces"));
            $html .= Horde::url('faces/gallery.php')->add('gallery', $this->_view->gallery->id)->link(
                         array('id' => 'edit_faces',
                               'class' => 'widget'))
                  . $link_text . '</a>';
        }

        $faces_html = '<div id="faces-on-gallery">';

        // Iterate over all the found faces and build the tiles.
        shuffle($images);
        foreach ($images as $face_id => $face) {
            // Get the tile for this face
            $html .= Ansel_Faces::getFaceTile($face);
        }

        // Close up the nodes
        $html .= '</div></div>';

        return $html;
    }

}
