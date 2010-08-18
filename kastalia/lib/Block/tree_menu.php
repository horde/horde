<?php
/**
 *
 * This product includes software developed by the Horde Project (http://www.horde.org/).
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Andre Pawlowski aka sqall <sqall@h4des.org>
 * @package Tree_Menu
 */

$block_name = _("Menu List");
$block_type = 'tree';

class Horde_Block_kastalia_tree_menu extends Horde_Block {

    var $_app = 'kastalia';

    function _buildTree(&$tree, $indent = 0, $parent = null)
    {
        require_once dirname(__FILE__) . '/../base.php';

        $tree->addNode(
            $parent . '__upload',
            $parent,
            _("Upload"),
            $indent + 1,
            false,
            array(
                'icon' => strval(Horde_Themes::img('menu/upload.png')),
                'url' => Horde::applicationUrl('upload_menu.php')
            )
        );
    }

}
