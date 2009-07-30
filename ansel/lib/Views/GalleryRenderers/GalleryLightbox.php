<?php
/**
 * Ansel_View_GalleryRenderer_GalleryLightbox:: Class wraps display of the lightbox
 * style gallery views.
 *
 * $Horde: ansel/lib/Views/GalleryRenderers/GalleryLightbox.php,v 1.33 2009/07/08 18:28:45 slusarz Exp $
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */

require_once ANSEL_BASE . '/lib/Views/GalleryRenderer.php';

class Ansel_View_GalleryRenderer_GalleryLightbox extends Ansel_View_GalleryRenderer {

    /**
     * Perform any tasks that should be performed before the view is rendered.
     *
     */
    function _init()
    {
        if (empty($this->view->_params['image_onclick'])) {
            $this->view->_params['image_onclick'] = 'return lb.start(%i);';
        }

        // Attach the script and CSS files here if we aren't being called via the api
        if (empty($this->view->_params['api'])) {
            Ansel::attachStylesheet('lightbox.css');
            Horde::addScriptFile('prototype.js', 'horde', true);
            Horde::addScriptFile('effects.js', 'horde', true);
            Horde::addScriptFile('lightbox.js', 'ansel', true);
        }
    }

    /**
     * Get the HTML representing this view.
     *
     * @return string The HTML
     */
    function _html()
    {
        global $conf, $prefs, $registry;

        $galleryOwner = $this->view->gallery->get('owner');
        $id = $this->view->gallery->getOwner();
        $owner = $id->getValue('fullname');
        if (!$owner) {
            $owner = $galleryOwner;
        }

        /* Get JSON data for view */
        // 0 == normal, 1 == by date
        if ($this->mode == 0) {
            $json = $this->view->json(null, !empty($this->view->_params['api']));
        } else {
            if (!empty($this->date['day']) && $this->numTiles) {
                $json = $this->view->json(null, !empty($this->view->_params['api']));
            } else {
                $json = '[]';
            }
        }

        /* Don't bother if we are being called from the api */
        if (empty($this->view->_params['api'])) {
            $option_edit = $this->view->gallery->hasPermission(Horde_Auth::getAuth(),
                                                         PERMS_EDIT);
            $option_select = $option_delete = $this->view->gallery->hasPermission(
                Horde_Auth::getAuth(), PERMS_DELETE);
            $option_move = ($option_delete && $GLOBALS['ansel_storage']->countGalleries(PERMS_EDIT));
            $option_copy = ($option_edit && $GLOBALS['ansel_storage']->countGalleries(PERMS_EDIT));
            /* See if we requested a show_actions change (fallback for non-js) */
            if (Horde_Util::getFormData('actionID', '') == 'show_actions') {
                $prefs->setValue('show_actions', (int)!$prefs->getValue('show_actions'));
            }
        }

        /* Set up the pager */
        $date_params = Ansel::getDateParameter(
            array('year' => isset($this->view->_params['year']) ? $this->view->_params['year'] : 0,
                  'month' => isset($this->view->_params['month']) ? $this->view->_params['month'] : 0,
                  'day' => isset($this->view->_params['day']) ? $this->view->_params['day'] : 0));

        $vars = Horde_Variables::getDefaultVariables();
        if (!empty($this->view->_params['page'])) {
            $vars->add('page', $this->view->_params['page']);
            $page = $this->view->_params['page'];
        } else {
            $page = 0;
        }
        if (!empty($this->view->_params['gallery_view_url'])) {
            $pagerurl = str_replace(array('%g', '%s'), array($this->galleryId, $this->gallerySlug), urldecode($this->view->_params['gallery_view_url']));
            $pagerurl = Horde_Util::addParameter($pagerurl, $date_params, null, false);
        } else {
            /*
             * Build the pager url. Add the needed variables directly to the
             * url instead of passing it as a preserved variable to the pager
             * since the logic to build the URL is already in getUrlFor()
             */
            $pager_params =  array_merge(
                array('gallery' => $this->galleryId,
                      'view' => 'Gallery',
                      'slug' => $this->view->gallery->get('slug')),
                $date_params);
            $pagerurl = Ansel::getUrlfor('view', $pager_params, true);
        }

        if (!empty($this->view->_params['urlCallback'])) {
            $callback = $this->view->_params['urlCallback'];
        } else {
            $callback = null;
        }
        $params = array('num' => $this->numTiles,
                        'url' => $pagerurl,
                        'perpage' => $this->perpage,
                        'url_callback' => $callback);

        $pager = new Horde_UI_Pager('page', $vars, $params);

        /* Start buffering */
        ob_start();

        /* Create the js variables to pass to the lightbox script */
        $jsvars = array('graphics_dir' => Horde::applicationUrl($registry->getImageDir(), true, -1),
                        'image_text' => _("Photo"),
                        'of_text' => _("of"),
                        'start_page' => $page);

        $flipped = array_flip($date_params);
        if (count($flipped) == 1 && !empty($flipped[0])) {
            $jsvars['gallery_url'] = $pagerurl . '?';
        } else {
            $jsvars['gallery_url'] = $pagerurl . '&';
        }
        /* Output js/css here if we are calling via the api */
        if (!empty($this->view->_params['api'])) {
            Ansel::attachStylesheet('lightbox.css', true);
            $includes = new Horde_Script_Files();
            $includes->disableAutoloadHordeJS();
            $includes->_add('prototype.js', 'horde', true, true);
            $includes->_add('accesskeys.js', 'horde', true, true);
            $includes->_add('effects.js', 'horde', true, true);
            $includes->_add('lightbox.js', 'ansel', true, true);
            $includes->includeFiles();
        }

        /* Needed in the template files */
        $tilesperrow = $prefs->getValue('tilesperrow');
        $cellwidth = round(100 / $tilesperrow);
        $count = 0;

        include ANSEL_TEMPLATES . '/view/gallerylightbox.inc';
        return ob_get_clean();
    }

}
