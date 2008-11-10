<?php
/**
 * The IMP_Horde_Mime_Viewer_partial class allows multipart/partial messages
 * to be displayed (RFC 2046 [5.2.2]).
 *
 * $Horde: imp/lib/MIME/Viewer/partial.php,v 1.39 2008/09/17 05:02:40 slusarz Exp $
 *
 * Copyright 2003-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Mime
 */
class IMP_Horde_Mime_Viewer_partial extends Horde_Mime_Viewer_Driver
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

        $base_ob = &$contents->getBaseObjectPtr();
        $curr_index = $base_ob->getMessageIndex();
        $id = $this->mime_part->getContentTypeParameter('id');
        $parts = array();

        /* Perform the search to find the other parts of the message. */
        $query = new Horde_Imap_Client_Search_Query();
        $query->header('Content-Type', $id);

        $indices = $GLOBALS['imp_search']->runSearchQuery($query, $GLOBALS['imp_mbox']['thismailbox']);

        /* If not able to find the other parts of the message, print error. */
        if (count($indices) != $this->mime_part->getContentTypeParameter('total')) {
            return $this->formatStatusMsg(sprintf(_("Cannot display - found only %s of %s parts of this message in the current mailbox."), count($indices), $this->mime_part->getContentTypeParameter('total')));
        }

        /* Get the contents of each of the parts. */
        foreach ($indices as $val) {
            /* No need to fetch the current part again. */
            if ($val == $curr_index) {
                $parts[$this->mime_part->getContentTypeParameter('number')] = $this->mime_part->getContents();
            } else {
                $imp_contents = &IMP_Contents::singleton($val . IMP::IDX_SEP . $GLOBALS['imp_mbox']['thismailbox']);
                $part = &$imp_contents->getMIMEPart(0);
                $parts[$part->getContentTypeParameter('number')] = $imp_contents->getBody();
            }
        }

        /* Sort the parts in numerical order. */
        ksort($parts, SORT_NUMERIC);

        /* Combine the parts and render the underlying data. */
        $mime_message = &MIME_Message::parseMessage(implode('', $parts));
        $mc = new MIME_Contents($mime_message, array('download' => 'download_attach', 'view' => 'view_attach'), array(&$contents));
        $mc->buildMessage();

        return '<table>' . $mc->getMessage(true) . '</table>';
    }

    /**
     * Return the content-type of the rendered output.
     *
     * @return string  The content-type of the output.
     */
    public function getType()
    {
        return 'text/html; charset=' . NLS::getCharset();
    }
}
