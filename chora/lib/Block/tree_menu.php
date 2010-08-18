<?php

$block_name = _("Menu List");
$block_type = 'tree';

class Horde_Block_chora_tree_menu extends Horde_Block
{
    var $_app = 'chora';

    function _buildTree(&$tree, $indent = 0, $parent = null)
    {
        define('CHORA_ERROR_HANDLER', true);

        $arr = array();
        asort($GLOBALS['sourceroots']);

        foreach ($GLOBALS['sourceroots'] as $key => $val) {
            if (Chora::checkPerms($key)) {
                $tree->addNode($parent . $key,
                    $parent,
                    $val['name'],
                    $indent + 1,
                    false,
                    array(
                        'icon' => Horde_Themes::img('tree/folder.png'),
                        'url' => Chora::url('browsedir', '', array('rt' => $key))
                    )
                );
            }
        }
    }

}
