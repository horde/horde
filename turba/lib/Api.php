<?php
/**
 * Turba external API interface.
 *
 * This file defines Turba's external API interface. Other applications can
 * interact with Turba through this API.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you did
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Turba
 */
class Turba_Api extends Horde_Registry_Api
{
    /**
     * Links.
     *
     * @var array
     */
    protected $_links = array(
        'show' => '%application%/contact.php?source=|source|&key=|key|&uid=|uid|',
        'smartmobile_browse' => '%application%/smartmobile.php#browse'
    );

    /**
     * The listing of API calls that do not require permissions checking.
     *
     * @var array
     */
    protected $_noPerms = array(
        'getClientSource', 'getClient', 'getClients', 'searchClients'
    );

    /**
     * Callback for comment API.
     *
     * @param integer $id  Internal data identifier.
     *
     * @return mixed  Name of object on success, false on failure.
     */
    public function commentCallback($id)
    {
        if (!$GLOBALS['conf']['comments']['allow']) {
            return false;
        }

        @list($source, $key) = explode('.', $id, 2);
        if (isset($GLOBALS['cfgSources'][$source]) && $key) {
            try {
                return $GLOBALS['injector']->getInstance('Turba_Factory_Driver')->create($source)->getObject($key)->getValue('name');
            } catch (Turba_Exception $e) {}
        }

        return false;
    }

    /**
     * Does this API allow comments?
     *
     * @return boolean  True if API allows comments.
     */
    public function hasComments()
    {
        return !empty($GLOBALS['conf']['comments']['allow']);
    }

    /**
     * Returns a list of available sources.
     *
     * @param boolean $writeable  If true, limits to writeable sources.
     *
     * @return array  An array of the available sources. Keys are source IDs,
     *                values are source titles.
     */
    public function sources($writeable = false)
    {
        $out = array();

        foreach (Turba::getAddressBooks($writeable ? Horde_Perms::EDIT : Horde_Perms::READ) as $key => $val) {
            $out[$key] = $val['title'];
        }

        return $out;
    }

    /**
     * Returns a list of fields avaiable in a source.
     *
     * @param string $source  The source name.
     *
     * @return array  An array describing the fields. Keys are the field name,
     *                values are arrays with these keys:
     *   - name: (string) Field name.
     *   - label: (string) Field label.
     *   - search: (boolean) Can this field be searched?
     *   - type: (string) See turba/config/attributes.php.
     *
     * @throws Turba_Exception
     */
    public function fields($source = null)
    {
        global $attributes, $cfgSources;

        if (is_null($source) || !isset($cfgSources[$source])) {
            throw new Turba_Exception(_("Invalid address book."));
        }

        $fields = array();

        foreach (array_keys($cfgSources[$source]['map']) as $name) {
            if (substr($name, 0, 2) != '__') {
                $fields[$name] = array(
                    'label' => $attributes[$name]['label'],
                    'name' => $name,
                    'search' => in_array($name, $cfgSources[$source]['search']),
                    'type' => $attributes[$name]['type']
                );
            }
        }

        return $fields;
    }

    /**
     * Retrieve the UID for the current user's default Turba share.
     *
     * @return string  UID.
     */
    public function getDefaultShare()
    {
        global $injector, $prefs, $session;

        // Bring in a clean copy of sources.
        $cfgSources = Turba::availableSources();

        if ($session->get('turba', 'has_share')) {
            $driver = $injector->getInstance('Turba_Factory_Driver');

            foreach (Turba::listShares(true) as $uid => $share) {
                $params = @unserialize($share->get('params'));
                if (!empty($params['source'])) {
                    try {
                        if ($driver->create($uid)->checkDefaultShare($share, $cfgSources[$params['source']])) {
                            return $uid;
                        }
                    } catch (Turba_Exception $e) {}
                }
            }
        }

        // Return Turba's default_dir as default.
        return $prefs->getValue('default_dir');
    }

    /**
     * Retrieve the UID for the Global Address List source.
     *
     * @return string|boolean  The UID or false if none configured.
     */
    public function getGalUid()
    {
        return empty($GLOBALS['conf']['gal']['addressbook'])
            ? false
            : $GLOBALS['conf']['gal']['addressbook'];
    }

