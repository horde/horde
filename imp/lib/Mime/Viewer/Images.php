<?php
/**
 * Copyright 2002-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2002-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Renderer for image data.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2002-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Mime_Viewer_Images extends Horde_Mime_Viewer_Images
{
    /**
     * This driver's display capabilities.
     *
     * @var array
     */
    protected $_capability = array(
        'full' => false,
        'info' => true,
        'inline' => true,
        'raw' => false
    );

    /**
     */
    public function canRender($mode)
    {
        global $browser;

        switch ($mode) {
        case 'full':
        case 'raw':
            /* Only display raw images we know the browser supports, and we
             * know can't cause any sort of security issue. */
            if ($browser->isViewable($this->_getType())) {
                return true;
            }
            break;
        }

        return parent::canRender($mode);
    }

    /**
     * Return the full rendered version of the Horde_Mime_Part object.
     *
     * URL parameters used by this function:
     *   - imp_img_view: (string) One of the following:
     *     - data: Output the image directly.
     *     - view_convert: Convert the image to browser-viewable format and
     *                     display.
     *     - view_thumbnail: Create thumbnail and display.
     *
     * @return array  See parent::render().
     */
    protected function _render()
    {
        switch ($GLOBALS['injector']->getInstance('Horde_Variables')->imp_img_view) {
        case 'data':
            /* If calling page is asking us to output data, do that without
             * any further delay and exit. */
            return parent::_render();

        case 'view_convert':
            /* Convert image to browser-viewable format and display. */
            return $this->_viewConvert(false);

        case 'view_thumbnail':
            /* Create thumbnail and display. */
            return $this->_viewConvert(true);

        default:
            return parent::_render();
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
                $showimg = $GLOBALS['injector']->getInstance('IMP_Images')->showInlineImage($this->getConfigParam('imp_contents'));
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
                    'type' => 'text/html; charset=' . $this->getConfigParam('charset')
                )
            );
        }

        /* The browser cannot view this image. Inform the user of this and
         * ask user if we should convert to another image type. */
        $status = new IMP_Mime_Status(_("Your browser does not support inline display of this image type."));

        /* See if we can convert to an inline browser viewable form. */
        $img = $this->_getHordeImageOb(false);
        if ($img &&
            $GLOBALS['browser']->isViewable($img->getContentType())) {
            $convert_link = $this->getConfigParam('imp_contents')->linkViewJS($this->_mimepart, 'view_attach', _("HERE"), array('params' => array('imp_img_view' => 'view_convert')));
            $status->addText(sprintf(_("Click %s to convert the image file into a format your browser can attempt to view."), $convert_link));
        }

        return array(
            $this->_mimepart->getMimeId() => array(
                'data' => '',
                'status' => $status,
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
        /* Check to see if convert utility is available. */
        if (!$this->_getHordeImageOb(false)) {
            return array();
        }

        $status = new IMP_Mime_Status(_("This is a thumbnail of an image attachment."));
        $status->icon('mime/image.png');
        $status->addText($this->getConfigParam('imp_contents')->linkViewJS($this->_mimepart, 'view_attach', $this->_outputImgTag('view_thumbnail', _("View Attachment")), null, null, null));

        return array(
            $this->_mimepart->getMimeId() => array(
                'data' => '',
                'status' => $status,
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
                if (($dim['height'] > 150) || ($dim['width'] > 150)) {
                    $img->resize(150, 150, true);
                }
            }
            $type = $img->getContentType();
            try {
                $data = $img->raw(true);
            } catch (Exception $e) {}
        }

        if (!$img || !$data) {
            $type = 'image/png';
            $img_ob = Horde_Themes::img('mini-error.png', 'imp');
            $data = file_get_contents($img_ob->fs);
        }

        return array(
            $this->_mimepart->getMimeId() => array(
                'data' => $data,
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
        if (!$this->getConfigParam('thumbnails')) {
            return false;
        }

        try {
            if (($img = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Image')->create()) && $load) {
                $img->loadString($this->_mimepart->getContents());
            }
            return $img;
        } catch (Horde_Exception $e) {
            Horde::log($e, 'DEBUG');
        }

        return false;
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
        global $browser;

        switch ($type) {
        case 'view_thumbnail':
            if ($this->getConfigParam('thumbnails_dataurl') &&
                $browser->getFeature('dataurl')) {
                $thumb = $this->_viewConvert(true);
                $thumb = reset($thumb);
                $src = Horde_Url_Data::create($thumb['type'], $thumb['data']);
                break;
            }

        default:
            $src = $this->getConfigParam('imp_contents')->urlView($this->_mimepart, 'view_attach', array('params' => array('imp_img_view' => $type)));
            break;
        }

        return '<img src="' . $src . '" alt="' . htmlspecialchars($alt, ENT_COMPAT, $this->getConfigParam('charset')) . '" />';
    }

}
