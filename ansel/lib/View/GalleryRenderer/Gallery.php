<?php
/**
 * Ansel_View_GalleryRenderer_Gallery:: Class wraps display of the traditional
 * Gallery View.
 *
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_View_GalleryRenderer_Gallery extends Ansel_View_GalleryRenderer_Base
{

    public function __construct($view)
    {
        parent::__construct($view);
        $this->title = _("Standard Gallery");
    }

    /**
     * Perform any tasks that should be performed before the view is rendered.
     *
     */
    protected function _init()
    {
    }

    /**
     * Return the HTML representing this view.
     *
     * @return string  The HTML.
     */
    public function html()
    {
        global $conf, $prefs, $registry;

        $galleryOwner = $this->view->gallery->get('owner');
        $id = $this->view->gallery->getIdentity();
        $owner = $id->getValue('fullname');
        if (!$owner) {
            $owner = $galleryOwner;
        }

        if (empty($this->view->api)) {
            $option_edit = $this->view->gallery->hasPermission(
                $GLOBALS['registry']->getAuth(), Horde_Perms::EDIT);

            $option_select = $option_delete = $this->view->gallery->hasPermission(
                $GLOBALS['registry']->getAuth(), Horde_Perms::DELETE);

            $option_move = ($option_delete && $GLOBALS['injector']
                ->getInstance('Ansel_Storage')
                ->countGalleries($GLOBALS['registry']->getAuth(), array('perm' => Horde_Perms::EDIT)));

            $option_copy = ($option_edit && $GLOBALS['injector']
                ->getInstance('Ansel_Storage')
                ->countGalleries($GLOBALS['registry']->getAuth(), array('perm' => Horde_Perms::EDIT)));

            // See if we requested a show_actions change
            if (Horde_Util::getFormData('actionID', '') == 'show_actions') {
                $prefs->setValue('show_actions', (int)!$prefs->getValue('show_actions'));
            }
        }

        // Set up the pager
        $date_params = Ansel::getDateParameter(
            array('year' => !empty($this->view->year) ? $this->view->year : 0,
                  'month' => !empty($this->view->month) ? $this->view->month : 0,
                  'day' => !empty($this->view->day) ? $this->view->day : 0));

        $vars = Horde_Variables::getDefaultVariables();
        if (!empty($this->view->page)) {
            $vars->add('page', $this->view->page);
        }
        if (!empty($this->view->gallery_view_url)) {
            $pagerurl = new Horde_Url(str_replace(array('%g', '%s'), array($this->galleryId, $this->gallerySlug), urldecode($this->view->gallery_view_url)));
            $pagerurl->add($date_params);
        } else {
             // Build the pager url. Add the needed variables directly to the
             // url instead of passing it as a preserved variable to the pager
             // since the logic to build the URL is already in getUrlFor()
            $pager_params =  array_merge(
                array('gallery' => $this->galleryId,
                      'view' => 'Gallery',
                      'slug' => $this->view->gallery->get('slug')),
                $date_params);
            $pagerurl = Ansel::getUrlfor('view', $pager_params, true);
        }

        // See what callback to use to tweak the pager urls
        $params = array(
            'num' => $this->numTiles,
            'url' => $pagerurl,
            'perpage' => $this->perpage,
            'url_callback' => empty($this->view->urlCallback) ? null : $this->view->urlCallback,
        );
        $pager = new Horde_Core_Ui_Pager('page', $vars, $params);

        Horde::startBuffer();
        if (!empty($this->view->api)) {
            $prototypejs = new Horde_Script_File_JsDir('prototype.js', 'horde');
            echo $prototypejs->tag_full;
        }

        // Needed in the template files
        $tilesperrow = $prefs->getValue('tilesperrow');
        $cellwidth = round(100 / $tilesperrow);
        $count = 0;
        $action_links = array();
        $url = new Horde_Url('#');
        if ($GLOBALS['conf']['gallery']['downloadzip'] && $registry->getAuth()) {
            $action_links[] = $url->link(array('class' => 'widget', 'onclick.raw' => 'downloadSelected(); return false;')) . _("Download selected images") . '</a>';
        }
        if (!empty($option_edit)) {
            $action_links[] = $url->link(array('class' => 'widget', 'onclick.raw' => 'editDates(); return false;')) . _("Edit Dates") . '</a>';
        }
        if (!empty($option_delete)) {
            $action_links[] = $url->link(array('class' => 'widget', 'onclick.raw' => 'deleteSelected(); return false;')) . _("Delete") . '</a>';
        }
        if (!empty($option_move)) {
            $action_links[] = $url->link(array('class' => 'widget', 'onclick.raw' =>'moveSelected(); return false;')) . _("Move") . '</a>';
        }
        if (!empty($option_copy)) {
            $action_links[] = $url->link(array('class' => 'widget', 'onclick.raw' => 'copySelected(); return false;')) . _("Copy") . '</a>';
        }
        $GLOBALS['page_output']->addScriptFile('popup.js', 'horde');
        include ANSEL_TEMPLATES . '/view/gallery.inc';
        return Horde::endBuffer();
    }

}
