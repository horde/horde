<?php
/**
 * Horde_Widget_GalleryFaces:: class to display a widget containing mini
 * thumbnails of faces in the gallery.
 *
 * $Horde: ansel/lib/Widget/GalleryFaces.php,v 1.6 2009/07/08 18:28:46 slusarz Exp $
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * @author Duck <duck@obala.net>
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 *
 * @package Ansel
 */
class Ansel_Widget_GalleryFaces extends Ansel_Widget {

    /**
     * @TODO
     *
     * @var unknown_type
     */
    var $_supported_views = array('Gallery');

    /**
     * Constructor
     *
     * @param array $params  Any parameters for this widget
     * @return Ansel_Widget_ImageFaces
     */
    function Ansel_Widget_GalleryFaces($params)
    {
        parent::Ansel_Widget($params);
        $this->_title = _("People in this gallery");
    }

    /**
     * Return the HTML representing this widget.
     *
     * @return string  The HTML for this widget.
     */
    function html()
    {   if ($GLOBALS['conf']['faces']['driver']) {
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
    function _getFaceNames()
    {
        if ($this->_view->resource->get('faces')) {
            return '<div id="faces_widget_content">'
                    . '<br /><em>' . _("No faces found") . '</em></div>';
        }

        require_once ANSEL_BASE . '/lib/Faces.php';
        $faces = Ansel_Faces::factory();
        if (is_a($faces, 'PEAR_Error')) {
            return $faces->getMessage();
        }

        // Check for existing faces for this gallery.
        $html = '<div style="display: block'
            . ';background:' . $this->_style['background']
            . ';width:100%;max-height:300px;overflow:auto;" id="faces_widget_content" >';

        $images = $faces->getGalleryFaces($this->_view->resource->id);
        if (is_a($images, 'PEAR_Error')) {
            return $images->getMessage();
        }

        if ($this->_view->gallery->hasPermission(Horde_Auth::getAuth(), PERMS_EDIT)) {
            $link_text = (empty($images) ? _("Find faces") : _("Edit faces"));
            $html .= '<a id="edit_faces" href="' . Horde_Util::addParameter(Horde::applicationUrl('faces/gallery.php'), 'gallery', $this->_view->gallery->id)
                    . '" class="widget">' . $link_text . '</a>';
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
