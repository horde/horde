<?php
/**
 * Copyright 2012-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2012-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Defines AJAX actions used in the IMP advanced search page.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2012-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Ajax_Application_Handler_Search
extends Horde_Core_Ajax_Application_Handler
{
    /**
     * AJAX action: Create mailbox select list for advanced search page.
     *
     * Variables used:
     *   - unsub: (integer) If set, includes unsubscribed mailboxes.
     *
     * @return object  An object with the following entries:
     *   - mbox_list: (array)
     *   - tree: (string)
     */
    public function searchMailboxList()
    {
        $ob = $this->_getSearchMboxList($this->vars->unsub);

        $result = new stdClass;
        $result->mbox_list = $ob->mbox_list;
        $result->tree = $ob->tree->getTree();

        return $result;
    }

    /**
     * Create list of mailboxes used on advanced search page.
     *
     * @param boolean $unsub  Include unsubcribed mailboxes?
     *
     * @return object  Object with the following properties:
     * <pre>
     *   - mbox_list: (array) Mapping of mailbox name (key) to display
     *                string (values).
     *   - tree: (IMP_Tree_Flist) Tree object.
     * </pre>
     */
    protected function _getSearchMboxList($unsub = false)
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
                'heading' => _("Add search mailbox") . '...'
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
