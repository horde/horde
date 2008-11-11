<?php
/**
 * The IMP_Horde_Mime_Viewer_appledouble class handles multipart/appledouble
 * messages conforming to RFC 1740.
 *
 * Copyright 2003-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Mime
 */
class IMP_Horde_Mime_Viewer_appledouble extends Horde_Mime_Viewer_Driver
{
    /**
     * This driver's capabilities.
     *
     * @var boolean
     */
    protected $_capability = array(
        'embedded' => false,
        'full' => false,
        'info' => true,
        'inline' => true
    );

    /**
     * Return the rendered inline version of the Horde_Mime_Part object.
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
     */
    protected function _renderInline()
    {
        return $this->_IMPrender(true);
    }

    /**
     * Return the rendered information about the Horde_Mime_Part object.
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
     */
    protected function _renderInfo()
    {
        return $this->_IMPrender(false);
    }

    /**
     * Render the part based on the view mode.
     *
     * @param boolean $inline  True if viewing inline.
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
     */
    protected function _IMPrender($inline)
    {
        /* RFC 1740 [4]: There are two parts to an appledouble message:
         *   (1) application/applefile
         *   (2) Data embedded in the Mac file
         * Since the resource fork is not very useful to us, only provide a
         * means to download. */

        /* Display the resource fork download link. */
        $mime_id = $this->_mimepart->getMimeId();
        $applefile_id = Horde_Mime::mimeIdArithmetic($mime_id, 'down');
        $data_id = Horde_Mime::mimeIdArithmetic($applefile_id, 'next');

        $applefile_part = $this->_mimepart->getPart($applefile_id);
        $data_part = $this->_mimepart->getPart($data_id);

        $data_name = $data_part->getName(true);
        if (empty($data_name)) {
            $data_name = _("unnamed");
        }

        $status = array(
            'icon' => Horde::img('apple.png', _("Macintosh File")),
            'text' => array(
                sprintf(_("This message contains a Macintosh file (named \"%s\")."), $data_name),
                sprintf(_("The Macintosh resource fork can be downloaded %s."), $this->_params['contents']->linkViewJS($applefile_part, 'download_attach', _("HERE"), array('jstext' => _("The Macintosh resource fork"))))
            )
        );

        $can_display = false;
        $ids = array($mime_id, $applefile_id);

        /* For inline viewing, attempt to display the data inline. */
        if ($inline) {
            $viewer = Horde_Mime_Viewer::factory($data_part->getType());
            if (($viewer->canRender('inline') &&
                 ($data_part->getDisposition() == 'inline')) ||
                $viewer->canRender('info')) {
                $can_display = true;
                $status['text'][] = _("The contents of the Macintosh file are below.");
            }
        }

        if (!$can_display) {
            $ids[] = $data_id;
        }

        return array(
            'ids' => $ids,
            'status' => array($status)
        );
    }
}
