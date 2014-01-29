<?php
/**
 * Horde_Widget_ImageFaces:: class to display a widget containing mini
 * thumbnails of faces in the image.
 *
 * Copyright 2008-2014 Horde LLC (http://www.horde.org/)
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
    protected $_supported_views = array('Image');

    /**
     * Attach widget to supplied view.
     *
     * @param Ansel_View_Base $view
     *
     * @return boolean
     */
    public function attach(Ansel_View_Base $view)
    {
        if (empty($GLOBALS['conf']['faces']['driver'])) {
            return false;
        }
        $GLOBALS['page_output']->addScriptFile('imagefaces.js');
        return parent::attach($view);
    }

    /**
     * Return the HTML representing this widget.
     *
     * @return string  The HTML for this widget.
     */
    public function html()
    {
        $view = $GLOBALS['injector']->getInstance('Horde_View');
        $view->addTemplatePath(ANSEL_TEMPLATES . '/widgets');
        $view->title = _("People in this Photo");
        $view->background = $this->_style->background;
        $view->hasEdit = $this->_view->gallery->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT);

        $faces = $GLOBALS['injector']->getInstance('Ansel_Faces');
        $view->images = $faces->getImageFacesData($this->_view->resource->id, true);

        if ($view->hasEdit) {
            $view->editUrl = strval(Horde::url('faces/gallery.php')->add('gallery', $this->_view->gallery->id));
            $view->manualUrl = strval(Horde::url('faces/custom.php')->add(array('image' => $this->_view->resource->id, 'url' => $this->_params['selfUrl'])));

            // Attach the ajax edit actions
            $GLOBALS['injector']
                ->getInstance('Horde_Core_Factory_Imple')
                ->create(
                    'Ansel_Ajax_Imple_EditFaces',
                    array('id' => 'edit_faces', 'image_id' => $this->_view->resource->id)
                );
        }

        $faces_js = '';
        // Iterate over all the found faces and build the tiles.
        foreach ($view->images as $face) {
            // Attach events to the face tile for showing the overlay
            $faces_js .= '$(\'facediv' . $face['face_id'] . '\').observe(\'mouseover\', function() {showFace(' . $face['face_id'] . ')});'
                . '$(\'facediv' . $face['face_id'] . '\').observe(\'mouseout\', function() {hideFace(' . $face['face_id'] . ')});'
                . '$(\'face' . $face['face_id'] . '\').firstDescendant().observe(\'mouseover\', function() {showFace(' . $face['face_id'] . ')});'
                . '$(\'face' . $face['face_id'] . '\').firstDescendant().observe(\'mouseout\', function() {hideFace(' . $face['face_id'] . ')});';
        }
        $GLOBALS['page_output']->addInlineScript($faces_js, 'dom');

        return $view->render('imagefaces');
    }

}
