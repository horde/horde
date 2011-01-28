<?php
/**
 * This class handles the attachment search query.
 *
 * Right now, uses a tremendously simplistic algorithm: it checks if the
 * base part is 'multipart/mixed' or 'message/rfc822'.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Search_Element_Attachment extends IMP_Search_Element
{
    /**
     * Constructor.
     *
     * @param boolean $not  If true, do a 'NOT' search of $text.
     */
    public function __construct($not = false)
    {
        /* Data element: (integer) Do a NOT search? */
        $this->_data = intval($not);
    }

    /**
     */
    public function createQuery($mbox, $queryob)
    {
        $ob = new Horde_Imap_Client_Search_Query();
        $ob2 = clone $ob;
        $ob3 = clone $ob;

        $ob->headerText('content-type', 'multipart/mixed');
        $ob2->headerText('content-type', 'message/rfc822');

        /* These searches are OR'd together.  Only 1 must match. */
        $ob3->orSearch(array($ob, $ob2));

        /* ...but the combined OR search must be AND'd with the rest of the
         * search terms. */
        $queryob->andSearch(array($ob3));

        return $queryob;
    }

    /**
     */
    public function queryText()
    {
        return $this->_data
            ? _("messages without attachment(s)")
            : _("messages with attachment(s)");
    }

}
