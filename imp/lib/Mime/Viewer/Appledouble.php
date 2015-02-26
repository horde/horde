<?php
/**
 * Copyright 2003-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2003-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Handler for multipart/appledouble data (RFC 1740).
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2003-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Mime_Viewer_Appledouble extends Horde_Mime_Viewer_Base
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
     * Metadata for the current viewer/data.
     *
     * @var array
     */
    protected $_metadata = array(
        'compressed' => false,
        'embedded' => false,
        'forceinline' => true
    );

    /**
     * Return the rendered inline version of the Horde_Mime_Part object.
     *
     * @return array  See parent::render().
     */
    protected function _renderInline()
    {
        return $this->_IMPrender(true);
    }

    /**
     * Return the rendered information about the Horde_Mime_Part object.
     *
     * @return array  See parent::render().
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
     * @return array  See parent::render().
     */
    protected function _IMPrender($inline)
    {
        /* RFC 1740 [4]: There are two parts to an appledouble message:
         *   (1) application/applefile
         *   (2) Data embedded in the Mac file
         * Since the resource fork is not very useful to us, only provide a
         * means to download. */

        /* Display the resource fork download link. */
        $iterator = $this->_mimepart->partIterator(false);
        $iterator->rewind();
        $mime_id = $iterator->current()->getMimeId();
        $iterator->next();
        $applefile_id = $iterator->current()->getMimeId();

        $id_ob = new Horde_Mime_Id($applefile_id);
        $data_id = $id_ob->idArithmetic($id_ob::ID_NEXT);

        $applefile_part = $this->_mimepart[$applefile_id];
        $data_part = $this->_mimepart[$data_id];

        $data_name = $this->getConfigParam('imp_contents')->getPartName($data_part);

        $status = new IMP_Mime_Status($this->_mimepart, array(
            sprintf(_("This message contains a Macintosh file (named \"%s\")."), $data_name),
            $this->getConfigParam('imp_contents')->linkViewJS(
                $applefile_part,
                'download_attach',
                "Download the Macintosh resource fork."
            )
        ));
        $status->icon('mime/apple.png', _("Macintosh File"));

        /* For inline viewing, attempt to display the data inline. */
        $ret = array();
        if ($inline && (($disp = $this->getConfigParam('imp_contents')->canDisplay($data_part, IMP_Contents::RENDER_INLINE | IMP_Contents::RENDER_INFO)))) {
            $ret = $this->getConfigParam('imp_contents')->renderMIMEPart($data_id, $disp);
        }

        foreach ($iterator as $ob) {
            $id = $ob->getMimeId();

            if (!isset($ret[$id]) && (strcmp($id, $data_id) !== 0)) {
                $ret[$id] = (strcmp($id, $mime_id) === 0)
                    ? array(
                          'data' => '',
                          'status' => $status,
                          'type' => 'text/html; charset=' . $this->getConfigParam('charset'),
                          'wrap' => 'mimePartWrap'
                      )
                    : null;
            }
        }

        return $ret;
    }

}
