<?php
/**
 * Ansel_View_EmbeddedRenderer_GalleryLink
 *
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * Example usage:
 * <pre>
 *   <script type="text/javascript" src="http://example.com/horde/services/
 *   imple.php?imple=Embed/impleApp=ansel/gallery_view=GalleryLink/
 *   gallery_slug=slug1:slug2:slug3/container=divId/
 *   thumbsize=prettythumb/style=ansel_polaroid"></script>
 *   <div id="divId"></div>
 *   <style type="text/css">#divId .anselGalleryWidget img {border:none;}</style>
 *</pre>
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_View_EmbeddedRenderer_GalleryLink extends Ansel_View_Base
{
    /**
     * Build the javascript that will render the view.
     *
     * @return string  A string containing valid javascript.
     */
    public function html()
    {
        // Required
        $node = $this->_params['container'];
        if (empty($node)) {
            return '';
        }

        // Need at least one of these
        $galleries = !empty($this->_params['gallery_slug']) ? explode(':', $this->_params['gallery_slug']) : '';
        $haveSlugs = true;
        if (empty($galleries)) {
            $galleries = !empty($this->_params['gallery_id']) ? explode(':', $this->_params['gallery_id']) : null;
            $haveSlugs = false;
        }

        // Determine the style/thumnailsize etc...
        $thumbsize = empty($this->_params['thumbsize']) ? 'thumb' : $this->_params['thumbsize'];
        $images = array();
        foreach ($galleries as $id) {
            try {
                if ($haveSlugs) {
                    $gallery = $this->_getGallery(null, $id);
                } else {
                    $gallery = $this->_getGallery($id);
                }
            } catch (Horde_Exception $e) {
                Horde::logMessage($e, 'ERR');
                exit;
            }
            if (!$gallery->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::READ)) {
                return '';
            }

            // Since we are possibly displaying multiple galleries, standardize
            // on a single gallery style. If none is requested, default to
            // ansel_default.
            $gallery_style = empty($this->_params['style']) ? 'ansel_default' : $this->_params['style'];
            $images[] = $gallery->getKeyImage(Ansel::getStyleDefinition($gallery_style));

        }
        $json = $GLOBALS['injector']->getInstance('Ansel_Storage')->getImageJson($images, null, true, $thumbsize, true);

        $GLOBALS['injector']->getInstance('Horde_Themes_Css')->addThemeStylesheet('jsembed.css');
        Horde::startBuffer();
        Horde::includeStylesheetFiles(array(
            'nobase' => true,
            'nohorde' => true), true);
        $css = Horde::endBuffer();

        // Some paths
        $js_path = $GLOBALS['registry']->get('jsuri', 'horde');
        $pturl = Horde::url($js_path . '/prototype.js', true);
        $ansel_js_path = $GLOBALS['registry']->get('jsuri', 'ansel');
        $jsurl = Horde::url($ansel_js_path . '/embed.js', true);

        // Start building the javascript - we use the same parameters as with
        //the mini gallery view so we can use the same javascript to display it
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
                document.write('$css');
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
