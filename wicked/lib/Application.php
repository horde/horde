<?php
/**
 * Wicked application API.
 *
 * This file defines Horde's core API interface. Other core Horde libraries
 * can interact with Wicked through this API.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @package Wicked
 */

/* Determine the base directories. */
if (!defined('WICKED_BASE')) {
    define('WICKED_BASE', __DIR__ . '/..');
}

if (!defined('HORDE_BASE')) {
    /* If Horde does not live directly under the app directory, the HORDE_BASE
     * constant should be defined in config/horde.local.php. */
    if (file_exists(WICKED_BASE . '/config/horde.local.php')) {
        include WICKED_BASE . '/config/horde.local.php';
    } else {
        define('HORDE_BASE', WICKED_BASE . '/..');
    }
}

/* Load the Horde Framework core (needed to autoload
 * Horde_Registry_Application::). */
require_once HORDE_BASE . '/lib/core.php';

class Wicked_Application extends Horde_Registry_Application
{
    /**
     */
    public $version = 'H5 (2.0-git)';

    protected function _bootstrap()
    {
        $GLOBALS['injector']->bindFactory('Wicked_Driver', 'Wicked_Factory_Driver', 'create');
    }

    /**
     * Global variables defined:
     * - $wicked:   The Wicked_Driver object.
     */
    protected function _init()
    {
        $GLOBALS['wicked'] = $GLOBALS['injector']->getInstance('Wicked_Driver');
        $GLOBALS['injector']->getInstance('Horde_PageOutput')->addLinkTag(array(
            'href' => Horde::url('opensearch.php', true, -1),
            'rel' => 'search',
            'title' => $GLOBALS['registry']->get('name') . ' (' . Horde::url('', true) . ')',
            'type' => 'application/opensearchdescription+xml'
        ));
    }

    /**
     */
    public function perms()
    {
        $perms = array(
            'pages' => array(
                'title' => _("Pages")
            )
        );

        foreach (array('AllPages', 'LeastPopular', 'MostPopular', 'RecentChanges') as $val) {
            $perms['pages:' . $val] = array(
                'title' => $val
            );
        }

        try {
            $pages = $GLOBALS['wicked']->getPages();
            sort($pages);
            foreach ($pages as $pagename) {
                $perms['pages:' .$GLOBALS['wicked']->getPageId($pagename)] = array(
                    'title' => $pagename
                );
            }
        } catch (Wicked_Exception $e) {}

        return $perms;
    }

}
