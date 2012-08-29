<?php
/**
 * This class is designed to provide a place to store common code for the
 * advanced search page.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Ui_Search
{
    /**
     * Create SELECT list of mailboxes for advanced search page.
     *
     * @param boolean $unsub  Include unsubcribed mailboxes?
     *
     * @return object  Object with the following properties:
     *   - mbox_list: (array) Mapping of mailbox name (key) to display
     *                string (values).
     *   - tree: (IMP_Tree_Flist) Tree object.
     */
    public function getSearchMboxList($unsub = false)
    {
        global $injector;

        $ob = new stdClass;

        $imap_tree = $injector->getInstance('IMP_Imap_Tree');
        $imap_tree->setIteratorFilter($unsub ? IMP_Imap_Tree::FLIST_UNSUB : 0);

        $t2 = $injector->createInstance('Horde_Template');
        $t2->setOption('gettext', true);
        $t2->set('allsearch', IMP_Mailbox::formTo(IMP_Search_Query::ALLSEARCH));

        $ob->tree = $imap_tree->createTree('imp_search', array(
            'render_params' => array(
                'abbrev' => 0,
                'container_select' => true,
                'customhtml' => $t2->fetch(IMP_TEMPLATES . '/imp/search/search-all.html'),
                'heading' => _("Add search mailbox:")
            ),
            'render_type' => 'IMP_Tree_Flist'
        ));

        $mbox_list = array();
        foreach ($imap_tree as $val) {
            $mbox_list[$val->form_to] = $val->display;
        }
        $ob->mbox_list = $mbox_list;

        return $ob;
    }

}
