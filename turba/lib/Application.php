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
    public $version = 'H5 (4.0.0beta1)';

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
        if (empty(Turba::$source)) {
            if (!(Turba::$source = $GLOBALS['session']->get('turba', 'source'))) {
                Turba::$source = Turba::getDefaultAddressbook();
            }
            Turba::$source = Horde_Util::getFormData('source', Turba::$source);
        }

        $GLOBALS['browse_source_count'] = 0;
        $GLOBALS['browse_source_options'] = '';

        foreach (Turba::getAddressBooks() as $key => $curSource) {
            if (!empty($curSource['browse'])) {
                $selected = ($key == Turba::$source) ? ' selected="selected"' : '';
                $GLOBALS['browse_source_options'] .= '<option value="' . htmlspecialchars($key) . '" ' . $selected . '>' .
                    htmlspecialchars($curSource['title']) . '</option>';

                ++$GLOBALS['browse_source_count'];

                if (empty(Turba::$source)) {
                    Turba::$source = $key;
                }
            }
        }

        if (empty($cfgSources[Turba::$source]['browse'])) {
            Turba::$source = Turba::getDefaultAddressbook();
        }
        $GLOBALS['session']->set('turba', 'source', Turba::$source);

        $GLOBALS['addSources'] = Turba::getAddressBooks(Horde_Perms::EDIT, array('require_add' => true));
        $GLOBALS['copymoveSources'] = array_diff($GLOBALS['addSources'], array(Turba::$source));
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
            $menu->add(Horde::url('addressbooks/index.php'), _("_My Address Books"), 'turba-addressbooks');
        }

        if ($GLOBALS['browse_source_count']) {
            $menu->add(Horde::url('browse.php'), _("_Browse"), 'turba-browse', null, null, null, (($GLOBALS['prefs']->getValue('initial_page') == 'browse.php' && basename($_SERVER['PHP_SELF']) == 'index.php' && basename(dirname($_SERVER['PHP_SELF'])) != 'addressbooks') || (basename($_SERVER['PHP_SELF']) == 'browse.php' && Horde_Util::getFormData('key') != '**search')) ? 'current' : '__noselection');
        }

        $menu->add(Horde::url('search.php'), _("_Search"), 'turba-search', null, null, null, (($GLOBALS['prefs']->getValue('initial_page') == 'search.php' && basename($_SERVER['PHP_SELF']) == 'index.php' && strpos($_SERVER['PHP_SELF'], 'addressbooks/index.php') === false) || (basename($_SERVER['PHP_SELF']) == 'browse.php' && Horde_Util::getFormData('key') == '**search')) ? 'current' : null);

        /* Import/Export */
        if ($GLOBALS['conf']['menu']['import_export']) {
            $menu->add(Horde::url('data.php'), _("_Import/Export"), 'horde-data');
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

    /* Topbar method. */

    /**
     */
    public function topbarCreate(Horde_Tree_Renderer_Base $tree, $parent = null,
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
        global $attributes, $cfgSources, $injector;

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