    /**
     * Browses through Turba's object tree.
     *
     * @param string $path       The path of the tree to browse.
     * @param array $properties  The item properties to return. Defaults to
     *                           'name', 'icon', and 'browseable'.
     *
     * @return array  Content of the specified path.
     * @throws Turba_Exception
     */
    public function browse($path = '',
                          $properties = array('name', 'icon', 'browseable'))
    {
        global $injector, $session;

        // Strip off the application name if present
        if (substr($path, 0, 5) == 'turba') {
            $path = substr($path, 5);
        }
        $path = trim($path, '/');

        $results = array();

        if (empty($path)) {
            /* We always provide the "global" folder which contains address
             * book sources that are shared among all users.  Per-user shares
             * are shown in a folder for each respective user. */
            $owners = array(
                'global' => true
            );

            foreach (Turba::listShares() as $share) {
                $owners[$share->get('owner') ? $share->get('owner') : '-system-'] = true;
            }

            $now = time();

            foreach (array_keys($owners) as $owner) {
                if (in_array('name', $properties)) {
                    $results['turba/' . $owner]['name'] = $owner;
                }
                if (in_array('icon', $properties)) {
                    $results['turba/' . $owner]['icon'] = Horde_Themes::img('turba.png');
                }
                if (in_array('browseable', $properties)) {
                    $results['turba/' . $owner]['browseable'] = true;
                }
                if (in_array('contenttype', $properties)) {
                    $results['turba/' . $owner]['contenttype'] = 'httpd/unix-directory';
                }
                if (in_array('contentlength', $properties)) {
                    $results['turba/' . $owner]['contentlength'] = 0;
                }
                if (in_array('modified', $properties)) {
                    // @TODO: Get a real modification date
                    $results['turba/' . $owner]['modified'] = $now;
                }
                if (in_array('created', $properties)) {
                    // @TODO Get a real creation date
                    $results['turba/' . $owner]['created'] = 0;
                }
            }

            return $results;
        }

        $parts = explode('/', $path);

        if (count($parts) == 1) {
            /* We should either have the username that is a valid share owner
             * or 'global'. */
            if (empty($parts[0])) {
                // We need either 'global' or a valid username with shares.
                return array();
            }

            if ($parts[0] == 'global') {
                // The client is requesting a list of global address books.
                $addressbooks = Turba::getAddressBooks();
                foreach ($addressbooks as $addressbook => $info) {
                    if ($info['type'] == 'share') {
                        // Ignore address book shares in the 'global' folder
                        unset($addressbooks[$addressbook]);
                    }
                }
            } else {
                /* Assume $parts[0] is a valid username and we need to list
                 * their shared addressbooks. */
                if (!$session->get('turba', 'has_share')) {
                    // No backends are configured to provide shares
                    return array();
                }
                $addressbooks = $injector->getInstance('Turba_Shares')->listShares($parts[0], array(
                    'attributes' => $parts[0],
                    'perm' => Horde_Perms::READ
                ));

                /* The last check returns all addressbooks for the requested
                 * user, but that does not mean the requesting user has access
                 * to them.
                 * Filter out those address books for which the requesting
                 * user has no access. */
                $addressbooks = Turba::permissionsFilter($addressbooks);
            }

            $curpath = 'turba/' . $parts[0] . '/';
            $now = time();

            foreach ($addressbooks as $addressbook => $info) {
                if (in_array('name', $properties)) {
                    $results[$curpath . $addressbook]['name'] = ($info instanceof Horde_Share_Object)
                        ? $info->get('title')
                        : $info['title'];
                }
                if (in_array('icon', $properties)) {
                    $results[$curpath . $addressbook]['icon'] = Horde_Themes::img('turba.png');
                }
                if (in_array('browseable', $properties)) {
                    $results[$curpath . $addressbook]['browseable'] = true;
                }
                if (in_array('contenttype', $properties)) {
                    $results[$curpath . $addressbook]['contenttype'] = 'httpd/unix-directory';
                }
                if (in_array('contentlength', $properties)) {
                    $results[$curpath . $addressbook]['contentlength'] = 0;
                }
                if (in_array('modified', $properties)) {
                    // @TODO: Get a real modification date
                    $results[$curpath . $addressbook]['modified'] = $now;
                }
                if (in_array('created', $properties)) {
                    // @TODO Get a real creation date
                    $results[$curpath . $addressbook]['created'] = 0;
                }
            }

            return $results;
        }

        if (count($parts) == 2) {
            /* The client is requesting all contacts from a given
             * addressbook. */
            if (empty($parts[0]) || empty($parts[1])) {
                /* $parts[0] must be either 'global' or a valid user with
                 * shares; $parts[1] must be an addressbook ID. */
                return array();
            }

            $addressbooks = Turba::getAddressBooks();
            if (!isset($addressbooks[$parts[1]])) {
                // We must have a valid addressbook to continue.
                return array();
            }

            $contacts = $injector->getInstance('Turba_Factory_Driver')->create($parts[1])->search(array());
            $contacts->reset();

            $curpath = 'turba/' . $parts[0] . '/' . $parts[1] . '/';

            while ($contact = $contacts->next()) {
                $key = $curpath . $contact->getValue('__key');
                if (in_array('name', $properties)) {
                    $results[$key]['name'] = Turba::formatName($contact);
                }
                if (in_array('icon', $properties)) {
                    $results[$key]['icon'] = Horde_Themes::img('mime/vcard.png');
                }
                if (in_array('browseable', $properties)) {
                    $results[$key]['browseable'] = false;
                }
                if (in_array('contenttype', $properties)) {
                    $results[$key]['contenttype'] = 'text/x-vcard';
                }
                if (in_array('contentlength', $properties)) {
                    try {
                        $data = $this->export($contact->getValue('__uid'), 'text/x-vcard', $contact->getSource());
                    } catch (Turba_Exception $e) {
                        $data = '';
                    }
                    $results[$key]['contentlength'] = strlen($data);
                }
                if (in_array('modified', $properties)) {
                    $results[$key]['modified'] = $this->_modified($contact->getValue('__uid'));
                }
                if (in_array('created', $properties)) {
                    $results[$key]['created'] = $this->getActionTimestamp($contact->getValue('__uid'), 'add');
                }
            }

            return $results;
        }

        if (count($parts) == 3) {
            /* The client is requesting an individual contact. */
            $addressbooks = Turba::getAddressBooks();
            if (!isset($addressbooks[$parts[1]])) {
                // We must have a valid addressbook to continue.
                return array();
            }

            // Load the Turba driver.
            $driver = $injector->getInstance('Turba_Factory_Driver')->create($parts[1]);

            $contact = $driver->getObject($parts[2]);

            $result = array(
                'data' => $this->export($contact->getValue('__uid'), 'text/x-vcard', $contact->getSource()),
                'mimetype' => 'text/x-vcard'
            );
            $modified = $this->_modified($contact->getValue('__uid'));
            if (!empty($modified)) {
                $result['mtime'] = $modified;
            }
            return $result;
        }

        throw new Turba_Exception(_("Malformed request."));
    }

    /**
     * Deletes a file from the Turba tree.
     *
     * @param string $path  The path to the file.
     *
     * @return string  The event's UID.
     * @throws Turba_Exception
     */
    public function path_delete($path)
    {
        // Strip off the application name if present
        if (substr($path, 0, 5) == 'turba') {
            $path = substr($path, 5);
        }

        $parts = explode('/', trim($path, '/'));

        if (count($parts) < 3) {
            // Deletes must be on individual contacts.
            throw new Turba_Exception(_("Delete denied."));
        }

        if (!array_key_exists($parts[1], Turba::getAddressBooks())) {
            throw new Turba_Exception(_("Address book does not exist"));
        }

        return $GLOBALS['injector']->getInstance('Turba_Factory_Driver')->create($parts[1])->delete($parts[2]);
    }

    /**
     * Returns an array of UIDs for all contacts that the current user is
     * authorized to see.
     *
     * @param string|array $sources  The name(s) of the source(s) to return
     *                               contacts of. If empty, the current user's
     *                               sync sources or default source are used.
     *
     * @return array  An array of UIDs for all contacts the user can access.
     * @throws Turba_Exception
     */
    public function listUids($sources = null)
    {
        $driver = $GLOBALS['injector']->getInstance('Turba_Factory_Driver');
        $uids = array();

        foreach ($this->_getSources($sources) as $source) {
            try {
                $results = $driver->create($source)->search(array());
            } catch (Turba_Exception $e) {
                throw new Turba_Exception(sprintf(_("Error searching the address book: %s"), $e->getMessage()));
            }

            foreach ($results->objects as $o) {
                if (!$o->isGroup()) {
                    $uids[] = $o->getValue('__uid');
                }
            }
        }

        return $uids;
    }

    /**
     * Returns an array of UIDs for contacts that have had a given action
     * since a certain time.
     *
     * @param string $action         The action to check for - add, modify, or
     *                               delete.
     * @param integer $timestamp     The time to start the search.
     * @param string|array $sources  The source(s) for which to retrieve the
     *                               history.
     * @param integer $end           The optional ending timestamp.
     *
     * @return array  An array of UIDs matching the action and time criteria.
     *
     * @throws Turba_Exception
     */
    public function listBy($action, $timestamp, $sources = null, $end = null)
    {
        $driver = $GLOBALS['injector']->getInstance('Turba_Factory_Driver');
        $history = $GLOBALS['injector']->getInstance('Horde_History');
        $filter = array(
            array(
                'field' => 'action',
                'op' => '=',
                'value' => $action
            )
        );
        $uids = array();

        if (!empty($end)) {
            $filter[] = array(
                'field' => 'ts',
                'op' => '<',
                'value' => $end
            );
        }

        foreach ($this->_getSources($sources) as $source) {
            $sdriver = $driver->create($source);

            $histories = $history->getByTimestamp(
                '>', $timestamp, $filter,
                'turba:' . $sdriver->getName()
            );

            // Filter out groups
            $nguids = str_replace(
                'turba:' . $sdriver->getName() . ':',
                '',
                array_keys($histories)
            );

            $include = array();
            foreach ($nguids as $uid) {
                if ($action != 'delete') {
                    $list = $sdriver->search(array('__uid' => $uid));
                    if ($list->count()) {
                        $object = $list->next();
                        if ($object->isGroup()) {
                            continue;
                        }
                    }
                }
                $include[] = $uid;
            }

            // Strip leading turba:addressbook:.
            $uids = array_merge($uids, $include);
        }

        return $uids;
    }

