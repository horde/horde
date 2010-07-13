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

    protected function _buildTree(&$tree, $indent = 0, $parent = null)
    {
        /* Run filters now */
        if ($GLOBALS['prefs']->getValue('filter_on_display')) {
            $imp_filter = new IMP_Filter();
            $imp_filter->filter('INBOX');
        }

        /* Cache some additional values. */
        $image_dir = (string)Horde_Themes::img();

        $tree->addNode($parent . 'compose', $parent, _("New Message"),
                       $indent, false,
                       array('icon' => 'compose.png',
                             'icondir' => $image_dir,
                             'url' => strval(IMP::composeLink()),
                             'target' => $GLOBALS['prefs']->getValue('compose_popup') ? 'horde_menu' : 'horde_main'));

        /* Add link to the search page. */
        $tree->addNode($parent . 'search', $parent, _("Search"),
                       $indent, false,
                       array('icon' => 'search.png',
                             'icondir' => $image_dir,
                             'url' => Horde::applicationUrl('search.php')));

        if ($_SESSION['imp']['protocol'] == 'pop') {
            return;
        }

        $name_url = Horde::applicationUrl('mailbox.php')->add('no_newmail_popup', 1);

        /* Initialize the IMP_Tree object. */
        $imaptree = $GLOBALS['injector']->getInstance('IMP_Imap_Tree');
        $mask = IMP_Imap_Tree::NEXT_SHOWCLOSED;
        if ($GLOBALS['prefs']->getValue('subscribe')) {
            $mask |= IMP_Imap_Tree::NEXT_SHOWSUB;
        }

        $unseen = 0;
        $inbox = null;
        $tree_ob = $imaptree->build($mask, null, null, false);

        foreach ($tree_ob[0] as $val) {
            $label = $val['name'];
            if (!empty($val['unseen'])) {
                $unseen += $val['unseen'];
                $label = '<span dir="ltr"><strong>' . $label . '</strong> (' . $val['unseen'] . '/' . $val['msgs'] . ')</span>';
            }

            /* If this is the INBOX, save it to later rewrite our parent node
             * to include new mail notification. */
            if ($val['value'] == 'INBOX') {
                $inbox = $val;
            }

            $node_params = array(
                'icon' => $val['icon'],
                'icondir' => (string)$val['icondir'],
                'iconopen' => $val['iconopen'],
                'url' => ($val['container']) ? null : $name_url->add('mailbox', $val['value']),
            );
            $tree->addNode($parent . $val['value'],
                           ($val['level']) ? $parent . $val['parent'] : $parent,
                           $label, $indent + $val['level'], $imaptree->isOpenSidebar($val['value']), $node_params);
        }

        /* We want to rewrite the parent node of the INBOX to include new mail
         * notification. */
        if ($inbox) {
            $url = $GLOBALS['registry']->get('url', $parent);
            if (empty($url)) {
                if (($GLOBALS['registry']->get('status', $parent) == 'heading') ||
                    !$GLOBALS['registry']->get('webroot')) {
                    $url = null;
                } else {
                    $url = Horde::url($GLOBALS['registry']->getInitialPage($parent));
                }
            }

            $node_params = array('url' => $url,
                                 'icon' => (string)$GLOBALS['registry']->get('icon', $parent),
                                 'icondir' => '');
            $menu_parent = $GLOBALS['registry']->get('menu_parent', $parent);
            $name = $GLOBALS['registry']->get('name', $parent);
            if ($unseen) {
                $node_params['icon'] = 'newmail.png';
                $node_params['icondir'] = $image_dir;
                $name = sprintf('<strong>%s</strong> (%s)', $name, $unseen);
            }
            $tree->addNode($parent, $menu_parent, $name, $indent - 1, $imaptree->isOpenSidebar($parent), $node_params);
        }
    }

}
