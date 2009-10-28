<?php
/**
 * The IMP_Horde_Mime_Viewer_Partial class allows message/partial messages
 * to be displayed (RFC 2046 [5.2.2]).
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Mime
 */
class IMP_Horde_Mime_Viewer_Partial extends Horde_Mime_Viewer_Driver
{
    /**
     * Can this driver render various views?
     *
     * @var boolean
     */
    protected $_capability = array(
        'embedded' => true,
        'forceinline' => true,
        'full' => false,
        'info' => false,
        'inline' => false,
    );

    /**
     * If this MIME part can contain embedded MIME parts, and those embedded
     * MIME parts exist, return a list of MIME parts that contain the embedded
     * MIME part information.
     *
     * @return mixed  An array with MIME IDs as the keys and Horde_Mime_Part
     *                objects as the parts to replace the current value of
     *                the given MIME ID.
     *                Returns null if no embedded MIME parts exist.
     */
    protected function _getEmbeddedMimeParts()
    {
        $id = $this->_mimepart->getContentTypeParameter('id');
        $number = $this->_mimepart->getContentTypeParameter('number');
        $total = $this->_mimepart->getContentTypeParameter('total');

        if (is_null($id) || is_null($number) || is_null($total)) {
            return null;
        }

        $mbox = $this->_params['contents']->getMailbox();

        /* Perform the search to find the other parts of the message. */
        $query = new Horde_Imap_Client_Search_Query();
        $query->headerText('Content-Type', $id);
        $indices = $GLOBALS['imp_search']->runSearchQuery($query, $mbox);

        /* If not able to find the other parts of the message, print error. */
        if (count($indices) != $total) {
            $mime_part = new Horde_Mime_Part();
            $mime_part->setType('text/plain');
            $mime_part->setCharset(Horde_Nls::getCharset());
            $mime_part->setContents(sprintf(_("[Cannot display message - found only %s of %s parts of this message in the current mailbox.]"), count($indices), $total));
            return array($this->_mimepart->getMimeId() => $mime_part);
        }

        /* Get the contents of each of the parts. */
        $parts = array();
        foreach ($indices as $val) {
            /* No need to fetch the current part again. */
            if ($val == $number) {
                $parts[$number] = $this->_mimepart->getContents();
            } else {
                $ic = IMP_Contents::singleton($val . IMP::IDX_SEP . $mbox);
                $parts[$ic->getMIMEMessage()->getContentTypeParameter('number')] = $ic->getBody();
            }
        }

        /* Sort the parts in numerical order. */
        ksort($parts, SORT_NUMERIC);

        /* Combine the parts. */
        $mime_part = Horde_Mime_Part::parseMessage(implode('', $parts), array('forcemime' => true));
        return ($mime_part === false)
            ? null
            : array($this->_mimepart->getMimeId() => $mime_part);
    }
}