    /**
     * Method for obtaining all server changes between two timestamps.
     * Essentially a wrapper around listBy(), but returns an array containing
     * all adds, edits, and deletions.
     *
     * @param integer $start  The starting timestamp
     * @param integer $end    The ending timestamp.
     *
     * @return array  A hash with 'add', 'modify' and 'delete' arrays.
     */
    public function getChanges($start, $end)
    {
        return array(
            'add' => $this->listBy('add', $start, null, $end),
            'modify' => $this->listBy('modify', $start, null, $end),
            'delete' => $this->listBy('delete', $start, null, $end)
        );
    }

    /**
     * Returns the timestamp of an operation for a given UID and action.
     *
     * @param string $uid            The UID to look for.
     * @param string $action         The action to check for - add, modify, or
     *                               delete.
     * @param string|array $sources  The source(s) for which to retrieve the
     *                               history.
     *
     * @return integer  The timestamp for this action.
     *
     * @throws Turba_Exception
     */
    public function getActionTimestamp($uid, $action, $sources = null)
    {
        $driver = $GLOBALS['injector']->getInstance('Turba_Factory_Driver');
        $history = $GLOBALS['injector']->getInstance('Horde_History');
        $last = 0;

        foreach ($this->_getSources($sources) as $source) {
            $ts = $history->getActionTimestamp(
                'turba:' . $driver->create($source)->getName() . ':' . $uid,
                $action
            );
            if (!empty($ts) && $ts > $last) {
                $last = $ts;
            }
        }

        return $last;
    }

    /**
     * Import a contact represented in the specified contentType.
     *
     * @param string $content      The content of the contact.
     * @param string $contentType  What format is the data in? Currently
     *                             supports array, text/directory, text/vcard,
     *                             text/x-vcard, and activesync.
     * @param string $source       The source into which the contact will be
     *                             imported.
     *
     * @return string  The new UID.
     *
     * @throws Turba_Exception
     * @throws Turba_Exception_ObjectExists
     */
    public function import($content, $contentType = 'array', $source = null)
    {
        global $cfgSources, $injector, $prefs;

        /* Get default address book from user preferences. */
        if (empty($source) &&
            !($source = $prefs->getValue('default_dir'))) {
            /* On new installations default_dir is not set; use first source
             * instead. */
            $source = key(Turba::getAddressBooks(Horde_Perms::EDIT));
        }

        // Check existence of and permissions on the specified source.
        if (!isset($cfgSources[$source])) {
            throw new Turba_Exception(sprintf(_("Invalid address book: %s"), $source));
        }

        $driver = $injector
            ->getInstance('Turba_Factory_Driver')
            ->create($source);

        if (!$driver->hasPermission(Horde_Perms::EDIT)) {
            throw new Turba_Exception(_("Permission denied"));
        }

        // Create a category manager.
        $cManager = new Horde_Prefs_CategoryManager();
        $categories = $cManager->get();

        if (!($content instanceof Horde_Icalendar_Vcard)) {
            switch ($contentType) {
            case 'activesync':
                $content = $driver->fromASContact($content);
                break;

            case 'array':
                if (!isset($content['emails']) && isset($content['email'])) {
                    $content['emails'] = $content['email'];
                }
                break;

            case 'text/x-vcard':
            case 'text/vcard':
            case 'text/directory':
                $iCal = new Horde_Icalendar();
                if (!$iCal->parsevCalendar($content)) {
                    throw new Turba_Exception(_("There was an error importing the iCalendar data."));
                }

                switch ($iCal->getComponentCount()) {
                case 0:
                    throw new Turba_Exception(_("No vCard data was found."));

                case 1:
                    $content = $iCal->getComponent(0);
                    break;

                default:
                    $ids = array();

                    foreach ($iCal->getComponents() as $c) {
                        if ($c instanceof Horde_Icalendar_Vcard) {
                            $content = $driver->toHash($c);
                            $result = $driver->search($content);
                            if (count($result)) {
                                continue;
                            }

                            $result = $driver->add($content);
                            if (!empty($content['category']) &&
                                !in_array($content['category'], $categories)) {
                                $cManager->add($content['category']);
                                $categories[] = $content['category'];
                            }
                            $ids[] = $result;
                        }
                    }

                    return $ids;
                }
                break;

            default:
                throw new Turba_Exception(sprintf(_("Unsupported Content-Type: %s"), $contentType));
            }
        }

        if ($content instanceof Horde_Icalendar_Vcard) {
            $content = $driver->toHash($content);
        }

        // Check if the entry already exists in the data source.
        $result = $driver->search($content);
        if (count($result)) {
            throw new Turba_Exception_ObjectExists(_("Already Exists"));
        }

        // We can't use $object->setValue() here since that cannot be used
        // with composite fields.
        if (Horde::hookExists('encode_attribute', 'turba')) {
            foreach ($content as $attribute => &$value) {
                try {
                    $value = Horde::callHook('encode_attribute', array($attribute, $value, null, null), 'turba');
                } catch (Turba_Exception $e) {}
            }
        }
        $result = $driver->add($content);

        if (!empty($content['category']) &&
            !in_array($content['category'], $categories)) {
            $cManager->add($content['category']);
        }

        return $driver->getObject($result)->getValue('__uid');
    }

    /**
     * Export a contact, identified by UID, in the requested contentType.
     *
     * @param string $uid            Identify the contact to export.
     * @param mixed $contentType     What format should the data be in?  Either
     *                               a string with one of: - text/directory -
     *                               text/vcard - text/x-vcard The first two
     *                               produce a vcard3.0 (rfc2426), the second
     *                               produces a vcard in old 2.1 format
     *                               defined by imc.org Also supports a raw
     *                               array
     * @param string|array $sources  The source(s) from which the contact will
     *                               be exported.
     * @param array $fields          Hash of field names and
     *                               Horde_SyncMl_Property properties with the
     *                               requested fields.
     * @param array $options         Any additional options to be passed to the
     *                               exporter.
     *
     * @return mixed  The requested data.
     * @throws Turba_Exception
     */
    public function export($uid, $contentType, $sources = null, $fields = null, array $options = array())
    {
        if (empty($uid)) {
            throw new Turba_Exception(_("Invalid ID"));
        }

        $driver = $GLOBALS['injector']->getInstance('Turba_Factory_Driver');

        foreach ($this->_getSources($sources) as $source) {
            $sdriver = $driver->create($source);

            if (!$sdriver->hasPermission(Horde_Perms::READ)) {
                continue;
            }

            $result = $sdriver->search(array('__uid' => $uid));
            if (count($result) == 0) {
                continue;
            } elseif (count($result) > 1) {
                throw new Turba_Exception("Internal Horde Error: multiple Turba objects with same objectId.");
            }

            $version = '3.0';
            list($contentType,) = explode(';', $contentType);

            switch ($contentType) {
            case 'text/x-vcard':
                $version = '2.1';
                // Fall-through

            case 'text/vcard':
            case 'text/directory':
                $export = '';
                foreach ($result->objects as $obj) {
                    $vcard = $sdriver->tovCard($obj, $version, $fields);
                    /* vCards are not enclosed in
                     * BEGIN:VCALENDAR..END:VCALENDAR.  Export the individual
                     * cards instead. */
                    $export .= $vcard->exportvCalendar();
                }
                return $export;

            case 'array':
                $attributes = array();
                foreach ($result->objects as $object) {
                    foreach (array_keys($GLOBALS['cfgSources'][$source]['map']) as $field) {
                        $attributes[$field] = $object->getValue($field);
                    }
                }
                return $attributes;

            case 'activesync':
                foreach ($result->objects as $object) {
                    $return = $object;
                }
                return $sdriver->toASContact($return, $options);

            default:
                throw new Turba_Exception(sprintf(_("Unsupported Content-Type: %s"), $contentType));
            }
        }

        throw new Turba_Exception(_("Object not found."));
    }

