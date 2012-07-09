<?php
/**
 * Turba Base Class.
 *
 * Copyright 2000-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you did
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Jon Parise <jon@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Turba
 */
class Turba
{
    /**
     * The virtual path to use for VFS data.
     */
    const VFS_PATH = '.horde/turba/documents';

    /**
     * The current source.
     *
     * @var string
     */
    static public $source;

    /**
     * Cached data.
     *
     * @var array
     */
    static protected $_cache = array();

    /**
     * Returns the source entries from config/backends.php that have been
     * configured as available sources in the main Turba configuration.
     *
     * @return array  List of available sources.
     */
    static public function availableSources()
    {
        $cfgSources = Horde::loadConfiguration('backends.php', 'cfgSources', 'turba');
        $sources = array();
        foreach ($cfgSources as $key => $source) {
            if (empty($source['disabled'])) {
                $sources[$key] = $source;
            }
        }
        return $sources;
    }

    /**
     * Get all the address books the user has the requested permissions to and
     * return them in the user's preferred order.
     *
     * @param integer $permission  The Horde_Perms::* constant to filter on.
     * @param array $options       Any additional options.
     *
     * @return array  The filtered, ordered $cfgSources entries.
     */
    static public function getAddressBooks($permission = Horde_Perms::READ,
                                           array $options = array())
    {
        $addressbooks = array();
        foreach (array_keys(self::getAddressBookOrder()) as $addressbook) {
            $addressbooks[$addressbook] = $GLOBALS['cfgSources'][$addressbook];
        }

        return self::permissionsFilter(
            empty($addressbooks) ? $GLOBALS['cfgSources'] : $addressbooks,
            $permission,
            $options
        );
    }

