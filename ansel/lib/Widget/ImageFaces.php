<?php
/**
 * Horde_Widget_ImageFaces:: class to display a widget containing mini
 * thumbnails of faces in the image.
 *
 * $Horde: ansel/lib/Widget/ImageFaces.php,v 1.29 2009/07/30 13:15:10 mrubinsk Exp $
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * @author Duck <duck@obala.net>
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 *
 * @package Ansel
 */
class Ansel_Widget_ImageFaces extends Ansel_Widget {

    /**
     * @TODO
     *
     * @var unknown_type
     */
    var $_supported_views = array('Image');

    /**
     * Constructor
     *
     * @param array $params  Any parameters for this widget
     * @return Ansel_Widget_ImageFaces
     */
    function Ansel_Widget_ImageFaces($params)
    {
        parent::Ansel_Widget($params);
        $this->_title = _("People in this photo");
    }

    /**
     * Return the HTML representing this widget.
     *
     * @return string  The HTML for this widget.
     */
    function html()
    {

        if ($GLOBALS['conf']['faces']['driver']) {
            $html = $this->_getFaceNames();
            return $this->_htmlBegin() . $html . $this->_htmlEnd();
        } else {
            return '';
        }
    }

    /**
     * Helper function for getting faces for this image.
     *
     * @return string  The HTML
     */
    function _getFaceNames()
    {
        require_once ANSEL_BASE . '/lib/Faces.php';
        $faces = Ansel_Faces::factory();
        if (is_a($faces, 'PEAR_Error')) {
            return $faces->getMessage();
        }

        // Check for existing faces for this image.
        $html = '';
        $images = $faces->getImageFacesData($this->_view->resource->id, true);
        if (is_a($images, 'PEAR_Error')) {
            return $images->getMessage();
        }

        // Generate the top ajax action links and attach the edit actions. Falls
        // back on going to the find all faces in gallery page if no js...
        // although, currently, *that* page requires js as well so...
        // TODO: A way to 'close', or go back to, the normal widget view.
        if ($this->_view->gallery->hasPermission(Horde_Auth::getAuth(), PERMS_EDIT)) {
            $link_text = (empty($images) ? _("Find faces") : _("Edit faces"));
            $html .= '<a id="edit_faces" href="' . Horde_Util::addParameter(Horde::applicationUrl('faces/gallery.php'), 'gallery', $this->_view->gallery->id)
                    . '" class="widget">' . $link_text . '</a> | '
                    . Horde::link(Horde::applicationUrl(Horde_Util::addParameter('faces/custom.php', array('image' => $this->_view->resource->id, 'url' => $this->_params['selfUrl']))),'', 'widget')
                    . _("Manual face selection") . '</a>';

            // Attach the ajax edit actions
            ob_start();
            $imple = Horde_Ajax_Imple::factory(
                array('ansel', 'EditFaces'),
                array('image_id' => $this->_view->resource->id,
                      'domid' => 'edit_faces',
                      'selfUrl' => $this->_params['selfUrl']));

            $imple->attach();
            $html .= ob_get_clean();
        }

        // Build the main content area of the widget
        $html .= '<div id="faces_widget_content">';
        if (empty($images)) {
            return $html .= '<br /><em>' . _("No faces found") . '</em></div>';
        }

        // Start the image overlay node to show the face rectangles
        $faces_html = '<div id="faces-on-image">';

        // Iterate over all the found faces and build the tiles.
        foreach ($images as $face_id => $face) {

            // Get the tile for this face
            $html .= Ansel_Faces::getFaceTile($face);

            // Build the overlay for the image
            $faces_html .= '<div id="facediv' . $face_id . '" class="face-div" style="'
                . 'width: ' . ($face['face_x2'] - $face['face_x1']) . 'px;'
                . ' margin-left: ' . $face['face_x1'] . 'px; '
                . ' height: ' . ($face['face_y2'] - $face['face_y1']) . 'px;'
                . ' margin-top: ' . $face['face_y1'] . 'px;" >'
                . '<div id="facedivname' . $face_id . '" class="face-div-name" style="display:none;">'
                . $face['face_name'] . '</div></div>' . "\n";

            // Attach events to the face tile for showing the overlay
            $faces_html .= '<script type = "text/javascript">';
            $faces_html .= '$(\'facediv' . $face_id . '\').observe(\'mouseover\', function() {showFace(' . $face_id . ')});'
                . '$(\'facediv' . $face_id . '\').observe(\'mouseout\', function() {hideFace(' . $face_id . ')});'
                . '$(\'face' . $face_id . '\').firstDescendant().observe(\'mouseover\', function() {showFace(' . $face_id . ')});'
                . '$(\'face' . $face_id . '\').firstDescendant().observe(\'mouseout\', function() {hideFace(' . $face_id . ')});'
                . "\n</script>\n";
        }

        // Close up the nodes
        $html .= $faces_html . '</div></div>';

        // Include the needed javascript
        Horde::addScriptFile('imagefaces.js', 'ansel', true);

        return $html;
    }

}
