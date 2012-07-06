<?php
/**
 * This class handles the attachment search query.
 *
 * Right now, uses a tremendously simplistic algorithm: it checks if the
 * base part is 'multipart/mixed' or 'message/rfc822'.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
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
        $this->_data = intval(!empty($not));
    }

    /**
     */
    public function createQuery($mbox, $queryob)
    {
        $ob = new Horde_Imap_Client_Search_Query();
        $ob2 = clone $ob;
        $ob3 = clone $ob;

        $ob->headerText('content-type', 'multipart/mixed', $this->_data);
        $ob2->headerText('content-type', 'message/rfc822', $this->_data);

        /* If regular search, searches are OR'd: only one must match.
         * If NOT search, searches are AND'd: both must not match. */
        if ($this->_data) {
            $ob3->andSearch(array($ob, $ob2));
        } else {
            $ob3->orSearch(array($ob, $ob2));
        }

        /* ...but the combined search must be AND'd with the rest of the
         * search terms. */
        $queryob->andSearch($ob3);

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
