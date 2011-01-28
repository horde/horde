<?php
/**
 * The IMP_Mime_Viewer_Images class allows display of images attached
 * to a message.
 *
 * Copyright 2002-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Mime_Viewer_Images extends Horde_Mime_Viewer_Images
{
    /**
     * This driver's display capabilities.
     *
     * @var array
     */
    protected $_capability = array(
        'full' => true,
        'info' => true,
        'inline' => true,
        'raw' => true
    );

    /**
     * Return the full rendered version of the Horde_Mime_Part object.
     *
     * URL parameters used by this function:
     * <pre>
     * 'imp_img_view' - (string) One of the following:
     *   'data' - Output the image directly.
     *   'load_convert' - TODO
     *   'view_convert' - TODO
     *   'view_thumbnail' - TODO
     * </pre>
     *
     * @return array  See parent::render().
     */
    protected function _render()
    {
        $view = Horde_Util::getFormData('imp_img_view');

        switch ($view) {
        case 'data':
            /* If calling page is asking us to output data, do that without
             * any further delay and exit. */
            return parent::_render();

        case 'view_convert':
            /* Convert the image to browser-viewable format and display. */
            return $this->_viewConvert(false);

        case 'view_thumbnail':
            /* Create the thumbnail and display. */
            return $this->_viewConvert(true);

        case 'load_convert':
            /* The browser can display the image type directly - output the JS
             * code to render the auto resize popup image window. */
            return $this->_popupImageWindow();

        default:
            return $GLOBALS['browser']->getFeature('dom')
                ? $this->_popupImageWindow()
                : parent::_render();
        }
    }

    /**
     * Return the rendered inline version of the Horde_Mime_Part object.
     *
     * @return array  See parent::render().
     */
    protected function _renderInline()
    {
        /* Only display the image inline if the browser can display it and the
         * size of the image is below the config value. */
        if ($GLOBALS['browser']->isViewable($this->_getType())) {
            if (!isset($this->_conf['inlinesize']) ||
                ($this->_mimepart->getBytes() < $this->_conf['inlinesize'])) {
                $imgview = new IMP_Ui_Imageview();
                $showimg = $imgview->showInlineImage($this->getConfigParam('imp_contents'));
            } else {
                $showimg = false;
            }

            if (!$showimg) {
                return $this->_renderInfo();
            }

            /* Viewing inline, and the browser can handle the image type
             * directly. So output an <img> tag to load the image. */
            return array(
                $this->_mimepart->getMimeId() => array(
                    'data' => $this->_outputImgTag('data', $this->_mimepart->getName(true)),
                    'status' => array(),
                    'type' => 'text/html; charset=' . $this->getConfigParam('charset')
                )
            );
        }

        /* The browser cannot view this image. Inform the user of this and
         * ask user if we should convert to another image type. */
        $status = array(_("Your browser does not support inline display of this image type."));

        /* See if we can convert to an inline browser viewable form. */
        if ($GLOBALS['browser']->hasFeature('javascript')) {
            $img = $this->_getHordeImageOb(false);
            if ($img &&
                $GLOBALS['browser']->isViewable($img->getContentType())) {
                $convert_link = $this->getConfigParam('imp_contents')->linkViewJS($this->_mimepart, 'view_attach', _("HERE"), array('params' => array('imp_img_view' => 'load_convert')));
                $status[] = sprintf(_("Click %s to convert the image file into a format your browser can attempt to view."), $convert_link);
            }
        }

        return array(
            $this->_mimepart->getMimeId() => array(
                'data' => '',
                'status' => array(
                    array(
                        'text' => $status
                    )
                ),
                'type' => 'text/html; charset=' . $this->getConfigParam('charset')
            )
        );
    }

    /**
     * Return the rendered information about the Horde_Mime_Part object.
     *
     * @return array  See parent::render().
     */
    protected function _renderInfo()
    {
        /* Display the thumbnail link only if we show thumbs for all images or
         * if image is over 50 KB. Also, check to see if convert utility is
         * available. */
        if ((!$this->getConfigParam('allthumbs') &&
             ($this->_mimepart->getBytes() < 51200)) ||
            !$this->_getHordeImageOb(false)) {
            return array();
        }

        $status = array(_("This is a thumbnail of an image attached to this message."));

        if ($GLOBALS['browser']->hasFeature('javascript')) {
            $status[] = $this->getConfigParam('imp_contents')->linkViewJS($this->_mimepart, 'view_attach', $this->_outputImgTag('view_thumbnail', _("View Attachment")), null, null, null);
        } else {
            $status[] = Horde::link($this->getConfigParam('imp_contents')->urlView($this->_mimepart, 'view_attach')) . $this->_outputImgTag('view_thumbnail', _("View Attachment")) . '</a>';
        }

        return array(
            $this->_mimepart->getMimeId() => array(
                'data' => '',
                'status' => array(
                    array(
                        'icon' => Horde::img('mime/image.png'),
                        'text' => $status
                    )
                ),
                'type' => 'text/html; charset=' . $this->getConfigParam('charset')
            )
        );
    }

    /**
     * Return the full rendered version of the Horde_Mime_Part object.
     *
     * @return array  See parent::render().
     */
    protected function _renderRaw()
    {
        return parent::_render();
    }

    /**
     * Generate the HTML output for the JS auto-resize view window.
     *
     * @return string  The HTML output.
     */
    protected function _popupImageWindow()
    {
        $loading = _("Loading...");
        $self_url = IMP::selfUrl()->add(array('actionID' => 'view_attach', 'imp_img_view' => ((Horde_Util::getFormData('imp_img_view') == 'load_convert') ? 'view_convert' : 'data')));
        $title = $this->_mimepart->getName(true);

        $str = <<<EOD
<html>
<head>
<title>$title</title>
<style type="text/css"><!-- body { margin:0px; padding:0px; } --></style>
<script type="text/javascript">
function resizeWindow()
{
    var h, img = document.getElementById('disp_image'), w;
    document.getElementById('splash').style.display = 'none';
    img.style.display = 'block';
    window.moveTo(0, 0);
    h = img.height - (self.innerHeight ? self.innerHeight : (document.documentElement.clientHeight ? document.documentElement.clientHeight : document.body.clientHeight));
    w = img.width - (self.innerWidth ? self.innerWidth : (document.documentElement.clientWidth ? document.documentElement.clientWidth : document.body.clientWidth));
    window.resizeBy(w, h);
    self.focus();
}
</script></head>
<body onload="resizeWindow();"><span id="splash" style="color:gray;font-family:sans-serif;padding:2px;">$loading</span><img id="disp_image" style="display:none;" src="$self_url" /></body></html>
EOD;

        return array(
            $this->_mimepart->getMimeId() => array(
                'data' => $str,
                'status' => array(),
                'type' => 'text/html; charset=' . $this->getConfigParam('charset')
            )
        );
    }

    /**
     * Convert image.
     *
     * @param boolean $thumb  View image in thumbnail size?
     *
     * @return string  The image data.
     */
    protected function _viewConvert($thumb)
    {
        $img = $this->_getHordeImageOb(true);

        if ($img) {
            if ($thumb) {
                $dim = $img->getDimensions();
                if (($dim['height'] > 96) || ($dim['width'] > 96)) {
                    $img->resize(96, 96, true);
                }
            }
            $type = $img->getContentType();
            $data = $img->raw(true);
        }

        if (!$img || !$data) {
            $type = 'image/png';
            $data = file_get_contents(IMP_BASE . '/themes/graphics/mini-error.png');
        }

        return array(
            $this->_mimepart->getMimeId() => array(
                'data' => $data,
                'status' => array(),
                'type' => $type
            )
        );
    }

    /**
     * Return a Horde_Image object.
     *
     * @param boolean $load  Whether to load the image data.
     *
     * @return mixed  The Horde_Image object, or false on error.
     */
    protected function _getHordeImageOb($load)
    {
        if (empty($GLOBALS['conf']['image']['driver'])) {
            return false;
        }
        $img = null;
        //@TODO: Pass in a Horde_Logger in $context if desired.
        $context = array('tmpdir' => Horde::getTempDir());
        try {
            $img = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Image')->create();
        } catch (Horde_Exception $e) {
            return false;
        }

        if (!$img) {
            return false;
        }

        if ($load) {
            try {
                $ret = $img->loadString($this->_mimepart->getContents());
            } catch (Horde_Image_Exception $e) {
                return false;
            }
        }

        return $img;
    }

    /**
     * Output an image tag.
     *
     * @param string $type  The view type.
     * @param string $alt   The ALT text.
     *
     * @return string  An image tag.
     */
    protected function _outputImgTag($type, $alt)
    {
        return '<img src="' . $this->getConfigParam('imp_contents')->urlView($this->_mimepart, 'view_attach', array('params' => array('imp_img_view' => $type))) . '" alt="' . htmlspecialchars($alt, ENT_COMPAT, $this->getConfigParam('charset')) . '" />';
    }

}
