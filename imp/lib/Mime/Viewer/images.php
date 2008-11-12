<?php
/**
 * The IMP_Horde_Mime_Viewer_images class allows display of images attached
 * to a message.
 *
 * Copyright 2002-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Mime
 */
class IMP_Horde_Mime_Viewer_images extends Horde_Mime_Viewer_images
{
    /**
     * Can this driver render various views?
     *
     * @var boolean
     */
    protected $_capability = array(
        'embedded' => false,
        'full' => true,
        'info' => true,
        'inline' => true
    );

    /**
     * Return the full rendered version of the Horde_Mime_Part object.
     *
     * URL parameters used by this function:
     * <pre>
     * 'img_data' - (boolean) If true, output the image directly.
     * 'img_load_convert' - (boolean) TODO
     * 'img_view_convert' - (boolean) TODO
     * 'img_view_thumbnail' - (boolean) TODO
     * </pre>
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
     */
    protected function _render()
    {
        /* If calling page is asking us to output data, do that without any
         * further delay and exit. */
        if (Util::getFormData('img_data')) {
            return parent::_render();
        }

        /* Convert the image to browser-viewable format and display. */
        if (Util::getFormData('img_view_convert')) {
            return $this->_viewConvert(false);
        }

        /* Create the thumbnail and display. */
        if (Util::getFormData('img_view_thumbnail')) {
            return $this->_viewConvert(true);
        }

        /* The browser can display the image type directly - output the JS
         * code to render the auto resize popup image window. */
        if (Util::getFormData('img_load_convert') ||
            ($GLOBALS['browser']->hasFeature('javascript') &&
             $GLOBALS['browser']->isViewable($this->_getType()))) {
            return $this->_popupImageWindow();
        }

        return parent::_render();
    }

    /**
     * Return the rendered inline version of the Horde_Mime_Part object.
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
     */
    protected function _renderInline()
    {
        /* Only display the image inline if the browser can display it and the
         * size of the image is small. */
        if ($GLOBALS['browser']->isViewable($this->_getType())) {
            if ($this->_mimepart->getBytes() < 51200) {
                /* Viewing inline, and the browser can handle the image type
                 * directly. So output an <img> tag to load the image. */
                return array(
                    'data' => Horde::img($this->_params['contents']->urlView($this->_mimepart, 'view_attach', array('params' => array('img_data' => 1))), $this->_mimepart->getName(true), null, '')
                );
            } else {
                return $this->_renderInfo();
            }
        }

        /* The browser cannot view this image. Inform the user of this and
         * ask user if we should convert to another image type. */
        $status = array(_("Your browser does not support inline display of this image type."));

        /* See if we can convert to an inline browser viewable form. */
        $img = $this->_getHordeImageOb(false);
        if ($img && $GLOBALS['browser']->isViewable($img->getContentType())) {
            $convert_link = $contents->linkViewJS($this->_mimepart, 'view_attach', _("HERE"), null, null, array('img_load_convert' => 1));
            $status[] = sprintf(_("Click %s to convert the image file into a format your browser can view."), $convert_link);
        }

        return array(
            'status' => array(
                array(
                    'text' => $status
                )
            )
        );
    }

    /**
     * Return the rendered information about the Horde_Mime_Part object.
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
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

        $status = array(sprintf(_("An image named %s is attached to this message. A thumbnail is below."), $this->_mimepart->getName(true)));

        if ($GLOBALS['browser']->hasFeature('javascript')) {
            $status[] = $this->_params['contents']->linkViewJS($this->_mimepart, 'view_attach', Horde::img($this->_params['contents']->urlView($this->_mimepart, 'view_attach', array('params' => array('img_view_thumbnail' => 1)), false), _("View Attachment"), null, ''), null, null, null);
        } else {
            $status[] = Horde::link($this->_params['contents']->urlView($this->_mimepart, 'view_attach')) . Horde::img($this->_params['contents']->urlView($this->_mimepart, 'view_attach', array('params' => array('img_view_thumbnail' => 1)), false), _("View Attachment"), null, '') . '</a>';
        }

        return array(
            'status' => array(
                array(
                    'icon' => Horde::img('mime/image.png', _("Thumbnail of attached image")),
                    'text' => $status
                )
            )
        );
    }

    /**
     * Generate the HTML output for the JS auto-resize view window.
     *
     * @return string  The HTML output.
     */
    protected function _popupImageWindow()
    {
        $params = $remove_params = array();

        if (Util::getFormData('img_load_convert')) {
            $params['img_view_convert'] = 1;
            $remove_params[] = 'img_load_convert';
        } else {
            $params['img_data'] = 1;
        }

        $self_url = Util::addParameter(Util::removeParameter(Horde::selfUrl(true), $remove_params), $params);
        $title = $this->_mimepart->getName(true);

        $str = <<<EOD
<html>
<head>
<title>$title</title>
<style type="text/css"><!-- body { margin:0px; padding:0px; } --></style>
EOD;

        /* Only use javascript if we are using a DOM capable browser. */
        if ($GLOBALS['browser']->getFeature('dom')) {
            /* Javascript display. */
            $loading = _("Loading...");
            $str .= <<<EOD
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
        } else {
            /* Non-javascript display. */
            $img_txt = _("Image");
            $str .= <<<EOD
</head>
<body bgcolor="#ffffff">
<img border="0" src="$self_url" alt="$img_txt" />
</body>
</html>
EOD;
        }

        return array(
            'data' => $str,
            'type' => 'text/html; charset=' . NLS::getCharset()
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
                $img->resize(96, 96, true);
            }
            $type = $img->getContentType();
            $data = $img->raw(true);
        }

        if (!$img || !$data) {
            $type = 'image/png';
            $data = file_get_contents(IMP_BASE . '/themes/graphics/mini-error.png');
        }

        return array(
            'data' => $data,
            'type' => $type
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
        $img = null;
        $params = array('temp' => Horde::getTempdir());

        if (!empty($GLOBALS['conf']['image']['convert'])) {
            $img = &Horde_Image::singleton('im', $params);
        } elseif (Util::extensionExists('gd')) {
            $img = &Horde_Image::singleton('gd', $params);
        }

        if (!$img || is_a($img, 'PEAR_Error')) {
            return false;
        }

        if ($load) {
            $ret = $img->loadString(1, $this->_mimepart->getContents());
            if (is_a($ret, 'PEAR_Error')) {
                return false;
            }
        }

        return $img;
    }
}
