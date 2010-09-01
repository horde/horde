<?php
/**
 * Ansel_Widget_OwnerFaces:: class to display a widget containing mini
 * thumbnails of faces that have been tagged by the gallery owner.
 *
 * @author Duck <duck@obala.net>
 * @package Ansel
 */
class Ansel_Widget_OwnerFaces extends Ansel_Widget_Base
{
    protected $_faces;
    protected $_count;
    protected $_owner;

    /**
     * Return the HTML representing this widget.
     *
     * @return string  The HTML for this widget.
     */
    public function html()
    {
        if (!$GLOBALS['conf']['faces']['driver']) {
            return '';
        }

        $this->_faces = $GLOBALS['injector']->getInstance('Ansel_Faces');
        $this->_owner = $this->_view->gallery->get('owner');
        try {
            $this->_count = $this->_faces->countOwnerFaces($this->_owner);
        } catch (Horde_Exception $e) {
            Horde::logMessage($e->getMessage(), 'ERR');
            $this->_count = 0;
        }
        if (empty($this->_count)) {
            return null;
        }

        $this->_title = Horde::url('faces/search/owner.php')->add('owner', $this->_owner)->link()
            . sprintf(_("People in galleries owned by %s (%d of %d)"), $this->_owner, min(12, $this->_count), number_format($this->_count))
            . '</a>';

        $html = $this->_htmlBegin();

        $results = $this->_faces->ownerFaces($this->_owner, 0, 12, true);
        $html .= '<div style="display: block'
            . ';background:' . $this->_style['background']
            . ';width:100%;max-height:300px;overflow:auto;" id="faces_widget_content" >';
        foreach ($results as $face_id => $face) {
            $facename = htmlspecialchars($face['face_name']);
            $html .= '<a href="' . Ansel_Faces::getLink($face) . '" title="' . $facename . '">'
                    . '<img src="' . $this->_faces->getFaceUrl($face['image_id'], $face_id, 'mini')
                    . '" style="padding-bottom: 5px; padding-left: 5px" alt="' . $facename . '" /></a>';
        }

        return $html . '</div>' . $this->_htmlEnd();
    }
}