    /**
     * Exports the user's own contact as a vCard string.
     *
     * @return string  The requested vCard data.
     * @throws Turba_Exception
     */
    public function ownVCard()
    {
        $contact = $this->getOwnContactObject();

        $vcard = $GLOBALS['injector']->getInstance('Turba_Factory_Driver')->create($contact['source'])->tovCard($contact['contact'], '3.0', null, true);
        $vcard->setAttribute('VERSION', '3.0');

        return $vcard->exportvCalendar();
    }

    /**
     * Export the user's own contact as a hash.
     *
     * @return array  The contact hash.
     * @throws Turba_Exception
     */
    public function ownContact()
    {
        $contact = $this->getOwnContactObject();
        return $contact['contact']->getAttributes();
    }

    /**
     * Helper function to return the user's own contact object
     *
     * @return array  An array containing the following keys:
     *   - contact: (Turba_Object) Object representing the user's own contact.
     *   - source: (string) The source of the user's own contact.
     * @throws Turba_Exception
     */
    public function getOwnContactObject()
    {
        $own_contact = $GLOBALS['prefs']->getValue('own_contact');
        if (empty($own_contact)) {
            throw new Turba_Exception(_("You didn't mark a contact as your own yet."));
        }
        @list($source, $id) = explode(';', $own_contact);

        if (!isset($GLOBALS['cfgSources'][$source])) {
            throw new Turba_Exception(_("The address book with your own contact doesn't exist anymore."));
        }

        $driver = $GLOBALS['injector']->getInstance('Turba_Factory_Driver')->create($source);

        if (!$driver->hasPermission(Horde_Perms::READ)) {
            throw new Turba_Exception(_("You don't have sufficient permissions to read the address book that contains your own contact."));
        }

        try {
            $contact = $driver->getObject($id);
        } catch (Turba_Exception $e) {
            throw new Turba_Exception(_("Your own contact cannot be found in the address book."));
        }

        return array(
            'contact' => $contact,
            'source'=> $source
        );
    }

    /**
     * Deletes a contact identified by UID.
     *
     * @param string|array $uid      Identify the contact to delete, either a
     *                               single UID or an array.
     * @param string|array $sources  The source(s) from which the contact will
     *                               be deleted.
     *
     * @return boolean  Success or failure.
     * @throws Turba_Exception
     */
    public function delete($uid, $sources = null)
    {
        if (empty($uid)) {
            throw new Turba_Exception(_("Invalid ID"));
        }

        // Handle an array of UIDs for convenience of deleting multiple
        // contacts at once.
        if (is_array($uid)) {
            foreach ($uid as $g) {
                if (!$this->delete($g, $sources)) {
                    return false;
                }
            }

            return true;
        }

        $driver = $GLOBALS['injector']->getInstance('Turba_Factory_Driver');

        foreach ($this->_getSources($sources) as $source) {
            $sdriver = $driver->create($source);

            if (!$GLOBALS['registry']->isAdmin() &&
                !$sdriver->hasPermission(Horde_Perms::DELETE)) {
                continue;
            }

            // If the objectId isn't in $source in the first place, just
            // return true. Otherwise, try to delete it and return success or
            // failure.
            $result = $sdriver->search(array('__uid' => $uid));
            if (count($result) != 0) {
                $r = $result->objects[0];
                return $sdriver->delete($r->getValue('__key'));
            }
        }

        return true;
    }

    /**
     * Replaces the contact identified by UID with the content represented in
     * the specified contentType.
     *
     * @param string $uid            Idenfity the contact to replace.
     * @param mixed  $content        The content of the contact.
     * @param string $contentType    What format is the data in? Currently
     *                               supports array, text/directory,
     *                               text/vcard, text/x-vcard and activesync.
     * @param string|array $sources  The source(s) where the contact will be
     *                               replaced.
     *
     * @return boolean  Success or failure.
     * @throws Turba_Exception
     */
    public function replace($uid, $content, $contentType, $sources = null)
    {
        if (empty($uid)) {
            throw new Turba_Exception(_("Invalid contact unique ID"));
        }

        $driver = $GLOBALS['injector']->getInstance('Turba_Factory_Driver');

        foreach ($this->_getSources($sources) as $source) {
            $sdriver = $driver->create($source);

            // Check permissions.
            if (!$sdriver->hasPermission(Horde_Perms::EDIT)) {
                continue;
            }

            $result = $sdriver->search(array('__uid' => $uid));
            if (!count($result)) {
                continue;
            } elseif (count($result) > 1) {
                throw new Turba_Exception(_("Multiple contacts found with same unique ID."));
            }

            $object = $result->objects[0];

            switch ($contentType) {
            case 'activesync':
                $content = $sdriver->fromASContact($content);
                /* Must check for ghosted properties for activesync requests */
                foreach ($content as $attribute => $value) {
                    if ($attribute != '__key') {
                        $object->setValue($attribute, $value);
                    }
                }
                return $object->store();

            case 'array':
                break;

            case 'text/x-vcard':
            case 'text/vcard':
            case 'text/directory':
                $iCal = new Horde_Icalendar();
                if (!$iCal->parsevCalendar($content)) {
                    throw new Turba_Exception(_("There was an error importing the iCalendar data."));
                }

                switch ($iCal->getComponentCount()) {
                case 0:
                    throw new Turba_Exception(_("No vCard data was found."));

                case 1:
                    $content = $sdriver->toHash($iCal->getComponent(0));
                    break;

                default:
                    throw new Turba_Exception(_("Only one vcard supported."));
                }
                break;

            default:
                throw new Turba_Exception(sprintf(_("Unsupported Content-Type: %s"), $contentType));
            }

            foreach ($content as $attribute => $value) {
                if ($attribute != '__key') {
                    $object->setValue($attribute, $value);
                }
            }

            return $object->store();
        }

        throw new Turba_Exception(_("Object not found."));
    }

