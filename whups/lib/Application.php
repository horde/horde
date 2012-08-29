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
    define('WHUPS_BASE', __DIR__ . '/..');
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
    public $version = 'H5 (3.0-git)';

    /**
     * Global variables defined:
     * - $whups_driver: The global Whups driver object.
     */
    protected function _init()
    {
        $GLOBALS['whups_driver'] = $GLOBALS['injector']->getInstance('Whups_Factory_Driver')->create();
        $GLOBALS['page_output']->addLinkTag(array(
            'href' => Horde::url('opensearch.php', true, -1),
            'rel' => 'search',
            'type' => 'application/opensearchdescription+xml',
            'title' => $GLOBALS['registry']->get('name') . ' (' . Horde::url('', true) . ')'
        ));

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

    /* Topbar method. */

    /**
     */
    public function topbarCreate(Horde_Tree_Renderer_Base $tree, $parent = null,
                                 array $params = array())
    {
        $tree->addNode(array(
            'id' => $parent . '__new',
            'parent' => $parent,
            'label' => _("New Ticket"),
            'expanded' => false,
            'params' => array(
                'icon' => Horde_Themes::img('create.png'),
                'url' => Horde::url('ticket/create.php')
            )
        ));

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
     * @throws Whups_Exception
     */
    public function download(Horde_Variables $vars)
    {
        global $injector, $whups_driver;

        switch ($vars->actionID) {
        case 'download_file':
            // Get the ticket details first.
            if (empty($vars->id)) {
                exit;
            }

            $details = $whups_driver->getTicketDetails($vars->id);

            // Check permissions on this ticket.
            if (!count(Whups::permissionsFilter($whups_driver->getHistory($vars->id), 'comment', Horde_Perms::READ))) {
                throw new Whups_Exception(sprintf(_("You are not allowed to view ticket %d."), $vars->id));
            }

            try {
                $vfs = $injector->getInstance('Horde_Core_Factory_Vfs')->create();
            } catch (Horde_Exception $e) {
                throw new Whups_Exception(_("The VFS backend needs to be configured to enable attachment uploads."));
            }

            try {
                return array(
                    'data' => $vfs->read(Whups::VFS_ATTACH_PATH . '/' . $vars->id, $vars->file),
                    'name' => $vars->file
                );
            } catch (Horde_Vfs_Exception $e) {
                throw Whups_Exception(sprintf(_("Access denied to %s"), $vars->file));
            }
            break;

        case 'report':
            $_templates = Horde::loadConfiguration('templates.php', '_templates', 'whups');
            $tpl = $vars->template;
            if (empty($_templates[$tpl])) {
                throw new Whups_Exception(_("The requested template does not exist."));
            }
            if ($_templates[$tpl]['type'] != 'searchresults') {
                throw new Whups_Exception(_("This is not a search results template."));
            }

            // Fetch all unresolved tickets assigned to the current user.
            $info = array('id' => explode(',', $vars->ids));
            $tickets = $whups_driver->getTicketsByProperties($info);
            foreach ($tickets as $id => $info) {
                $tickets[$id]['#'] = $id + 1;
                $tickets[$id]['link'] = Whups::urlFor('ticket', $info['id'], true, -1);
                $tickets[$id]['date_created'] = strftime('%x', $info['timestamp']);
                $tickets[$id]['owners'] = Whups::getOwners($info['id']);
                $tickets[$id]['owner_name'] = Whups::getOwners($info['id'], false, true);
                $tickets[$id]['owner_email'] = Whups::getOwners($info['id'], true, false);
                if (!empty($info['date_assigned'])) {
                    $tickets[$id]['date_assigned'] = strftime('%x', $info['date_assigned']);
                }
                if (!empty($info['date_resolved'])) {
                    $tickets[$id]['date_resolved'] = strftime('%x', $info['date_resolved']);
                }

                // If the template has a callback function defined for data
                // filtering, call it now.
                if (!empty($_templates[$tpl]['callback'])) {
                    array_walk($tickets[$id], $_templates[$tpl]['callback']);
                }
            }

            Whups::sortTickets($tickets,
                isset($_templates[$tpl]['sortby']) ? $_templates[$tpl]['sortby'] : null,
                isset($_templates[$tpl]['sortdir']) ? $_templates[$tpl]['sortdir'] : null
            );

            $template = $injector->createInstance('Horde_Template');
            $template->set('tickets', $tickets);
            $template->set('now', strftime('%x'));
            $template->set('values', Whups::getSearchResultColumns(null, true));

            return array(
                'data' => $template->parse($_templates[$tpl]['template']),
                'name' => isset($_templates[$tpl]['filename']) ? $_templates[$tpl]['filename'] : 'report.html'
            );
        }
    }

}
