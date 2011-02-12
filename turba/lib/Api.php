<?php
/**
 * Turba external API interface.
 *
 * This file defines Turba's external API interface. Other applications can
 * interact with Turba through this API.
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you did
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/asl.php ASL
 * @package  Turba
 */
class Turba_Api extends Horde_Registry_Api
{
    /**
     * Links.
     *
     * @var array
     */
    public $links = array(
        'show' => '%application%/contact.php?source=|source|&key=|key|&uid=|uid|'
    );

    /**
     * The listing of API calls that do not require permissions checking.
     *
     * @var array
     */
    public $noPerms = array(
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
                $driver = $GLOBALS['injector']->getInstance('Turba_Factory_Driver')->create($source);
                $object = $driver->getObject($key)->getValue('name');
            } catch (Turba_Exception $e) {}
        }

        return false;
    }

    /**
     * Returns if applications allows comments
     *
     * @return boolean
     */
    public function hasComments()
    {
        return $GLOBALS['conf']['comments']['allow'];
    }

    /**
     * Returns a list of available sources.
     *
     * @param boolean $writeable  Set to true to limit to writeable sources.
     *
     * @return array  An array of the available sources.
     */
    public function sources($writeable = false)
    {
        $addressbooks = Turba::getAddressBooks($writeable ? Horde_Perms::EDIT : Horde_Perms::READ);
        foreach ($addressbooks as $addressbook => $config) {
            $addressbooks[$addressbook] = $config['title'];
        }

        return $addressbooks;
    }

    /**
     * Returns a list of fields avaiable in a source.
     *
     * @param string $source  The name of the source
     *
     * @return array  An array describing the fields.
     * @throws Turba_Exception
     */
    public function fields($source = null)
    {
        global $cfgSources, $attributes;

        if (empty($source) || !isset($cfgSources[$source])) {
            throw new Turba_Exception(sprintf(_("Invalid address book: %s"), $source));
        }

        $fields = array();
        foreach ($cfgSources[$source]['map'] as $field_name => $null) {
            if (substr($field_name, 0, 2) != '__') {
                $fields[$field_name] = array('name' => $field_name,
                    'type' => $attributes[$field_name]['type'],
                    'label' => $attributes[$field_name]['label'],
                    'search' => in_array($field_name, $cfgSources[$source]['search']));
            }
        }

        return $fields;
    }

    /**
     * Retrieve the UID for the current user's default Turba share.
     */
    public function getDefaultShare()
    {
        global $prefs, $session;

        // Bring in a clean copy of sources.
        $cfgSources = Turba::availableSources();

        if ($session->get('turba', 'has_share')) {
            $shares = Turba::listShares(true);
            foreach ($shares as $uid => $share) {
                $params = @unserialize($share->get('params'));
                if (empty($params['source'])) {
                    continue;
                }

                try {
                    $driver = $GLOBALS['injector']->getInstance('Turba_Factory_Driver')->create($params['source']);
                    if ($driver->checkDefaultShare($share, $cfgSources[$params['source']])) {
                        return $uid;
                    }
                } catch (Turba_Exception $e) {}
            }
        }

        // Return Turba's default_dir as default
        return $prefs->getValue('default_dir');
    }

    /**
     * Retrieve the UID for the Global Address List source, or false if none
     * configured.
     *
     * @return string | boolean  The UID or false if none configured.
     */
    public function getGalUid()
    {
        if (!empty($GLOBALS['conf']['gal']['addressbook'])) {
            return $GLOBALS['conf']['gal']['addressbook'];
        }

        return false;
    }

    private function _modified($uid)
    {
        $modified = $this->getActionTimestamp($uid, 'modify');
        if (empty($modified)) {
            $modified = $this->getActionTimestamp($uid, 'add');
        }
        return $modified;
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
    public function browse($path = '', $properties = array())
    {
        global $registry, $session, $cfgSources;

        // Default properties.
        if (!$properties) {
            $properties = array('name', 'icon', 'browseable');
        }

        // Strip off the application name if present
        if (substr($path, 0, 5) == 'turba') {
            $path = substr($path, 5);
        }
        $path = trim($path, '/');
        $parts = explode('/', $path);

        $now = time();
        $results = array();
        if (empty($path)) {
            // We always provide the "global" folder which contains address book
            // sources that are shared among all users.  Per-user shares are shown
            // in a folder for each respective user.
            $results = array();
            $shares = Turba::listShares();
            $owners = array('global' => true);
            foreach ($shares as $share) {
                $owners[$share->get('owner') ? $share->get('owner') : '-system-'] = true;
            }

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
        } elseif (count($parts) == 1) {
            //
            // We should either have the username that is a valid share owner or
            // 'global'
            //
            if (empty($parts[0])) {
                // We need either 'global' or a valid username with shares
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
                // Assume $parts[0] is a valid username and we need to list their
                // shared addressbooks.
                if (!$session->get('turba', 'has_share')) {
                    // No backends are configured to provide shares
                    return array();
                }
                $addressbooks = $GLOBALS['turba_shares']->listShares(
                    $parts[0],
                    array('perm' => Horde_Perms::READ,
                          'attributes' => $parts[0]));
                // The last check returns all addressbooks for the requested user,
                // but that does not mean the requesting user has access to them.
                // Filter out those address books for which the requesting user has
                // no access.
                $addressbooks = Turba::permissionsFilter($addressbooks);
            }

            $curpath = 'turba/' . $parts[0] . '/';
            foreach ($addressbooks as $addressbook => $info) {
                if (in_array('name', $properties)) {
                    if ($info instanceof Horde_Share_Object) {
                        $name = $info->get('title');
                    } else {
                        $name = $info['title'];
                    }
                    $results[$curpath . $addressbook]['name'] = $name;
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

        } elseif (count($parts) == 2) {
            //
            // The client is requesting all contacts from a given addressbook
            //
            if (empty($parts[0]) || empty($parts[1])) {
                // $parts[0] must be either 'global' or a valid user with shares
                // $parts[1] must be an address book ID
                return array();
            }

            $addressbooks = Turba::getAddressBooks();
            if (!isset($addressbooks[$parts[1]])) {
                // We must have a valid addressbook to continue.
                return array();
            }

            // Load the Turba driver.
            $driver = $GLOBALS['injector']->getInstance('Turba_Factory_Driver')->create($parts[1]);

            $contacts = $driver->search(array());

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

        } elseif (count($parts) == 3) {
            //
            // The client is requesting an individual contact
            //
            $addressbooks = Turba::getAddressBooks();
            if (!isset($addressbooks[$parts[1]])) {
                // We must have a valid addressbook to continue.
                return array();
            }

            // Load the Turba driver.
            $driver = $GLOBALS['injector']->getInstance('Turba_Factory_Driver')->create($parts[1]);

            $contact = $driver->getObject($parts[2]);

            $result = array('data' => $this->export($contact->getValue('__uid'), 'text/x-vcard', $contact->getSource()),
                'mimetype' => 'text/x-vcard');
            $modified = $this->_modified($contact->getValue('__uid'));
            if (!empty($modified)) {
                $result['mtime'] = $modified;
            }
            return $result;
        } else {
            throw new Turba_Exception(_("Malformed request."));
        }
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
        global $registry, $cfgSources;

        // Strip off the application name if present
        if (substr($path, 0, 5) == 'turba') {
            $path = substr($path, 5);
        }
        $path = trim($path, '/');
        $parts = explode('/', $path);

        $now = time();
        $results = array();

        if (count($parts) < 3) {
            // Deletes must be on individual contacts
            throw new Turba_Exception(_("Delete denied."));
        }
        if (!array_key_exists($parts[1], Turba::getAddressBooks())) {
            throw new Turba_Exception("Address book does not exist");
        }

        // Load the Turba driver.
        $driver = $GLOBALS['injector']->getInstance('Turba_Factory_Driver')->create($parts[1]);

        return $driver->delete($parts[2]);
    }

    /**
     * Returns an array of UIDs for all contacts that the current user is
     * authorized to see.
     *
     * @param string|array $sources  The name(s) of the source(s) to return
     *                               contacts of. If left empty, the current
     *                               user's sync sources or default source are
     *                               used.
     *
     * @return array  An array of UIDs for all contacts the user can access.
     * @throws Turba_Exception
     */
    public function listUids($sources = null)
    {
        global $cfgSources, $prefs;

        /* Get default address book from user preferences. */
        if (empty($sources)) {
            $sources = @unserialize($prefs->getValue('sync_books'));
        } elseif (!is_array($sources)) {
            $sources = array($sources);
        }
        if (empty($sources)) {
            $sources = array(Turba::getDefaultAddressbook());
        }
        if (empty($sources)) {
            throw new Turba_Exception(_("No address book specified"));
        }

        $uids = array();
        foreach ($sources as $source) {
            if (empty($source) || !isset($cfgSources[$source])) {
                throw new Turba_Exception(sprintf(_("Invalid address book: %s"), $source));
            }

            $storage = $GLOBALS['injector']->getInstance('Turba_Factory_Driver')->create($source);

            try {
                $results = $storage->search(array());
            } catch (Turba_Exception $e) {
                throw new Turba_Exception(sprintf(_("Error searching the address book: %s"), $e->getMessage()));
            }

            foreach ($results->objects as $o) {
                $uids[] = $o->getValue('__uid');
            }
        }

        return $uids;
    }

    /**
     * Returns an array of UIDs for contacts that have had $action happen since
     * $timestamp.
     *
     * @param string  $action        The action to check for - add, modify, or
     *                               delete.
     * @param integer $timestamp     The time to start the search.
     * @param string|array $sources  The source(s) for which to retrieve the
     *                               history.
     * @param integer $end           The optinal ending timestamp.
     *
     * @return array  An array of UIDs matching the action and time criteria.
     *
     * @throws Turba_Exception
     * @throws InvalidArgumentException
     */
    public function listBy($action, $timestamp, $sources = null, $end = null)
    {
        global $prefs, $cfgSources;

        /* Get default address book from user preferences. */
        if (empty($sources)) {
            $sources = @unserialize($prefs->getValue('sync_books'));
        } elseif (!is_array($sources)) {
            $sources = array($sources);
        }
        if (empty($sources)) {
            $sources = array(Turba::getDefaultAddressbook());
        }
        if (empty($sources)) {
            throw new Turba_Exception(_("No address book specified"));
        }

        $uids = array();
        $history = $GLOBALS['injector']->getInstance('Horde_History');
        $filter = array(array('op' => '=', 'field' => 'action', 'value' => $action));
        if (!empty($end)) {
            $filter[] = array('op' => '<', 'field' => 'ts', 'value' => $end);
        }
        foreach ($sources as $source) {
            if (empty($source) || !isset($cfgSources[$source])) {
                throw new Turba_Exception(sprintf(_("Invalid address book: %s"), $source));
            }

            $driver = $GLOBALS['injector']->getInstance('Turba_Factory_Driver')->create($source);

            $histories = $history->getByTimestamp(
                '>', $timestamp, $filter,
                'turba:' . $driver->getName());

            // Strip leading turba:addressbook:.
            $uids = array_merge($uids,
                                str_replace('turba:' . $driver->getName() . ':',
                                            '',
                                            array_keys($histories)));
        }

        return $uids;
    }

    /**
     * Method for obtaining all server changes between two timestamps. Basically
     * a wrapper around listBy(), but returns an array containing all adds,
     * edits and deletions.
     *
     * @param integer $start             The starting timestamp
     * @param integer $end               The ending timestamp.
     *
     * @return array  An hash with 'add', 'modify' and 'delete' arrays.
     */
    public function getChanges($start, $end)
    {
        return array('add' => $this->listBy('add', $start, null, $end),
                     'modify' => $this->listBy('modify', $start, null, $end),
                     'delete' => $this->listBy('delete', $start, null, $end));
    }

    /**
     * Returns the timestamp of an operation for a given uid an action.
     *
     * @param string $uid            The uid to look for.
     * @param string $action         The action to check for - add, modify, or
     *                               delete.
     * @param string|array $sources  The source(s) for which to retrieve the
     *                               history.
     *
     * @return integer  The timestamp for this action.
     *
     * @throws Turba_Exception
     * @throws InvalidArgumentException
     */
    public function getActionTimestamp($uid, $action, $sources = null)
    {
        global $prefs, $cfgSources;

        /* Get default address book from user preferences. */
        if (empty($sources)) {
            $sources = @unserialize($prefs->getValue('sync_books'));
        } elseif (!is_array($sources)) {
            $sources = array($sources);
        }
        if (empty($sources)) {
            $sources = array(Turba::getDefaultAddressbook());
        }
        if (empty($sources)) {
            throw new Turba_Exception(_("No address book specified"));
        }

        $last = 0;
        $history = $GLOBALS['injector']->getInstance('Horde_History');
        foreach ($sources as $source) {
            if (empty($source) || !isset($cfgSources[$source])) {
                throw new Turba_Exception(sprintf(_("Invalid address book: %s"), $source));
            }

            $driver = $GLOBALS['injector']->getInstance('Turba_Factory_Driver')->create($source);

            $ts = $history->getActionTimestamp('turba:' . $driver->getName()
                . ':' . $uid,
                $action);
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
     * @return string  The new UID, or false on failure.
     * @throws Turba_Exception
     */
    public function import($content, $contentType = 'array',
                           $import_source = null)
    {
        global $cfgSources, $prefs;

        /* Get default address book from user preferences. */
        if (empty($import_source)) {
            $import_source = $prefs->getValue('default_dir');
            /* On new installations default_dir is not set, use first source
             * instead. */
            if (empty($import_source)) {
                $import_source = key(Turba::getAddressBooks(Horde_Perms::EDIT));
            }
        }

        // Check existance of and permissions on the specified source.
        if (!isset($cfgSources[$import_source])) {
            throw new Turba_Exception(sprintf(_("Invalid address book: %s"), $import_source));
        }

        $driver = $GLOBALS['injector']->getInstance('Turba_Factory_Driver')->create($import_source);

        if (!$driver->hasPermission(Horde_Perms::EDIT)) {
            throw new Turba_Exception(_("Permission denied"));
        }

        /* Create a category manager. */
        $cManager = new Horde_Prefs_CategoryManager();
        $categories = $cManager->get();

        if (!($content instanceof Horde_Icalendar_Vcard)) {
            switch ($contentType) {
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

            case 'activesync':
                $content = $driver->fromASContact($content);
                break;

            default:
                throw new Turba_Exception(sprintf(_("Unsupported Content-Type: %s"), $contentType));
            }
        }

        if ($content instanceof Horde_Icalendar_Vcard) {
            $content = $driver->toHash($content);
        }

        // Check if the entry already exists in the data source:
        $result = $driver->search($content);
        if (count($result)) {
            $o = $result->objects[0];
            throw new Turba_Exception(_("Already Exists"));
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
     * @param string|array $sources The source(s) from which the contact will
     *                               be exported.
     * @param array $fields          Hash of field names and SyncML_Property
     *                               properties with the requested fields.
     *
     * @return mixed  The requested data.
     * @throws Turba_Exception
     */
    public function export($uid, $contentType, $sources = null, $fields = null)
    {
        global $cfgSources, $prefs;

        /* Get default address book from user preferences. */
        if (empty($sources)) {
            $sources = @unserialize($prefs->getValue('sync_books'));
        } elseif (!is_array($sources)) {
            $sources = array($sources);
        }
        if (empty($sources)) {
            $sources = array(Turba::getDefaultAddressbook());
        }
        if (empty($sources)) {
            throw new Turba_Exception(_("No address book specified"));
        }

        foreach ($sources as $source) {
            if (empty($source) || !isset($cfgSources[$source])) {
                throw new Turba_Exception(sprintf(_("Invalid address book: %s"), $source));
            }

            if (empty($uid)) {
                throw new Turba_Exception(_("Invalid ID"));
            }

            $driver = $GLOBALS['injector']->getInstance('Turba_Factory_Driver')->create($source);

            if (!$driver->hasPermission(Horde_Perms::READ)) {
                continue;
            }

            $result = $driver->search(array('__uid' => $uid));
            if (count($result) == 0) {
                continue;
            } elseif (count($result) > 1) {
                throw new Turba_Exception("Internal Horde Error: multiple turba objects with same objectId.");
            }

            $version = '3.0';
            list($contentType,) = explode(';', $contentType);
            switch ($contentType) {
            case 'text/x-vcard':
                $version = '2.1';

            case 'text/vcard':
            case 'text/directory':
                $export = '';
                foreach ($result->objects as $obj) {
                    $vcard = $driver->tovCard($obj, $version, $fields);
                    /* vCards are not enclosed in
                     * BEGIN:VCALENDAR..END:VCALENDAR.  Export the individual
                     * cards instead. */
                    $export .= $vcard->exportvCalendar();
                }
                return $export;

            case 'array':
                $attributes = array();
                foreach ($result->objects as $object) {
                    foreach ($cfgSources[$source]['map'] as $field => $map) {
                        $attributes[$field] = $object->getValue($field);
                    }
                }

                return $attributes;

            case 'activesync':
                foreach ($result->objects as $object) {
                    $return = $object;
                }

                return $driver->toASContact($return);
            }

            throw new Turba_Exception(sprintf(_("Unsupported Content-Type: %s"), $contentType));
        }

        throw new Turba_Exception(_("Object not found"));
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
        $driver = $GLOBALS['injector']->getInstance('Turba_Factory_Driver')->create($contact['source']);

        $vcard = $driver->tovCard($contact['contact'], '3.0', null, true);
        $vcard->setAttribute('VERSION', '3.0');

        return $vcard->exportvCalendar();
    }

    /**
     * Export the user's own contact as a hash
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
     * Helper function to  return the user's own contact object
     *
     * @return array  A hash containing the Turba_Object representing the
     *                user's own contact and the source that it is from.
     * @throws Turba_Exception
     */
    public function getOwnContactObject()
    {
        global $cfgSources;

        $own_contact = $GLOBALS['prefs']->getValue('own_contact');
        if (empty($own_contact)) {
            throw new Turba_Exception(_("You didn't mark a contact as your own yet."));
        }
        @list($source, $id) = explode(';', $own_contact);

        if (!isset($cfgSources[$source])) {
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
        // Handle an array of UIDs for convenience of deleting multiple contacts
        // at once.
        if (is_array($uid)) {
            foreach ($uid as $g) {
                $this->delete($uid, $source);
            }

            return true;
        }

        global $cfgSources, $prefs;

        /* Get default address book from user preferences. */
        if (empty($sources)) {
            $sources = @unserialize($prefs->getValue('sync_books'));
        } elseif (!is_array($sources)) {
            $sources = array($sources);
        }
        if (empty($sources)) {
            $sources = array(Turba::getDefaultAddressbook());
        }
        if (empty($sources)) {
            throw new Turba_Exception(_("No address book specified"));
        }

        foreach ($sources as $source) {
            if (empty($source) || !isset($cfgSources[$source])) {
                throw new Turba_Exception(sprintf(_("Invalid address book: %s"), $source));
            }

            if (empty($uid)) {
                throw new Turba_Exception(_("Invalid ID"));
            }

            $driver = $GLOBALS['injector']->getInstance('Turba_Factory_Driver')->create($source);

            if (!$GLOBALS['registry']->isAdmin() &&
                !$driver->hasPermission(Horde_Perms::DELETE)) {
                continue;
            }

            // If the objectId isn't in $source in the first place, just return
            // true. Otherwise, try to delete it and return success or failure.
            $result = $driver->search(array('__uid' => $uid));
            if (count($result) == 0) {
                continue;
            }

            $r = $result->objects[0];
            return $driver->delete($r->getValue('__key'));
        }

        return true;
    }

    /**
     * Replaces the contact identified by UID with the content represented in
     * the specified contentType.
     *
     * @param string $uid            Idenfity the contact to replace.
     * @param string $content        The content of the contact.
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
        global $cfgSources, $prefs;

        /* Get default address book from user preferences. */
        if (empty($sources)) {
            $sources = @unserialize($prefs->getValue('sync_books'));
        } elseif (!is_array($sources)) {
            $sources = array($sources);
        }
        if (empty($sources)) {
            $sources = array(Turba::getDefaultAddressbook());
        }
        if (empty($sources)) {
            throw new Turba_Exception(_("No address book specified"));
        }

        foreach ($sources as $source) {
            if (empty($source) || !isset($cfgSources[$source])) {
                throw new Turba_Exception(sprintf(_("Invalid address book: %s"), $source));
            }

            if (empty($uid)) {
                throw new Turba_Exception(_("Invalid contact unique ID"));
            }

            // Check permissions.
            $driver = $GLOBALS['injector']->getInstance('Turba_Factory_Driver')->create($source);
            if (!$driver->hasPermission(Horde_Perms::EDIT)) {
                continue;
            }
            $result = $driver->search(array('__uid' => $uid));
            if (!count($result)) {
                continue;
            } elseif (count($result) > 1) {
                throw new Turba_Exception(_("Multiple contacts found with same unique ID."));
            }

            $object = $result->objects[0];

            switch ($contentType) {
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
                    $content = $iCal->getComponent(0);
                    $content = $driver->toHash($content);
                    break;

                default:
                    throw new Turba_Exception(_("Only one vcard supported."));
                }
                break;
            case 'activesync':
                $content = $driver->fromASContact($content);
                /* Must check for ghosted properties for activesync requests */
                foreach ($content as $attribute => $value) {
                    if ($attribute != '__key') {
                        $object->setValue($attribute, $value);
                    }
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

        throw new Turba_Exception(_("Object not found"));
    }

    /**
     * Returns a contact search result.
     *
     * @param array $names          The search filter values.
     * @param array $sources        The sources to search in.
     * @param array $fields         The fields to search on.
     * @param boolean $matchBegin   Match word boundaries only?
     * @param boolean $forceSource  Whether to use the specified sources, even
     *                              if they have been disabled in the
     *                              preferences?
     *
     * @return array  Hash containing the search results.
     * @throws Turba_Exception
     */
    public function search($names = array(), $sources = array(),
                           $fields = array(), $matchBegin = false,
                           $forceSource = false)
    {
        global $cfgSources, $attributes, $prefs;

        if (!isset($cfgSources) || !is_array($cfgSources) || !count($cfgSources)) {
            return array();
        }

        if (!is_array($names)) {
            $names = is_null($names) ? array() : array($names);
        }

        if (!$forceSource) {
            // Make sure the selected source is activated in Turba.
            $addressbooks = array_keys(Turba::getAddressBooks());
            foreach (array_keys($sources) as $id) {
                if (!in_array($sources[$id], $addressbooks)) {
                    unset($sources[$id]);
                }
            }
        }

        // ...and ensure the default source is used as a default.
        if (!count($sources)) {
            $sources = array(Turba::getDefaultAddressBook());
        }

        // Read the columns to display from the preferences.
        $sort_columns = Turba::getColumns();

        $results = array();
        $seen = array();
        foreach ($sources as $source) {
            // Skip invalid sources.
            if (!isset($cfgSources[$source])) {
                continue;
            }

            // Skip sources that aren't browseable if the search is empty.
            if (empty($cfgSources[$source]['browse']) &&
                (!count($names) || (count($names) == 1 && empty($names[0])))) {
                    continue;
                }

            $driver = $GLOBALS['injector']->getInstance('Turba_Factory_Driver')->create($source);

            // Determine the name of the column to sort by.
            $columns = isset($sort_columns[$source])
                ? $sort_columns[$source] : array();

            foreach ($names as $name) {
                $criteria = array();
                if (isset($fields[$source])) {
                    foreach ($fields[$source] as $field) {
                        $criteria[$field] = trim($name);
                    }
                }
                if (count($criteria) == 0) {
                    $criteria['name'] = trim($name);
                }

                $search = $driver->search($criteria, Turba::getPreferredSortOrder(), 'OR', array(), array(), $matchBegin);
                if (!($search instanceof Turba_List)) {
                    continue;
                }

                while ($ob = $search->next()) {
                    if (!$ob->isGroup()) {
                        /* Not a group. */
                        $att = array('__key' => $ob->getValue('__key'));
                        foreach ($ob->driver->getCriteria() as $info_key => $info_val) {
                            $att[$info_key] = $ob->getValue($info_key);
                        }
                        $email = array();
                        foreach (array_keys($att) as $key) {
                            if (!$ob->getValue($key) ||
                                !isset($attributes[$key]) ||
                                $attributes[$key]['type'] != 'email') {
                                    continue;
                            }
                            $email_val = $ob->getValue($key);

                            // Multiple addresses support
                            if (isset($attributes[$key]['params'])
                                && is_array($attributes[$key]['params'])
                                && !empty($attributes[$key]['params']['allow_multi'])) {
                                $addrs = Horde_Mime_Address::explode($email_val);
                            } else {
                                $addrs = array($email_val);
                            }

                            foreach ($addrs as $addr) {
                                $email[] = trim($addr);
                            }
                        }

                        if ($ob->hasValue('name') ||
                            !isset($ob->driver->alternativeName)) {
                            $display_name = Turba::formatName($ob);
                        } else {
                            $display_name = $ob->getValue($ob->driver->alternativeName);
                        }
                        if (count($email)) {
                            for ($i = 0; $i < count($email); $i++) {
                                $seen_key = trim(Horde_String::lower($display_name)) . '/' . trim(Horde_String::lower($email[$i]));
                                if (!empty($seen[$seen_key])) {
                                    continue;
                                }
                                $seen[$seen_key] = true;
                                if (!isset($results[$name])) {
                                    $results[$name] = array();
                                }
                                $results[$name][] = array_merge($att,
                                    array('id' => $att['__key'],
                                    'name' => $display_name,
                                    'email' => $email[$i],
                                    '__type' => 'Object',
                                    'source' => $source));
                            }
                        } else {
                            if (!isset($results[$name])) {
                                $results[$name] = array();
                            }
                            $results[$name][] = array_merge($att,
                                array('id' => $att['__key'],
                                'name' => $display_name,
                                'email' => null,
                                '__type' => 'Object',
                                'source' => $source));
                        }
                    } else {
                        /* Is a distribution list. */
                        $listatt = $ob->getAttributes();
                        $seeninlist = array();
                        $members = $ob->listMembers();
                        $listName = $ob->getValue('name');
                        if (!($members instanceof Turba_List)) {
                            continue;
                        }
                        if (count($members)) {
                            if (!isset($results[$name])) {
                                $results[$name] = array();
                            }
                            $emails = array();
                            while ($ob = $members->next()) {
                                $att = $ob->getAttributes();
                                foreach (array_keys($att) as $key) {
                                    $value = $ob->getValue($key);
                                    if (empty($value)) {
                                        continue;
                                    }
                                    if (!is_array($value)) {
                                        $seen_key = trim(Horde_String::lower($ob->getValue('name')))
                                            . trim(Horde_String::lower($value));
                                    } else {
                                        $seen_key = trim(Horde_String::lower($ob->getValue('name')))
                                            . trim(Horde_String::lower($value['load']['file']));
                                    }
                                    if (isset($attributes[$key]) &&
                                        $attributes[$key]['type'] == 'email' &&
                                        empty($seeninlist[$seen_key])) {
                                            $emails[] = $value;
                                            $seeninlist[$seen_key] = true;
                                        }
                                }
                            }
                            $results[$name][] = array('name' => $listName,
                                'email' => implode(', ', $emails),
                                'id' => $listatt['__key'],
                                'source' => $source);
                        }
                    }
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

        if (!isset($cfgSources) || !is_array($cfgSources) || !count($cfgSources)) {
            return array();
        }

        if (isset($cfgSources[$source])) {
            $driver = $GLOBALS['injector']->getInstance('Turba_Factory_Driver')->create($source);

            $object = $driver->getObject($objectId);

            $attributes = array();
            foreach ($cfgSources[$source]['map'] as $field => $map) {
                $attributes[$field] = $object->getValue($field);
            }
            return $attributes;
        }

        return array();
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
    public function getContacts($source = '', $objectIds = array())
    {
        global $cfgSources;
        $results = array();
        if (!is_array($objectIds)) {
            $objectIds = array($objectIds);
        }

        if (!isset($cfgSources) || !is_array($cfgSources) || !count($cfgSources)) {
            return array();
        }

        if (isset($cfgSources[$source])) {
            $driver = $GLOBALS['injector']->getInstance('Turba_Factory_Driver')->create($source);

            $objects = $driver->getObjects($objectIds);

            foreach ($objects as $object) {
                $attributes = array();
                foreach ($cfgSources[$source]['map'] as $field => $map) {
                    $attributes[$field] = $object->getValue($field);
                }
                $results[] = $attributes;
            }
        }

        return $results;
    }

    /**
     * Retrieves a list of all possible values of a field in specified
     * source(s).
     *
     * @param string $field   Field name to check
     * @param array $sources  Array containing the sources to look in
     *
     * @return array  An array of fields and possible values.
     * @throws Turba_Exception
     */
    public function getAllAttributeValues($field = '', $sources = array())
    {
        global $cfgSources;

        if (!isset($cfgSources) || !is_array($cfgSources) || !count($cfgSources)) {
            return array();
        }

        if (!count($sources)) {
            $sources = array(Turba::getDefaultAddressBook());
        }

        $results = array();
        foreach ($sources as $source) {
            if (isset($cfgSources[$source])) {
                $driver = $GLOBALS['injector']->getInstance('Turba_Factory_Driver')->create($source);

                $res = $driver->search(array());
                if (!($res instanceof Turba_List)) {
                    throw new Turba_Exception(_("Search failed"));
                }

                while ($ob = $res->next()) {
                    if ($ob->hasValue($field)) {
                        $results[$source . ':' . $ob->getValue('__key')] = array(
                            'name' => $ob->getValue('name'),
                            'email' => $ob->getValue('email'),
                            $field => $ob->getValue($field));
                    }
                }
            }
        }

        return $results;
    }

    /**
     * Retrieves a list of available time objects categories
     *
     * @return array  An array of all configured time object categories.
     */
    public function listTimeObjectCategories()
    {
        $categories = array();
        foreach ($GLOBALS['attributes'] as $key => $attribute) {
            if ($attribute['type'] == 'monthdayyear' &&
                !empty($attribute['time_object_label'])) {
                foreach ($GLOBALS['cfgSources'] as $srcKey => $source) {
                    if (!empty($source['map'][$key])) {
                        $categories[$key . '/'. $srcKey] = sprintf(_("%s in %s"), $attribute['time_object_label'], $source['title']);
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
        global $cfgSources;

        $start = new Horde_Date($start);
        $end = new Horde_Date($end);

        if (!isset($cfgSources) || !is_array($cfgSources) || !count($cfgSources)) {
            return array();
        }

        $objects = array();
        foreach ($time_categories as $category) {
            list($category, $source) = explode('/', $category, 2);
            $driver = $GLOBALS['injector']->getInstance('Turba_Factory_Driver')->create($source);
            $objects = array_merge($objects, $driver->listTimeObjects($start, $end, $category));
        }

        return $objects;
    }

    /**
     * Returns the client source name
     *
     * @return string  The name of the source to use with the clients api.
     */
    public function getClientSource()
    {
        return !empty($GLOBALS['conf']['client']['addressbook']) ? $GLOBALS['conf']['client']['addressbook'] : false;
    }

    /**
     * Returns the available client fields
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
     * @param string $objectId  Client unique ID
     *
     * @return array  Array of client data.
     * @throws Turba_Exception
     */
    public function getClient($objectId = '')
    {
        return $this->getContact($GLOBALS['conf']['client']['addressbook'], $objectId);
    }

    /**
     * Returns mulitple contacts from the client source
     *
     * @param array $objectIds  client unique ids
     *
     * @return array  An array of clients data.
     * @throws Turba_Exception
     */
    public function getClients($objectIds = array())
    {
        return $this->getContacts($GLOBALS['conf']['client']['addressbook'], $objectIds);
    }

    /**
     * Adds a client to the client source
     *
     * @param array $attributes  Array containing the client attributes
     */
    public function addClient($attributes = array())
    {
        return $this->import($attributes, 'array', $this->getClientSource());
    }

    /**
     * Updates client data
     *
     * @param string $objectId   The unique id of the client
     * @param array $attributes  An array of client attributes
     *
     * @return boolean
     */
    public function updateClient($objectId = '', $attributes = array())
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
     * Search for clients
     *
     * @param array $names         The search filter values
     * @param array $fields        The fields to search in
     * @param boolean $matchBegin  Match word boundaries only
     *
     * @return array  A hash containing the search results.
     * @throws Turba_Exception
     */
    public function searchClients($names = array(), $fields = array(),
                                  $matchBegin = false)
    {
        return $this->search(
            $names,
            array($GLOBALS['conf']['client']['addressbook']),
            array($GLOBALS['conf']['client']['addressbook'] => $fields),
            $matchBegin,
            true
        );
    }

    /**
     * Sets the value of the specified attribute of a contact
     *
     * @param string $address  Contact email address
     * @param string $name     Contact name
     * @param string $field    Field to update
     * @param string $value    Field value to set
     * @param string $source   Contact source
     *
     * @return string  The new __key value on success.
     * @throws Turba_Exception
     */
    public function addField($address = '', $name = '', $field = '',
                             $value = '',
        $source = '')
    {
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
            return $driver->add(array('email' => $address, 'name' => $name, $field => $value, '__owner' => $GLOBALS['registry']->getAuth()));
        }
    }

    /**
     * Returns a field value
     *
     * @param string $address    Contact email address
     * @param string $field      Field to get
     * @param array $sources     Sources to check
     * @param boolean $strict    Match the email address strictly
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

        if (!isset($cfgSources) || !is_array($cfgSources) || !count($cfgSources)) {
            return array();
        }

        if (!count($sources)) {
            $sources = array(Turba::getDefaultAddressbook());
        }

        $result = array();
        foreach ($sources as $source) {
            if (!isset($cfgSources[$source])) {
                continue;
            }

            $driver = $GLOBALS['injector']->getInstance('Turba_Factory_Driver')->create($source);
            $criterium = array('email' => $address);
            if (!isset($driver->map['email'])) {
                if (isset($driver->map['emails'])) {
                    $criterium = array('emails' => $address);
                } else {
                    continue;
                }
            }

            $list = $driver->search($criterium, null, 'AND', array(), $strict ? array('email') : array());
            if (!($list instanceof Turba_List)) {
                continue;
            }

            while ($ob = $list->next()) {
                if ($ob->hasValue($field)) {
                    $result[] = $ob->getValue($field);
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
     * Deletes a field value
     *
     * @param string $address Contact email address
     * @param string $field   Field to delete value for
     * @param array $sources  Sources to delete value from
     *
     * @return boolean  TODO
     * @throws Turba_Exception
     */
    public function deleteField($address = '', $field = '', $sources = array())
    {
        global $cfgSources;

        if (empty($address)) {
            throw new Turba_Exception(_("Invalid email"));
        }

        if (!isset($cfgSources) || !is_array($cfgSources) || !count($cfgSources)) {
            return array();
        }

        if (count($sources) == 0) {
            $sources = array(Turba::getDefaultAddressbook());
        }

        $success = false;

        foreach ($sources as $source) {
            if (isset($cfgSources[$source])) {
                $driver = $GLOBALS['injector']->getInstance('Turba_Factory_Driver')->create($source);
                if (!$driver->hasPermission(Horde_Perms::EDIT)) {
                    continue;
                }

                $res = $driver->search(array('email' => $address));
                if ($res instanceof Turba_List) {
                    if (count($res) > 1) {
                        continue;
                    }

                    $ob = $res->next();
                    if (is_object($ob) && $ob->hasValue($field)) {
                        $ob->setValue($field, '');
                        $ob->store();
                        $success = true;
                    }
                }
            }
        }

        if (!$success) {
            throw new Turba_Exception(sprintf(_("No %s entry found for %s"), $field, $address));
        }
    }

}
