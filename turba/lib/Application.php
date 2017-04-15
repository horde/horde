<?php
/**
 * Copyright 2010-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you did
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category  Horde
 * @copyright 2010-2017 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Turba
 */

/**
 * Turba application API.
 *
 * This file defines Horde's core API interface. Other core Horde libraries
 * can interact with Turba through this API.
 *
 * @author    Michael Slusarz <slusarz@horde.org?
 * @category  Horde
 * @copyright 2010-2017 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Turba
 */

/* Determine the base directories. */
if (!defined('TURBA_BASE')) {
    define('TURBA_BASE', realpath(__DIR__ . '/..'));
}

if (!defined('HORDE_BASE')) {
    /* If Horde does not live directly under the app directory, the HORDE_BASE
     * constant should be defined in config/horde.local.php. */
    if (file_exists(TURBA_BASE . '/config/horde.local.php')) {
        include TURBA_BASE . '/config/horde.local.php';
    } else {
        define('HORDE_BASE', realpath(TURBA_BASE . '/..'));
    }
}

/* Load the Horde Framework core (needed to autoload
 * Horde_Registry_Application::). */
require_once HORDE_BASE . '/lib/core.php';

use Sabre\CalDAV;
use Sabre\CardDAV;

class Turba_Application extends Horde_Registry_Application
{
    /**
     */
    public $features = array(
        'smartmobileView' => true,
        'modseq' => true,
    );

    /**
     */
    public $version = 'H5 (5.0.0-git)';

