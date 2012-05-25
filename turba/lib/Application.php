<?php
/**
 * Turba application API.
 *
 * This file defines Horde's core API interface. Other core Horde libraries
 * can interact with Turba through this API.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (APL). If you
 * did not receive this file, see http://www.horde.org/licenses/apl.html.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/apl.html APL
 * @package  Turba
 */

/* Determine the base directories. */
if (!defined('TURBA_BASE')) {
    define('TURBA_BASE', __DIR__ . '/..');
}

if (!defined('HORDE_BASE')) {
    /* If Horde does not live directly under the app directory, the HORDE_BASE
     * constant should be defined in config/horde.local.php. */
    if (file_exists(TURBA_BASE . '/config/horde.local.php')) {
        include TURBA_BASE . '/config/horde.local.php';
    } else {
        define('HORDE_BASE', TURBA_BASE . '/..');
    }
}

/* Load the Horde Framework core (needed to autoload
 * Horde_Registry_Application::). */
require_once HORDE_BASE . '/lib/core.php';

class Turba_Application extends Horde_Registry_Application
{
    /**
     */
    public $version = 'H5 (4.0-git)';

    /**
     */
    protected function _bootstrap()
    {
        /* Add Turba-specific factories. */
        $factories = array(
            'Turba_Shares' => 'Turba_Factory_Shares'
        );

        foreach ($factories as $key => $val) {
            $GLOBALS['injector']->bindFactory($key, $val, 'create');
        }
    }

    /**
     * Global variables defined:
     *   $addSources   - TODO
     *   $attributes - (array) Attribute data from the config/attributes.php
     *                 file.
     *   $browse_source_count - TODO
     *   $browse_source_options - TODO
     *   $cfgSources   - TODO
     *   $copymoveSources - TODO
     */
    protected function _init()
    {
        // Turba source and attribute configuration.
        $attributes = Horde::loadConfiguration('attributes.php', 'attributes', 'turba');
        $cfgSources = Turba::availableSources();

        /* UGLY UGLY UGLY - we should NOT be using this as a global
         * variable all over the place. */
        $GLOBALS['cfgSources'] = &$cfgSources;

        // See if any of our sources are configured to use Horde_Share.
        foreach ($cfgSources as $key => $cfg) {
            if (!empty($cfg['use_shares'])) {
                // Create a share instance.
                $GLOBALS['session']->set('turba', 'has_share', true);
                $cfgSources = Turba::getConfigFromShares($cfgSources);
                break;
            }
        }

        $GLOBALS['attributes'] = $attributes;
        $cfgSources = Turba::permissionsFilter($cfgSources);

        // Build the directory sources select widget.
        $default_source = Horde_Util::nonInputVar('source');
        if (empty($default_source)) {
            if (!($default_source = $GLOBALS['session']->get('turba', 'source'))) {
                $default_source = Turba::getDefaultAddressbook();
            }
            $default_source = Horde_Util::getFormData('source', $default_source);
        }

        $GLOBALS['browse_source_count'] = 0;
        $GLOBALS['browse_source_options'] = '';

        foreach (Turba::getAddressBooks() as $key => $curSource) {
            if (!empty($curSource['browse'])) {
                $selected = ($key == $default_source) ? ' selected="selected"' : '';
                $GLOBALS['browse_source_options'] .= '<option value="' . htmlspecialchars($key) . '" ' . $selected . '>' .
                    htmlspecialchars($curSource['title']) . '</option>';

                ++$GLOBALS['browse_source_count'];

                if (empty($default_source)) {
                    $default_source = $key;
                }
            }
        }

        if (empty($cfgSources[$default_source]['browse'])) {
            $default_source = Turba::getDefaultAddressbook();
        }
        $GLOBALS['session']->set('turba', 'source', $default_source);
        $GLOBALS['default_source'] = $default_source;

        $GLOBALS['addSources'] = Turba::getAddressBooks(Horde_Perms::EDIT, array('require_add' => true));
        $GLOBALS['copymoveSources'] = array_diff($GLOBALS['addSources'], array($default_source));
    }

