<?php
/**
 * Turba application API.
 *
 * This file defines Horde's core API interface. Other core Horde libraries
 * can interact with Turba through this API.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
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
    define('TURBA_BASE', dirname(__FILE__) . '/..');
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
     * The application's version.
     *
     * @var string
     */
    public $version = 'H4 (3.0-git)';

    /**
     * Initialization.
     *
     * Global variables defined:
     *   $addSources   - TODO
     *   $attributes - (array) Attribute data from the config/attributes.php
     *                 file.
     *   $browse_source_count - TODO
     *   $browse_source_options - TODO
     *   $cfgSources   - TODO
     *   $copymove_source_options - TODO
     *   $copymoveSources - TODO
     *   $turba_shares - TODO
     */
    protected function _init()
    {
        /* Add Turba-specific binders. */
        $binders = array(
            'Turba_Driver' => new Turba_Injector_Binder_Driver()
        );

        foreach ($binders as $key => $val) {
            $GLOBALS['injector']->addBinder($key, $val);
        }

        // Turba source and attribute configuration.
        $attributes = Horde::loadConfiguration('attributes.php', 'attributes', 'turba');
        include TURBA_BASE . '/config/backends.php';

        /* UGLY UGLY UGLY - we should NOT be using this as a global
         * variable all over the place. */
        $GLOBALS['cfgSources'] = &$cfgSources;

        // See if any of our sources are configured to use Horde_Share.
        foreach ($cfgSources as $key => $cfg) {
            if (!empty($cfg['use_shares'])) {
                // Create a share instance.
                $GLOBALS['session']->set('turba', 'has_share', true);
                $GLOBALS['turba_shares'] = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Share')->create();
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
                $default_source = Turba::getDefaultAddressBook();
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
            $default_source = Turba::getDefaultAddressBook();
        }
        $GLOBALS['session']->set('turba', 'source', $default_source);
        $GLOBALS['default_source'] = $default_source;

        /* Only set $add_source_options if there is at least one editable
         * address book that is not the current address book. */
        $addSources = Turba::getAddressBooks(Horde_Perms::EDIT, array('require_add' => true));
        $copymove_source_options = '';
        $copymoveSources = $addSources;
        unset($copymoveSources[$default_source]);
        foreach ($copymoveSources as $key => $curSource) {
            if ($key != $default_source) {
                $copymove_source_options .= '<option value="' . htmlspecialchars($key) . '">' .
                    htmlspecialchars($curSource['title']) . '</option>';
            }
        }

        $GLOBALS['addSources'] = $addSources;
        $GLOBALS['copymove_source_options'] = $copymove_source_options;
        $GLOBALS['copymoveSources'] = $copymoveSources;
    }

    /**
     * Returns a list of available permissions.
     *
     * @return array  An array describing all available permissions.
     */
    public function perms()
    {
        require TURBA_BASE . '/config/backends.php';

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
     * Add additional items to the menu.
     *
     * @param Horde_Menu $menu  The menu object.
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

        $menu->add(Horde::url('search.php'), _("_Search"), 'search.png', Horde_Themes::img(null, 'horde'), null, null, (($GLOBALS['prefs']->getValue('initial_page') == 'search.php' && basename($_SERVER['PHP_SELF']) == 'index.php' && strpos($_SERVER['PHP_SELF'], 'addressbooks/index.php') === false) || (basename($_SERVER['PHP_SELF']) == 'browse.php' && Horde_Util::getFormData('key') == '**search')) ? 'current' : null);

        /* Import/Export */
        if ($GLOBALS['conf']['menu']['import_export']) {
            $menu->add(Horde::url('data.php'), _("_Import/Export"), 'data.png', Horde_Themes::img(null, 'horde'));
        }
    }

    /**
     * Populate dynamically-generated preference values.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     */
    public function prefsEnum($ui)
    {
        global $prefs;

        switch ($ui->group) {
        case 'addressbooks':
            if (!$prefs->isLocked('default_dir')) {
                $out = array();
                foreach ($GLOBALS['cfgSources'] as $key => $info) {
                    $out[$key] = $info['title'];
                }
                $ui->override['default_dir'] = $out;
            }

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
            break;
        }
    }

    /**
     * Code to run on init when viewing prefs for this application.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     */
    public function prefsInit($ui)
    {
        global $prefs;

        switch ($ui->group) {
        case 'addressbooks':
            Horde_Core_Prefs_Ui_Widgets::sourceInit();
            break;

        case 'columns':
            Horde::addScriptFile('effects.js', 'horde');
            Horde::addScriptFile('dragdrop.js', 'horde');
            Horde::addScriptFile('columnprefs.js', 'turba');
            break;
        }
    }

    /**
     * Generate code used to display a special preference.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     * @param string $item             The preference name.
     *
     * @return string  The HTML code to display on the prefs page.
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
     * Special preferences handling on update.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     * @param string $item             The preference name.
     *
     * @return boolean  True if preference was updated.
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
     * Removes user data.
     *
     * @param string $user  Name of user to remove data for.
     *
     * @throws Turba_Exception
     */
    public function removeUserData($user)
    {
        /* We need a clean copy of the $cfgSources array here.*/
        require TURBA_BASE . '/config/backends.php';

        foreach ($cfgSources as $source) {
            if (empty($source['use_shares'])) {
                // Shares not enabled for this source
                try {
                    $driver = $GLOBALS['injector']->getInstance('Turba_Driver')->getDriver($source);
                } catch (Turba_Exception $e) {
                    Horde::logMessage($e, 'ERR');
                    throw new Turba_Exception(sprintf(_("There was an error removing an address book for %s"), $user));
                }

                try {
                    $driver->removeUserData($user);
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

        $shares = $GLOBALS['turba_shares']->listShares($user, Horde_Perms::EDIT, $user);

        /* Look for the deleted user's default share and remove it */
        foreach ($shares as $share) {
            $params = @unserialize($share->get('params'));

            /* Only attempt to delete the user's default share */
            if (!empty($params['default'])) {
                $config = Turba::getSourceFromShare($share);
                try {
                    $driver = $GLOBALS['injector']->getInstance('Turba_Driver')->getDriver($config);
                } catch (Turba_Exception $e) {
                    continue;
                }

                try {
                    $driver->removeUserData($user);
                } catch (Turba_Exception $e) {
                    Horde::logMessage($e, 'ERR');
                    throw new Turba_Exception(sprintf(_("There was an error removing an address book for %s"), $user));
                }
            }
        }

        /* Get a list of all shares this user has perms to and remove the
         * perms. */
        try {
            $shares = $GLOBALS['turba_shares']->listShares($user);
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
     * Add node(s) to the sidebar tree.
     *
     * @param Horde_Tree_Base $tree  Tree object.
     * @param string $parent         The current parent element.
     * @param array $params          Additional parameters.
     *
     * @throws Horde_Exception
     */
    public function sidebarCreate(Horde_Tree_Base $tree, $parent = null,
                                  array $params = array())
    {
        $add = Horde::url('add.php');
        $browse = Horde::url('browse.php');

        if ($GLOBALS['addSources']) {
            $newimg = Horde_Themes::img('menu/new.png');

            $tree->addNode(
                $parent . '__new',
                $parent,
                _("New Contact"),
                1,
                false,
                array(
                    'icon' => $newimg,
                    'url' => $add
                )
            );

            foreach ($GLOBALS['addSources'] as $addressbook => $config) {
                $tree->addNode(
                    $parent . $addressbook . '__new',
                    $parent . '__new',
                    sprintf(_("in %s"), $config['title']),
                    2,
                    false,
                    array(
                        'icon' => $newimg,
                        'url' => $add->copy()->add('source', $addressbook)
                    )
                );
            }
        }

        foreach (Turba::getAddressBooks() as $addressbook => $config) {
            if (!empty($config['browse'])) {
                $tree->addNode(
                    $parent . $addressbook,
                    $parent,
                    $config['title'],
                    1,
                    false,
                    array(
                        'icon' => Horde_Themes::img('menu/browse.png'),
                        'url' => $browse->copy()->add('source', $addressbook)
                    )
                );
            }
        }

        $tree->addNode(
            $parent . '__search',
            $parent,
            _("Search"),
            1,
            false,
            array(
                'icon' => Horde_Themes::img('search.png'),
                'url' => Horde::url('search.php')
            )
        );
    }

}