    /**
     */
    protected function _bootstrap()
    {
        /* Add Turba-specific factories. */
        $factories = array(
            'Turba_Shares' => 'Turba_Factory_Shares',
            'Turba_Tagger' => 'Turba_Factory_Tagger'
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
     *   $cfgSources   - TODO
     *   $copymoveSources - TODO
     */
    protected function _init()
    {
        global $conf, $injector, $registry, $session;

        if ($conf['tags']['enabled']) {
            /* For now, autoloading the Content_* classes depend on there being
             * a registry entry for the 'content' application that contains at
             * least the fileroot entry. */
            $injector->getInstance('Horde_Autoloader')
                ->addClassPathMapper(
                    new Horde_Autoloader_ClassPathMapper_Prefix('/^Content_/', $registry->get('fileroot', 'content') . '/lib/'));

            if (!class_exists('Content_Tagger')) {
                throw new Horde_Exception(_("The Content_Tagger class could not be found. Make sure the Content application is installed."));
            }
        }

        // Turba source and attribute configuration.
        $attributes = $registry->loadConfigFile('attributes.php', 'attributes', 'turba')->config['attributes'];
        $cfgSources = Turba::availableSources();

        /* UGLY UGLY UGLY - we should NOT be using this as a global
         * variable all over the place. */
        $GLOBALS['cfgSources'] = &$cfgSources;

        // See if any of our sources are configured to use Horde_Share.
        foreach ($cfgSources as $key => $cfg) {
            if (!empty($cfg['use_shares'])) {
                // Create a share instance.
                $session->set('turba', 'has_share', true);
                $cfgSources = Turba::getConfigFromShares($cfgSources);
                break;
            }
        }

        $GLOBALS['attributes'] = $attributes;
        $cfgSources = Turba::permissionsFilter($cfgSources);

        // Build the directory sources select widget.
        if (empty(Turba::$source)) {
            if (!(Turba::$source = $session->get('turba', 'source'))) {
                Turba::$source = Turba::getDefaultAddressbook();
            }
            Turba::$source = Horde_Util::getFormData('source', Turba::$source);
        }

        $GLOBALS['browse_source_count'] = 0;

        foreach (Turba::getAddressBooks() as $key => $curSource) {
            if (!empty($curSource['browse'])) {
                $GLOBALS['browse_source_count']++;
                if (empty(Turba::$source)) {
                    Turba::$source = $key;
                }
            }
        }

        if (empty($cfgSources[Turba::$source]['browse'])) {
            Turba::$source = Turba::getDefaultAddressbook();
        }
        $session->set('turba', 'source', Turba::$source);

        $GLOBALS['addSources'] = Turba::getAddressBooks(Horde_Perms::EDIT, array('require_add' => true));
        $GLOBALS['copymoveSources'] = $GLOBALS['addSources'];
        unset($GLOBALS['copymoveSources'][Turba::$source]);
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
    public function getInitialPage()
    {
        global $registry;

        switch ($registry->getView()) {
        case $registry::VIEW_SMARTMOBILE:
            return strval(Horde::url('smartmobile.php')->setRaw(true));
            break;

        default:
            return null;
        }
    }

    /**
     * @param  Horde_Menu $menu  The menu object
     */
    public function menu($menu)
    {
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
     * Add additional items to the sidebar.
     *
     * @param Horde_View_Sidebar $sidebar  The sidebar object.
     */
    public function sidebar($sidebar)
    {
        global $conf, $cfgSources;

        if (count($GLOBALS['addSources'])) {
            $sidebar->addNewButton(_("_New Contact"), Horde::url('add.php'));
        }

        $user = $GLOBALS['registry']->getAuth();
        $edit = Horde::url('addressbooks/edit.php');
        $url = Horde::url('');

        $sidebar->containers['my'] = array(
            'header' => array(
                'id' => 'turba-toggle-my',
                'label' => _("My Address Books"),
                'collapsed' => false,
            ),
        );
        if ($GLOBALS['registry']->getAuth() &&
            $GLOBALS['session']->get('turba', 'has_share') &&
            !empty($conf['shares']['source'])) {
            $create = true;
            $sidebar->containers['my']['header']['add'] = array(
                'url' => Horde::url('addressbooks/create.php'),
                'label' => _("Create a new Address Book"),
            );
        }
        $shares = array();
        $shared = array();
        foreach (Turba::listShares(false, Horde_Perms::SHOW) as $id => $abook) {
            $row = array(
                'selected' => $id == Turba::$source,
                'url' => $url->copy()->add('source', $id),
                'label' => $abook->get('name'),
                'edit' => $edit->copy()->add('a', $abook->getName()),
                'type' => 'radiobox',
            );
            if ($abook->get('owner') && $abook->get('owner') == $user) {
                $sidebar->addRow($row, 'my');
                if ($row['selected']) {
                    $sidebar->containers['my']['header']['collapsed'] = false;
                }
            } else {
                if ($abook->get('owner')) {
                    $row['label'] .= ' [' . $GLOBALS['registry']->convertUsername($abook->get('owner'), false) . ']';
                }
                $shared[] = $row;
            }
            $shares[$id] = true;
        }

        if (!empty($create) || count($shared)) {
            $sidebar->containers['shared'] = array(
                'header' => array(
                    'id' => 'turba-toggle-shared',
                    'label' => _("Shared Address Books"),
                    'collapsed' => true,
                ),
            );
            foreach ($shared as $row) {
                $sidebar->addRow($row, 'shared');
                if ($row['selected']) {
                    $sidebar->containers['shared']['header']['collapsed'] = false;
                }
            }
        }

        $sidebar->containers['other'] = array(
            'header' => array(
                'id' => 'turba-toggle-other',
                'label' => _("Other Address Books"),
                'collapsed' => true,
            ),
        );
        foreach (Turba::getAddressBooks(Horde_Perms::SHOW) as $id => $abook) {
            if (isset($shares[$id])) {
                continue;
            }
            $row = array(
                'selected' => $id == Turba::$source,
                'url' => $url->copy()->add('source', $id),
                'label' => $abook['title'],
                'type' => 'radiobox',
            );
            $sidebar->addRow($row, 'other');
            if ($row['selected']) {
                $sidebar->containers['other']['header']['collapsed'] = false;
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
        $err = sprintf(
            _("There was an error removing an address book for %s"),
            $user
        );

        /* We need a clean copy of the $cfgSources array here.*/
        $cfgSources = Turba::availableSources();
        foreach ($cfgSources as $sourceId => $source) {
            if (empty($source['use_shares'])) {
                // Shares not enabled for this source
                try {
                    $driver = $GLOBALS['injector']
                        ->getInstance('Turba_Factory_Driver')
                        ->create($source, $sourceId);
                } catch (Turba_Exception $e) {
                    Horde::log($e, 'ERR');
                    continue;
                }

                try {
                    $driver->removeUserData($user);
                } catch (Turba_Exception_NotSupported $e) {
                    continue;
                } catch (Turba_Exception $e) {
                    Horde::log($e, 'ERR');
                    throw new Turba_Exception($err);
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
            array('attributes' => $user)
        );

        // Look for the deleted user's shares and remove them
        $sources = Turba::getConfigFromShares(
            $cfgSources,
            true,
            array('shares' => $shares, 'auth_user' => $user)
        );

        foreach ($shares as $share) {
            $config = $sources[$share->getName()];
            try {
                $driver = $GLOBALS['injector']
                    ->getInstance('Turba_Factory_Driver')
                    ->create($config, $share->getName(), $sources);
            } catch (Turba_Exception $e) {
                continue;
            }

            try {
                $driver->removeUserData($user);
            } catch (Turba_Exception_NotSupported $e) {
                continue;
            } catch (Turba_Exception $e) {
                Horde::log($e, 'ERR');
                throw new Turba_Exception($err);
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
            Horde::log($e, 'ERR');
            throw new Turba_Exception($err);
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
     * @throws Turba_Exception
     * @throws Horde_Exception_NotFound
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

            try {
                return array(
                    'data' => $object->vfsInit()->read(Turba::VFS_PATH . '/' . $object->getValue('__uid'), $vars->file),
                    'name' => $vars->file
                );
            } catch (Horde_Vfs_Exception $e) {
                Horde::log($e, 'ERR');
                throw new Turba_Exception(sprintf(_("Access denied to %s"), $vars->file));
            }

        case 'export':
            $sources = array();
            if ($vars->objectkeys) {
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
                $blobs = $driver->getBlobs();

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
                            if ((substr($field, 0, 2) == '__' && $field != '__members' && $field != '__uid') ||
                                isset($blobs[$field])) {
                                continue;
                            }
                            $attribute = $ob->getValue($field);
                            if ($field == '__members') {
                                if (empty($attribute)) {
                                    $row['kind'] = '';
                                    $row['members'] = '';
                                    continue;
                                }
                                $row['kind'] = 'group';
                                $members = $ob->listMembers();
                                $uids = array();
                                foreach ($members->objects as $member) {
                                    $uids[] = $member->getValue('__uid');
                                }
                                $row['members'] = implode(',', $uids);
                            } elseif ($field == '__uid') {
                                $row['uid'] = !empty($attribute) ? $attribute : '';
                            } elseif ($attributes[$field]['type'] == 'date') {
                                $row[$field] = strftime('%Y-%m-%d', $attribute);
                            } elseif ($attributes[$field]['type'] == 'time') {
                                $row[$field] = strftime('%R', $attribute);
                            } elseif ($attributes[$field]['type'] == 'datetime') {
                                $row[$field] = strftime('%Y-%m-%d %R', $attribute);
                            } else {
                                $row[$field] = Horde_String::convertCharset($attribute, 'UTF-8', $params['charset']);
                            }
                        }
                        $data[] = $row;
                    }
                }
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

            $type;
            $filename;
            switch ($vars->exportID) {
            case Horde_Data::EXPORT_CSV:
                $type = 'Csv';
                $filename = "contacts.csv";
                break;

            case Horde_Data::EXPORT_OUTLOOKCSV:
                $type = 'Outlookcsv';
                $filename = "contacts.csv";
                break;

            case Horde_Data::EXPORT_TSV:
                $type = 'Tsv';
                $filename = "contacts.tsv";
                break;

            case Horde_Data::EXPORT_VCARD:
            case 'vcard30':
                $type = 'Vcard';
                $filename = "contacts.vcf";
                break;

            case 'ldif':
                $ldif = new Turba_Data_Ldif(array(
                    'browser' => $injector->getInstance('Horde_Browser'),
                    'vars' => Horde_Variables::getDefaultVariables(),
                    'cleanup' => array($this, 'cleanupData')
                ));
                $ldif->exportFile(_("contacts.ldif"), $data, true);
                exit;
            }

            if ( strlen($type) ) {
                $imc = $injector->getInstance('Horde_Core_Factory_Data')->create($type, array('cleanup' => array($this, 'cleanupData')));
                if ( $vars->exportData ) {
                    return $imc->exportData($data, true);
                } else {
                    $imc->exportFile(_($filename), $data, true);
                    exit;
                }
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
            'Categories' => '__tags',
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

    /* DAV methods. */

    /**
     */
    public function davGetCollections($user)
    {
        global $injector, $registry;

        $hordeUser = $registry->convertUsername($user, true);
        $dav = $injector->getInstance('Horde_Dav_Storage');
        $factory = $injector->getInstance('Turba_Shares');
        $books = array();
        foreach (Turba::getAddressBooks(Horde_Perms::SHOW) as $id => $book) {
            $readOnly = false;
            switch ($book['type']) {
            // Ugly hack! There is currently no clean way to retrieve address
            // books that the user "owns", or to find out if a SQL/LDAP/Kolab
            // address book contains per-user or global contacts.
            case 'share':
                $share = $factory->getShare($id);
                if (($user == '-system-' && strlen($share->get('owner'))) ||
                    ($user != '-system-' &&
                     $hordeUser != $share->get('owner') &&
                     $hordeUser != $registry->getAuth())) {
                    continue 2;
                }
                $readOnly = !$share->hasPermission($hordeUser, Horde_Perms::EDIT);
                break;

            case 'favourites':
            case 'vbook':
                if ($user == '-system-') {
                    continue 2;
                }
                $readOnly = true;
                break;

            default:
                if (!Turba::permissionsFilter(array($id => $book), Horde_Perms::EDIT)) {
                    $readOnly = true;
                }
                break;
            }
            try {
                $id = $dav->getExternalCollectionId($id, 'contacts') ?: $id;
            } catch (Horde_Dav_Exception $e) {
            }
            $books[] = array(
                'id' => $id,
                'uri' => $id,
                'principaluri' => 'principals/' . $user,
                '{DAV:}displayname' => $book['title'],
                '{' . CardDAV\Plugin::NS_CARDDAV . '}supported-address-data'
                    => new CardDAV\Property\SupportedAddressData(
                        array(
                            array(
                                'contentType' => 'text/directory',
                                'version' => '3.0'
                            ),
                            array(
                                'contentType' => 'text/vcard',
                                'version' => '3.0'
                            ),
                            array(
                                'contentType' => 'text/x-vcard',
                                'version' => '2.1'
                            ),
                        )
                    ),
                '{http://sabredav.org/ns}read-only' => $readOnly
            );
        }
        return $books;
    }

    /**
     */
    public function davGetObjects($collection)
    {
        $dav = $GLOBALS['injector']
            ->getInstance('Horde_Dav_Storage');

        $internal = $dav->getInternalCollectionId($collection, 'contacts') ?: $collection;
        $driver = $GLOBALS['injector']
            ->getInstance('Turba_Factory_Driver')
            ->create($internal);
        if (!$driver->hasPermission(Horde_Perms::READ)) {
            throw new Turba_Exception("Address Book does not exist or no permission to edit");
        }

        $list = $driver->search(array());
        $list->reset();
        $contacts = array();
        while ($contact = $list->next()) {
            $id = $contact->getValue('__key');
            $modified = $contact->lastModification();
            try {
                $id = $dav->getExternalObjectId($id, $internal) ?: $id . '.vcf';
            } catch (Horde_Dav_Exception $e) {
            }
            $contacts[] = array(
                'id' => $id,
                'uri' => $id,
                'lastmodified' => $modified,
                'etag' => '"' . md5($contact->getValue('__key') . '|' . $modified) . '"',
            );
        }

        return $contacts;
    }

    /**
     */
    public function davGetObject($collection, $object)
    {
        $dav = $GLOBALS['injector']
            ->getInstance('Horde_Dav_Storage');

        $internal = $dav->getInternalCollectionId($collection, 'contacts') ?: $collection;
        $driver = $GLOBALS['injector']
            ->getInstance('Turba_Factory_Driver')
            ->create($internal);
        if (!$driver->hasPermission(Horde_Perms::READ)) {
            throw new Turba_Exception("Address Book does not exist or no permission to edit");
        }

        try {
            $object = $dav->getInternalObjectId($object, $internal) ?: preg_replace('/\.vcf$/', '', $object);
        } catch (Horde_Dav_Exception $e) {
        }
        $contact = $driver->getObject($object);
        $id = $contact->getValue('__key');
        $modified = $contact->lastModification();
        try {
            $id = $dav->getExternalObjectId($id, $internal) ?: $id . '.vcf';
        } catch (Horde_Dav_Exception $e) {
        }

        $data = $driver->tovCard($contact, '3.0', null, true)
            ->exportvCalendar();

        return array(
            'id' => $id,
            'carddata' => $data,
            'uri' => $id,
            'lastmodified' => $modified,
            'etag' => '"' . md5($contact->getValue('__key') . '|' . $modified) . '"',
            'size' => strlen($data),
        );
    }

    /**
     */
    public function davPutObject($collection, $object, $data)
    {
        $dav = $GLOBALS['injector']
            ->getInstance('Horde_Dav_Storage');

        $internal = $dav->getInternalCollectionId($collection, 'contacts') ?: $collection;
        $driver = $GLOBALS['injector']
            ->getInstance('Turba_Factory_Driver')
            ->create($internal);
        if (!$driver->hasPermission(Horde_Perms::EDIT)) {
            throw new Turba_Exception("Address Book does not exist or no permission to edit");
        }

        $ical = new Horde_Icalendar();
        if (!$ical->parsevCalendar($data)) {
            throw new Turba_Exception(_("There was an error importing the vCard data."));
        }

        foreach ($ical->getComponents() as $content) {
            if (!($content instanceof Horde_Icalendar_Vcard)) {
                continue;
            }

            $contact = $driver->toHash($content);

            try {
                try {
                    $existing_id = $dav->getInternalObjectId($object, $internal)
                        ?: preg_replace('/\.vcf$/', '', $object);
                } catch (Horde_Dav_Exception $e) {
                    $existing_id = $object;
                }
                $existing_contact = $driver->getObject($existing_id);
                /* Check if our contact is newer then the existing - get the
                 * contact's history. */
                $modified = $existing_contact->lastModification();
                try {
                    if (!empty($modified) &&
                        $content->getAttribute('LAST-MODIFIED')->before($modified)) {
                        /* LAST-MODIFIED timestamp of existing entry is newer:
                         * don't replace it. */
                        continue;
                    }
                } catch (Horde_Icalendar_Exception $e) {
                }
                foreach ($contact as $attribute => $value) {
                    if ($attribute != '__key') {
                        $existing_contact->setValue($attribute, $value);
                    }
                }
                $existing_contact->store();
            } catch (Horde_Exception_NotFound $e) {
                $id = $driver->add($contact);
                $dav->addObjectMap($id, $object, $internal);
            }
        }
    }

    /**
     */
    public function davDeleteObject($collection, $object)
    {
        $dav = $GLOBALS['injector']->getInstance('Horde_Dav_Storage');

        $internal = $dav->getInternalCollectionId($collection, 'contacts') ?: $collection;
        $driver = $GLOBALS['injector']
            ->getInstance('Turba_Factory_Driver')
            ->create($internal);
        if (!$driver->hasPermission(Horde_Perms::DELETE)) {
            throw new Turba_Exception("Address Book does not exist or no permission to edit");
        }

        try {
            $object = $dav->getInternalObjectId($object, $internal)
                ?: preg_replace('/\.vcf$/', '', $object);
        } catch (Horde_Dav_Exception $e) {
        }
        $driver->delete($object);

        try {
            $dav->deleteExternalObjectId($object, $internal);
        } catch (Horde_Dav_Exception $e) {
        }
    }

    /**
     * Returns the last modification (or creation) date of a contact.
     *
     * @param string $collection  A address book ID.
     * @param string $object      A contact UID.
     *
     * @return integer  Timestamp of the last modification.
     */
    protected function _modified($collection, $uid)
    {
        $history = $GLOBALS['injector']
            ->getInstance('Horde_History');
        $modified = $history->getActionTimestamp(
            'turba:' . $collection . ':' . $uid,
            'modify'
        );
        if (!$modified) {
            $modified = $history->getActionTimestamp(
                'turba:' . $collection . ':' . $uid,
                'add'
            );
        }
        return $modified;
    }
}
