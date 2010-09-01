<?php
/** The virtual path to use for VFS data. */
define('TURBA_VFS_PATH', '.horde/turba/documents');

/**
 * Turba Base Class.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Jon Parise <jon@horde.org>
 * @package Turba
 */
class Turba {

    /**
     * @todo Consolidate on a single mail/compose method.
     */
    function formatEmailAddresses($data, $name)
    {
        global $registry;
        static $useRegistry;

        if (!isset($useRegistry)) {
            $useRegistry = $registry->hasMethod('mail/batchCompose');
        }

        $array = is_array($data);
        if (!$array) {
            $data = array($data);
        }

        $addresses = array();
        foreach ($data as $i => $email_vals) {
            $email_vals = explode(',', $email_vals);
            foreach ($email_vals as $j => $email_val) {
                $email_val = trim($email_val);

                // Format the address according to RFC822.
                $mailbox_host = explode('@', $email_val);
                if (!isset($mailbox_host[1])) {
                    $mailbox_host[1] = '';
                }
                $address = Horde_Mime_Address::writeAddress($mailbox_host[0], $mailbox_host[1], $name);

                // Get rid of the trailing @ (when no host is included in
                // the email address).
                $addresses[$i . ':' . $j] = array('to' => addslashes(str_replace('@>', '>', $address)));
            }
        }

        if ($useRegistry) {
            try {
                $addresses = $GLOBALS['registry']->call('mail/batchCompose', array($addresses));
            } catch (Horde_Exception $e) {
                $useRegistry = false;
                $addresses = array();
            }
        } else {
            $addresses = array();
        }

        foreach ($data as $i => $email_vals) {
            $email_vals = explode(',', $email_vals);
            $email_values = false;
            foreach ($email_vals as $j => $email_val) {
                if (isset($addresses[$i . ':' . $j])) {
                    $mail_link = $addresses[$i . ':' . $j];
                } else {
                    $mail_link = 'mailto:' . urlencode($email_val);
                }

                $email_value = Horde::link($mail_link) . htmlspecialchars($email_val) . '</a>';
                if ($email_values) {
                    $email_values .= ', ' . $email_value;
                } else {
                    $email_values = $email_value;
                }
            }
        }

        if ($array) {
            return $email_values[0];
        } else {
            return $email_values;
        }
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
    function getAddressBooks($permission = Horde_Perms::READ, $options = array())
    {
        $addressbooks = array();
        foreach (array_keys(Turba::getAddressBookOrder()) as $addressbook) {
            $addressbooks[$addressbook] = $GLOBALS['cfgSources'][$addressbook];
        }

        if (!$addressbooks) {
            $addressbooks = $GLOBALS['cfgSources'];
        }

        return Turba::permissionsFilter($addressbooks, $permission, $options);
    }

    /**
     * Get the order the user selected for displaying address books.
     *
     * @return array  An array describing the order to display the address books.
     */
    function getAddressBookOrder()
    {
        $lines = json_decode($GLOBALS['prefs']->getValue('addressbooks'));
        $addressbooks = array();

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
    function getDefaultAddressBook()
    {
        $lines = json_decode($GLOBALS['prefs']->getValue('addressbooks'));
        if (!empty($lines)) {
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line && isset($GLOBALS['cfgSources'][$line])) {
                    return $line;
                }
            }
        }

        reset($GLOBALS['cfgSources']);
        return key($GLOBALS['cfgSources']);
    }

    /**
     * Returns the sort order selected by the user
     */
    function getPreferredSortOrder()
    {
        return @unserialize($GLOBALS['prefs']->getValue('sortorder'));
    }

    /**
     * Retrieves a column's field name
     */
    function getColumnName($i, $columns)
    {
        return $i == 0 ? 'name' : $columns[$i - 1];
    }

    /**
     */
    function getColumns()
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
     * Returns a best guess at the lastname in a string.
     *
     * @param string $name  String contain the full name.
     *
     * @return string  String containing the last name.
     */
    function guessLastname($name)
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

