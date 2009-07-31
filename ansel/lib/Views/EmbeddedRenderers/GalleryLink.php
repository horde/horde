<?php

/** Ansel_View_Gallery */
require_once ANSEL_BASE . '/lib/Views/Gallery.php';

/**
 * Ansel_View_EmbeddedRenderer_GalleryLink
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * Example usage:
 * <pre>
 *
 *   <script type="text/javascript" src="http://example.com/horde/services/
 *   imple.php?imple=Embed/impleApp=ansel/gallery_view=GalleryLink/
 *   gallery_slug=slug1:slug2:slug3/container=divId/
 *   thumbsize=prettythumb/style=ansel_polaroid"></script>
 *   <div id="divId"></div>
 *   <style type="text/css">#divId .anselGalleryWidget img {border:none;}</style>
 *
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_View_EmbeddedRenderer_GalleryLink extends Ansel_View_Gallery {

    /**
     * Create a new renderer.
     *
     * @see Ansel_View_Embedded
     *
     * @param array $params
     *
     * @return Ansel_View_EmbeddedRenderer  The renderer object.
     */
    function makeView($params = array())
    {
        $view = new Ansel_View_EmbeddedRenderer_GalleryLink();
        $view->_params = $params;

        return $view;
    }

    /**
     * Build the javascript that will render the view.
     *
     * @return string  A string containing valid javascript.
     */
    function html()
    {
        /* Read in parameters and set defaults */

        /* Required */
        $node = $this->_params['container'];
        if (empty($node)) {
            return '';
        }

        /* Need at least one of these */
        $galleries = !empty($this->_params['gallery_slug']) ? explode(':', $this->_params['gallery_slug']) : '';
        $haveSlugs = true;
        if (empty($galleries)) {
            $galleries = !empty($this->_params['gallery_id']) ? explode(':', $this->_params['gallery_id']) : null;
            $haveSlugs = false;
        }

        /* Determine the style/thumnailsize etc... */
        $thumbsize = empty($this->_params['thumbsize']) ?
            'thumb' :
            $this->_params['thumbsize'];

        foreach ($galleries as $identifier) {
            if ($haveSlugs) {
                $gallery = $this->getGallery(null, $identifier);
            } else {
                $gallery = $this->getGallery($identifier);
            }
            if (is_a($gallery, 'PEAR_Error')) {
                Horde::logMessage($gallery, __FILE__, __LINE__, PEAR_LOG_ERR);
                exit;
            }
            if (!$gallery->hasPermission(Horde_Auth::getAuth(), PERMS_READ)) {
                return '';
            }

            /*If a gallery_style is not specified, default to the gallery's
             * defined style. Note that this only matters if the $thumbsize
             * parameter is set to 'prettythumb' anyway.
             */
            $gallery_style = empty($this->_params['style']) ?
                $gallery->get('style') :
                $this->_params['style'];

            /* Ideally, since gallery default images are unique in that each style
             * needs it's own unique image_id, the thumbsize and style parameters
             * are mutually exclusive - specifying a specific gallery style is only
             * needed if requesting the prettythumb thumbsize value. Make sure that
             * both were not passed in.
             */
            if ($thumbsize == 'thumb') {
                $images[] = $gallery->getDefaultImage('ansel_default');
            } else {
                $images[] = $gallery->getDefaultImage($gallery_style);
            }
        }
        $json = $GLOBALS['ansel_storage']->getImageJson($images, null, true, $thumbsize, true);

        /* Some paths */
        $cssurl = Horde::url($GLOBALS['registry']->get('themesuri', 'ansel') . '/jsembed.css', true);
        $js_path = $GLOBALS['registry']->get('jsuri', 'horde');
        $pturl = Horde::url($js_path . '/prototype.js', true);
        $ansel_js_path = $GLOBALS['registry']->get('jsuri', 'ansel');
        $jsurl = Horde::url($ansel_js_path . '/embed.js', true);

        /* Start building the javascript - we use the same parameters as with
         * the mini gallery view so we can use the same javascript to display it
         */
        $html = <<<EOT
            //<![CDATA[
            // Old fashioned way to play nice with Safari 2 (Adding script inline with the
            // DOM won't work).  Need two seperate files output here since the incldued
            // files don't seem to be parsed until after the entire page is loaded, so we
            // can't include prototype on the same page it's needed.

            if (typeof anseljson == 'undefined') {
                if (typeof Prototype == 'undefined') {
                    document.write('<script type="text/javascript" src="$pturl"></script>');
                }
                anselnodes = new Array();
                anseljson = new Object();
                document.write('<link type="text/css" rel="stylesheet" href="$cssurl" />');
                document.write('<script type="text/javascript" src="$jsurl"></script>');
            }
            anselnodes[anselnodes.length] = '$node';
            anseljson['$node'] = new Object();
            anseljson['$node']['data'] = $json;
            anseljson['$node']['perpage'] = 0;
            anseljson['$node']['page'] = 0;
            anseljson['$node']['hideLinks'] = false;
            anseljson['$node']['linkToGallery'] = true;
            //]]>
EOT;

        return $html;
    }

}