    /**
     * Get the order the user selected for displaying address books.
     *
     * @return array  An array describing the order to display the address
     *                books.
     */
    static public function getAddressBookOrder()
    {
        $addressbooks = array();
        $lines = json_decode($GLOBALS['prefs']->getValue('addressbooks'));

        if (!empty($lines)) {
            $i = 0;
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line && isset($GLOBALS['cfgSources'][$line])) {
                    $addressbooks[$line] = $i++;
                }
            }
        }

        return $addressbooks;
    }

    /**
     * Returns the current user's default address book.
     *
     * @return string  The default address book name.
     */
    static public function getDefaultAddressbook()
    {
        if ($abooks = self::getAddressBookOrder()) {
            reset($abooks);
            return key($abooks);
        }

        /* In case of shares select first user owned address book as default */
        if (!empty($_SESSION['turba']['has_share'])) {
            try {
                $owned_shares = self::listShares(true);
                if (count($owned_shares)) {
                    return key($owned_shares);
                }
            } catch (Exception $e) {}
        }

        reset($GLOBALS['cfgSources']);
        return key($GLOBALS['cfgSources']);
    }

    /**
     * Returns the sort order selected by the user.
     *
     * @return array  TODO
     */
    static public function getPreferredSortOrder()
    {
        return @unserialize($GLOBALS['prefs']->getValue('sortorder'));
    }

    /**
     * Retrieves a column's field name.
     *
     * @param integer $i      TODO
     * @param array $columns  TODO
     *
     * @return string  TODO
     */
    static public function getColumnName($i, $columns)
    {
        return ($i == 0)
            ? 'name'
            : $columns[$i - 1];
    }

    /**
     * TODO
     */
    static public function getColumns()
    {
        $columns = array();
        $lines = explode("\n", $GLOBALS['prefs']->getValue('columns'));

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line) {
                $cols = explode("\t", $line);
                if (count($cols) > 1) {
                    $source = array_splice($cols, 0, 1);
                    $columns[$source[0]] = $cols;
                }
            }
        }

        return $columns;
    }

    /**
     * Builds and cleans up a composite field.
     *
     * @param string $format  The sprintf field format.
     * @param array $fields   The fields that compose the composite field.
     *
     * @return string  The formatted composite field.
     */
    static public function formatCompositeField($format, $fields)
    {
        return preg_replace('/ +/', ' ', trim(vsprintf($format, $fields), " \t\n\r\0\x0B,"));
    }

    /**
     * Returns a best guess at the lastname in a string.
     *
     * @param string $name  String contain the full name.
     *
     * @return string  String containing the last name.
     */
    static public function guessLastname($name)
    {
        $name = trim(preg_replace('|\s|', ' ', $name));
        if (!empty($name)) {
            /* Assume that last names are always before any commas. */
            if (is_int(strpos($name, ','))) {
                $name = Horde_String::substr($name, 0, strpos($name, ','));
            }

            /* Take out anything in parentheses. */
            $name = trim(preg_replace('|\(.*\)|', '', $name));

            $namelist = explode(' ', $name);
            $name = $namelist[($nameindex = (count($namelist) - 1))];

            while (!empty($name) &&
                   (($nlength = Horde_String::length($name)) < 5) &&
                   strspn($name[($nlength - 1)], '.:-') &&
                   !empty($namelist[($nameindex - 1)])) {
                $name = $namelist[--$nameindex];
            }
        }

        return strlen($name)
            ? $name
            : null;
    }

    /**
     * Formats the name according to the user's preference.
     *
     * If the format is 'none', the full name with all parts is returned. If
     * the format is 'last_first' or 'first_last', only the first name and
     * last name are returned.
     *
     * @param Turba_Object $ob     The object to get a name from.
     * @param string $name_format  The formatting. One of 'none', 'last_first'
     *                             or 'first_last'. Defaults to the user
     *                             preference.
     *
     * @return string  The formatted name, either "Firstname Lastname"
     *                 or "Lastname, Firstname" depending on $name_format or
     *                 the user's preference.
     */
    static public function formatName(Turba_Object $ob, $name_format = null)
    {
        if (!$name_format) {
            if (!isset(self::$_cache['defaultFormat'])) {
                self::$_cache['defaultFormat'] = $GLOBALS['prefs']->getValue('name_format');
            }
            $name_format = self::$_cache['defaultFormat'];
        }

        /* If no formatting, return original name. */
        if (!in_array($name_format, array('first_last', 'last_first'))) {
            return $ob->getValue('name');
        }

        /* See if we have the name fields split out explicitly. */
        if ($ob->hasValue('firstname') && $ob->hasValue('lastname')) {
            return ($name_format == 'last_first')
                ? $ob->getValue('lastname') . ', ' . $ob->getValue('firstname')
                : $ob->getValue('firstname') . ' ' . $ob->getValue('lastname');
        }

        /* One field, we'll have to guess. */
        $name = $ob->getValue('name');
        $lastname = self::guessLastname($name);
        if (($name_format == 'last_first') &&
            !is_int(strpos($name, ',')) &&
            (Horde_String::length($name) > Horde_String::length($lastname))) {
            return $lastname . ', ' . preg_replace('/\s+' . preg_quote($lastname, '/') . '/', '', $name);
        }

        if (($name_format == 'first_last') &&
            is_int(strpos($name, ',')) &&
            (Horde_String::length($name) > Horde_String::length($lastname))) {
            return preg_replace('/' . preg_quote($lastname, '/') . ',\s*/', '', $name) . ' ' . $lasname;
        }

        return $name;
    }

    /**
     * TODO
     *
     * @param mixed $data   Either a single email address or an array of email
     *                      addresses to format.
     * @param string $name  The personal name phrase.
     *
     * @return mixed  Either the formatted address or an array of formatted
     *                addresses.
     */
    static public function formatEmailAddresses($data, $name)
    {
        if (!isset(self::$_cache['useRegistry'])) {
            self::$_cache['useRegistry'] = $GLOBALS['registry']->hasMethod('mail/batchCompose');
        }

        $out = array();
        $rfc822 = $GLOBALS['injector']->getInstance('Horde_Mail_Rfc822');

        if (!is_array($data)) {
            $data = array($data);
        }

        foreach ($data as $i => $email_vals) {
            foreach ($rfc822->parseAddressList($email_vals) as $ob) {
                $addr = strval($ob);
                $tmp = null;

                if (self::$_cache['useRegistry']) {
                    try {
                        $tmp = $GLOBALS['registry']->call('mail/batchCompose', array(array($addr)));
                    } catch (Horde_Exception $e) {
                        $self::$_cache['useRegistry'] = false;
                    }
                }

                $tmp = empty($tmp)
                    ? 'mailto:' . urlencode($addr)
                    : reset($tmp);

                $out[] = Horde::link($tmp) . htmlspecialchars($addr) . '</a>';
            }
        }

        return implode(', ', $out);
    }

    /**
     * Returns the real name, if available, of a user.
     *
     * @param string $uid  The uid of the name to return.
     *
     * @return string  The user's full, real name.
     */
    static public function getUserName($uid)
    {
        if (!isset(self::$_cache['names'])) {
            self::$_cache['names'] = array();
        }

        if (!isset(self::$_cache['names'][$uid])) {
            $ident = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Identity')->create($uid);
            $ident->setDefault($ident->getDefault());
            $name = $ident->getValue('fullname');
            self::$_cache['names'][$uid] = empty($name)
                ? $uid
                : $name;
        }

        return self::$_cache['names'][$uid];
    }

    /**
     * Gets extended permissions on an address book.
     *
     * @param Turba_Driver $addressBook  The address book to get extended
     *                                   permissions for.
     * @param string $permission         What extended permission to get.
     *
     * @return mixed  The requested extended permissions value, or true if it
     *                doesn't exist.
     */
    static public function getExtendedPermission(Turba_Driver $addressBook,
                                                 $permission)
    {
        // We want to check the base source as extended permissions
        // are enforced per backend, not per share.
        $key = $addressBook->getName() . ':' . $permission;

        $perms = $GLOBALS['injector']->getInstance('Horde_Perms');
        if (!$perms->exists('turba:sources:' . $key)) {
            return true;
        }

        $allowed = $perms->getPermissions('turba:sources:' . $key, $GLOBALS['registry']->getAuth());
        if (is_array($allowed)) {
            switch ($permission) {
            case 'max_contacts':
                $allowed = max($allowed);
                break;
            }
        }
        return $allowed;
    }

    /**
     * Filters data based on permissions.
     *
     * @param array $in            The data we want filtered.
     * @param string $filter       What type of data we are filtering.
     * @param integer $permission  The Horde_Perms::* constant we will filter
     *                             on.
     * @param array $options       Additional options.
     *
     * @return array  The filtered data.
     */
    static public function permissionsFilter(array $in,
                                             $permission = Horde_Perms::READ,
                                             array $options = array())
    {
        $factory = $GLOBALS['injector']->getInstance('Turba_Factory_Driver');
        $out = array();

        foreach ($in as $sourceId => $source) {
            try {
                $driver = $factory->create($sourceId);
                if ($driver->hasPermission($permission) &&
                    (empty($options['require_add']) || $driver->canAdd())) {
                    $out[$sourceId] = $source;
                }
            } catch (Turba_Exception $e) {
                Horde::logMessage($e, 'ERR');
            }
        }

        return $out;
    }

    /**
     * Replaces all share-enabled sources in a source list with all shares
     * from this source that the current user has access to.
     *
     * This will only sync shares that are unique to Horde (such as a SQL
     * source).  Any backend that supports ACLs or similar mechanism should be
     * configured from within backends.php or via Horde's share_* hooks.
     *
     * @param array $sources  The default $cfgSources array.
     *
     * @return array  The $cfgSources array.
     */
    static public function getConfigFromShares(array $sources)
    {
        try {
            $shares = self::listShares();
        } catch (Horde_Share_Exception $e) {
            // Notify the user if we failed, but still return the $cfgSource
            // array.
            $GLOBALS['notification']->push($e, 'horde.error');
            return $sources;
        }

        /* See if any of our sources are configured to handle all otherwise
         * unassigned Horde_Share objects. */
        $all_shares = null;
        foreach ($sources as $key => $cfg) {
            if (!empty($cfg['all_shares'])) {
                // Indicate the source handler that catches unassigned shares.
                $all_shares = $key;
            }
        }

        $auth_user = $GLOBALS['registry']->getAuth();
        $sortedSources = $defaults = $vbooks = array();
        $personal = false;

        foreach ($shares as $name => &$share) {
            if (isset($sources[$name])) {
                continue;
            }

            $personal |= ($share->get('owner') == $auth_user);

            $params = @unserialize($share->get('params'));
            if (empty($params['source']) && !empty($all_shares)) {
                $params['source'] = $all_shares;
            }

            if (isset($params['type']) && $params['type'] == 'vbook') {
                // We load vbooks last in case they're based on other shares.
                $params['share'] = $share;
                $vbooks[$name] = $params;
            } elseif (!empty($params['source']) &&
                      !empty($sources[$params['source']]['use_shares'])) {
                if (empty($params['name'])) {
                    $params['name'] = $name;
                    $share->set('params', serialize($params));
                    try {
                        $share->save();
                    } catch (Horde_Share_Exception $e) {
                        Horde::logMessage($e, 'ERR');
                    }
                }

                $info = $sources[$params['source']];
                $info['params']['config'] = $sources[$params['source']];
                $info['params']['config']['params']['share'] = $share;
                $info['params']['config']['params']['name'] = $params['name'];
                $info['title'] = $share->get('name');
                $info['type'] = 'share';
                $info['use_shares'] = false;
                $sortedSources[$params['source']][$name] = $info;
            }
        }

        // Check for the user's default share and built new source list.
        $newSources = array();
        foreach (array_keys($sources) as $source) {
            if (empty($sources[$source]['use_shares'])) {
                $newSources[$source] = $sources[$source];
                continue;
            }

            if (isset($sortedSources[$source])) {
                $newSources = array_merge($newSources, $sortedSources[$source]);
            }

            if (!empty($GLOBALS['conf']['share']['auto_create']) &&
                $auth_user &&
                !$personal) {
                // User's default share is missing.
                try {
                    $driver = $GLOBALS['injector']
                        ->getInstance('Turba_Factory_Driver')
                        ->create($source);
                } catch (Turba_Exception $e) {
                    $GLOBALS['notification']->push($e->getMessage(), 'horde.error');
                    continue;
                }

                $sourceKey = strval(new Horde_Support_Randomid());
                try {
                    $share = $driver->createShare(
                        $sourceKey,
                        array(
                            'params' => array(
                                'source' => $source,
                                'default' => true,
                                'name' => $auth_user
                            )
                        )
                    );

                    $source_config = $sources[$source];
                    $source_config['params']['share'] = $share;
                    $newSources[$sourceKey] = $source_config;
                    $personal = true;
                } catch (Horde_Share_Exception $e) {
                    Horde::logMessage($e, 'ERR');
                }
            }
        }

        // Add vbooks now that all available address books are loaded.
        foreach ($vbooks as $name => $params) {
            if (isset($newSources[$params['source']])) {
                $newSources[$name] = array(
                    'title' => $shares[$name]->get('name'),
                    'type' => 'vbook',
                    'params' => $params,
                    'export' => true,
                    'browse' => true,
                    'map' => $newSources[$params['source']]['map'],
                    'search' => $newSources[$params['source']]['search'],
                    'strict' => $newSources[$params['source']]['strict'],
                    'use_shares' => false,
                );
            }
        }

        return $newSources;
    }

    /**
     * Retrieve a new source config entry based on a Turba share.
     *
     * @param Horde_Share object  The share to base config on.
     *
     * @return array  The $cfgSource entry for this share source.
     */
    static public function getSourceFromShare(Horde_Share $share)
    {
        // Require a fresh config file.
        $cfgSources = self::availableSources();

        $params = @unserialize($share->get('params'));
        $newConfig = $cfgSources[$params['source']];
        $newConfig['params']['config'] = $cfgSources[$params['source']];
        $newConfig['params']['config']['params']['share'] = $share;
        $newConfig['params']['config']['params']['name'] = $params['name'];
        $newConfig['title'] = $share->get('name');
        $newConfig['type'] = 'share';
        $newConfig['use_shares'] = false;

        return $newConfig;
    }

    /**
     * Returns all shares the current user has specified permissions to.
     *
     * @param boolean $owneronly   Only return address books owned by the user?
     *                             Defaults to false.
     * @param integer $permission  Permissions to filter by.
     *
     * @return array  Shares the user has the requested permissions to.
     */
    static public function listShares($owneronly = false,
                                      $permission = Horde_Perms::READ)
    {
        if (!$GLOBALS['session']->get('turba', 'has_share') ||
            ($owneronly && !$GLOBALS['registry']->getAuth())) {
            return array();
        }

        try {
            return $GLOBALS['injector']->getInstance('Turba_Shares')->listShares(
                $GLOBALS['registry']->getAuth(),
                array(
                    'attributes' => $owneronly ? $GLOBALS['registry']->getAuth() : null,
                    'perm' => $permission
                )
            );
        } catch (Horde_Share_Exception $e) {
            Horde::logMessage($e, 'ERR');
            return array();
        }
    }

    /**
     * Create a new Turba share.
     *
     * @param string $share_name  The id for the new share.
     * @param array  $params      Parameters for the new share.
     *
     * @return Horde_Share  The new share object.
     * @throws Turba_Exception
     */
    static public function createShare($share_name, $params)
    {
        if (isset($params['name'])) {
            $name = $params['name'];
            unset($params['name']);
        } else {
            /* Sensible default for empty display names */
            $name = sprintf(_("Address book of %s"), $GLOBALS['injector']->getInstance('Horde_Core_Factory_Identity')->create()->getName());
        }

        /* Generate the new share. */
        try {
            $turba_shares = $GLOBALS['injector']->getInstance('Turba_Shares');

            $share = $turba_shares->newShare($GLOBALS['registry']->getAuth(), $share_name, $name);

            /* Now any other params. */
            foreach ($params as $key => $value) {
                if (!is_scalar($value)) {
                    $value = serialize($value);
                }
                $share->set($key, $value);
            }
            $turba_shares->addShare($share);
            $result = $share->save();
        } catch (Horde_Share_Exception $e) {
            Horde::logMessage($e, 'ERR');
            throw new Turba_Exception($e);
        }

        /* Update share_id as backends like Kolab change it to the IMAP folder
         * name. */
        $share_name = $share->getName();

        /* Add the new addressbook to the user's list of visible address
         * books. */
        $prefs = json_decode($GLOBALS['prefs']->getValue('addressbooks'), true);
        if (!is_array($prefs) || array_search($share_name, $prefs) === false) {
            $prefs[] = $share_name;
            $GLOBALS['prefs']->setValue('addressbooks', json_encode($prefs));
        }

        return $share;
    }

    /**
     * Add browse.js javascript to page.
     */
    static public function addBrowseJs()
    {
        global $page_output;

        $page_output->addScriptFile('browse.js');
        $page_output->addInlineJsVars(array(
            'TurbaBrowse.confirmdelete' => _("Are you sure that you want to delete %s?"),
            'TurbaBrowse.contact1' => _("You must select at least one contact first."),
            'TurbaBrowse.contact2' => _("You must select a target contact list."),
            'TurbaBrowse.contact3' => _("Please name the new contact list:"),
            'TurbaBrowse.copymove' => _("You must select a target address book."),
            'TurbaBrowse.submit' => _("Are you sure that you want to delete the selected contacts?")
        ));
    }

    /**
     * Build Turba's menu.
     *
     * @return string  The menu output.
     */
    static public function menu()
    {
        $sidebar = Horde::menu(array('menu_ob' => true))->render();

        if (count($GLOBALS['addSources'])) {
            $sidebar->addNewButton(_("_New Contact"), Horde::url('add.php'));
        }

        return $sidebar;
    }
}