            while (!empty($name) && Horde_String::length($name) < 5 &&
                   strspn($name[(Horde_String::length($name) - 1)], '.:-') &&
                   !empty($namelist[($nameindex - 1)])) {
                $nameindex--;
                $name = $namelist[$nameindex];
            }
        }
        return strlen($name) ? $name : null;
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
    function formatName($ob, $name_format = null)
    {
        static $default_format;

        if (!$name_format) {
            if (!isset($default_format)) {
                $default_format = $GLOBALS['prefs']->getValue('name_format');
            }
            $name_format = $default_format;
        }

        /* if no formatting, return original name */
        if ($name_format != 'first_last' && $name_format != 'last_first') {
            return $ob->getValue('name');
        }

        /* See if we have the name fields split out explicitly. */
        if ($ob->hasValue('firstname') && $ob->hasValue('lastname')) {
            if ($name_format == 'last_first') {
                return $ob->getValue('lastname') . ', ' . $ob->getValue('firstname');
            } else {
                return $ob->getValue('firstname') . ' ' . $ob->getValue('lastname');
            }
        } else {
            /* One field, we'll have to guess. */
            $name = $ob->getValue('name');
            $lastname = Turba::guessLastname($name);
            if ($name_format == 'last_first' &&
                !is_int(strpos($name, ',')) &&
                Horde_String::length($name) > Horde_String::length($lastname)) {
                $name = preg_replace('/\s+' . preg_quote($lastname, '/') . '/', '', $name);
                $name = $lastname . ', ' . $name;
            }
            if ($name_format == 'first_last' &&
                is_int(strpos($name, ',')) &&
                Horde_String::length($name) > Horde_String::length($lastname)) {
                $name = preg_replace('/' . preg_quote($lastname, '/') . ',\s*/', '', $name);
                $name = $name . ' ' . $lastname;
            }
            return $name;
        }
    }

    /**
     * Returns the real name, if available, of a user.
     */
    function getUserName($uid)
    {
        static $names = array();

        if (!isset($names[$uid])) {
            $ident = $GLOBALS['injector']->getInstance('Horde_Prefs_Identity')->getIdentity($uid);
            $ident->setDefault($ident->getDefault());
            $names[$uid] = $ident->getValue('fullname');
            if (empty($names[$uid])) {
                $names[$uid] = $uid;
            }
        }

        return $names[$uid];
    }

    /**
     * Gets extended permissions on an address book.
     *
     * @param Turba_Driver $addressBook The address book to get extended permissions for.
     * @param string $permission  What extended permission to get.
     *
     * @return mixed  The requested extended permissions value, or true if it doesn't exist.
     */
    function getExtendedPermission($addressBook, $permission)
    {
        // We want to check the base source as extended permissions
        // are enforced per backend, not per share.
        $key = $addressBook->getName() . ':' . $permission;

        $perms = $GLOBALS['injector']->getInstance('Horde_Perms');
        if (!$perms->exists('turba:sources:' . $key)) {
            return true;
        }

        $allowed = $perms->getPermissions('turba:sources:' . $key);
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
    function permissionsFilter($in, $permission = Horde_Perms::READ, $options = array())
    {
        $out = array();

        foreach ($in as $sourceId => $source) {
            try {
                $driver = $GLOBALS['injector']->getInstance('Turba_Driver')->getDriver($sourceId);
            } catch (Turba_Exception $e) {
                Horde::logMessage($e, 'ERR');
                continue;
            }

            if ($driver->hasPermission($permission)) {
                if (!empty($options['require_add']) && !$driver->canAdd()) {
                    continue;
                }
                $out[$sourceId] = $source;
            }
        }

        return $out;
    }

    /**
     * Replaces all share-enabled sources in a source list with all shares
     * from this source that the current user has access to.
     *
     * This will only sync shares that are unique to Horde (basically, a SQL
     * driver source for now).  Any backend that supports ACLs or similar
     * mechanism should be configured from within sources.php or
     * _horde_hook_share_* calls.
     *
     * @param array $sources  The default $cfgSources array.
     *
     * @return array  The $cfgSources array.
     */
    function getConfigFromShares($sources)
    {
        global $notification;

        try {
            $shares = Turba::listShares();
        } catch (Horde_Share_Exception $e) {
            // Notify the user if we failed, but still return the $cfgSource array.
            $notification->push($e, 'horde.error');
            return $sources;
        }

        $sortedShares = $defaults = $vbooks = array();
        $defaults = array();
        foreach (array_keys($shares) as $name) {
            if (isset($sources[$name])) {
                continue;
            }

            $params = @unserialize($shares[$name]->get('params'));
            if (isset($params['type']) && $params['type'] == 'vbook') {
                // We load vbooks last in case they're based on other shares.
                $params['share'] = $shares[$name];
                $vbooks[$name] = $params;
            } elseif (!empty($params['source']) &&
                      !empty($sources[$params['source']]['use_shares'])) {
                if (empty($params['name'])) {
                    $params['name'] = $name;
                    $shares[$name]->set('params', serialize($params));
                    $shares[$name]->save();
                }

                // Default share?
                if (empty($defaults[$params['source']])) {
                    try {
                        $driver = $GLOBALS['injector']->getInstance('Turba_Driver')->getDriver($params['source']);
                        if ($driver->hasPermission(Horde_Perms::EDIT)) {
                            $defaults[$params['source']] = $driver->checkDefaultShare(
                                $shares[$name],
                                $sources[$params['source']]
                            );
                        }
                    } catch (Turba_Exception $e) {
                        $notification->push($e, 'horde.error');
                    }
                }

                $share = $sources[$params['source']];
                $share['params']['config'] = $sources[$params['source']];
                $share['params']['config']['params']['share'] = $shares[$name];
                $share['params']['config']['params']['name'] = $params['name'];
                $share['title'] = $shares[$name]->get('name');
                $share['type'] = 'share';
                $share['use_shares'] = false;
                $sortedSources[$params['source']][$name] = $share;
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
            if ($GLOBALS['registry']->getAuth() && empty($defaults[$source])) {
                // User's default share is missing.
                try {
                    $driver = $GLOBALS['injector']->getInstance('Turba_Driver')->getDriver($sourceId);
                } catch (Turba_Exception $e) {
                    $notification->push($driver, 'horde.error');
                    continue;
                }

                $sourceKey = md5(mt_rand());
                try {
                    $share = $driver->createShare(
                        $sourceKey,
                        array(
                            'params' => array(
                                'source' => $source,
                                'default' => true,
                                'name' => $GLOBALS['registry']->getAuth()
                            )
                        )
                    );
                } catch (Horde_Share_Exception $e) {
                    Horde::logMessage($e, 'ERR');
                    continue;
                }

                $source_config = $sources[$source];
                $source_config['params']['share'] = $share;
                $newSources[$sourceKey] = $source_config;
            }
        }

        // Add vbooks now that all available address books are loaded.
        foreach ($vbooks as $name => $params) {
            if (!isset($newSources[$params['source']])) {
                continue;
            }
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

        return $newSources;
    }

    /**
     * Retrieve a new source config entry based on a Turba share.
     *
     * @param Horde_Share object  The share to base config on.
     */
    function getSourceFromShare($share)
    {
        // Require a fresh config file.
        require TURBA_BASE . '/config/sources.php';

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
    function listShares($owneronly = false, $permission = Horde_Perms::READ)
    {
        if (empty($_SESSION['turba']['has_share'])) {
            // No backends are configured to provide shares
            return array();
        }
        if ($owneronly && !$GLOBALS['registry']->getAuth()) {
            return array();
        }

        try {
            $sources = $GLOBALS['turba_shares']->listShares(
                $GLOBALS['registry']->getAuth(), $permission,
                $owneronly ? $GLOBALS['registry']->getAuth() : null);
        } catch (Horde_Share_Exception $e) {
            Horde::logMessage($e, 'ERR');
            return array();
        }

        return $sources;
    }

    /**
     * Create a new Turba share.
     *
     * @param string $share_id The id for the new share.
     * @param array $params Parameters for the new share.
     *
     * @return Horde_Share  The new share object.
     * @throws Turba_Exception
     */
    static public function createShare($share_id, $params)
    {
        if (!isset($params['name'])) {
            /* Sensible default for empty display names */
            $identity = $GLOBALS['injector']->getInstance('Horde_Prefs_Identity')->getIdentity();
            $name = $identity->getValue('fullname');
            if (trim($name) == '') {
                $name = $GLOBALS['registry']->getAuth('original');
            }
            $name = sprintf(_("%s's Address Book"), $name);
        } else {
            $name = $params['name'];
            unset($params['name']);
        }

        /* Generate the new share. */
        try {
            $share = $GLOBALS['turba_shares']->newShare($share_id);

            /* Set the display name for this share. */
            $share->set('name', $name);

            /* Now any other params. */
            foreach ($params as $key => $value) {
                if (!is_scalar($value)) {
                    $value = serialize($value);
                }
                $share->set($key, $value);
            }
            $GLOBALS['turba_shares']->addShare($share);
            $result = $share->save();
        } catch (Horde_Share_Exception $e) {
            Horde::logMessage($e, 'ERR');
            throw new Turba_Exception($e);
        }

        /* Update share_id as backends like Kolab change it to the IMAP folder
         * name. */
        $share_id = $share->getName();

        /* Add the new addressbook to the user's list of visible address
         * books. */
        $prefs = json_decode($GLOBALS['prefs']->getValue('addressbooks'));
        if (!is_array($prefs) || array_search($share_id, $prefs) === false) {
            $prefs[] = $share_id;
            $GLOBALS['prefs']->setValue('addressbooks', json_encode($prefs));
        }

        return $share;
    }

    /**
     * Build Turba's list of menu items.
     */
    function getMenu()
    {
        $menu = new Horde_Menu();

        if (!empty($_SESSION['turba']['has_share'])) {
            $menu->add(Horde::url('addressbooks/index.php'), _("_My Address Books"), 'turba.png');
        }
        if ($GLOBALS['browse_source_count']) {
            $menu->add(Horde::url('browse.php'), _("_Browse"), 'menu/browse.png', null, null, null, (($GLOBALS['prefs']->getValue('initial_page') == 'browse.php' && basename($_SERVER['PHP_SELF']) == 'index.php' && basename(dirname($_SERVER['PHP_SELF'])) != 'addressbooks') || (basename($_SERVER['PHP_SELF']) == 'browse.php' && Horde_Util::getFormData('key') != '**search')) ? 'current' : '__noselection');
        }
        if (count($GLOBALS['addSources'])) {
            $menu->add(Horde::url('add.php'), _("_New Contact"), 'menu/new.png');
        }
        $menu->add(Horde::url('search.php'), _("_Search"), 'search.png', Horde_Themes::img(null, 'horde'), null, null, (($GLOBALS['prefs']->getValue('initial_page') == 'search.php' && basename($_SERVER['PHP_SELF']) == 'index.php' && strpos($_SERVER['PHP_SELF'], 'addressbooks/index.php') === false) || (basename($_SERVER['PHP_SELF']) == 'browse.php' && Horde_Util::getFormData('key') == '**search')) ? 'current' : null);

        /* Import/Export */
        if ($GLOBALS['conf']['menu']['import_export']) {
            $menu->add(Horde::url('data.php'), _("_Import/Export"), 'data.png', Horde_Themes::img(null, 'horde'));
        }

        return $menu;
    }

    /**
     * Add browse.js javascript to page.
     */
    public function addBrowseJs()
    {
        $js = array();
        $js_text = array(
            'confirmdelete' => _("Are you sure that you want to delete %s?"),
            'contact1' => _("You must select at least one contact first."),
            'contact2' => ("You must select a target contact list."),
            'contact3' => _("Please name the new contact list:"),
            'copymove' => _("You must select a target address book."),
            'submit' => _("Are you sure that you want to delete the selected contacts?"),
        );

        foreach ($js_text as $key => $val) {
            $js[] = 'TurbaBrowse.' . $key . ' = ' . Horde_Serialize::serialize($val, Horde_Serialize::JSON, $GLOBALS['registry']->getCharset());
        }

        Horde::addScriptFile('browse.js', 'turba');
        Horde::addInlineScript($js);
    }

}
