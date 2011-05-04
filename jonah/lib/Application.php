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
     * Global variables defined:
     * - $linkTags: <link> tags for common-header.inc.
     */
    protected function _init()
    {
        $GLOBALS['injector']->bindFactory('Jonah_Driver', 'Jonah_Factory_Driver', 'create');
        $GLOBALS['jonah_shares'] = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Share')->create();

        if ($channel_id = Horde_Util::getFormData('channel_id')) {
            $url = Horde::url('delivery/rss.php', true, -1)
                ->add('channel_id', $channel_id);
            if ($tag_id = Horde_Util::getFormData('tag_id')) {
                $url->add('tag_id', $tag_id);
            }
            $GLOBALS['linkTags'] = array('<link rel="alternate" type="application/rss+xml" title="RSS 0.91" href="' . $url . '" />');
        }
    }

    /**
     */
    public function perms()
    {
        $perms = array(
            'admin' => array(
                'title' => _("Administrator")
            ),
            'news' => array(
                'title' => _("News")
            )
        );

        return $perms;
    }

    /**
     */
    public function menu($menu)
    {
        /* If authorized, show admin links. */
        if (Jonah::checkPermissions('jonah:news', Horde_Perms::EDIT)) {
            $menu->addArray(array(
                'icon' => 'jonah.png',
                'text' => _("_Feeds"),
                'url' => Horde::url('channels/index.php')
            ));
        }
        foreach ($GLOBALS['conf']['news']['enable'] as $channel_type) {
            if (Jonah::checkPermissions($channel_type, Horde_Perms::EDIT)) {
                $menu->addArray(array(
                    'icon' => 'new.png',
                    'text' => _("New Feed"),
                    'url' => Horde::url('channels/edit.php')
                ));
                break;
            }
        }
        if ($channel_id = Horde_Util::getFormData('channel_id')) {
            $channel = Jonah::getFeed($channel_id);
            // @todo: The share feeds currently still lack type information.
            /* if ($channel['channel_type'] == Jonah::INTERNAL_CHANNEL && */
            /*     Jonah::checkPermissions(Jonah::typeToPermName($channel['channel_type']), Horde_Perms::EDIT, $channel_id)) { */
            /*     $menu->addArray(array( */
            /*         'icon' => 'new.png', */
            /*         'text' => _("_New Story"), */
            /*         'url' => Horde::url('stories/edit.php')->add('channel_id', (int)$channel_id) */
            /*     )); */
            /* } */
        }
    }

    /* Sidebar method. */

    /**
     */
    public function sidebarCreate(Horde_Tree_Base $tree, $parent = null,
                                  array $params = array())
    {
        if (!Jonah::checkPermissions('jonah:news', Horde_Perms::EDIT) ||
            !in_array('internal', $GLOBALS['conf']['news']['enable'])) {
            return;
        }

        $url = Horde::url('stories/');

        try {
            $channels = Jonah::listFeeds();
        } catch (Jonah_Exception $e) {
            var_dump($e);
            return;
        }

        $story_img = Horde_Themes::img('editstory.png');

        foreach ($channels as $channel) {
            $tree->addNode(
                $parent . $channel->getName(),
                $parent,
                $channel->get('name'),
                1,
                false,
                array(
                    'icon' => $story_img,
                    'url' => $url->add('channel_id', $channel->getName())
                )
            );
        }
    }

}
