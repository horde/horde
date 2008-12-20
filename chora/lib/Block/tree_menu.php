<?php

$block_name = _("Menu List");
$block_type = 'tree';

class Horde_Block_chora_tree_menu extends Horde_Block {

    var $_app = 'chora';

    function _buildTree(&$tree, $indent = 0, $parent = null)
    {
        global $perms, $sourceroots;

        define('CHORA_ERROR_HANDLER', true);
        require_once dirname(__FILE__) . '/../base.php';

        $arr = array();
        asort($sourceroots);
        foreach ($sourceroots as $key => $val) {
            if ((!$perms->exists('chora:sourceroots:' . $key) ||
                 $perms->hasPermission('chora:sourceroots:' . $key,
                                       Auth::getAuth(),
                                       PERMS_READ | PERMS_SHOW))) {
                $tree->addNode($parent . $key,
                               $parent,
                               $val['name'],
                               $indent + 1,
                               false,
                               array('icon' => 'folder.png',
                                     'icondir' => $registry->getImageDir('horde') . '/tree',
                                     'url' => Chora::url('', '', array('rt' => $key))));
            }
        }

    }

}