    /**
     */
    public function perms()
    {
        $cfgSources = Turba::availableSources();

        $perms = array(
            'sources' => array(
                'title' => _("Sources")
            )
        );

        // Run through every contact source.
        foreach ($cfgSources as $source => $curSource) {
            $perms['sources:' . $source] = array(
                'title' => $curSource['title']
            );
            $perms['sources:' . $source . ':max_contacts'] = array(
                'title' => _("Maximum Number of Contacts"),
                'type' => 'int'
            );
        }

        return $perms;
    }

    /**
     */
    public function menu($menu)
    {
        if ($GLOBALS['session']->get('turba', 'has_share')) {
            $menu->add(Horde::url('addressbooks/index.php'), _("_My Address Books"), 'turba.png');
        }

        if ($GLOBALS['browse_source_count']) {
            $menu->add(Horde::url('browse.php'), _("_Browse"), 'menu/browse.png', null, null, null, (($GLOBALS['prefs']->getValue('initial_page') == 'browse.php' && basename($_SERVER['PHP_SELF']) == 'index.php' && basename(dirname($_SERVER['PHP_SELF'])) != 'addressbooks') || (basename($_SERVER['PHP_SELF']) == 'browse.php' && Horde_Util::getFormData('key') != '**search')) ? 'current' : '__noselection');
        }

        if (count($GLOBALS['addSources'])) {
            $menu->add(Horde::url('add.php'), _("_New Contact"), 'menu/new.png');
        }

        $menu->add(Horde::url('search.php'), _("_Search"), 'search.png', null, null, null, (($GLOBALS['prefs']->getValue('initial_page') == 'search.php' && basename($_SERVER['PHP_SELF']) == 'index.php' && strpos($_SERVER['PHP_SELF'], 'addressbooks/index.php') === false) || (basename($_SERVER['PHP_SELF']) == 'browse.php' && Horde_Util::getFormData('key') == '**search')) ? 'current' : null);

        /* Import/Export */
        if ($GLOBALS['conf']['menu']['import_export']) {
            $menu->add(Horde::url('data.php'), _("_Import/Export"), 'data.png');
        }
    }

    /**
     */
    public function prefsGroup($ui)
    {
        global $page_output, $prefs;

        $source_init = false;

        foreach ($ui->getChangeablePrefs() as $val) {
            switch ($val) {
            case 'columnselect':
                $page_output->addScriptFile('scriptaculous/effects.js', 'horde');
                $page_output->addScriptFile('scriptaculous/dragdrop.js', 'horde');
                $page_output->addScriptFile('columnprefs.js');
                break;

            case 'default_dir':
                $out = array();
                foreach ($GLOBALS['cfgSources'] as $key => $info) {
                    $out[$key] = $info['title'];
                }
                $ui->override['default_dir'] = $out;

                $source_init = true;
                break;

            case 'sync_books':
                $out = array();
                foreach (Turba::getAddressBooks() as $key => $curSource) {
                    if (empty($curSource['map']['__uid'])) {
                        continue;
                    }
                    if (!empty($curSource['browse'])) {
                        $out[$key] = $curSource['title'];
                    }
                    $sync_books = @unserialize($prefs->getValue('sync_books'));
                    if (empty($sync_books)) {
                        $prefs->setValue('sync_books', serialize(array(Turba::getDefaultAddressbook())));
                    }
                }
                $ui->override['sync_books'] = $out;

                $source_init = true;
                break;
            }
        }

        if ($source_init) {
            Horde_Core_Prefs_Ui_Widgets::sourceInit();
        }
    }