    /**
     * Returns a contact search result.
     *
     * @param mixed $names  The search filter values.
     * @param array $opts   Optional parameters:
     *   - customStrict: (array) An array of fields that must match exactly.
     *                   DEFAULT: None
     *   - fields: (array) The fields to search on.
     *             DEFAULT: All fields
     *   - forceSource: (boolean) Whether to use the specified sources, even
     *                  if they have been disabled in the preferences?
     *                  DEFAULT: false
     *   - matchBegin: (boolean) Match word boundaries only?
     *                 DEFAULT: false
     *   - returnFields: Only return these fields.
     *                   DEFAULT: Return all fields.
     *   - rfc822Return: Return a Horde_Mail_Rfc822_List object.
     *                   DEFAULT: Returns an array of search results.
     *   - sources: (array) The sources to search in.
     *              DEFAULT: Search the user's default address book
     *   - count_only: (boolean) If true, only return the count of matching
     *                           results.
     *                 DEFAULT: false (Return the full data set).
     *
     * @return mixed  Either a hash containing the search results or a
     *                Rfc822 List object (if 'rfc822Return' is true).
     * @throws Turba_Exception
     */
    public function search($names = null, array $opts = array())
    {
        global $attributes, $cfgSources, $injector;

        $opts = array_merge(array(
            'fields' => array(),
            'forceSource' => false,
            'matchBegin' => false,
            'returnFields' => array(),
            'rfc822Return' => false,
            'sources' => array(),
            'customStrict' => array(),
            'count_only' => false,
        ), $opts);

        $results = !empty($opts['count_only'])
            ? 0
            : (empty($opts['rfc822Return'])
                ? array()
                : new Horde_Mail_Rfc822_List());

        if (!isset($cfgSources) ||
            !is_array($cfgSources) ||
            !count($cfgSources) ||
            is_null($names)) {
            return $results;
        }

        if (!is_array($names)) {
            $names = array($names);
        }

        if (!$opts['forceSource']) {
            // Make sure the selected source is activated in Turba.
            $addressbooks = array_keys(Turba::getAddressBooks());
            foreach (array_keys($opts['sources']) as $id) {
                if (!in_array($opts['sources'][$id], $addressbooks)) {
                    unset($opts['sources'][$id]);
                }
            }
        }

        // ...and ensure the default source is used as a default.
        if (!count($opts['sources'])) {
            $opts['sources'] = array(Turba::getDefaultAddressbook());
        }

        $driver = $injector->getInstance('Turba_Factory_Driver');
        foreach ($opts['sources'] as $source) {
            // Skip invalid sources -or-
            // skip sources that aren't browseable if the search is empty.
            if (!isset($cfgSources[$source]) ||
                (empty($cfgSources[$source]['browse']) &&
                 (!count($names) ||
                  ((count($names) == 1) && empty($names[0]))))) {

                continue;
            }

            $sdriver = $driver->create($source);

            foreach ($names as $name) {
                $trimname = trim($name);
                $out = $criteria = array();
                if (strlen($trimname)) {
                    if (isset($opts['fields'][$source])) {
                        foreach ($opts['fields'][$source] as $field) {
                            $criteria[$field] = $trimname;
                        }
                    }
                    if (!count($criteria)) {
                        $criteria['name'] = $trimname;
                    }
                }

                $search = $sdriver->search(
                    $criteria,
                    Turba::getPreferredSortOrder(),
                    'OR',
                    $opts['returnFields'],
                    $opts['customStrict'],
                    $opts['matchBegin'],
                    $opts['count_only']
                );
                if ($opts['count_only']) {
                    $results += $search;

                    continue;
                } elseif (!($search instanceof Turba_List)) {
                    continue;
                }

                $rfc822 = new Horde_Mail_Rfc822();

                while ($ob = $search->next()) {
                    $emails = $seen = array();

                    if ($ob->isGroup()) {
                        /* Is a distribution list. */
                        $members = $ob->listMembers();
                        if (!($members instanceof Turba_List) ||
                            !count($members)) {
                            continue;
                        }

                        $listatt = $ob->getAttributes();
                        $listName = $ob->getValue('name');

                        while ($ob = $members->next()) {
                            foreach (array_keys($ob->getAttributes()) as $key) {
                                $value = $ob->getValue($key);
                                if (empty($value)) {
                                    continue;
                                }

                                $seen_key = trim(Horde_String::lower($ob->getValue('name'))) . trim(Horde_String::lower(is_array($value) ? $value['load']['file'] : $value));

                                if (isset($attributes[$key]) &&
                                    ($attributes[$key]['type'] == 'email') &&
                                    empty($seen[$seen_key])) {
                                    $emails[] = $value;
                                    $seen[$seen_key] = true;
                                }
                            }
                        }

                        if (empty($opts['rfc822Return'])) {
                            $out[] = array(
                                'email' => implode(', ', $emails),
                                'id' => $listatt['__key'],
                                'name' => $listName,
                                'source' => $source
                            );
                        } else {
                            $results->add(new Horde_Mail_Rfc822_Group($listName, $emails));
                        }
                    } else {
                        /* Not a group. */
                        $att = array(
                            '__key' => $ob->getValue('__key')
                        );

                        foreach (array_keys($ob->driver->getCriteria()) as $key) {
                            $att[$key] = $ob->getValue($key);
                        }

                        $email = new Horde_Mail_Rfc822_List();

                        foreach (array_keys($att) as $key) {
                            if ($ob->getValue($key) &&
                                isset($attributes[$key]) &&
                                ($attributes[$key]['type'] == 'email')) {
                                // Multiple addresses support
                                $email->add($rfc822->parseAddressList($ob->getValue($key), array(
                                    'limit' => (isset($attributes[$key]['params']) && is_array($attributes[$key]['params']) && !empty($attributes[$key]['params']['allow_multi'])) ? 0 : 1
                                )));
                            }
                        }

                        $display_name = ($ob->hasValue('name') || !isset($ob->driver->alternativeName))
                            ? Turba::formatName($ob)
                            : $ob->getValue($ob->driver->alternativeName);

                        if (count($email)) {
                            foreach ($email as $val) {
                                $seen_key = trim(Horde_String::lower($display_name)) . '/' . Horde_String::lower($val->bare_address);
                                if (empty($seen[$seen_key])) {
                                    $seen[$seen_key] = true;
                                    if (empty($opts['rfc822Return'])) {
                                        $emails[] = $val->bare_address;
                                    } else {
                                        $val->personal = $display_name;
                                        $results->add($val);
                                    }
                                }
                            }
                        } elseif (empty($opts['rfc822Return'])) {
                            $emails[] = null;
                        }

                        if (empty($opts['rfc822Return'])) {
                            foreach ($emails as $val) {
                                $out[] = array_merge($att, array(
                                    '__type' => 'Object',
                                    'email' => $val,
                                    'id' => $att['__key'],
                                    'name' => $display_name,
                                    'source' => $source
                                ));
                            }
                        }
                    }
                }

                if (!empty($out)) {
                    $results[$name] = $out;
                }
            }
        }

        return $results;
    }

    /**
     * Retrieves a contact.
     *
     * @param string $source    The source name where the contact is stored
     * @param string $objectId  The unique id of the contact to retrieve
     *
     * @return array  The retrieved contact.
     * @throws Turba_Exception
     */
    public function getContact($source = null, $objectId = '')
    {
        global $cfgSources;

        if (!isset($cfgSources) ||
            !is_array($cfgSources) ||
            !isset($cfgSources[$source])) {
            return array();
        }

        $attributes = array();
        $object = $GLOBALS['injector']->getInstance('Turba_Factory_Driver')->create($source)->getObject($objectId);

        foreach (array_keys($cfgSources[$source]['map']) as $field) {
            $attributes[$field] = $object->getValue($field);
        }

        return $attributes;
    }

    /**
     * Retrieves a set of contacts from a single source.
     *
     * @param string $source    The source name where the contact is stored
     * @param array $objectIds  The unique ids of the contact to retrieve.
     *
     * @return array  The retrieved contact.
     * @throws Turba_Exception
     */
    public function getContacts($source = '', array $objectIds = array())
    {
        global $cfgSources;

        if (!isset($cfgSources) ||
            !is_array($cfgSources) ||
            !isset($cfgSources[$source])) {
            return array();
        }

        if (!is_array($objectIds)) {
            $objectIds = array($objectIds);
        }

        $objects = $GLOBALS['injector']->getInstance('Turba_Factory_Driver')->create($source)->getObjects($objectIds);
        $results = array();

        foreach ($objects as $object) {
            $attributes = array();
            foreach (array_keys($cfgSources[$source]['map']) as $field) {
                $attributes[$field] = $object->getValue($field);
            }
            $results[] = $attributes;
        }

        return $results;
    }

