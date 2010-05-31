<?php
/**
 * Horde_Widget_ImageFaces:: class to display a widget containing mini
 * thumbnails of faces in the image.
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * @author Duck <duck@obala.net>
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 *
 * @package Ansel
 */
class Ansel_Widget_ImageFaces extends Ansel_Widget_Base
{
    /**
     * The views this Widget may appear in
     *
     * @var array
     */
    private $_supported_views = array('Image');

    /**
     * Constructor
     *
     * @param array $params  Any parameters for this widget
     * @return Ansel_Widget_ImageFaces
     */
    public function __construct($params)
    {
        parent::__construct($params);
        $this->_title = _("People in this photo");
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
     * Helper function for getting faces for this image.
     *
     * @return string  The HTML
     */
    protected function _getFaceNames()
    {
        $faces = Ansel_Faces::factory();

        // Check for existing faces for this image.
        $html = '';
        $images = $faces->getImageFacesData($this->_view->resource->id, true);

        // Generate the top ajax action links and attach the edit actions. Falls
        // back on going to the find all faces in gallery page if no js...
        // although, currently, *that* page requires js as well so...
        // TODO: A way to 'close', or go back to, the normal widget view.
        if ($this->_view->gallery->hasPermission(Horde_Auth::getAuth(), Horde_Perms::EDIT)) {
            $link_text = (empty($images) ? _("Find faces") : _("Edit faces"));
            $html .= '<a id="edit_faces" href="' . Horde_Util::addParameter(Horde::applicationUrl('faces/gallery.php'), 'gallery', $this->_view->gallery->id)
                    . '" class="widget">' . $link_text . '</a> | '
                    . Horde::link(Horde::applicationUrl(Horde_Util::addParameter('faces/custom.php', array('image' => $this->_view->resource->id, 'url' => $this->_params['selfUrl']))),'', 'widget')
                    . _("Manual face selection") . '</a>';

            // Attach the ajax edit actions
            Horde::startBuffer();
            $imple = Horde_Ajax_Imple::factory(
                array('ansel', 'EditFaces'),
                array('image_id' => $this->_view->resource->id,
                      'domid' => 'edit_faces',
                      'selfUrl' => $this->_params['selfUrl']));

            $imple->attach();
            $html .= Horde::endBuffer();
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
        Horde::addScriptFile('imagefaces.js', 'ansel');

        return $html;
    }

}
