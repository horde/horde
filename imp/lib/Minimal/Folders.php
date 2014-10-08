<?php
/**
 * Folders page for minimal view.
 *
 * Copyright 2012-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Minimal_Folders extends IMP_Minimal_Base
{
    /**
     * URL Parameters:
     *   - ts: (integer) Toggle subscribe view.
     */
    protected function _init()
    {
        global $injector, $notification, $prefs, $session;

        $imp_imap = $injector->getInstance('IMP_Factory_Imap')->create();

        /* Redirect back to the mailbox if folder use is not allowed. */
        if (!$imp_imap->access(IMP_Imap::ACCESS_FOLDERS)) {
            $notification->push(_("The folder view is not enabled."), 'horde.error');
            IMP_Minimal_Mailbox::url()->redirect();
        }

        /* Decide whether or not to show all the unsubscribed mailboxes. */
        $subscribe = $prefs->getValue('subscribe');
        $showAll = (!$subscribe || $session->get('imp', 'showunsub'));

        /* Toggle subscribed view, if necessary. */
        if ($subscribe && $this->vars->ts) {
            $showAll = !$showAll;
            $session->set('imp', 'showunsub', $showAll);
        }

        /* Initialize the IMP_Ftree object. */
        $ftree = $injector->getInstance('IMP_Ftree');
        $iterator = new IMP_Ftree_IteratorFilter($ftree);
        $iterator->add($iterator::REMOTE);
        if ($showAll) {
            $ftree->loadUnsubscribed();
            $iterator->remove($iterator::UNSUB);
        }
        $tree = $ftree->createTree('mimp_folders', array(
            'iterator' => $iterator,
            'poll_info' => true,
            'render_type' => 'IMP_Tree_Simplehtml'
        ));

        $selfurl = self::url();
        $menu = array(array(_("Refresh"), $selfurl));
        if ($subscribe) {
            $menu[] = array(
                ($showAll ? _("Show Subscribed Mailboxes") : _("Show All Mailboxes")),
                $selfurl->copy()->add('ts', 1)
            );
        }

        $this->title = _("Folders");

        $this->view->menu = $this->getMenu('folders', $menu);
        $this->view->title = $this->title;
        $this->view->tree = $tree->getTree(true);

        $this->_pages[] = 'folders';
        $this->_pages[] = 'menu';
    }

    /**
     */
    public static function url(array $opts = array())
    {
        return Horde::url('minimal.php')->add('page', 'folders');
    }

}