    /**
     */
    public function prefsSpecial($ui, $item)
    {
        switch ($item) {
        case 'addressbookselect':
            $order = Turba::getAddressBookOrder();
            $selected = $sorted = $unselected = array();

            foreach (array_keys($GLOBALS['cfgSources']) as $val) {
                if (isset($order[$val])) {
                    $sorted[intval($order[$val])] = $val;
                } else {
                    $unselected[$val] = $GLOBALS['cfgSources'][$val]['title'];
                }
            }
            ksort($sorted);

            foreach ($sorted as $val) {
                $selected[$val] = $GLOBALS['cfgSources'][$val]['title'];
            }

            return Horde_Core_Prefs_Ui_Widgets::source(array(
                'mainlabel' => _("Choose which address books to display, and in what order:"),
                'selectlabel' => _("These address books will display in this order:"),
                'sources' => array(array(
                    'selected' => $selected,
                    'unselected' => $unselected
                )),
                'unselectlabel' => _("Address books that will not be displayed:")
            ));

        case 'columnselect':
            $sources = Turba::getColumns();

            $t = $GLOBALS['injector']->createInstance('Horde_Template');
            $t->setOption('gettext', true);

            $t->set('columns', htmlspecialchars($GLOBALS['prefs']->getValue('columns')));

            $col_list = $cols = array();
            foreach ($GLOBALS['cfgSources'] as $source => $info) {
                $col_list[] = array(
                    'first' => empty($col_list),
                    'source' => htmlspecialchars($source),
                    'title' => htmlspecialchars($info['title'])
                );

                // First the selected columns in their current order.
                $i = 0;
                $inputs = array();

                if (isset($sources[$source])) {
                    $selected = array_flip($sources[$source]);
                    foreach ($sources[$source] as $column) {
                        if ((substr($column, 0, 2) == '__') ||
                            ($column == 'name')) {
                            continue;
                        }

                        $inputs[] = array(
                            'checked' => isset($selected[$column]),
                            'column' => htmlspecialchars($column),
                            'i' => $i,
                            'label' => htmlspecialchars($GLOBALS['attributes'][$column]['label'])
                        );
                    }
                } else {
                    // Need to unset this for the loop below, otherwise
                    // selected columns from another source could interfere
                    unset($selected);
                }

                // Then the unselected columns in source order.
                foreach (array_keys($info['map']) as $column) {
                    if ((substr($column, 0, 2) == '__') ||
                        ($column == 'name') ||
                        isset($selected[$column])) {
                        continue;
                    }

                    $inputs[] = array(
                        'checked' => isset($selected[$column]),
                        'column' => htmlspecialchars($column),
                        'i' => $i,
                        'label' => htmlspecialchars($GLOBALS['attributes'][$column]['label'])
                    );
                }

                $cols[] = array(
                    'first' => empty($cols),
                    'inputs' => $inputs,
                    'source' => htmlspecialchars($source)
                );
            }

            if (!empty($col_list)) {
                $t->set('col_list', $col_list);
                $t->set('cols', $cols);
            }

            return $t->fetch(TURBA_TEMPLATES . '/prefs/column.html');
        }
    }

    /**
     */
    public function prefsSpecialUpdate($ui, $item)
    {
        global $prefs;

        switch ($item) {
        case 'addressbookselect':
            $data = Horde_Core_Prefs_Ui_Widgets::sourceUpdate($ui);
            if (isset($data['sources'])) {
                $prefs->setValue('addressbooks', $data['sources']);
                return true;
            }
            break;

        case 'columnselect':
            if (isset($ui->vars->columns)) {
                $prefs->setValue('columns', $ui->vars->columns);
                return true;
            }
            break;
        }

        return false;
    }

    /**
     */
    public function prefsCallback($ui)
    {
        if ($GLOBALS['conf']['activesync']['enabled'] && $GLOBALS['prefs']->isDirty('sync_books')) {
            try {
                $stateMachine = $GLOBALS['injector']->getInstance('Horde_ActiveSyncState');
                $stateMachine->setLogger($GLOBALS['injector']->getInstance('Horde_Log_Logger'));
                $devices = $stateMachine->listDevices($GLOBALS['registry']->getAuth());
                foreach ($devices as $device) {
                    $stateMachine->removeState(array(
                        'devId' => $device['device_id'],
                        'user' => $GLOBALS['registry']->getAuth(),
                        'id' => Horde_Core_ActiveSync_Driver::CONTACTS_FOLDER_UID)
                    );
                }
                $GLOBALS['notification']->push(_("All state removed for your ActiveSync devices. They will resynchronize next time they connect to the server."));
            } catch (Horde_ActiveSync_Exception $e) {
                $GLOBALS['notification']->push(_("There was an error communicating with the ActiveSync server: %s"), $e->getMessage(), 'horde.err');
            }
        }
    }

