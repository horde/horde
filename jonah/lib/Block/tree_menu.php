<?php

$block_name = _("Menu List");
$block_type = 'tree';

/**
 * $Horde: jonah/lib/Block/tree_menu.php,v 1.7 2009/12/03 15:28:22 chuck Exp $
 *
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
        $icondir = $GLOBALS['registry']->getImageDir();
        $news = Jonah_News::factory();
        $channels = $news->getChannels('internal');
        if (is_a($channels, 'PEAR_Error')) {
            return;
        }
        $channels = Jonah::checkPermissions('channels', Horde_Perms::SHOW, $channels);

        foreach ($channels as $channel) {
            $tree->addNode($parent . $channel['channel_id'],
                           $parent,
                           $channel['channel_name'],
                           $indent + 1,
                           false,
                           array('icon' => 'editstory.png',
                                 'icondir' => $icondir,
                                 'url' => Horde_Util::addParameter($url, array('channel_id' => $channel['channel_id']))));
        }
    }

}
