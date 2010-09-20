<?php
/**
 * This class handles the bulk message search query.
 *
 * Precedence is a non-standard, discouraged header pursuant to RFC 2076
 * [3.9]. However, it is widely used and may be useful in sorting out
 * unwanted e-mail.
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
class IMP_Search_Element_Bulk extends IMP_Search_Element
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
        $queryob->headerText('precedence', 'bulk', $this->_data);

        return $queryob;
    }

    /**
     * Return search query text representation.
     *
     * @return array  The textual description of this search element.
     */
    public function queryText()
    {
        return ($this->_data ? _("not") . ' ' : '') . _("Bulk Messages");
    }

}
