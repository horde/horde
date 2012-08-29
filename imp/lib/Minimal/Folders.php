<?php
/**
 * Folders page for minimal view.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl21 GPL
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

        /* Initialize the IMP_Imap_Tree object. */
        $imptree = $injector->getInstance('IMP_Imap_Tree');
        $mask = 0;

        /* Toggle subscribed view, if necessary. */
        if ($subscribe && $this->vars->ts) {
            $showAll = !$showAll;
            $session->set('imp', 'showunsub', $showAll);
            $imptree->showUnsubscribed($showAll);
            if ($showAll) {
                $mask |= IMP_Imap_Tree::FLIST_UNSUB;
            }
        }

        $imptree->setIteratorFilter($mask);
        $tree = $imptree->createTree('mimp_folders', array(
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
    static public function url(array $opts = array())
    {
        return Horde::url('minimal.php')->add('page', 'folders');
    }

}
