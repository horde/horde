<?php
/**
 * Jonah application API.
 *
 * @package Jonah
 */

if (!defined('JONAH_BASE')) {
    define('JONAH_BASE', __DIR__. '/..');
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
    public $version = 'H5 (1.0-git)';

    /**
     */
    protected function _bootstrap()
    {
        $GLOBALS['injector']->bindFactory('Jonah_Driver', 'Jonah_Factory_Driver', 'create');
    }

    /**
     */
    protected function _init()
    {
        if ($channel_id = Horde_Util::getFormData('channel_id')) {
            $url = Horde::url('delivery/rss.php', true, -1)
                ->add('channel_id', $channel_id);
            if ($tag_id = Horde_Util::getFormData('tag_id')) {
                $url->add('tag_id', $tag_id);
            }

            $GLOBALS['page_output']->addLinkTag(array(
                'href' => $url,
                'title' => 'RSS 0.91'
            ));
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
            ),
            'news:channels' => array(
                'title' => _("Channels")
            )
        );

        /* Loop through internal channels and add them to the perms
         * titles. */
        $channels = $GLOBALS['injector']->getInstance('Jonah_Driver')->getChannels();

        foreach ($channels as $channel) {
            $perms['news:channels:' . $channel['channel_id']] = array(
                'title' => $channel['channel_name']
            );
        }

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
            $menu->addArray(array(
                'icon' => 'new.png',
                'text' => _("New Feed"),
                'url' => Horde::url('channels/edit.php')
            ));
        }

        /* If viewing a channel, show new story links if authorized */
        if ($channel_id = Horde_Util::getFormData('channel_id')) {
            $news = $GLOBALS['injector']->getInstance('Jonah_Driver');
            $channel = $news->getChannel($channel_id);
            if (Jonah::checkPermissions('channels', Horde_Perms::EDIT, array($channel_id))) {
                $menu->addArray(array(
                    'icon' => 'new.png',
                    'text' => _("_New Story"),
                    'url' => Horde::url('stories/edit.php')->add('channel_id', (int)$channel_id)
                ));
            }
        }
    }

    /* Topbar method. */

    /**
     */
    public function topbarCreate(Horde_Tree_Renderer_Base $tree, $parent = null,
                                 array $params = array())
    {
        if (!Jonah::checkPermissions('jonah:news', Horde_Perms::EDIT)) {
            return;
        }

        $url = Horde::url('stories/');
        $driver = $GLOBALS['injector']->getInstance('Jonah_Driver');

        try {
            $channels = $driver->getChannels('internal');
        } catch (Jonah_Exception $e) {
            return;
        }

        $channels = Jonah::checkPermissions('channels', Horde_Perms::SHOW, $channels);
        $story_img = Horde_Themes::img('editstory.png');

        foreach ($channels as $channel) {
            $tree->addNode(array(
                'id' => $parent . $channel['channel_id'],
                'parent' => $parent,
                'label' => $channel['channel_name'],
                'expanded' => false,
                'params' => array(
                    'icon' => $story_img,
                    'url' => $url->add('channel_id', $channel['channel_id'])
                )
            ));
        }
    }

}