    /**
     * Returns values for <configspecial> configuration settings.
     *
     * @param string $what  Either 'client-fields' or 'sources'.
     *
     * @return array  The values for the requested configuration setting.
     */
    public function configSpecialValues($what)
    {
        switch ($what) {
        case 'client-fields':
            try {
                $fields = $GLOBALS['registry']->call('clients/clientFields');
            } catch (Horde_Exception $e) {
                return array();
            }
            $f = array();
            foreach ($fields as $field) {
                $f[$field['name']] = $field['label'];
            }
            return $f;

        case 'sources':
            try {
                $addressbooks = Turba::getAddressBooks(Horde_Perms::READ);
            } catch (Horde_Exception $e) {
                return array();
            }
            foreach ($addressbooks as &$addressbook) {
                $addressbook = $addressbook['title'];
            }

            $addressbooks[''] = _("None");
            return $addressbooks;
        }
    }

    /**
     */
    public function removeUserData($user)
    {
        /* We need a clean copy of the $cfgSources array here.*/
        $cfgSources = Turba::availableSources();
        foreach ($cfgSources as $source) {
            if (empty($source['use_shares'])) {
                // Shares not enabled for this source
                try {
                    $driver = $GLOBALS['injector']
                        ->getInstance('Turba_Factory_Driver')
                        ->create($source);
                } catch (Turba_Exception $e) {
                    Horde::logMessage($e, 'ERR');
                }

                try {
                    $driver->removeUserData($user);
                } catch (Turba_Exception_NotSupported $e) {
                    continue;
                } catch (Turba_Exception $e) {
                    Horde::logMessage($e, 'ERR');
                    throw new Turba_Exception(sprintf(_("There was an error removing an address book for %s"), $user));
                }
            }
        }

        /* Only attempt share removal if we have shares configured */
        if (!$GLOBALS['session']->get('turba', 'has_share')) {
            return;
        }

        $turba_shares = $GLOBALS['injector']->getInstance('Turba_Shares');
        $shares = $turba_shares->listShares(
            $user,
            array('perm' => Horde_Perms::EDIT,
                  'attributes' => $user));

        // Look for the deleted user's shares and remove them
        foreach ($shares as $share) {
            $config = Turba::getSourceFromShare($share);
            try {
                $driver = $GLOBALS['injector']
                    ->getInstance('Turba_Factory_Driver')
                    ->create($config);
            } catch (Turba_Exception $e) {
                continue;
            }

            try {
                $driver->removeUserData($user);
            } catch (Turba_Exception_NotSupported $e) {
                continue;
            } catch (Turba_Exception $e) {
                Horde::logMessage($e, 'ERR');
                throw new Turba_Exception(sprintf(_("There was an error removing an address book for %s"), $user));
            }
        }

        /* Get a list of all shares this user has perms to and remove the
         * perms. */
        try {
            $shares = $turba_shares->listShares($user);
            foreach ($shares as $share) {
                $share->removeUser($user);
            }
        } catch (Horde_Share_Exception $e) {
            Horde::logMessage($e, 'ERR');
            throw new Turba_Exception(sprintf(_("There was an error removing an address book for %s"), $user));
        }
    }

    /* Sidebar method. */

