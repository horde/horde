<?php
/**
 * Block: menu folder list.
 *
 * Copyright 2005-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */

$block_name = _("Menu Folder List");
$block_type = 'tree';

class Horde_Block_imp_tree_folders extends Horde_Block
{
    protected $_app = 'imp';

    protected function _buildTree($tree, $indent = 0, $parent = null)
    {
        global $injector, $prefs, $registry;

        /* Run filters now */
        if ($prefs->getValue('filter_on_display')) {
            $injector->getInstance('IMP_Filter')->filter('INBOX');
        }

        /* Cache some additional values. */
        $image_dir = strval(Horde_Themes::img());

        $tree->addNode(
            $parent . 'compose',
            $parent,
            _("New Message"),
            $indent,
            false,
            array(
                'icon' => 'compose.png',
                'icondir' => $image_dir,
                'url' => strval(IMP::composeLink()),
                'target' => $prefs->getValue('compose_popup') ? 'horde_menu' : 'horde_main'
            )
        );

        /* Add link to the search page. */
        $tree->addNode(
            $parent . 'search',
            $parent,
            _("Search"),
            $indent,
            false,
            array(
                'icon' => 'search.png',
                'icondir' => $image_dir,
                'url' => Horde::applicationUrl('search.php')
            )
        );

        if ($_SESSION['imp']['protocol'] == 'pop') {
            return;
        }

        $name_url = Horde::applicationUrl('mailbox.php')->add('no_newmail_popup', 1);

        /* Initialize the IMP_Tree object. */
        $imaptree = $injector->getInstance('IMP_Imap_Tree');
        $mask = IMP_Imap_Tree::FLIST_CONTAINER |
            IMP_Imap_Tree::FLIST_VFOLDER |
            IMP_Imap_Tree::FLIST_ELT;

        $unseen = 0;
        $inbox = null;

        foreach ($imaptree->folderList($mask) as $val) {
            $label = $val->name;

            if ($val->polled) {
                $poll_info = $val->poll_info;
                if (!empty($poll_info->unseen)) {
                    $unseen += $poll_info->unseen;
                    $label = '<span dir="ltr"><strong>' . $label . '</strong> (' . $poll_info->unseen . '/' . $poll_info->msgs . ')</span>';
                }
            }

            /* If this is the INBOX, save it to later rewrite our parent node
             * to include new mail notification. */
            if ($val->value == 'INBOX') {
                $inbox = $val;
            }

            $icon = $val->icon;
            $tree->addNode(
                $parent . $val->value,
                ($val->level) ? $parent . $val->parent : $parent,
                $label,
                $indent + $val->level,
                $val->is_open,
                array(
                    'icon' => $icon->icon,
                    'icondir' => strval($icon->icondir),
                    'iconopen' => $icon->iconopen,
                    'url' => ($val->container) ? null : $name_url->add('mailbox', $val->value),
                )
            );
        }

        /* We want to rewrite the parent node of the INBOX to include new mail
         * notification. */
        if ($inbox) {
            $url = $registry->get('url', $parent);
            if (empty($url)) {
                $url = (($registry->get('status', $parent) == 'heading') || !$registry->get('webroot'))
                    ? null
                    : $registry->getInitialPage($parent);
            }

            $node_params = array(
                'icon' => strval($registry->get('icon', $parent)),
                'icondir' => '',
                'url' => $url
            );
            $name = $registry->get('name', $parent);

            if ($unseen) {
                $node_params['icon'] = 'newmail.png';
                $node_params['icondir'] = $image_dir;
                $name = sprintf('<strong>%s</strong> (%s)', $name, $unseen);
            }

            $tree->addNode(
                $parent,
                $registry->get('menu_parent', $parent),
                $name,
                $indent - 1,
                $imaptree->isOpen($parent),
                $node_params
            );
        }
    }

}
