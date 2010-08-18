<?php

$block_name = _("Menu List");
$block_type = 'tree';

/**
 * Gollem tree block.
 *
 * Copyright 2005-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Gollem
 */
class Horde_Block_gollem_tree_menu extends Horde_Block
{
    protected $_app = 'gollem';

    protected function _buildTree(&$tree, $indent = 0, $parent = null)
    {
        // TODO
        return;

        $login_url = Horde::applicationUrl('login.php');

        foreach ($GLOBALS['gollem_backends'] as $key => $val) {
            if (Gollem::checkPermissions('backend', Horde_Perms::SHOW, $key)) {
                $tree->addNode(
                    $parent . $key,
                    $parent,
                    $val['name'],
                    $indent + 1,
                    false,
                    array(
                        'icon' => Horde_Themes::img('gollem.png'),
                        'url' => $login_url->copy()->add(array('backend_key' => $key, 'change_backend' => 1))
                    )
                );
            }
        }
    }

}