    /**
     */
    public function sidebarCreate(Horde_Tree_Renderer_Base $tree, $parent = null,
                                  array $params = array())
    {
        $add = Horde::url('add.php');
        $browse = Horde::url('browse.php');

        if ($GLOBALS['addSources']) {
            $newimg = Horde_Themes::img('menu/new.png');

            $tree->addNode(array(
                'id' => $parent . '__new',
                'parent' => $parent,
                'label' => _("New Contact"),
                'params' => array(
                    'icon' => $newimg,
                    'url' => $add
                )
            ));

            foreach ($GLOBALS['addSources'] as $addressbook => $config) {
                $tree->addNode(array(
                    'id' => $parent . $addressbook . '__new',
                    'parent' => $parent . '__new',
                    'label' => sprintf(_("in %s"), $config['title']),
                    'expanded' => false,
                    'params' => array(
                        'icon' => $newimg,
                        'url' => $add->copy()->add('source', $addressbook)
                    )
                ));
            }
        }

        foreach (Turba::getAddressBooks() as $addressbook => $config) {
            if (!empty($config['browse'])) {
                $tree->addNode(array(
                    'id' => $parent . $addressbook,
                    'parent' => $parent,
                    'label' => $config['title'],
                    'expanded' => false,
                    'params' => array(
                        'icon' => Horde_Themes::img('menu/browse.png'),
                        'url' => $browse->copy()->add('source', $addressbook)
                    )
                ));
            }
        }

        $tree->addNode(array(
            'id' => $parent . '__search',
            'parent' => $parent,
            'label' => _("Search"),
            'expanded' => false,
            'params' => array(
                'icon' => Horde_Themes::img('search.png'),
                'url' => Horde::url('search.php')
            )
        ));
    }

    /* Download data. */

    /**
     * @throws Horde_Vfs_Exception
     * @throws Turba_Exception
     */
    public function download(Horde_Variables $vars)
    {
        global $cfgSources, $injector;

        switch ($vars->actionID) {
        case 'download_file':
            /* Get the object. */
            if (!isset($cfgSources[$vars->source])) {
                throw new Turba_Exception(_("The contact you requested does not exist."));
            }

            $object = $injector->getInstance('Turba_Factory_Driver')->create($vars->source)->getObject($vars->key);

            /* Check permissions. */
            if (!$object->hasPermission(Horde_Perms::READ)) {
                throw new Turba_Exception(_("You do not have permission to view this contact."));
            }

            $vfs = $injector->getInstance('Horde_Core_Factory_Vfs')->create('documents');
            try {
                return array(
                    'data' => $vfs->read(Turba::VFS_PATH . '/' . $object->getValue('__uid'), $vars->file),
                    'name' => $vars->file
                );
            } catch (Horde_Vfs_Exception $e) {
                Horde::logMessage($e, 'ERR');
                throw new Turba_Exception(sprintf(_("Access denied to %s"), $vars->file));
            }

        case 'export':
            $sources = array();
            if ($vars->selected) {
                foreach ($vars->objectkeys as $objectkey) {
                    list($source, $key) = explode(':', $objectkey, 2);
                    if (!isset($sources[$source])) {
                        $sources[$source] = array();
                    }
                    $sources[$source][] = $key;
                }
            } else {
                if (!isset($vars->source) && !empty($cfgSources)) {
                    reset($cfgSources);
                    $vars->source = key($cfgSources);
                }
                $sources[$vars->source] = array();
            }

            if ($vcard = in_array($vars->exportID, array(Horde_Data::EXPORT_VCARD, 'vcard30'))) {
                $version = ($vars->exportID == 'vcard30') ? '3.0' : '2.1';
            }

            $all_fields = $data = array();
            $tfd = $injector->getInstance('Turba_Factory_Driver');

            foreach ($sources as $source => $objectkeys) {
                /* Create a Turba storage instance. */
                $driver = $tfd->create($source);

                /* Get the full, sorted contact list. */
                try {
                    $results = count($objectkeys)
                        ? $driver->getObjects($objectkeys)
                        : $driver->search(array())->objects;
                } catch (Turba_Exception $e) {
                    throw new Turba_Exception(sprintf(_("Failed to search the directory: %s"), $e->getMessage()));
                }

                $fields = array_keys($driver->map);
                $all_fields = array_merge($all_fields, $fields);

                $params = $driver->getParams();
                foreach ($results as $ob) {
                    if ($vcard) {
                        $data[] = $driver->tovCard($ob, $version, null, true);
                    } else {
                        $row = array();
                        foreach ($fields as $field) {
                            if (substr($field, 0, 2) != '__') {
                                $attribute = $ob->getValue($field);
                                if ($attributes[$field]['type'] == 'date') {
                                    $row[$field] = strftime('%Y-%m-%d', $attribute);
                                } elseif ($attributes[$field]['type'] == 'time') {
                                    $row[$field] = strftime('%R', $attribute);
                                } elseif ($attributes[$field]['type'] == 'datetime') {
                                    $row[$field] = strftime('%Y-%m-%d %R', $attribute);
                                } else {
                                $row[$field] = Horde_String::convertCharset($attribute, 'UTF-8', $params['charset']);
                                }
                            }
                        }
                        $data[] = $row;
                    }
                }
            }

            if (empty($data)) {
                throw new Turba_Exception(_("There were no addresses to export."));
            }

            /* Make sure that all rows have the same columns if exporting from
             * different sources. */
            if (!$vcard && count($sources) > 1) {
                for ($i = 0; $i < count($data); $i++) {
                    foreach ($all_fields as $field) {
                        if (!isset($data[$i][$field])) {
                            $data[$i][$field] = '';
                        }
                    }
                }
            }

            switch ($vars->exportID) {
            case Horde_Data::EXPORT_CSV:
                $injector->getInstance('Horde_Core_Factory_Data')->create('Csv', array('cleanup' => array($this, 'cleanupData')))->exportFile(_("contacts.csv"), $data, true);
                exit;

            case Horde_Data::EXPORT_OUTLOOKCSV:
                $injector->getInstance('Horde_Core_Factory_Data')->create('Outlookcsv', array('cleanup' => array($this, 'cleanupData')))->exportFile(_("contacts.csv"), $data, true, array_flip($outlook_mapping));
                exit;

            case Horde_Data::EXPORT_TSV:
                $injector->getInstance('Horde_Core_Factory_Data')->create('Tsv', array('cleanup' => array($this, 'cleanupData')))->exportFile(_("contacts.tsv"), $data, true);
                exit;

            case Horde_Data::EXPORT_VCARD:
            case 'vcard30':
                $injector->getInstance('Horde_Core_Factory_Data')->create('Vcard', array('cleanup' => array($this, 'cleanupData')))->exportFile(_("contacts.vcf"), $data, true);
                exit;

            case 'ldif':
                $ldif = new Turba_Data_Ldif(array(
                    'browser' => $injector->getInstance('Horde_Browser'),
                    'vars' => Horde_Variables::getDefaultVariables(),
                    'cleanup' => array($this, 'cleanupData')
                ));
                $ldif->exportFile(_("contacts.ldif"), $data, true);
                exit;
            }

            break;
        }
    }

