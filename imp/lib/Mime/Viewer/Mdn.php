<?php
/**
 * The IMP_Mime_Viewer_Mdn class handles multipart/report messages that
 * that refer to message disposition notification (MDN) messages (RFC 3798).
 *
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
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
        $mdn_id = $this->_mimepart->getMimeId();
        $parts = array_keys($this->_mimepart->contentTypeMap());

        $status = new IMP_Mime_Status(_("A message you have sent has resulted in a return notification from the recipient."));
        $status->icon('info_icon.png', _("Info"));

        /* RFC 3798 [3]: There are three parts to a delivery status
         * multipart/report message:
         *   (1) Human readable message
         *   (2) Machine parsable body part (message/disposition-notification)
         *   (3) Original message (optional) */

        /* Get the human readable message. */
        reset($parts);
        $part1_id = next($parts);

        /* Display a link to more detailed message. */
        $part2_id = Horde_Mime::mimeIdArithmetic($part1_id, 'next');
        $part = $this->getConfigParam('imp_contents')->getMIMEPart($part2_id);
        if ($part) {
            $status->addText(sprintf(_("Technical details can be viewed %s."), $this->getConfigParam('imp_contents')->linkViewJS($part, 'view_attach', _("HERE"), array('jstext' => _("Technical details"), 'params' => array('ctype' => 'text/plain', 'mode' => IMP_Contents::RENDER_FULL)))));
        }
        $ret[$part2_id] = null;

        /* Display a link to the sent message. */
        $part3_id = Horde_Mime::mimeIdArithmetic($part2_id, 'next');
        $part = $this->getConfigParam('imp_contents')->getMIMEPart($part3_id);
        if ($part) {
            $status->addText(sprintf(_("The text of the sent message can be viewed %s."), $this->getConfigParam('imp_contents')->linkViewJS($part, 'view_attach', _("HERE"), array('jstext' => _("The text of the sent message"), 'params' => array('ctype' => 'message/rfc822', 'mode' => IMP_Contents::RENDER_FULL)))));
            foreach ($part->contentTypeMap() as $key => $val) {
                $ret[$key] = null;
            }
        }

        $ret[$mdn_id] = array(
            'data' => '',
            'status' => $status,
            'type' => 'text/html; charset=' . $this->getConfigParam('charset'),
            'wrap' => 'mimePartWrap'
        );

        return $ret;
    }

}
