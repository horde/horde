<?php
/**
 * The IMP_Horde_Mime_Viewer_rfc822 class renders out messages from
 * message/rfc822 content types.
 *
 * $Horde: imp/lib/MIME/Viewer/rfc822.php,v 1.41 2008/01/02 11:12:45 jan Exp $
 *
 * Copyright 2002-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Mime
 */
class IMP_Horde_Mime_Viewer_rfc822 extends Horde_Mime_Viewer_rfc822
{
    /**
     * Render out the currently set contents.
     *
     * @param array $params  An array with a reference to a MIME_Contents
     *                       object.
     *
     * @return string  The rendered text in HTML.
     */
    public function render($params)
    {
        $contents = &$params[0];

        /* Get the entire body part of the message/rfc822 contents. */
        if (!$this->mime_part->getInformation('imp_contents_set') &&
            is_a($contents, 'IMP_Contents') &&
            $this->mime_part->getMIMEId()) {
            $this->mime_part = &$contents->getDecodedMIMEPart($this->mime_part->getMIMEId(), true);
        }

        return parent::render();
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

        if (is_a($contents, 'IMP_Contents') &&
            !$this->mime_part->getContents()) {
            $id = $this->mime_part->getMIMEId();
            $hdr_id = substr($id, -2);
            if ($hdr_id != '.0') {
                $id .= '.0';
            }
            $this->mime_part = &$contents->getDecodedMIMEPart($id);
        }

        return parent::renderAttachmentInfo();
    }
}
