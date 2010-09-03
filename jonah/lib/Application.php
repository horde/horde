<?php
/**
 * Jonah application API.
 *
 * @package Jonah
 */

if (!defined('JONAH_BASE')) {
    define('JONAH_BASE', dirname(__FILE__). '/..');
}

if (!defined('HORDE_BASE')) {
    /* If horde does not live directly under the app directory, the HORDE_BASE
     * constant should be defined in config/horde.local.php. */
    if (file_exists(JONAH_BASE. '/config/horde.local.php')) {
        include JONAH_BASE . '/config/horde.local.php';
    } else {
        define('HORDE_BASE', JONAH_BASE . '/..');
    }
}

/* Load the Horde Framework core (needed to autoload
 * Horde_Registry_Application::). */
require_once HORDE_BASE . '/lib/core.php';

class Jonah_Application extends Horde_Registry_Application
{
    public $version = 'H4 (1.0-git)';

    /**
     * Initialization function.
     *
     * Global variables defined:
     */
    protected function _init()
    {
        $GLOBALS['injector']->addOndemandBinder('Jonah_Driver', 'Jonah_Injector_Binder_Driver');
    }

    /**
     * Returns a list of available permissions.
     *
     * @return array
     */
    public function perms()
    {
        $news = Jonah_News::factory();
        $channels = $news->getChannels(Jonah::INTERNAL_CHANNEL);

        /* Loop through internal channels and add their ids to the
         * perms. */
        $perms = array();
        foreach ($channels as $channel) {
            $perms['tree']['jonah']['news']['internal_channels'][$channel['channel_id']] = false;
        }

        /* Human names and default permissions. */
        $perms['title']['jonah:admin'] = _("Administrator");
        $perms['tree']['jonah']['admin'] = false;
        $perms['title']['jonah:news'] = _("News");
        $perms['tree']['jonah']['news'] = false;
        $perms['title']['jonah:news:internal_channels'] = _("Internal Channels");
        $perms['tree']['jonah']['news']['internal_channels'] = false;
        $perms['title']['jonah:news:external_channels'] = _("External Channels");
        $perms['tree']['jonah']['news']['external_channels'] = false;

        /* Loop through internal channels and add them to the perms
         * titles. */
        foreach ($channels as $channel) {
            $perms['title']['jonah:news:internal_channels:' . $channel['channel_id']] = $channel['channel_name'];
            $perms['tree']['jonah']['news']['internal_channels'][$channel['channel_id']] = false;
        }

        $channels = $news->getChannels(Jonah::EXTERNAL_CHANNEL);

        /* Loop through external channels and add their ids to the
         * perms. */
        foreach ($channels as $channel) {
            $perms['title']['jonah:news:external_channels:' . $channel['channel_id']] = $channel['channel_name'];
            $perms['tree']['jonah']['news']['external_channels'][$channel['channel_id']] = false;
        }

        return $perms;
    }

    /**
     * Generate the menu to use on the prefs page.
     *
     * @return Horde_Menu  A Horde_Menu object.
     */
    public function prefsMenu()
    {
        return Jonah::getMenu();
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
        if (!Jonah::checkPermissions('jonah:news', Horde_Perms::EDIT) ||
            !in_array('internal', $GLOBALS['conf']['news']['enable'])) {
            return;
        }

        $url = Horde::url('stories/');
        $news = Jonah_News::factory();
        $channels = $news->getChannels('internal');
        if ($channels instanceof PEAR_Error) {
            return;
        }
        $channels = Jonah::checkPermissions('channels', Horde_Perms::SHOW, $channels);

        foreach ($channels as $channel) {
            $tree->addNode(
                $parent . $channel['channel_id'],
                $parent,
                $channel['channel_name'],
                1,
                false,
                array(
                    'icon' => Horde_Themes::img('editstory.png'),
                    'url' => $url->add('channel_id', $channel['channel_id'])
                )
            );
        }
    }

}