    /**
     */
    public function cleanupData()
    {
        $GLOBALS['import_step'] = 1;
        return Horde_Data::IMPORT_FILE;
    }

    /**
     */
    public function getOutlookMapping()
    {
        return array(
            'Title' => 'namePrefix',
            'First Name' => 'firstname',
            'Middle Name' => 'middlenames',
            'Last Name' => 'lastname',
            'Nickname' => 'nickname',
            'Suffix' => 'nameSuffix',
            'Company' => 'company',
            'Department' => 'department',
            'Job Title' => 'title',
            'Business Street' => 'workStreet',
            'Business City' => 'workCity',
            'Business State' => 'workProvince',
            'Business Postal Code' => 'workPostalCode',
            'Business Country' => 'workCountry',
            'Home Street' => 'homeStreet',
            'Home City' => 'homeCity',
            'Home State' => 'homeProvince',
            'Home Postal Code' => 'homePostalCode',
            'Home Country' => 'homeCountry',
            'Business Fax' => 'workFax',
            'Business Phone' => 'workPhone',
            'Home Phone' => 'homePhone',
            'Mobile Phone' => 'cellPhone',
            'Pager' => 'pager',
            'Anniversary' => 'anniversary',
            'Assistant\'s Name' => 'assistant',
            'Birthday' => 'birthday',
            'Business Address PO Box' => 'workPOBox',
            'Categories' => 'category',
            'Children' => 'children',
            'E-mail Address' => 'email',
            'Home Address PO Box' => 'homePOBox',
            'Initials' => 'initials',
            'Internet Free Busy' => 'freebusyUrl',
            'Language' => 'language',
            'Notes' => 'notes',
            'Profession' => 'role',
            'Office Location' => 'office',
            'Spouse' => 'spouse',
            'Web Page' => 'website',
        );
    }

}
