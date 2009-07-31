<?php
/**
 * Ansel_View_GalleryRenderer_GalleryVimeo:: An example of extending Ansel by
 * adding a new gallery style. This fetches a list of videos from the Vimeo
 * video service, and displays them as a gallery. The videos are viewed in a
 * redbox overlay when the thumbnails are clicked.
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

class Ansel_View_GalleryRenderer_GalleryVimeo extends Ansel_View_GalleryRenderer {
    /**
     *
     * @var Horde_Service_Vimeo object
     */
    var $_vimeo;
    var $_thumbs;

    /**
     * Perform any tasks that should be performed before the view is rendered.
     *
     */
    function _init()
    {
        // Attach the script and CSS files here if we aren't being called via the api
        if (empty($this->view->_params['api'])) {
            Horde::addScriptFile('effects.js', 'horde', true);
            Horde::addScriptFile('redbox.js', 'horde', true);
        }
    }

    /**
     * Override the parent class' fetchChildren method so we can grab the video
     * thumbnail information from Vimeo instead of from our local image storage.
     *
     * @param boolean $noauto  Ignored in this class since we won't be doing any
     *                         date browsing.  Maybe another experiment? ;)
     */
    function fetchChildren($noauto = true)
    {
        // Build a Horde_Service_Vimeo object
        // It *requires* a http client object and can make use of a cache object,
        // so let's take advantage of it.
        $params = array('http_client' => new Horde_Http_Client(),
                        'cache' => $GLOBALS['cache'],
                        'cache_lifetime' => $GLOBALS['conf']['cache']['default_lifetime']);

        $this->_vimeo = Horde_Service_Vimeo::factory('Simple', $params);

        // The identifier for what we are requesting.
        // If we are requesting a user's videos, this is the user's vimeo_id
        // if we want to request a particular group, this would be the group_id
        // etc...
        //
        // For this example, the id is hard coded here, but if I were to implement
        // this on a live site I would add a new user pref to ansel for the
        // user to enter his/her own vimeo_id and then grab the value from
        // pref storage here.
        $vimeo_id = 'user1015172'; //TODO: Get this from prefs?

        // This gets the data representing the videos. See the API docs for
        // exactly what is returned, but for our purposes, we will be using:
        // clip_id, url, caption, thumbnail_large etc...
        $thumbs = unserialize($this->_vimeo->user($vimeo_id)->clips()->run());

        // We fetch the information needed to embed each video now to make things
        // easier for this example...the cache helps tremendously with load times
        // after the first page is requested.
        foreach ($thumbs as $thumb) {
            $this->_json[$thumb['clip_id']]  = $this->_vimeo->getEmbedJSON(array('url' => $thumb['url'], 'byline' => 'false', 'portrait' => 'false'));
            $this->_thumbs[$thumb['clip_id']] = $thumb;
        }

        // Vimeo's Simple API doesn't provide for paging - so we emulate it
        // by only returning the video thumbnails that should appear on this
        // current gallery page.  Like stated above, the first load will take
        // a bit of time depending on the number of videos the user has - but
        // each subsequent page will load *much* faster as we don't have to
        // contact Vimeo at all.

        // Total number of thumbnails in the gallery
        $this->numTiles = count($thumbs);

        // The last one to display on this page
        $this->pageend = min($this->numTiles, $this->pagestart + $this->perpage - 1);


       $this->children = $this->view->gallery->getGalleryChildren(
            PERMS_SHOW,
            $this->page * $this->perpage,
            $this->perpage,
            !empty($this->view->_params['force_grouping']));
    }

    /**
     * Get the HTML representing this view.
     *
     * Responsible for building the HTML for the view. It's stripped down
     * somewhat from the other styles...sets up the variables needed for the
     * template we put in ansel/templates/view - though there is really no
     * reason we *have* to have a template file there if we can generate the
     * entire HTML here, or load a template from this directory or....?
     *
     * @return string The HTML
     */
    function _html()
    {
        global $conf, $prefs, $registry;

        // Deal with getting the correct gallery owner string, get any
        // parameters we are interested in from the view
        $galleryOwner = $this->view->gallery->get('owner');
        $id = $this->view->gallery->getOwner();
        $owner = $id->getValue('fullname');
        if (!$owner) {
            $owner = $galleryOwner;
        }
        $vars = Horde_Variables::getDefaultVariables();
        if (!empty($this->view->_params['page'])) {
            $vars->add('page', $this->view->_params['page']);
            $page = $this->view->_params['page'];
        } else {
            $page = 0;
        }

        // Build the proper pager urls
        if (!empty($this->view->_params['gallery_view_url'])) {
            $pagerurl = str_replace(array('%g', '%s'), array($this->galleryId, $this->gallerySlug), urldecode($this->view->_params['gallery_view_url']));
        } else {
            /*
             * Build the pager url. Add the needed variables directly to the
             * url instead of passing it as a preserved variable to the pager
             * since the logic to build the URL is already in getUrlFor()
             */
            $pager_params =
                array('gallery' => $this->galleryId,
                      'view' => 'Gallery',
                      'slug' => $this->view->gallery->get('slug'));
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

        /* Output js/css here if we are calling via the api */
        if (!empty($this->view->_params['api'])) {
            $includes = new Horde_Script_Files();
            $includes->disableAutoloadHordeJS();
            $includes->_add('redbox.js', 'horde', true, true);
            $includes->includeFiles();
        }

        /* Needed in the template files */
        $tilesperrow = $prefs->getValue('tilesperrow');
        $cellwidth = round(100 / $tilesperrow);
        $count = 0;

        include ANSEL_TEMPLATES . '/view/galleryvimeo.inc';
        return ob_get_clean();
    }

    function getTile($image, $video, $cnt)
    {
        $imgOnClick = 'return showVideo(' . $cnt . ');';
        $tile = '<div class="image-tile" id="imagetile_' . (int)$video->clip_id . '">'
            . Horde::link($video->url, $video->title, '', '', $imgOnClick, $video->title)
            . '<img src="' Ansel::getImageUrl($image->id, 'prettythumb', true, $this->view->gallery->get('style')) . '" />' . '</a>';
        $tile .= '<div style="valign: bottom;">';
        $tile .= ' <div class="image-tile-caption" id="' . (int)$video->clip_id . 'caption">' . $video->caption  . '</div></div></div>';

        return $tile;
    }

}
