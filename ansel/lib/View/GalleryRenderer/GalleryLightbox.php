<?php
/**
 * @copyright 2008-2013 Horde LLC (http://www.horde.org)
 * @author Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Ansel
 */
/**
 * Ansel_View_GalleryRenderer_GalleryLightbox:: Class wraps display of the lightbox
 * style gallery views.
 *
 * Copyright 2008-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @copyright 2008-2013 Horde LLC (http://www.horde.org)
 * @author Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Ansel
 */
class Ansel_View_GalleryRenderer_GalleryLightbox extends Ansel_View_GalleryRenderer_Base
{
    public function __construct($view)
    {
        parent::__construct($view);
        $this->title = _("Lightbox Gallery");
    }

    /**
     * Perform any tasks that should be performed before the view is rendered.
     */
    protected function _init()
    {
        global $page_output;

        if (empty($this->view->image_onclick)) {
            $this->view->image_onclick = 'return lb.start(%i);';
        }

        if (empty($this->view->api)) {
            $page_output->addThemeStylesheet('lightbox.css');
            $page_output->addScriptFile('scriptaculous/effects.js', 'horde');
            $page_output->addScriptFile('lightbox.js');
        }
    }

    /**
     * Get the HTML representing this view.
     *
     * @return string The HTML
     */
    public function html()
    {
        global $conf, $prefs, $registry;

        // Initialize the Horde_View instance.
        $view = $this->_getHordeView();

        // Get JSON data for view
        if ($this->mode == Ansel_GalleryMode_Base::MODE_NORMAL) {
            $json = Ansel_View_Base::json(
                $this->view->gallery,
                array(
                    'full' => !empty($this->view->api),
                    'perpage' => $this->perpage
                )
            );
        } else {
            if (!empty($this->date['day']) && $this->numTiles) {
                $json = Ansel_View_Base::json(
                    $this->view->gallery,
                    array(
                        'full' => !empty($this->view->api),
                        'perpage' => $this->perpage
                    )
                );
            } else {
                $json = '[]';
            }
        }

        $pagerurl = $this->_getPagerUrl();
        $graphics_dir = Horde::url(Horde_Themes::img(), true, -1);
        $image_text = _("Photo");
        $of = _("of");
        $flipped = array_flip($date_params);
        if (count($flipped) == 1 && !empty($flipped[0])) {
            $gallery_url = $pagerurl . '?';
        } else {
            $gallery_url = $pagerurl . '&';
        }
        $js = array();
        $js[] = <<<EOT
        LightboxOptions = {
            gallery_json: {$json},
            fileLoadingImage: '{$graphics_dir}/lightbox/loading.gif',
            fileBottomNavCloseImage: '{$graphics_dir}/lightbox/closelabel.gif',
            overlayOpacity: 0.8,   // controls transparency of shadow overlay
            animate: true,         // toggles resizing animations
            resizeSpeed: 7,        // controls the speed of the image resizing animations (1=slowest and 10=fastest)
            borderSize: 10,        // if you adjust the padding in the CSS, you will need to update this variable

            // Used to write: Image # of #.
            labelImage: '{$image_text}',
            labelOf: '{$of}',
            //URL to return to when the lightbox closes
            returnURL: '{$gallery_url}',
            startPage: '{$view->page}'
        };
        document.lb = new Lightbox(LightboxOptions); if (window.location.hash.length) document.lb.start(window.location.hash.substring(1));
EOT;
        $GLOBALS['page_output']->addInlineScript($js, true);

        // Output js/css here if we are calling via the api
        if ($this->view->api) {
            Horde::startBuffer();
            global $page_output;
            $page_output->addThemeStylesheet('lightbox.css');
            $page_output->includeStylesheetFiles(array('nobase' => true), true);

            foreach (array('prototype.js', 'accesskeys.js', 'scriptaculous/effects.js') as $val) {
                $tmp = new Horde_Script_File_JsDir($val, 'horde');
                echo $tmp->tag_full;
            }

            $tmp = new Horde_Script_File_JsDir('lightbox.js');
            echo $tmp->tag_full;

            $page_output->outputInlineScript();

            $html = Horde::endBuffer();

            return $html . $view->render('gallery');
        }

        return $view->render('gallery');
    }

}
