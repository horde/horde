<?php
/**
 * The IMP_Horde_Mime_Viewer_pdf class enables generation of thumbnails for
 * PDF attachments.
 *
 * Copyright 2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Mime_Viewer
 */
class IMP_Horde_Mime_Viewer_pdf extends Horde_Mime_Viewer_Driver
{
    /**
     * The content-type of the generated data.
     *
     * @var string
     */
    protected $_contentType;

    /**
     * Render out the currently set contents.
     *
     * @param array $params  An array with a reference to a MIME_Contents
     *                       object.
     *
     * @return string  The rendered information.
     */
    public function render($params)
    {
        /* Create the thumbnail and display. */
        if (Util::getFormData('images_view_thumbnail')) {
            $mime = $this->mime_part;
            $img = $this->_getHordeImageOb();

            if ($img) {
                $img->resize(96, 96, true);
                $type = $img->getContentType();
                $data = $img->raw(true);
            }

            if (!$img || !$data) {
                $type = 'image/png';
                $data = file_get_contents(IMP_BASE . '/themes/graphics/mini-error.png');
            }

            $mime->setType($type);
            $this->_contentType = $type;
            $mime->setContents($data);

            return $mime->getContents();
        }

        return parent::render($params);
    }

    /**
     * Render out attachment information.
     *
     * @param array $params  An array with a reference to a MIME_Contents
     *                       object.
     *
     * @return string  The rendered text in HTML.
     */
    public function renderAttachmentInfo($params)
    {
        $contents = &$params[0];

        if (is_a($contents, 'IMP_Contents')) {
            $this->mime_part = &$contents->getDecodedMIMEPart($this->mime_part->getMIMEId(), true);
        }

        /* Check to see if convert utility is available. */
        if (!$this->_getHordeImageOb(false)) {
            return '';
        }

        $status = array(
            sprintf(_("A PDF file named %s is attached to this message. A thumbnail is below."),
                    $this->mime_part->getName(true)),
        );

        if (!$GLOBALS['browser']->hasFeature('javascript')) {
            $status[] = Horde::link($contents->urlView($this->mime_part,
                            'view_attach')) .
                        Horde::img($contents->urlView($this->mime_part,
                            'view_attach', array('images_view_thumbnail' => 1), false),
                            _("View Attachment"), null, '') . '</a>';
        } else {
            $status[] = $contents->linkViewJS($this->mime_part, 'view_attach',
                        Horde::img($contents->urlView($this->mime_part,
                            'view_attach', array('images_view_thumbnail' => 1),
                            false), _("View Attachment"), null, ''), null, null,
                            null);
        }

        return $this->formatStatusMsg($status, Horde::img('mime/image.png',
                    _("Thumbnail of attached PDF file"), null, $GLOBALS['registry']->getImageDir('horde')), false);
    }

    /**
     * Return a Horde_Image object.
     *
     * @param boolean $load  Whether to load the image data.
     *
     * @return Horde_Image  The requested object.
     */
    protected function _getHordeImageOb($load = true)
    {
        if (empty($GLOBALS['conf']['image']['convert'])) {
            return false;
        }

        include_once 'Horde/Image.php';
        $img = &Horde_Image::singleton('im', array('temp' => Horde::getTempdir()));
        if (is_a($img, 'PEAR_Error')) {
            return false;
        }

        if ($load) {
            $ret = $img->loadString(1, $this->mime_part->getContents());
            if (is_a($ret, 'PEAR_Error')) {
                return false;
            }
        }

        return $img;
    }

    /**
     * Return the content-type
     *
     * @return string  The content-type of the output.
     */
    public function getType()
    {
        return ($this->_contentType) ? $this->_contentType : parent::getType();
    }
}
