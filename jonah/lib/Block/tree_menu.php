<?php

$block_name = _("Menu List");
$block_type = 'tree';

/**
 * @package Horde_Block
 */
class Horde_Block_jonah_tree_menu extends Horde_Block {

    var $_app = 'jonah';

    function _buildTree(&$tree, $indent = 0, $parent = null)
    {
        if (!Jonah::checkPermissions('jonah:news', Horde_Perms::EDIT) ||
            !in_array('internal', $GLOBALS['conf']['news']['enable'])) {
            return;
        }

        $url = Horde::applicationUrl('stories/');
        $news = Jonah_News::factory();
        $channels = $news->getChannels('internal');
        if (is_a($channels, 'PEAR_Error')) {
            return;
        }
        $channels = Jonah::checkPermissions('channels', Horde_Perms::SHOW, $channels);

        foreach ($channels as $channel) {
            $tree->addNode(
                $parent . $channel['channel_id'],
                $parent,
                $channel['channel_name'],
                $indent + 1,
                false,
                array(
                    'icon' => Horde_Themes::img('editstory.png'),
                    'url' => $url->add('channel_id', $channel['channel_id'])
                )
            );
        }
    }

}
