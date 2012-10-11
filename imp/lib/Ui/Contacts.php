<?php
/**
 * Common code dealing with contacts handling.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Ui_Contacts
{
    /**
     * Adds a contact to the user defined address book.
     *
     * @param string $addr  The contact's email address.
     * @param string $name  The contact's name.
     *
     * @return string  A link or message to show in the notification area.
     * @throws Horde_Exception
     */
    public function addAddress($addr, $name)
    {
        global $registry, $prefs;

        if (empty($name)) {
            $name = $addr;
        }

        $result = $registry->call('contacts/import', array(array(
            'email' => $addr,
            'name' => $name
        ), 'array', $prefs->getValue('add_source')));

        $escapeName = @htmlspecialchars($name, ENT_COMPAT, 'UTF-8');

        try {
            if ($contact_link = $registry->link('contacts/show', array('uid' => $result, 'source' => $prefs->getValue('add_source')))) {
                return Horde::link(Horde::url($contact_link), sprintf(_("Go to address book entry of \"%s\""), $name)) . $escapeName . '</a>';
            }
        } catch (Horde_Exception $e) {}

        return $escapeName;
    }

    /**
     * Determines parameters needed to do an address search
     *
     * @return array  An array with two keys: 'fields' and 'sources'.
     */
    public function getAddressbookSearchParams()
    {
        global $prefs;

        $fields = json_decode($prefs->getValue('search_fields'), true);
        $src = json_decode($prefs->getValue('search_sources'));

        return array(
            'fields' => empty($fields) ? array() : $fields,
            'sources' => empty($src) ? array() : $src
        );
    }

}