    /**
     * Retrieves a list of all possible values of a field in specified
     * source(s).
     *
     * @param string $field   Field name to check.
     * @param array $sources  Array containing the sources to look in.
     *
     * @return array  An array of fields and possible values.
     * @throws Turba_Exception
     */
    public function getAllAttributeValues($field = '',
                                          array $sources = array())
    {
        global $cfgSources;

        if (!isset($cfgSources) || !is_array($cfgSources)) {
            return array();
        }

        if (!count($sources)) {
            $sources = array(Turba::getDefaultAddressbook());
        }

        $driver = $GLOBALS['injector']->getInstance('Turba_Factory_Driver');
        $results = array();

        foreach ($sources as $source) {
            if (isset($cfgSources[$source])) {
                $res = $driver->create($source)->search(array());
                if (!($res instanceof Turba_List)) {
                    throw new Turba_Exception(_("Search failed"));
                }

                while ($ob = $res->next()) {
                    if ($ob->hasValue($field)) {
                        $results[$source . ':' . $ob->getValue('__key')] = array(
                            'email' => $ob->getValue('email'),
                            'name' => $ob->getValue('name'),
                            $field => $ob->getValue($field)
                        );
                    }
                }
            }
        }

        return $results;
    }

    /**
     * Retrieves a list of available time objects categories.
     *
     * @return array  An array of all configured time object categories.
     */
    public function listTimeObjectCategories()
    {
        $categories = array();

        foreach ($GLOBALS['attributes'] as $key => $attribute) {
            if (($attribute['type'] == 'monthdayyear') &&
                !empty($attribute['time_object_label'])) {
                foreach ($GLOBALS['cfgSources'] as $srcKey => $source) {
                    if (!empty($source['map'][$key])) {
                        $categories[$key . '/'. $srcKey] =array(
                            'title' => sprintf(_("%s in %s"), $attribute['time_object_label'], $source['title']),
                            'type' => 'share');
                    }
                }
            }
        }

        return $categories;
    }

    /**
     * Lists birthdays and/or anniversaries as time objects.
     *
     * @param array $time_categories  The time categories (from
     *                                listTimeObjectCategories) to list.
     * @param mixed $start            The start date of the period.
     * @param mixed $end              The end date of the period.
     *
     * @return array  An array of timeObject results.
     * @throws Turba_Exception
     */
    public function listTimeObjects($time_categories, $start, $end)
    {
        $start = new Horde_Date($start);
        $end = new Horde_Date($end);

        $driver = $GLOBALS['injector']->getInstance('Turba_Factory_Driver');
        $objects = array();

        foreach ($time_categories as $category) {
            list($category, $source) = explode('/', $category, 2);
            $objects = array_merge($objects, $driver->create($source)->listTimeObjects($start, $end, $category));
        }

        return $objects;
    }

    /**
     * Returns the client source name.
     *
     * @return string  The name of the source to use with the clients api.
     */
    public function getClientSource()
    {
        return empty($GLOBALS['conf']['client']['addressbook'])
            ? false
            : $GLOBALS['conf']['client']['addressbook'];
    }

    /**
     * Returns the available client fields.
     *
     * @return array  An array describing the fields.
     */
    public function clientFields()
    {
        return $this->fields($GLOBALS['conf']['client']['addressbook']);
    }

    /**
     * Returns a contact from the client source.
     *
     * @param string $objectId  Client unique ID.
     *
     * @return array  Array of client data.
     * @throws Turba_Exception
     */
    public function getClient($objectId = '')
    {
        return $this->getContact($GLOBALS['conf']['client']['addressbook'], $objectId);
    }

    /**
     * Returns mulitple contacts from the client source.
     *
     * @param array $objectIds  client unique ids.
     *
     * @return array  An array of clients data.
     * @throws Turba_Exception
     */
    public function getClients($objectIds = array())
    {
        return $this->getContacts($GLOBALS['conf']['client']['addressbook'], $objectIds);
    }

    /**
     * Adds a client to the client source.
     *
     * @param array $attributes  Array containing the client attributes.
     *
     * @return boolean
     */
    public function addClient(array $attributes = array())
    {
        return $this->import($attributes, 'array', $this->getClientSource());
    }

    /**
     * Updates client data.
     *
     * @param string $objectId   The unique id of the client.
     * @param array $attributes  An array of client attributes.
     *
     * @return boolean
     */
    public function updateClient($objectId = '', array $attributes = array())
    {
        return $this->replace($this->getClientSource() . ':' . $objectId, $attributes, 'array');
    }

    /**
     * Deletes a client
     *
     * @param string $objectId  The unique id of the client
     *
     * @return boolean
     */
    public function deleteClient($objectId = '')
    {
        return $this->delete($this->getClientSource() . ':' . $objectId);
    }

    /**
     * Search for clients.
     *
     * @param array $names         The search filter values.
     * @param array $fields        The fields to search in.
     * @param boolean $matchBegin  Match word boundaries only.
     *
     * @return array  A hash containing the search results.
     * @throws Turba_Exception
     */
    public function searchClients(array $names = array(),
                                  array $fields = array(),
                                  $matchBegin = false)
    {
        $abook = $GLOBALS['conf']['client']['addressbook'];
        return $this->search(
            $names,
            array('sources' => array($abook),
                  'fields' => array($abook => $fields),
                  'matchBegin' => $matchBegin,
                  'forceSource' => true)
        );
    }

    /**
     * Sets the value of the specified attribute of a contact
     *
     * @param string|array $address  Contact email address(es).
     * @param string $name           Contact name.
     * @param string $field          Field to update.
     * @param string $value          Field value to set.
     * @param string $source         Contact source.
     *
     * @throws Turba_Exception
     */
    public function addField($address = '', $name = '', $field = '',
                             $value = '', $source = '')
    {
        if (is_array($address)) {
            $e = null;
            $success = 0;

            foreach ($address as $tmp) {
                try {
                    $this->addField($tmp, $name, $field, $value, $source);
                    ++$success;
                } catch (Exception $e) {}
            }

            if ($e) {
                if ($success) {
                    throw new Turba_Exception(sprintf(ngettext("Added or updated %d contact, but at least one contact failed:", "Added or updated %d contacts, but at least one contact failed:", $success), $success) . ' ' . $e->getMessage());
                } else {
                    throw $e;
                }
            }
        }

        global $cfgSources;

        if (empty($source) || !isset($cfgSources[$source])) {
            throw new Turba_Exception(sprintf(_("Invalid address book: %s"), $source));
        }

        if (empty($address)) {
            throw new Turba_Exception(_("Invalid email"));
        }

        if (empty($name)) {
            throw new Turba_Exception(_("Invalid name"));
        }

        if (empty($value)) {
            throw new Turba_Exception(_("Invalid entry"));
        }

        $driver = $GLOBALS['injector']->getInstance('Turba_Factory_Driver')->create($source);

        if (!$driver->hasPermission(Horde_Perms::EDIT)) {
            throw new Turba_Exception(_("Permission denied"));
        }

        try {
            $res = $driver->search(array('email' => trim($address)), null, 'AND');
        } catch (Turba_Exception $e) {
            throw new Turba_Exception(sprintf(_("Search failed: %s"), $res->getMessage()));
        }

        if (count($res) > 1) {
            try {
                $res2 = $driver->search(array('email' => trim($address), 'name' => trim($name)), null, 'AND');
            } catch (Turba_Exception $e) {
                throw new Turba_Exception(sprintf(_("Search failed: %s"), $e->getMessage()));
            }

            if (!count($res2)) {
                throw new Turba_Exception(sprintf(_("Multiple persons with address [%s], but none with name [%s] already exist"), trim($address), trim($name)));
            }

            try {
                $res3 = $driver->search(array('email' => $address, 'name' => $name, $field => $value));
            } catch (Turba_Exception $e) {
                throw new Turba_Exception(sprintf(_("Search failed: %s"), $e->getMessage()));
            }

            if (count($res3)) {
                throw new Turba_Exception(sprintf(_("This person already has a %s entry in the address book"), $field));
            }

            $ob = $res2->next();
            $ob->setValue($field, $value);
            $ob->store();
        } elseif (count($res) == 1) {
            try {
                $res4 = $driver->search(array('email' => $address, $field => $value));
            } catch (Turba_Exception $e) {
                throw new Turba_Exception(sprintf(_("Search failed: %s"), $e->getMessage()));
            }

            if (count($res4)) {
                throw new Turba_Exception(sprintf(_("This person already has a %s entry in the address book"), $field));
            }

            $ob = $res->next();
            $ob->setValue($field, $value);
            $ob->store();
        } else {
            $driver->add(array('email' => $address, 'name' => $name, $field => $value, '__owner' => $GLOBALS['registry']->getAuth()));
        }
    }

