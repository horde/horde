<?php
/**
 * Copyright 2011-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2011-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Common UI code used in the search pages.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2011-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Search_Ui
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

        $imptree = $injector->getInstance('IMP_Imap_Tree');
        $filter = IMP_Imap_Tree_IteratorFilter::create(
            $unsub ? IMP_Imap_Tree_IteratorFilter::UNSUB : 0
        );

        $view = new Horde_View(array(
            'templatePath' => IMP_TEMPLATES . '/search'
        ));
        $view->allsearch = IMP_Mailbox::formTo(IMP_Search_Query::ALLSEARCH);

        $ob->tree = $imptree->createTree('imp_search', array(
            'iterator' => $filter,
            'render_params' => array(
                'abbrev' => 0,
                'container_select' => true,
                'customhtml' => $view->render('search-all'),
                'heading' => _("Add search mailbox:")
            ),
            'render_type' => 'IMP_Tree_Flist'
        ));

        $mbox_list = array();
        foreach ($filter as $val) {
            $mbox_ob = $val->mbox_ob;
            $mbox_list[$mbox_ob->form_to] = $mbox_ob->display;
        }
        $ob->mbox_list = $mbox_list;

        return $ob;
    }

}
