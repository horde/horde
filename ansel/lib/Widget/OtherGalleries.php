<?php
/**
 * Ansel_Widget_OtherGalleries:: class to display a widget containing mini
 * thumbnails and links to other galleries owned by the same user as the
 * currently viewed image/gallery.
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
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
     * @param Ansel_View $view  The view we are attaching to
     */
    public function attach($view)
    {
        parent::attach($view);

        $owner = $this->_view->gallery->getOwner();
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
        if ($GLOBALS['conf']['ansel_cache']['usecache'] &&
            ($widget = $GLOBALS['cache']->get('Ansel_OtherGalleries' . $this->_view->gallery->get('owner'))) !== false) {
            return $widget;
        }

        $widget = $this->_htmlBegin() . $this->_getOtherGalleries() . $this->_htmlEnd();
        if ($GLOBALS['conf']['ansel_cache']['usecache']) {
            $GLOBALS['cache']->set('Ansel_OtherGalleries' . $this->_view->gallery->get('owner'), $widget);
        }

        return $widget;
    }

    /**
     * Build the HTML for the other galleries widget content.
     *
     * @TODO Allow the sort order and maybe the count of galleries returned
     *       to be configurable via the params array.
     *
     * @return string  The HTML
     */
    protected function _getOtherGalleries()
    {
        $owner = $this->_view->gallery->get('owner');

        /* Set up the tree */
        $tree = Horde_Tree::singleton('otherAnselGalleries_' . md5($owner), 'javascript');
        $tree->setOption(array('class' => 'anselWidgets'));
        $gals = $GLOBALS['ansel_storage']->listGalleries(Horde_Perms::SHOW, $owner,
                                                         null, true, 0, 0,
                                                         'name', 0);

        $html = '<div style="display:'
            . (($GLOBALS['prefs']->getValue('show_othergalleries')) ? 'block' : 'none')
            . ';background:' . $this->_style['background']
            . ';width:100%;max-height:300px;overflow:auto;" id="othergalleries" >';

        //@TODO - for now, Horde_Share will still return PEAR_Error,
        //        this will be fixed when Ansel_Gallery is refactored.
        foreach($gals as $gal) {
            if (is_a($gal, 'PEAR_Error')) {
                Horde::logMessage($gal, __FILE__, __LINE__, PEAR_LOG_ERR);
                return '';
            }

            $parents = $gal->get('parents');
            if (empty($parents)) {
                $parent = null;
            } else {
                $parents = explode(':', $parents);
                $parent = array_pop($parents);
            }

            $img = Ansel::getImageUrl($gal->getDefaultImage('ansel_default'), 'mini', true);
            $link = Ansel::getUrlFor('view', array('gallery' => $gal->id,
                                                   'slug' => $gal->get('slug'),
                                                   'view' => 'Gallery'),
                                     true);

            $tree->addNode($gal->id, $parent, $gal->get('name'), null,
                           ($gal->id == $this->_view->gallery->id),
                           array('icon' => $img, 'icondir' => '', 'url' => $link));
        }
        ob_start();
        $imple = Horde_Ajax_Imple::factory(array('ansel', 'ToggleOtherGalleries'), array('bindTo' => 'othergalleries'));
        $imple->attach();

        $tree->sort('label');
        $tree->renderTree();
        $html .= ob_get_clean();
        $html .= '</div>';
        $selfurl = Horde::selfUrl(true, true);
        $html .=  '<div class="control"><a href="'
                 . Horde_Util::addParameter($selfurl, 'actionID',
                                     'show_actions')
                 . '" id="othergalleries-toggle" class="'
                 . (($GLOBALS['prefs']->getValue('show_othergalleries'))
                 ? 'hide'
                 : 'show') . '">&nbsp;</a></div>' . "\n";



        return $html;
    }
}
