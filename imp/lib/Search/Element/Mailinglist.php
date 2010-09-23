<?php
/**
 * This class handles the mailing list search query.
 *
 * Uses the List-Id header defined by RFC 2919 to identify mailing list
 * messages.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Search_Element_Mailinglist extends IMP_Search_Element
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
     * Adds the current query item to the query object.
     *
     * @param Horde_Imap_Client_Search_Query  The query object.
     *
     * @return Horde_Imap_Client_Search_Query  The query object.
     */
    public function createQuery($queryob)
    {
        $queryob->headerText('list-id', '', $this->_data);

        return $queryob;
    }

    /**
     * Return search query text representation.
     *
     * @return array  The textual description of this search element.
     */
    public function queryText()
    {
        return ($this->_data ? _("not") . ' ' : '') . _("Mailing List Message");
    }

}
