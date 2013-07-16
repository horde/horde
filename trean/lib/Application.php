<?php
/**
 * Trean application API
 *
 * Copyright 2002-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Mike Cochrane <mike@graftonhall.co.nz>
 */

/* Determine the base directories. */
if (!defined('TREAN_BASE')) {
    define('TREAN_BASE', __DIR__ . '/..');
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
     */
    public $version = 'H5 (1.0.3)';

    /**
     * Global variables defined:
     * - $trean_db:      Horde_Db object
     * - $trean_gateway: Trean_Bookmarks object
     */
    protected function _init()
    {
        /* For now, autoloading the Content_* classes depend on there being a
         * registry entry for the 'content' application that contains at least
         * the fileroot entry. */
        $GLOBALS['injector']->getInstance('Horde_Autoloader')
            ->addClassPathMapper(new Horde_Autoloader_ClassPathMapper_Prefix(
                '/^Content_/',
                $GLOBALS['registry']->get('fileroot', 'content') . '/lib/'
            ));
        if (!class_exists('Content_Tagger')) {
            throw new Horde_Exception('The Content_Tagger class could not be found. Make sure the Content application is installed.');
        }

        // Set the timezone variable.
        $GLOBALS['registry']->setTimeZone();

        // Create db and gateway instances.
        $GLOBALS['trean_db'] = $GLOBALS['injector']
            ->getInstance('Horde_Core_Factory_Db')
            ->create('trean', 'storage');
        $GLOBALS['trean_gateway'] = $GLOBALS['injector']
            ->getInstance('Trean_Bookmarks');
    }

    /**
     */
    public function perms()
    {
        return array(
            'max_bookmarks' => array(
                'title' => _("Maximum Number of Bookmarks"),
                'type' => 'int'
            ),
        );
    }

    /**
     */
    public function menu($menu)
    {
        $menu->add(Horde::url('browse.php'), _("_Browse"), 'trean-browse', null, null, null, basename($_SERVER['PHP_SELF']) == 'index.php' ? 'current' : null);
    }

    /**
     * Add additional items to the sidebar.
     *
     * @param Horde_View_Sidebar $sidebar  The sidebar object.
     */
    public function sidebar($sidebar)
    {
        $sidebar->addNewButton(_("_New Bookmark"), Horde::url('add.php'));

        $sidebar->containers['tags'] = array(
            'header' => array(
                'id' => 'trean-toggle-tags',
                'label' => _("Tags"),
                'collapsed' => false,
            ),
        );

        $tagger = $GLOBALS['injector']->getInstance('Trean_Tagger');
        $tags = $tagger->listBookmarkTags();
        natcasesort($tags);
        foreach ($tags as $tag) {
            $url = Horde::url("tag/$tag");
            $row = array(
                'url' => $url,
                'cssClass' => 'trean-tag',
                'label' => $tag,
            );
            $sidebar->addRow($row, 'tags');
        }
    }

}
