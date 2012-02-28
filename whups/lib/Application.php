<?php
/**
 * Whups application API.
 *
 * This file defines Horde's core API interface. Other core Horde libraries
 * can interact with Whups through this API.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @package Whups
 */

/* Determine the base directories. */
if (!defined('WHUPS_BASE')) {
    define('WHUPS_BASE', dirname(__FILE__) . '/..');
}

if (!defined('HORDE_BASE')) {
    /* If Horde does not live directly under the app directory, the HORDE_BASE
     * constant should be defined in config/horde.local.php. */
    if (file_exists(WHUPS_BASE . '/config/horde.local.php')) {
        include WHUPS_BASE . '/config/horde.local.php';
    } else {
        define('HORDE_BASE', WHUPS_BASE . '/..');
    }
}

/* Load the Horde Framework core (needed to autoload
 * Horde_Registry_Application::). */
require_once HORDE_BASE . '/lib/core.php';

class Whups_Application extends Horde_Registry_Application
{
    /**
     */
    public $version = 'H4 (2.0.3-git)';

    /**
     * Global variables defined:
     * - $whups_driver: The global Whups driver object.
     * - $linkTags:     <link> tags for common-header.inc.
     */
    protected function _init()
    {
        $GLOBALS['whups_driver'] = $GLOBALS['injector']->getInstance('Whups_Factory_Driver')->create();
        $GLOBALS['linkTags'] = array('<link href="' . Horde::url('opensearch.php', true, -1) . '" rel="search" type="application/opensearchdescription+xml" title="' . $GLOBALS['registry']->get('name') . ' (' . Horde::url('', true) . ')" />');

        /* Set the timezone variable, if available. */
        $GLOBALS['registry']->setTimeZone();
    }

    /**
     */
    public function perms()
    {
        /* Available Whups permissions. */
        $perms = array(
            'admin' => array(
                'title' => _("Administration")
            ),
            'hiddenComments' => array(
                'title' => _("Hidden Comments")
            ),
            'queues' => array(
                'title' => _("Queues")
            ),
            'replies' => array(
                'title' => _("Form Replies")
            )
        );

        /* Loop through queues and add their titles. */
        $queues = $GLOBALS['whups_driver']->getQueues();
        foreach ($queues as $id => $name) {
            $perms['queues:' . $id] = array(
                'title' => $name
            );

            $entries = array(
                'assign' => _("Assign"),
                'requester' => _("Set Requester"),
                'update' => _("Update")
            );

            foreach ($entries as $key => $val) {
                $perms['queues:' . $id . ':' . $key] = array(
                    'title' => $val,
                    'type' => 'boolean'
                );
            }
        }

        /* Loop through type and replies and add their titles. */
        foreach ($GLOBALS['whups_driver']->getAllTypes() as $type_id => $type_name) {
            foreach ($GLOBALS['whups_driver']->getReplies($type_id) as $reply_id => $reply) {
                $perms['replies:' . $reply_id] = array(
                    'title' => $type_name . ': ' . $reply['reply_name']
                );
            }
        }

        return $perms;
    }

    /**
     */
    public function menu($menu)
    {
        $menu->add(Horde::url('mybugs.php'), sprintf(_("_My %s"), $GLOBALS['registry']->get('name')), 'whups.png', null, null, null, $GLOBALS['prefs']->getValue('whups_default_view') == 'mybugs' && strpos($_SERVER['PHP_SELF'], $GLOBALS['registry']->get('webroot') . '/index.php') !== false ? 'current' : null);
        $menu->add(Horde::url('search.php'), _("_Search"), 'search.png', null, null, null, $GLOBALS['prefs']->getValue('whups_default_view') == 'search' && strpos($_SERVER['PHP_SELF'], $GLOBALS['registry']->get('webroot') . '/index.php') !== false ? 'current' : null);
        $menu->add(Horde::url('ticket/create.php'), _("_New Ticket"), 'create.png', null, null, null, $GLOBALS['prefs']->getValue('whups_default_view') == 'ticket/create' && basename($_SERVER['PHP_SELF']) == 'index.php' ? 'current' : null);
        $menu->add(Horde::url('query/index.php'), _("_Query Builder"), 'query.png');
        $menu->add(Horde::url('reports.php'), _("_Reports"), 'reports.png');

        /* Administration. */
        if ($GLOBALS['registry']->isAdmin(array('permission' => 'whups:admin'))) {
            $menu->add(Horde::url('admin/'), _("_Admin"), 'admin.png');
        }
    }

    /**
     */
    public function prefsInit($ui)
    {
        if (!$GLOBALS['registry']->hasMethod('contacts/sources')) {
            $ui->suppressGroups[] = 'addressbooks';
        }
    }

    /**
     */
    public function prefsGroup($ui)
    {
        foreach ($ui->getChangeablePrefs() as $val) {
            switch ($val) {
            case 'sourceselect':
                Horde_Core_Prefs_Ui_Widgets::addressbooksInit();
                break;
            }
        }
    }

    /**
     */
    public function prefsSpecial($ui, $item)
    {
        switch ($item) {
        case 'sourceselect':
            $search = Whups::getAddressbookSearchParams();
            return Horde_Core_Prefs_Ui_Widgets::addressbooks(array(
                'fields' => $search['fields'],
                'sources' => $search['sources']
            ));
        }

        return '';
    }

    /**
     */
    public function prefsSpecialUpdate($ui, $item)
    {
        $updated = false;

        switch ($item) {
        case 'sourceselect':
            $data = Horde_Core_Prefs_Ui_Widgets::addressbooksUpdate($ui);

            if (isset($data['sources'])) {
                $GLOBALS['prefs']->setValue('search_sources', $data['sources']);
                $updated = true;
            }

            if (isset($data['fields'])) {
                $GLOBALS['prefs']->setValue('search_fields', $data['fields']);
                $updated = true;
            }
            break;
        }

        return $updated;
    }

    /* Sidebar method. */

    /**
     */
    public function sidebarCreate(Horde_Tree_Base $tree, $parent = null,
                                  array $params = array())
    {
        $tree->addNode(
            $parent . '__new',
            $parent,
            _("New Ticket"),
            1,
            false,
            array(
                'icon' => Horde_Themes::img('create.png'),
                'url' => Horde::url('ticket/create.php')
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
                'url' => Horde::url('search.php')
            )
        );
    }

}
