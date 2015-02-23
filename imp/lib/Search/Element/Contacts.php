<?php
/**
 * Copyright 2011-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2011-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Search query for messages sent from a contact located in a user's
 * addressbook.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2011-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
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
        global $injector;

        $contacts = $injector->getInstance('IMP_Contacts');

        foreach ($contacts as $val) {
            if ($val instanceof Horde_Mail_Rfc822_Address) {
                $ob = new Horde_Imap_Client_Search_Query();
                $ob->headerText('from', $val->bare_address, $this->_data);
                $queryob->orSearch($ob);
            }
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
