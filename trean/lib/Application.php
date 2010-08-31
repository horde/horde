<?php
/**
 * Trean application API
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Mike Cochrane <mike@graftonhall.co.nz>
 */

/* Determine the base directories. */
if (!defined('TREAN_BASE')) {
    define('TREAN_BASE', dirname(__FILE__) . '/..');
}

if (!defined('HORDE_BASE')) {
    /* If Horde does not live directly under the app directory, the HORDE_BASE
     * constant should be defined in config/horde.local.php. */
    if (file_exists(TREAN_BASE . '/config/horde.local.php')) {
        include TREAN_BASE . '/config/horde.local.php';
    } else {
        define('HORDE_BASE', TREAN_BASE . '/..');
    }
}

/* Load the Horde Framework core (needed to autoload
 * Horde_Registry_Application::). */
require_once HORDE_BASE . '/lib/core.php';

class Trean_Application extends Horde_Registry_Application
{
    /**
     * The application's version.
     *
     * @var string
     */
    public $version = 'H4 (1.0-git)';

    /**
     * Initialization function.
     *
     * Global variables defined:
     *   $trean_db     - TODO
     *   $trean_shares - TODO
     */
    protected function _init()
    {
        // Set the timezone variable.
        $GLOBALS['registry']->setTimeZone();

        // Create db and share instances.
        $GLOBALS['trean_db'] = Trean::getDb();
        if ($GLOBALS['trean_db'] instanceof PEAR_Error) {
            throw new Horde_Exception($GLOBALS['trean_db']);
        }
        $GLOBALS['trean_shares'] = new Trean_Bookmarks();

        Trean::initialize();
    }

    /**
     * Returns a list of available permissions.
     *
     * @return array  An array describing all available permissions.
     */
    public function perms()
    {
        $perms = array();

        $perms['tree']['trean']['max_folders'] = false;
        $perms['title']['trean:max_folders'] = _("Maximum Number of Folders");
        $perms['type']['trean:max_folders'] = 'int';
        $perms['tree']['trean']['max_bookmarks'] = false;
        $perms['title']['trean:max_bookmarks'] = _("Maximum Number of Bookmarks");
        $perms['type']['trean:max_bookmarks'] = 'int';

        return $perms;
    }

    /**
     * Generate the menu to use on the prefs page.
     *
     * @return Horde_Menu  A Horde_Menu object.
     */
    public function prefsMenu()
    {
        return Trean::getMenu();
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
        $tree->addNode(
            $parent . '__new',
            $parent,
            _("Add"),
            1,
            false,
            array(
                'icon' => Horde_Themes::img('add.png'),
                'url' => Horde::applicationUrl('add.php')
            )
        );

        $tree->addNode(
            $parent . '__search',
            $parent,
            _("Search"),
            1,
            false,
            array(
                'icon' => Horde_Themes::img('search.png'),
                'url' => Horde::applicationUrl('search.php')
            )
        );

        $folders = Trean::listFolders();
        if (!($folders instanceof PEAR_Error)) {
            $browse = Horde::applicationUrl('browse.php');

            foreach ($folders as $folder) {
                $parent_id = $folder->getParent();
                $tree->addNode(
                    $parent . $folder->getId(),
                    $parent . $parent_id,
                    $folder->get('name'),
                    substr_count($folder->getName(), ':') + 1,
                    false,
                    array(
                        'icon' => Horde_Themes::img('tree/folder.png'),
                        'url' => $browse->copy()->add('f', $folder->getId())
                    )
                );
            }
        }
    }

}
