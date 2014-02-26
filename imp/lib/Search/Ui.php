<?php
/**
 * Copyright 2011-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2011-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Common UI code used in the search pages.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2011-2014 Horde LLC
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
        global $injector, $registry;

        $ob = new stdClass;

        $view = new Horde_View(array(
            'templatePath' => IMP_TEMPLATES . '/search'
        ));
        $view->allsearch = IMP_Mailbox::formTo(IMP_Search_Query::ALLSEARCH);

        $ftree = $injector->getInstance('IMP_Ftree');
        $iterator = new IMP_Ftree_IteratorFilter($ftree);
        if ($unsub) {
            $ftree->loadUnsubscribed();
            $iterator->remove($iterator::UNSUB);
        }
        if ($registry->getView() != $registry::VIEW_DYNAMIC) {
            $iterator->add($iterator::REMOTE);
        }

        $ob->tree = $ftree->createTree('imp_search', array(
            'iterator' => $iterator,
            'render_params' => array(
                'abbrev' => 0,
                'container_select' => true,
                'customhtml' => $view->render('search-all'),
                'heading' => _("Add search mailbox:")
            ),
            'render_type' => 'IMP_Tree_Flist'
        ));

        $mbox_list = array();
        foreach ($iterator as $val) {
            $mbox_ob = $val->mbox_ob;
            $mbox_list[$mbox_ob->form_to] = $mbox_ob->display;
        }
        $ob->mbox_list = $mbox_list;

        return $ob;
    }

}