    /**
     * Returns a field value.
     *
     * @param string $address    Contact email address.
     * @param string $field      Field to get.
     * @param array $sources     Sources to check.
     * @param boolean $strict    Match the email address strictly.
     * @param boolean $multiple  Return more than one entry if found and true,
     *                           return an error if this is false.
     *
     * @return array  An array of field value(s).
     * @throws Turba_Exception
     */
    public function getField($address = '', $field = '', $sources = array(),
                             $strict = false, $multiple = false)
    {
        global $cfgSources;

        if (empty($address)) {
            throw new Turba_Exception(_("Invalid email"));
        }

        if (!isset($cfgSources) || !is_array($cfgSources)) {
            return array();
        }

        if (!count($sources)) {
            $sources = array(Turba::getDefaultAddressbook());
        }

        $driver = $GLOBALS['injector']->getInstance('Turba_Factory_Driver');
        $result = array();

        foreach ($sources as $source) {
            if (!isset($cfgSources[$source])) {
                continue;
            }

            $sdriver = $driver->create($source);
            $criterium = array('email' => $address);
            if (!isset($sdriver->map['email'])) {
                if (isset($sdriver->map['emails'])) {
                    $criterium = array('emails' => $address);
                } else {
                    continue;
                }
            }

            try {
                $list = $sdriver->search($criterium, null, 'AND', array(), $strict ? array('email') : array());
            } catch (Turba_Exception $e) {
                Horde::logMessage($e, 'ERR');
            }

            if ($list instanceof Turba_List) {
                while ($ob = $list->next()) {
                    if ($ob->hasValue($field)) {
                        $result[] = $ob->getValue($field);
                    }
                }
            }
        }

        if (count($result) > 1) {
            if ($multiple) {
                return $result;
            } else {
                throw new Turba_Exception(_("More than 1 entry found"));
            }
        } elseif (empty($result)) {
            throw new Turba_Exception(sprintf(_("No %s entry found for %s"), $field, $address));
        }

        return reset($result);
    }

    /**
     * Deletes a field value.
     *
     * @param string $address  Contact email address.
     * @param string $field    Field to delete value for.
     * @param array $sources   Sources to delete value from.
     *
     * @throws Turba_Exception
     */
    public function deleteField($address = '', $field = '', $sources = array())
    {
        global $cfgSources;

        if (!strlen($address)) {
            throw new Turba_Exception(_("Invalid email"));
        }

        if (!isset($cfgSources) || !is_array($cfgSources)) {
            return;
        }

        if (count($sources) == 0) {
            $sources = array(Turba::getDefaultAddressbook());
        }

        $driver = $GLOBALS['injector']->getInstance('Turba_Factory_Driver');
        $success = false;

        foreach ($sources as $source) {
            if (isset($cfgSources[$source])) {
                $sdriver = $driver->create($source);
                if (!$sdriver->hasPermission(Horde_Perms::EDIT)) {
                    continue;
                }

                $res = $sdriver->search(array('email' => $address));
                if ($res instanceof Turba_List) {
                    if (count($res) == 1) {
                        $ob = $res->next();
                        if (is_object($ob) && $ob->hasValue($field)) {
                            $ob->setValue($field, '');
                            $ob->store();
                            $success = true;
                        }
                    }
                }
            }
        }

        if (!$success) {
            throw new Turba_Exception(sprintf(_("No %s entry found for %s"), $field, $address));
        }
    }

    /**
     * Obtain an array of $cfgSource entries matching the filter criteria.
     *
     * @param type $filter  A single key -> value hash to filter the sources.
     *
     * @return array
     */
    public function getSourcesConfig($filter = array())
    {
        $results = array();
        $turba_sources = Horde::loadConfiguration('backends.php', 'cfgSources', 'turba');

        foreach ($turba_sources as $key => $source) {
            if (!empty($filter)) {
                if (!empty($source[current(array_keys($filter))]) &&
                    $source[current(array_keys($filter))] == current($filter)) {
                    $results[$key] = $source;
                }
            }
        }

        return $results;
    }

    /**
     * Lists all shares the current user has access to.
     *
     * @param integer $perms
     *
     * @return  array of Turba_Share objects.
     */
    public function listShares($perms = Horde_Perms::READ)
    {
        return Turba::listShares(true, $perms);
    }

    /**
     * GroupObject API - Lists all turba lists for the current user that can
     * be treated as Horde_Group objects.
     *
     * @return array  A hash of all visible groups in the form of
     *                group_id => group_name
     * @throws Horde_Exception
     */
    public function listUserGroupObjects()
    {
        $groups = $owners = array();

        // Only turba's SQL based sources can act as Horde_Groups
        $sources = $this->getSourcesConfig(array('type' => 'sql'));

        foreach ($sources as $key => $source) {
            // Each source could have a different database connection
            $db[$key] = empty($source['params']['sql'])
                    ? $GLOBALS['injector']->getInstance('Horde_Db_Adapter')
                    : $GLOBALS['injector']->getInstance('Horde_Core_Factory_Db')->create('turba', $source['params']['sql']);

            if ($source['use_shares']) {
                if (empty($contact_shares)) {
                    $contact_shares = $this->listShares(Horde_Perms::SHOW);
                }
                foreach ($contact_shares as $id => $share) {
                    $params = @unserialize($share->get('params'));
                    if ($params['source'] == $key) {
                        $owners[] = $params['name'];
                    }
                }
                if (!$owners) {
                    return array();
                }
            } else {
                $owners = array($GLOBALS['registry']->getAuth());
            }

            $owner_ids = array();
            foreach ($owners as $owner) {
                $owner_ids[] = $db[$key]->quoteString($owner);
            }

            $sql = 'SELECT ' . $source['map']['__key'] . ', ' . $source['map'][$source['list_name_field']]
                . '  FROM ' . $source['params']['table'] . ' WHERE '
                . $source['map']['__type'] . ' = \'Group\' AND '
                . $source['map']['__owner'] . ' IN (' . implode(',', $owner_ids ) . ')';

            try {
                $results = $db[$key]->selectAssoc($sql);
            } catch (Horde_Db_Exception $e) {
                Horde::logMessage($e);
                throw new Horde_Exception_Wrapped($e);
            }
            foreach ($results as $id => $name) {
                $groups[$key . ':' . $id] = $name;
            }
        }

        return $groups;
    }

