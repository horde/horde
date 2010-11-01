<?php
/**
 * This class handles an OR clause in a search query.
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
class IMP_Search_Element_Or extends IMP_Search_Element
{
    /**
     * Adds the current query item to the query object.
     *
     * @param Horde_Imap_Client_Search_Query  The query object.
     *
     * @return Horde_Imap_Client_Search_Query  The query object.
     *
     */
    public function createQuery($queryob)
    {
        $ob = new Horde_Imap_Client_Search_Query();
        $ob->orSearch(array($queryob));

        return $ob;
    }

    /**
     * Return search query text representation.
     *
     * @return array  The textual description of this search element.
     */
    public function queryText()
    {
        return _("OR");
    }

}
