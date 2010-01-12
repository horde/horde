<?php
/**
 * Copyright 2005-2007 Alkaloid Networks <http://www.alkaloid.net>
 *
 * See the enclosed file LICENSE for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @author Ben Klang <ben@alkaloid.net>
 * @package Beatnik
 */
class Horde_Form_ContactSearch extends Horde_Form
{
    function Horde_Form_ContactSearch(&$vars)
    {
        parent::Horde_Form($vars, _("Search for Client Contact Record"));

        $this->addVariable(_("Name"), 'name', 'text', true, false, _("Enter a few characters to search for all clients whose names contain the search text"));

        $name = $vars->get('name');
        if (!empty($name)) {
            $results = $GLOBALS['registry']->call('contacts/searchClients', array($name));
            // We only pass one search string so there is only one element at
            // the top level of the results array.
            $results = array_pop($results);
            $contacts = array();
            foreach ($results as $contact) {
                $contacts[$contact['__uid']] = $contact['name'];
            }
            asort($contacts);
            $this->addVariable(_("Contact"), 'uid', 'radio', true, false, _("Select the matching contact record or begin a new search above"), array($contacts));
            return true;
        }
    }
}
