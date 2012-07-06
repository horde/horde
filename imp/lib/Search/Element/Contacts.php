<?php
/**
 * This class handles the search query for messages sent from a contact
 * located in a user's addressbook.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Search_Element_Contacts extends IMP_Search_Element
{
    /**
     * Constructor.
     *
     * @param boolean $not  If true, do a 'NOT' search.
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
        $ajax = new IMP_Ajax_Imple_ContactAutoCompleter();
        foreach ($ajax->getAddressList()->bare_addresses as $val) {
            $ob = new Horde_Imap_Client_Search_Query();
            $ob->headerText('from', $val, $this->_data);
            $queryob->orSearch($ob);
        }

        return $queryob;
    }

    /**
     */
    public function queryText()
    {
        return $this->_data
            ? _("messages not from a personal contact")
            : _("messages from a personal contact");
    }

}
