<?php
/**
 * Copyright 2012-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2012-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Common code dealing with contacts handling.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2012-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 *
 * @property-read boolean $changed  Has the internal data changed?
 * @property-read array $fields  The list of configured search fields.
 * @property-read array $sources  The list of configured sources.
 * @property-read array $source_list  The list of sources in the contacts
 *                                    backend.
 */
class IMP_Contacts
implements IteratorAggregate, Serializable
{
    /**
     * Has the internal data changed?
     *
     * @var boolean
     */
    private $_changed = false;

    /**
     * The list of search fields.
     *
     * @var array
     */
    private $_fields;

    /**
     * The list of sources.
     *
     * @var array
     */
    private $_sources;

    /**
     */
    public function __get($name)
    {
        global $registry;

        switch ($name) {
        case 'changed':
            return $this->_changed;

        case 'fields':
        case 'sources':
            if (!isset($this->_fields)) {
                $this->_init();
            }
            return $this->{'_' . $name};

        case 'source_list':
            if ($registry->hasMethod('contacts/sources')) {
                try {
                    return $registry->call('contacts/sources');
                } catch (Horde_Exception $e) {}
            }
            return array();
        }
    }

    /**
     * Clear cached contacts data.
     */
    public function clearCache()
    {
        unset($this->_fields, $this->_sources);
        $this->_changed = true;
    }

    /**
     * Adds e-mail address to the user defined address book.
     *
     * @param Horde_Mail_Rfc822_Object $addr  Address or group to add.
     *
     * @return string  A link or message to show in the notification area
     *                 (already HTML encoded).
     * @throws Horde_Exception
     */
    public function addAddress(Horde_Mail_Rfc822_Object $addr)
    {
        global $registry, $prefs;

        $source = $prefs->getValue('add_source');

        if ($addr instanceof Horde_Mail_Rfc822_Group) {
            $members = array();
            foreach ($addr->addresses as $val) {
                $members[] = array(
                    'email' => $val->bare_address,
                    'name' => $val->label
                );
            }

            $result = $registry->call(
                'contacts/addGroup',
                array(
                    $addr->groupname,
                    $members,
                    array(
                        'source' => $source
                    )
                )
            );
            $uid = $result['uid'];
        } else {
            $uid = $registry->call(
                'contacts/import',
                array(
                    array(
                        'email' => $addr->bare_address,
                        'name' => $addr->label
                    ),
                    'array',
                    $source
                )
            );
        }

        $escapeName = @htmlspecialchars($addr->label, ENT_COMPAT, 'UTF-8');

        try {
            $contact_link = $registry->link(
                'contacts/show',
                array(
                    'uid' => $uid,
                    'source' => $source
                )
            );

            if ($contact_link) {
                return Horde::link(Horde::url($contact_link))
                    . $escapeName . '</a>';
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

        $sources = empty($opts['sources'])
            ? $this->sources
            : $opts['sources'];

        if (empty($opts['email_exact'])) {
            $customStrict = array();
            $fields = $this->fields;
            $returnFields = array('email', 'name');
        } else {
            $customStrict = $returnFields = array('email');
            $fields = array_fill_keys($sources, array('email'));
        }

        try {
            $search = $registry->call('contacts/search', array($str, array(
                'customStrict' => $customStrict,
                'fields' => $fields,
                'returnFields' => $returnFields,
                'rfc822Return' => true,
                'sources' => $sources
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
     * Initializes parameters needed to do an address search.
     */
    private function _init()
    {
        global $prefs;

        $fields = json_decode($prefs->getValue('search_fields'), true);
        $src = json_decode($prefs->getValue('search_sources'));

        $this->_fields = empty($fields) ? array() : $fields;
        $this->_sources = empty($src) ? array() : $src;

        $this->_changed = true;
    }

    /* IteratorAggregate methods. */

    /**
     * Returns the list of all contacts.
     *
     * @return Horde_Mail_Rfc822_List  Listing of all contacts.
     */
    public function getIterator()
    {
        return $this->searchEmail('');
    }

    /* Serializable methods. */

    /**
     */
    public function serialize()
    {
        return json_encode(array(
            $this->_fields,
            $this->_sources
        ));
    }

    /**
     */
    public function unserialize($data)
    {
        list($this->_fields, $this->_sources) = json_decode($data, true);
    }

}
