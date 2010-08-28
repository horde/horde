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

    protected function _buildTree($tree, $indent = 0, $parent = '')
    {
        global $injector, $prefs, $registry;

        /* Run filters now */
        if ($prefs->getValue('filter_on_display')) {
            $injector->getInstance('IMP_Filter')->filter('INBOX');
        }

        $tree->addNode(
            $parent . 'compose',
            $parent,
            _("New Message"),
            $indent,
            false,
            array(
                'icon' => Horde_Themes::img('compose.png'),
                'url' => IMP::composeLink()
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
                'icon' => Horde_Themes::img('search.png'),
                'url' => Horde::applicationUrl('search.php')
            )
        );

        if ($_SESSION['imp']['protocol'] == 'pop') {
            return;
        }

        $name_url = Horde::applicationUrl('mailbox.php');

        /* Initialize the IMP_Tree object. */
        $imaptree = $injector->getInstance('IMP_Imap_Tree');
        $imaptree->setIteratorFilter(IMP_Imap_Tree::FLIST_VFOLDER);
        $imaptree->createTree($tree, array(
            'indent' => $indent,
            'parent' => $parent,
            'poll_info' => true
        ));

        /* We want to rewrite the parent node of the INBOX to include new mail
         * notification. */
        if (!($url = $registry->get('url', $parent))) {
            $url = (($registry->get('status', $parent) == 'heading') || !$registry->get('webroot'))
                ? null
                : $registry->getInitialPage($parent);
        }

        $node_params = array(
            'icon' => $registry->get('icon', $parent),
            'url' => $url
        );
        $name = $registry->get('name', $parent);

        if ($imaptree->unseen) {
            $node_params['icon'] = Horde_Themes::img('newmail.png');
            $name = sprintf('<strong>%s</strong> (%s)', $name, $imaptree->unseen);
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