    /**
     * Returns all contact groups.
     *
     * @return array  A list of group hashes.
     * @throws Turba_Exception
     */
    public function getGroupObjects()
    {
        $ret = array();

        foreach ($this->getSourcesConfig(array('type' => 'sql')) as $key => $source) {
            if (empty($source['map']['__type'])) {
                continue;
            }

            list($db, $sql) = $this->_getGroupObject($source, 'Group');

            try {
                $results = $db->selectAll($sql);
            } catch (Horde_Db_Exception $e) {
                Horde::logMessage($e);
                throw new Turba_Exception($e);
            }

            foreach ($results as $row) {
                /* name is a reserved word in Postgresql (at a minimum). */
                $row['name'] = $row['lname'];
                unset($row['lname']);
                $ret[$key . ':' . $row['id']] = $row;
            }
        }

        return $ret;
    }

    /**
     * Returns all contact groups that the specified user is a member of.
     *
     * @param string $user           The user.
     * @param boolean $parentGroups  Include user as a member of the any
     *                               parent group as well.
     *
     * @return array  An array of group identifiers that the specified user is
     *                a member of.
     * @throws Horde_Exception
     */
    public function getGroupMemberships($user, $parentGroups = false)
    {
        $memberships = array();

        foreach ($this->getGroupObjects() as $id => $list) {
            if (in_array($user, $this->getGroupMembers($id, $parentGroups))) {
                $memberships[$id] = $list['name'];
            }
        }

        return $memberships;
    }

    /**
     * Returns a contact group hash.
     *
     * @param string $gid  The group identifier.
     *
     * @return array  A hash defining the group.
     * @throws Turba_Exception
     */
    public function getGroupObject($gid)
    {
        if (empty($gid) || (strpos($gid, ':') === false)) {
            throw new Turba_Exception(sprintf('Unsupported group id: %s', $gid));
        }

        $sources = $this->getSourcesConfig(array('type' => 'sql'));
        list($source, $id) = explode(':', $gid);
        if (empty($sources[$source])) {
            return array();
        }

        list($db, $sql) = $this->_getGroupObject($sources[$source], $id);

        try {
            $ret = $db->selectOne($sql);
            $ret['name'] = $ret['lname'];
            unset($ret['lname']);
            return $ret;
        } catch (Horde_Db_Exception $e) {
            Horde::logMessage($e);
            throw new Turba_Exception($e);
        }
    }

    /**
     */
    protected function _getGroupObject($source, $key)
    {
        $db = empty($source['params']['sql'])
            ? $GLOBALS['injector']->getInstance('Horde_Db_Adapter')
            : $GLOBALS['injector']->getInstance('Horde_Core_Factory_Db')->create('turba', $source['params']['sql']);

        $sql = 'SELECT ' . $source['map']['__members'] . ' members,'
            . $source['map']['email'] . ' email,'
            . $source['map'][$source['list_name_field']]
            . ' lname FROM ' . $source['params']['table'] . ' WHERE '
            . $source['map']['__key'] . ' = ' . $db->quoteString($key);

        return array($db, $sql);
    }

    /**
     * Returns a list of all members belonging to a contact group.
     *
     * @param string $gid         The group identifier
     * @param boolean $subGroups  Also include members of any subgroups?
     *
     * @return array An array of group members (identified by email address).
     * @throws Horde_Exception
     */
    public function getGroupMembers($gid, $subGroups = false)
    {
        $contact_shares = $this->listShares(Horde_Perms::SHOW);
        $sources = $this->getSourcesConfig(array('type' => 'sql'));

        $entry = $this->getGroupObject($gid);
        if (!$entry) {
            return array();
        }
        list($source,) = explode(':', $gid);
        $members = @unserialize($entry['members']);
        if (!is_array($members)) {
            return array();
        }

        $db[$source] = empty($sources[$source]['params']['sql'])
            ? $GLOBALS['injector']->getInstance('Horde_Db_Adapter')
            : $GLOBALS['injector']->getInstance('Horde_Core_Factory_Db')->create('turba', $sources[$source]['params']['sql']);

        $users = array();
        foreach ($members as $member) {
            // Is this member from the same source or a different one?
            if (strpos($member, ':') !== false) {
                list($newSource, $uid) = explode(':', $member);
                if (!empty($contact_shares[$newSource])) {
                    $params = @unserialize($contact_shares[$newSource]->get('params'));
                    $newSource = $params['source'];
                    $member = $uid;
                    $db[$newSource] = empty($sources[$newSource]['params']['sql'])
                        ? $GLOBALS['injector']->getInstance('Horde_Db_Adapter')
                        : $GLOBALS['injector']->getInstance('Horde_Core_Factory_Db')->create('turba', $sources[$newSource]['params']['sql']);

                } elseif (empty($sources[$newSource])) {
                    // Last chance, it's not in one of our non-share sources
                    continue;
                }
            } else {
                // Same source
                $newSource = $source;
            }

            $type = $sources[$newSource]['map']['__type'];
            $email = $sources[$newSource]['map']['email'];
            $sql = 'SELECT ' . $email . ', ' . $type
                . ' FROM ' . $sources[$newSource]['params']['table']
                . ' WHERE ' . $sources[$newSource]['map']['__key']
                . ' = ' . $db[$newSource]->quoteString($member);

            try {
                $results = $db[$newSource]->selectOne($sql);
            } catch (Horde_Db_Exception $e) {
                Horde::logMessage($e);
                throw new Horde_Exception_Wrapped($e);
            }

            // Sub-Lists are treated as sub groups the best that we can...
            if ($subGroups && $results[$type] == 'Group') {
                $users = array_merge($users, $this->getGroupMembers($newSource . ':' . $member));
            }
            if (strlen($results[$email])) {
                // use a key to dump dups
                $users[$results[$email]] = true;
            }
        }

        ksort($users);
        return array_keys($users);
    }

    /* Helper methods. */

    /**
     */
    private function _modified($uid)
    {
        $modified = $this->getActionTimestamp($uid, 'modify');
        if (empty($modified)) {
            $modified = $this->getActionTimestamp($uid, 'add');
        }
        return $modified;
    }

    /**
     * @throws Turba_Exception
     */
    private function _getSources($sources)
    {
        /* Get default address book from user preferences. */
        if (empty($sources)) {
            $sources = @unserialize($GLOBALS['prefs']->getValue('sync_books'));
        } elseif (!is_array($sources)) {
            $sources = array($sources);
        }

        if (empty($sources)) {
            $sources = array(Turba::getDefaultAddressbook());
            if (empty($sources)) {
                throw new Turba_Exception(_("No address book specified"));
            }
        }

        foreach ($sources as $val) {
            if (!strlen($val) || !isset($GLOBALS['cfgSources'][$val])) {
                throw new Turba_Exception(sprintf(_("Invalid address book: %s"), $val));
            }
        }

        return $sources;
    }

}
