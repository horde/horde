<?php
/**
 * Copyright 2008-2014 Horde LLC (http://www.horde.org/)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
/**
 * Ansel_Widget_OtherGalleries:: class to display a widget containing mini
 * thumbnails and links to other galleries owned by the same user as the
 * currently viewed image/gallery.
 *
 * Copyright 2008-2014 Horde LLC (http://www.horde.org/)
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
        if (parent::attach($view)) {
            $owner = $this->_view->gallery->getIdentity();
            $name = $owner->getValue('fullname');
            if (!$name) {
                $name = $this->_view->gallery->get('owner');
            }
            $this->_title = sprintf(_("%s's Galleries"), $name);
        } else {
            return false;
        }

        return true;
    }

    /**
     * Build the HTML for this widget.
     *
     * @return string  The HTML representing this widget.
     */
    public function html()
    {
        $view = $GLOBALS['injector']->getInstance('Horde_View');
        $view->addTemplatePath(ANSEL_TEMPLATES . '/widgets');
        $view->title = $this->_title;
        $view->background = $this->_style->background;
        $view->toggle_url = Horde::selfUrl(true, true)
            ->add('actionID', 'show_othergalleries')
            ->link(array(
                'id' => 'othergalleries-toggle',
                'class' => ($GLOBALS['prefs']->getValue('show_othergalleries') ? 'hide' : 'show')
            )
        );
        $this->_getOtherGalleries($view);

        return $view->render('othergalleries');
    }

    /**
     * Build the HTML for the other galleries widget content.
     *
     * @param Horde_View $view  The view object.
     */
    protected function _getOtherGalleries(&$view)
    {
        $owner = $this->_view->gallery->get('owner');

        // Set up the tree
        $tree = $GLOBALS['injector']
            ->getInstance('Horde_Core_Factory_Tree')
            ->create('otherAnselGalleries_' . md5($owner), 'Javascript', array('class' => 'anselWidgets'));

        try {
            $galleries = $GLOBALS['injector']
                ->getInstance('Ansel_Storage')
                ->listGalleries(array('attributes' => $owner));
        } catch (Ansel_Exception $e) {
            Horde::logMessage($e, 'ERR');
            return;
        }

        foreach ($galleries as $gallery) {
            $parents = $gallery->get('parents');
            if (empty($parents)) {
                $parent = null;
            } else {
                $parents = explode(':', $parents);
                $parent = array_pop($parents);
            }

            $img = (string)Ansel::getImageUrl(
                $gallery->getKeyImage(Ansel::getStyleDefinition('ansel_default')),
                'mini',
                true);
            $link = Ansel::getUrlFor(
                'view',
                array('gallery' => $gallery->id,
                      'slug' => $gallery->get('slug'),
                      'view' => 'Gallery'),
                true);

            $tree->addNode(array(
                'id' => $gallery->id,
                'parent' => $parent,
                'label' => $gallery->get('name'),
                'expanded' => $gallery->id == $this->_view->gallery->id,
                'params' => array('icon' => $img, 'url' => $link)
            ));
        }

        Horde::startBuffer();
        $tree->sort('label');
        $tree->renderTree();
        $view->tree = Horde::endBuffer();

        $GLOBALS['injector']
            ->getInstance('Horde_Core_Factory_Imple')
            ->create(
                'Ansel_Ajax_Imple_ToggleOtherGalleries',
                array('id' => 'othergalleries-toggle'));

    }

}
