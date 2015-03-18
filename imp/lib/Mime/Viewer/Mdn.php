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
 * Handler for multipart/report messages that refer to message disposition
 * notification (MDN) messages (RFC 3798).
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2003-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Mime_Viewer_Mdn extends Horde_Mime_Viewer_Base
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
        return $this->_renderInfo();
    }

    /**
     * Return the rendered information about the Horde_Mime_Part object.
     *
     * @return array  See parent::render().
     */
    protected function _renderInfo()
    {
        /* RFC 3798 [3]: There are three parts to a delivery status
         * multipart/report message:
         *   (1) Human readable message
         *   (2) Machine parsable body part (message/disposition-notification)
         *   (3) Original message (optional) */

        /* Get the human readable message. */
        $iterator = $this->_mimepart->partIterator(false);
        $iterator->rewind();
        $ret = $this->_parseMdn($iterator->current());

        $status = new IMP_Mime_Status(
            $this->_mimepart,
            _("A message you have sent has resulted in a return notification from the recipient.")
        );
        $status->icon('info_icon.png', _("Info"));

        $ret[$this->_mimepart->getMimeId()] = array(
            'data' => '',
            'status' => $status,
            'type' => 'text/html; charset=' . $this->getConfigParam('charset'),
            'wrap' => 'mimePartWrap'
        );

        return $ret;
    }

    /**
     * Parse the MDN part.
     *
     * @param Horde_Mime_Part $part  MDN part.
     *
     * @return array  See parent::render().
     */
    protected function _parseMdn($part)
    {
        $ret = array();

        if (!$part) {
            return $ret;
        }

        $part1_id = $part->getMimeId();
        $id_ob = new Horde_Mime_Id($part1_id);

        /* Ignore the technical details.
         * TODO: parse technical details to give a better status description */
        $id_ob->id = $id_ob->idArithmetic($id_ob::ID_NEXT);
        $ret[$id_ob->id] = null;

        /* Display a link to the sent message. */
        $imp_contents = $this->getConfigParam('imp_contents');
        $part3_id = $id_ob->idArithmetic($id_ob::ID_NEXT);

        if ($part2 = $imp_contents->getMimePart($part3_id)) {
            $status->addText(
                $imp_contents->linkViewJS(
                    $part2,
                    'view_attach',
                    _("View the text of the sent message."),
                    array(
                        'params' => array(
                            'ctype' => 'message/rfc822',
                            'mode' => IMP_Contents::RENDER_FULL
                        )
                    )
                )
            );

            foreach ($part2->partIterator() as $val) {
                $ret[$val->getMimeId()] = null;
            }
        }

        return $ret;
    }

}
