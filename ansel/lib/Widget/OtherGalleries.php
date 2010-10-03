<?php
/**
 * Ansel_Widget_OtherGalleries:: class to display a widget containing mini
 * thumbnails and links to other galleries owned by the same user as the
 * currently viewed image/gallery.
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_Widget_OtherGalleries extends Ansel_Widget_Base
{
    /**
     * Override the parent class' attach method and set the owner in the
     * title string.
     *
     * @param Ansel_View_Base $view  The view we are attaching to
     */
    public function attach(Ansel_View_Base $view)
    {
        parent::attach($view);
        $owner = $this->_view->gallery->getIdentity();
        $name = $owner->getValue('fullname');
        if (!$name) {
            $name = $this->_view->gallery->get('owner');
        }
        $this->_title = sprintf(_("%s's Galleries"), $name);

        return true;
    }

    /**
     * Build the HTML for this widget.
     *
     * @return string  The HTML representing this widget.
     */
    public function html()
    {
        // The cache breaks this block for some reason, disable until figured
        // out.
//        if ($GLOBALS['conf']['ansel_cache']['usecache']) {
//            $widget = $GLOBALS['injector']->getInstance('Horde_Cache')->get('Ansel_OtherGalleries' . $this->_view->gallery->get('owner'));
//            if ($widget !== false) {
//                return $widget;
//            }
//        }

        $widget = $this->_htmlBegin() . $this->_getOtherGalleries() . $this->_htmlEnd();
//        if ($GLOBALS['conf']['ansel_cache']['usecache']) {
//            $GLOBALS['injector']->getInstance('Horde_Cache')->set('Ansel_OtherGalleries' . $this->_view->gallery->get('owner'), $widget);
//        }

        return $widget;
    }

    /**
     * Build the HTML for the other galleries widget content.
     *
     * @return string  The HTML
     */
    protected function _getOtherGalleries()
    {
        $owner = $this->_view->gallery->get('owner');

        /* Set up the tree */
        $tree = $GLOBALS['injector']->getInstance('Horde_Tree')->getTree('otherAnselGalleries_' . md5($owner), 'Javascript', array('class' => 'anselWidgets'));

        try {
            $galleries = $GLOBALS['injector']->getInstance('Ansel_Storage')
                    ->getScope()
                    ->listGalleries(array('filter' => $owner));
        } catch (Ansel_Exception $e) {
            Horde::logMessage($e, 'ERR');
            return '';
        }

        $html = '<div style="display:'
            . (($GLOBALS['prefs']->getValue('show_othergalleries')) ? 'block' : 'none')
            . ';background:' . $this->_style->background
            . ';width:100%;max-height:300px;overflow:auto;" id="othergalleries" >';

        foreach ($galleries as $gallery) {
            $parents = $gallery->get('parents');
            if (empty($parents)) {
                $parent = null;
            } else {
                $parents = explode(':', $parents);
                $parent = array_pop($parents);
            }

            $img = (string)Ansel::getImageUrl($gallery->getKeyImage(Ansel::getStyleDefinition('ansel_default')), 'mini', true);
            $link = Ansel::getUrlFor('view', array('gallery' => $gallery->id,
                                                   'slug' => $gallery->get('slug'),
                                                   'view' => 'Gallery'),
                                     true);

            $tree->addNode($gallery->id, $parent, $gallery->get('name'), null,
                           ($gallery->id == $this->_view->gallery->id),
                           array('icon' => $img, 'url' => $link));
        }

        Horde::startBuffer();
        $GLOBALS['injector']->getInstance('Horde_Core_Factory_Imple')->create(array('ansel', 'ToggleOtherGalleries'), array(
            'bindTo' => 'othergalleries'
        ));

        $tree->sort('label');
        $tree->renderTree();
        $html .= Horde::endBuffer();
        $html .= '</div>';
        $selfurl = Horde::selfUrl(true, true);
        $html .= '<div class="control">'
              . $selfurl->add('actionID', 'show_actions')->link(
                        array('id' => 'othergalleries-toggle',
                              'class' => ($GLOBALS['prefs']->getValue('show_othergalleries') ? 'hide' : 'show')))
              . '&nbsp;</a></div>';

        return $html;
    }

}
