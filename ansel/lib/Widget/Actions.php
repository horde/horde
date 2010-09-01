<?php
/**
 * Ansel_Widget_Actions:: class to wrap the display of gallery actions
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @TODO: Use Horde_View for html template output.
 * 
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_Widget_Actions extends Ansel_Widget_Base
{
    /**
     * @var string
     */
    protected $_supported_views = array('Gallery');

    /**
     * @param array $params
     */
    public function __construct($params)
    {
        $this->_title = _("Gallery Actions");
        parent::__construct($params);
    }

    /**
     *
     * @see ansel/lib/Widget/Ansel_Widget_Base#html()
     */
    public function html()
    {
        $html = $this->_htmlBegin();
        $id = $this->_view->gallery->id;
        $galleryurl = Horde::url('gallery.php')->add('gallery', $id);

        if ($this->_view->gallery->hasFeature('upload')) {
            $uploadurl = Horde::url('img/upload.php')->add(
                array('gallery' => $id,
                      'page' => !empty($this->_view->_params['page']) ? $this->_view->_params['page'] : 0));
        }

        $html .= '<ul style="list-style-type:none;">';
        if (empty($this->_params['hide_slideshow']) &&
            $this->_view->gallery->hasFeature('slideshow') &&
            $this->_view->gallery->countImages()) {

            /* Slideshow link */
            if (!empty($this->_params['slideshow_link'])) {
                $slideshow_url = str_replace(array('%i', '%g'),
                                             array(array_pop($this->_view->gallery->listImages(0, 1)), $id),
                                             urldecode($this->_params['slideshow_link']));
            } else {
                /* Get any date info the gallery has */
                $date = $this->_view->gallery->getDate();
                $slideshow_url = Horde::url('view.php')->add(
                    array_merge(array('gallery' => $id,
                                      'image' => array_pop($this->_view->gallery->listImages(0, 1)),
                                      'view' => 'Slideshow'),
                                $date));
            }
            $html .= '<li>' . $slideshow_url->link(array('class' => 'widget')) . Horde::img('slideshow_play.png', _("Start Slideshow")) . ' ' . _("Start Slideshow") . '</a></li>';
        }
        
        /* Upload and new subgallery Urls */
        if (!empty($uploadurl) && $this->_view->gallery->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
            $html .= '<li>' . $uploadurl->link(array('class' => 'widget')) . Horde::img('image_add.png') . ' ' . _("Upload photos") . '</a></li>';

            /* Subgalleries */
            if ($this->_view->gallery->hasFeature('subgalleries')) {
                $html .= '<li>' . $galleryurl->copy()->add('actionID', 'addchild')->link(array('class' => 'widget')) . Horde::img('add.png', '[icon]') . ' ' . _("Create a subgallery") . '</a></li>';
            }
        }
        $html .= '</ul>';
        $html .= $this->_getGalleryActions();

        $selfurl = Horde::selfUrl(true, true);
        $html .=  '<div class="control">'
                 . $selfurl->add('actionID', 'show_actions')->link(array('id' => 'gallery-actions-toggle', 'class' => ($GLOBALS['prefs']->getValue('show_actions') ? 'hide' : 'show')))
                 . '&nbsp;</a></div>' . "\n";

        $html .= $this->_htmlEnd();
        return $html;
    }

    /**
     * Helper function for generating the gallery actions selection widget.
     *
     * @return string  The HTML
     */
    protected function _getGalleryActions()
    {
        global $registry, $conf;

        $id = $this->_view->gallery->id;
        $galleryurl = Horde::url('gallery.php')->add('gallery', $id);
        $selfurl = Horde::selfUrl(true, false, true);
        $count = $this->_view->gallery->countImages();
        $date = $this->_view->gallery->getDate();

        $html = '<div style="display:' . (($GLOBALS['prefs']->getValue('show_actions')) ? 'block' : 'none') . ';" id="gallery-actions">';

        /* Attach the ajax action */
        Horde::startBuffer();
        $GLOBALS['injector']->getInstance('Horde_Ajax_Imple')->getImple(array('ansel', 'ToggleGalleryActions'), array(
            'bindTo' => 'gallery-actions'
        ));
        $html .= Horde::endBuffer();

        /* Buid the url parameters to the zip link */
        $view_params = array(
            'gallery' => $this->_view->gallery->id,
            'view' => 'Gallery',
            'slug' => $this->_view->gallery->get('slug'),
            'page' => (!empty($this->_view->_params['page']) ? $this->_view->_params['page'] : 0));

        /* Append the date information to the parameters if we need it */
        $view_params = array_merge($view_params, $date);

        $html .= '<ul style="list-style-type:none;">';

        /* Bookmark link */
        if ($registry->hasMethod('bookmarks/getAddUrl')) {
            $api_params = array(
                'url' => Ansel::getUrlFor('view', $view_params, true),
                'title' => $this->_view->gallery->get('name'));

            try {
                $url = new Horde_Url($registry->bookmarks->getAddUrl($api_params));
                $html .= '<li>' . $url->link(array('class' => 'widget')) . Horde::img(Horde_Themes::img('trean.png', 'trean')) . ' ' . _("Add to bookmarks") . '</a></li>';
            } catch (Horde_Exception $e) {}
        }

        /* Download as ZIP link */
        if (!empty($conf['gallery']['downloadzip']) &&
            $this->_view->gallery->canDownload() &&
            $count &&
            $this->_view->gallery->hasFeature('zipdownload')) {

            $zip_params = array_merge(array('actionID' => 'downloadzip'), $date);
            $html .= '<li>' . $galleryurl->copy()->add($zip_params)->link(array('class' => 'widget')) . Horde::img('mime/compressed.png') . ' ' .  _("Download as zip file") . '</a></li>';
        }

        /* Image upload, subgalleries, captions etc... */
        if ($this->_view->gallery->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
            /* Properties */
            $html .= '<li>' . $galleryurl->copy()->add(array('actionID' => 'modify', 'url' => $selfurl))->link(array('class' => 'widget')) . Horde::img('edit.png') . ' ' . _("Change properties") . '</a></li>';
            if ($count) {
                /* Captions */
                if ($this->_view->gallery->hasFeature('image_captions')) {
                    $params = array_merge(array('gallery' => $id), $date);
                    $html .= '<li>' . Horde::url('gallery/captions.php')->add($params)->link(array('class' => 'widget')) . Horde::img('text.png') . ' ' . _("Set captions") . ' ' . '</a></li>';
                }

                /* Sort */
                if ($this->_view->gallery->hasFeature('sort_images')) {
                    $sorturl = Horde::url('gallery/sort.php')->add(array_merge(array('gallery' => $id), $date));
                    $html .= '<li>' . $sorturl->copy()->add('actionId' , 'getOrder')->link(array('class' => 'widget')) . Horde::img('arrow_switch.png') . ' ' . _("Sort photos") . '</a></li>';
                }

                /* Regenerate Thumbnails */
                $html .= '<li>' . $galleryurl->copy()->add(array('actionID' => 'generateThumbs'))->link(array('class' => 'widget')) . Horde::img('reload.png') . ' ' . _("Reset all thumbnails") . '</a></li>';

                /* Regenerate all views  */
                $html .= '<li>' . $galleryurl->copy()->add(array('actionID' => 'deleteCache'))->link(array('class' => 'widget')) . Horde::img('reload.png') . ' ' . _("Regenerate all photo views") . '</a></li>';

                /* Find faces */
                if ($conf['faces']['driver'] && $this->_view->gallery->hasFeature('faces')) {
                    $html .= '<li>' . Horde::url('faces/gallery.php')->add(
                        array_merge($date,
                                    array('gallery' => $id, 'page' => (!empty($this->_view->_params['page']) ? $this->_view->_params['page'] : 0))))
                        ->link(array('class' => 'widget')) . Horde::img('user.png') . ' ' . _("Find faces") . '</a></li>';
                }

            } /* end if ($count) {} */

            if (Ansel::isAvailable('photo_stack') && $this->_view->gallery->hasFeature('stacks')) {
                $html .= '<li>' . $galleryurl->copy()->add(array('actionID' => 'generateDefault', 'url' => $selfurl))->link(array('class' => 'widget')) . Horde::img('reload.png') . ' ' . _("Reset default photo") . '</a></li>';
            }
        }

        if ($GLOBALS['registry']->getAuth() &&
            $this->_view->gallery->get('owner') == $GLOBALS['registry']->getAuth()) {
             $url = new Horde_Url('#');
             $html .= '<li>' . $url->link(array('class' => 'popup widget', 'onclick' => Horde::popupJs(Horde::url('perms.php'), array('params' => array('cid' => $this->_view->gallery->id), 'urlencode' => true)) . 'return false;')) . Horde::img('perms.png') . ' ' . _("Set permissions") . '</a></li>';
        } elseif (!empty($conf['report_content']['driver']) &&
            (($conf['report_content']['allow'] == 'authenticated' &&
            $registry->isAuthenticated()) ||
                   $conf['report_content']['allow'] == 'all')) {

            $reporturl = Horde::url('report.php')->add('gallery', $id);
            $html .= '<li>' . $reporturl->link(array('class' => 'widget')) . _("Report") . '</a></li>';
        }

        if ($this->_view->gallery->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::DELETE)) {
            $html .= '<li>' . $galleryurl->copy()->add('actionID', 'empty')->link(array('class' => 'widget')) . Horde::img('delete.png') . ' ' . _("Delete All Photos") . '</a></li>';
            $html .= '<li>' . $galleryurl->copy()->add('actionID', 'delete')->link(array('class' => 'widget')) . Horde::img('delete.png', 'horde') . ' ' . _("Delete Entire Gallery") . '</a></li>';
        }
        $html .= '</ul></div>';

        return $html;
    }
}
