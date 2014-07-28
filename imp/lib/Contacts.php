<?php
/**
 * Copyright 2012-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2012-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Common code dealing with contacts handling.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2012-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Contacts
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
     * Search the addressbook for email addresses.
     *
     * @param string $str  The search string.
     * @param array $opts  Additional options:
     *   - email_exact: (boolean) Require exact match in e-mail?
     *   - levenshtein: (boolean) Do levenshtein sorting of results?
     *   - sources: (array) Use this list of sources instead of default.
     *
     * @return Horde_Mail_Rfc822_List  Results.
     */
    public function searchEmail($str, array $opts = array())
    {
        global $registry;

        if (!$registry->hasMethod('contacts/search')) {
            return new Horde_Mail_Rfc822_List();
        }

        $spref = $this->getAddressbookSearchParams();

        try {
            $search = $registry->call('contacts/search', array($str, array(
                'customStrict' => empty($opts['email_exact']) ? array() : array('email'),
                'fields' => $spref['fields'],
                'returnFields' => array('email', 'name'),
                'rfc822Return' => true,
                'sources' => empty($opts['sources']) ? $spref['sources'] : $opts['sources']
            )));
        } catch (Horde_Exception $e) {
            Horde::log($e, 'ERR');
            return new Horde_Mail_Rfc822_List();
        }

        if (empty($opts['levenshtein'])) {
            return $search;
        }

        $sort_list = array();
        foreach ($search->base_addresses as $val) {
            $sort_list[strval($val)] = @levenshtein($str, $val);
        }
        asort($sort_list, SORT_NUMERIC);

        return new Horde_Mail_Rfc822_List(array_keys($sort_list));
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
