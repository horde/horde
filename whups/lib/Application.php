<?php
/**
 * Whups application API.
 *
 * This file defines Horde's core API interface. Other core Horde libraries
 * can interact with Whups through this API.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
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
     * The application's version.
     *
     * @var string
     */
    public $version = 'H4 (2.0-git)';

    /**
     * Whups initialization.
     *
     * Global variables defined:
     * - $whups_driver: The global Whups driver object.
     * - $linkTags:     <link> tags for common-header.inc.
     */
    protected function _init()
    {
        // TODO: Remove once they can be autoloaded
        require_once 'Horde/Form.php';
        require_once 'Horde/Form/Renderer.php';

        $GLOBALS['whups_driver'] = Whups_Driver::factory();
        $GLOBALS['whups_driver']->initialise();

        $GLOBALS['linkTags'] = array('<link href="' . Horde::url('opensearch.php', true, -1) . '" rel="search" type="application/opensearchdescription+xml" title="' . $GLOBALS['registry']->get('name') . ' (' . Horde::url('', true) . ')" />');
    }

    /**
     * Returns a list of available permissions.
     *
     * @return array  An array describing all available permissions.
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
     * Code to run on init when viewing prefs for this application.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     */
    public function prefsInit($ui)
    {
        if (!$GLOBALS['registry']->hasMethod('contacts/sources')) {
            $ui->suppressGroups[] = 'addressbooks';
        }
    }

    /**
     * Determine active prefs when displaying a group.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
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
     * Special preferences handling on update.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     * @param string $item             The preference name.
     *
     * @return boolean  True if preference was updated.
     */
    public function prefsSpecialUpdate($ui, $item)
    {
        $updated = false;

        switch ($item) {
        case 'sourceselect':
            $data = Horde_Core_Prefs_Ui_Widgets::addressbooksUpdate($ui);

            if (isset($data['sources'])) {
                $prefs->setValue('search_sources', $data['sources']);
                $updated = true;
            }

            if (isset($data['fields'])) {
                $prefs->setValue('search_fields', $data['fields']);
                $updated = true;
            }
            break;
        }

        return $updated;
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
