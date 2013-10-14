<?php
/**
 * Copyright 2000-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2000-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Folder tree display for basic view.
 *
 * @author    Anil Madhavapeddy <avsm@horde.org>
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2000-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Basic_Folders extends IMP_Basic_Base
{
    /**
     */
    protected function _init()
    {
        global $injector, $notification, $page_output, $prefs, $registry, $session;

        /* Redirect back to the mailbox if folder use is not allowed. */
        $imp_imap = $injector->getInstance('IMP_Factory_Imap')->create();
        if (!$imp_imap->access(IMP_Imap::ACCESS_FOLDERS)) {
            $notification->push(_("The folder view is not enabled."), 'horde.error');
            Horde::url('mailbox', true)->redirect();
        }

        /* Decide whether or not to show all the unsubscribed mailboxes. */
        $subscribe = $prefs->getValue('subscribe');
        $showAll = (!$subscribe || $session->get('imp', 'showunsub'));

        $page_output->addScriptFile('hordecore.js', 'horde');
        $page_output->addScriptFile('folders.js');

        /* Get the base URL for this page. */
        $folders_url = self::url();

        /* These JS defines are required by all sub-pages. */
        $page_output->addInlineJsVars(array(
            'ImpFolders.folders_url' => strval($folders_url),
            'ImpFolders.text' => array(
                'download1' => _("All messages in the following mailbox(es) will be downloaded into one MBOX file:"),
                'download2' => _("This may take some time. Are you sure you want to continue?"),
                'oneselect' => _("Only one mailbox should be selected for this action."),
                'rename1' => _("You are renaming the mailbox:"),
                'rename2' => _("Please enter the new name:"),
                'select' => _("Please select a mailbox before you perform this action."),
                'subfolder1' => _("You are creating a subfolder to"),
                'subfolder2' => _("Please enter the name of the new mailbox:"),
                'toplevel' => _("You are creating a top-level mailbox.") . "\n" . _("Please enter the name of the new mailbox:")
            )
        ));

        /* Initialize the IMP_Ftree object. */
        $ftree = $injector->getInstance('IMP_Ftree');

        /* $mbox_list entries are urlencoded. */
        $mbox_list = isset($this->vars->mbox_list)
            ? IMP_Mailbox::formFrom($this->vars->mbox_list)
            : array();

        /* Token to use in requests */
        $token = $injector->getInstance('Horde_Token');
        $folders_token = $token->get('imp.folders');

        /* META refresh time (might be altered by actionID). */
        $refresh_time = $prefs->getValue('refresh_time');

        /* Set up the master View object. */
        $view = new Horde_View(array(
            'templatePath' => IMP_TEMPLATES . '/basic/folders'
        ));
        $view->addHelper('FormTag');
        $view->addHelper('Tag');

        $view->folders_token = $folders_token;

        /* Run through the action handlers. */
        if ($this->vars->actionID) {
            try {
                $token->validate($this->vars->folders_token, 'imp.folders');
            } catch (Horde_Token_Exception $e) {
                $notification->push($e);
                $this->vars->actionID = null;
            }
        }

        switch ($this->vars->actionID) {
        case 'expand_all_folders':
            $ftree->expandAll();
            break;

        case 'collapse_all_folders':
            $ftree->collapseAll();
            break;

        case 'rebuild_tree':
            $ftree->init();
            break;

        case 'expunge_mbox':
            if (!empty($mbox_list)) {
                $injector->getInstance('IMP_Message')->expungeMailbox(array_fill_keys($mbox_list, null));
            }
            break;

        case 'delete_mbox':
            foreach ($mbox_list as $val) {
                $val->delete();
            }
            break;

        case 'download_mbox':
        case 'download_mbox_zip':
            $registry->downloadUrl('mbox', array(
                'actionID' => 'download_mbox',
                'mbox_list' => $this->vars->mbox_list,
                'type' => ($this->vars->actionID == 'download_mbox') ? 'mbox' : 'mboxzip'
            ))->redirect();
            exit;

        case 'import_mbox':
            if ($this->vars->import_mbox) {
                try {
                    $notification->push($injector->getInstance('IMP_Mbox_Import')->import($this->vars->import_mbox, 'mbox_upload'), 'horde.success');
                } catch (Horde_Exception $e) {
                    $notification->push($e);
                }
                $this->vars->actionID = null;
            } else {
                $refresh_time = null;
            }
            break;

        case 'create_mbox':
            if (isset($this->vars->new_mailbox)) {
                try {
                    $parent = empty($mbox_list)
                        ? IMP_Mailbox::get(IMP_Ftree::BASE_ELT)
                        : $mbox_list[0];
                    $new_mbox = $parent->createMailboxName($this->vars->new_mailbox);
                    if ($new_mbox->exists) {
                        $notification->push(sprintf(_("Mailbox \"%s\" already exists."), $new_mbox->display), 'horde.warning');
                    } else {
                        $new_mbox->create();
                    }
                } catch (Horde_Exception $e) {
                    $notification->push($e);
                }
            }
            break;

        case 'rename_mbox':
            // $old_names may be URL encoded.
            $old_names = array_map('trim', explode("\n", $this->vars->old_names));
            $new_names = array_map('trim', explode("\n", $this->vars->new_names));

            $iMax = count($new_names);
            if (!empty($new_names) &&
                !empty($old_names) &&
                ($iMax == count($old_names))) {
                for ($i = 0; $i < $iMax; ++$i) {
                    $old_name = IMP_Mailbox::formFrom($old_names[$i]);
                    $old_ns = $old_name->namespace_info;
                    $new = trim($new_names[$i], $old_ns['delimiter']);

                    /* If this is a personal namespace, then anything goes as
                     * far as the input. Just append the personal namespace to
                     * it. */
                    if (($old_ns['type'] == Horde_Imap_Client::NS_PERSONAL) ||
                        ($old_ns['name'] &&
                         (stripos($new_names[$i], $old_ns['name']) !== 0))) {
                        $new = $old_ns['name'] . $new;
                    }

                    $old_name->rename($new);
                }
            }
            break;

        case 'subscribe_mbox':
        case 'unsubscribe_mbox':
            if (empty($mbox_list)) {
                $notification->push(_("No mailboxes were specified"), 'horde.message');
            } else {
                foreach ($mbox_list as $val) {
                    $val->subscribe($this->vars->actionID == 'subscribe_mbox');
                }
            }
            break;

        case 'toggle_subscribed_view':
            if ($subscribe) {
                $showAll = !$showAll;
                $session->set('imp', 'showunsub', $showAll);
            }
            break;

        case 'poll_mbox':
            if (!empty($mbox_list)) {
                $ftree->poll->addPollList($mbox_list);
            }
            break;

        case 'nopoll_mbox':
            if (!empty($mbox_list)) {
                $ftree->poll->removePollList($mbox_list);
            }
            break;

        case 'empty_mbox':
            if (!empty($mbox_list)) {
                $injector->getInstance('IMP_Message')->emptyMailbox($mbox_list);
            }
            break;

        case 'mark_mbox_seen':
        case 'mark_mbox_unseen':
            if (!empty($mbox_list)) {
                $injector->getInstance('IMP_Message')->flagAllInMailbox(array('\\seen'), $mbox_list, ($this->vars->actionID == 'mark_mbox_seen'));
            }
            break;

        case 'delete_mbox_confirm':
        case 'empty_mbox_confirm':
            if (!empty($mbox_list)) {
                $loop = array();
                foreach ($mbox_list as $val) {
                    switch ($this->vars->actionID) {
                    case 'delete_mbox_confirm':
                        if (!$val->access_deletembox) {
                            $notification->push(sprintf(_("The mailbox \"%s\" may not be deleted."), $val->display), 'horde.error');
                            continue 2;
                        }
                        break;

                    case 'empty_mbox_confirm':
                        if (!$val->access_empty) {
                            $notification->push(sprintf(_("The mailbox \"%s\" may not be emptied."), $val->display), 'horde.error');
                            continue 2;
                        }
                        break;
                    }

                    try {
                        $elt_info = $imp_imap->status($val, Horde_Imap_Client::STATUS_MESSAGES);
                    } catch (IMP_Imap_Exception $e) {
                        $elt_info = null;
                    }

                    $data = array(
                        'name' => $val->display,
                        'msgs' => $elt_info ? $elt_info['messages'] : 0,
                        'val' => $val->form_to
                    );
                    $loop[] = $data;
                }

                if (!count($loop)) {
                    break;
                }

                $page_output->addScriptFile('stripe.js', 'horde');

                $this->title = _("Folder Actions - Confirmation");

                $v = clone $view;

                if ($this->vars->actionID == 'delete_mbox_confirm') {
                    $v->actionID = 'delete_mbox';
                    $v->delete = true;
                } elseif ($this->vars->actionID == 'empty_mbox_confirm') {
                    $v->actionID = 'empty_mbox';
                    $v->empty = true;
                }
                $v->mboxes = $loop;
                $v->folders_url = $folders_url;

                $this->output = $v->render('folders_confirm');
                return;
            }
            break;

        case 'mbox_size':
            if (!empty($mbox_list)) {
                $loop = array();
                $sum = 0;

                foreach ($mbox_list as $val) {
                    $size = $this->_sizeMailbox($val, false);
                    $data = array(
                        'name' => $val->display,
                        'size' => sprintf(_("%.2fMB"), $size / (1024 * 1024)),
                        'sort' => $size
                    );
                    $sum += $size;
                    $loop[] = $data;
                }

                /* Prepare the topbar. */
                $injector->getInstance('Horde_View_Topbar')->subinfo =
                    $injector->getInstance('IMP_View_Subinfo')->render();

                $v = clone $view;

                $v->folders_url = $folders_url;
                $v->mboxes = $loop;
                $v->mboxes_sum = sprintf(_("%.2fMB"), $sum / (1024 * 1024));

                $page_output->addScriptFile('stripe.js', 'horde');
                $page_output->addScriptFile('tables.js', 'horde');

                $this->title = _("Mailbox Sizes");
                $this->output = $v->render('folders_size');
                return;
            }
            break;

        case 'search':
            if (!empty($mbox_list)) {
                IMP_Basic_Search::url()->add(array(
                    'mailbox_list' => IMP_Mailbox::formTo($mbox_list),
                    'subfolder' => 1
                ))->redirect();
            }
            break;
        }

        $this->title = _("Folder Navigator");

        $folders_url->add('folders_token', $folders_token);

        /* Prepare the topbar. */
        $injector->getInstance('Horde_View_Topbar')->subinfo =
            $injector->getInstance('IMP_View_Subinfo')->render();

        if ($session->get('imp', 'file_upload') &&
            ($this->vars->actionID == 'import_mbox')) {
            /* Prepare import template. */
            $v = clone $view;

            $v->folders_url = $folders_url;
            $v->import_mbox = $mbox_list[0];

            $this->output = $v->render('import');
            return;
        }

        /* Prepare the header template. */
        $head_view = clone $view;
        $head_view->folders_url = $folders_url;

        /* Prepare the actions template. */
        $actions = clone $view;
        $actions->addHelper('Horde_Core_View_Helper_Accesskey');
        $actions->addHelper('Horde_Core_View_Helper_Help');

        $actions->id = 0;

        $actions->refresh = Horde::widget(array(
            'title' => _("_Refresh"),
            'url' => $folders_url->copy()
        ));
        $actions->create_mbox = ($imp_imap->access(IMP_Imap::ACCESS_CREATEMBOX) && $imp_imap->access(IMP_Imap::ACCESS_CREATEMBOX_MAX));
        if ($prefs->getValue('subscribe')) {
            $actions->subscribe = true;
            $subToggleText = $showAll
                ? _("Hide Unsubscribed")
                : _("Show All");
            $actions->toggle_subscribe = Horde::widget(array(
                'url' => $folders_url->copy()->add(array(
                    'actionID' => 'toggle_subscribed_view',
                    'folders_token' => $folders_token
                )),
                'title' => $subToggleText,
                'nocheck' => true
            ));
        }
        $actions->nav_poll = (!$prefs->isLocked('nav_poll') && !$prefs->getValue('nav_poll_all'));
        $actions->notrash = !$prefs->getValue('use_trash');
        $actions->file_upload = $session->get('imp', 'file_upload');
        $actions->expand_all = Horde::widget(array(
            'url' => $folders_url->copy()->add(array(
                'actionID' => 'expand_all_folders',
                'folders_token' => $folders_token
            )),
            'title' => _("Expand All"),
            'nocheck' => true
        ));
        $actions->collapse_all = Horde::widget(array(
            'url' => $folders_url->copy()->add(array(
                'actionID' => 'collapse_all_folders',
                'folders_token' => $folders_token
            )),
            'title' => _("Collapse All"),
            'nocheck' => true
        ));

        /* Build the folder tree. */
        $mask = IMP_Ftree_IteratorFilter::NO_REMOTE |
                IMP_Ftree_IteratorFilter::NO_VFOLDER;
        if ($showAll) {
            $mask |= IMP_Ftree_IteratorFilter::UNSUB;
        }
        $tree = $ftree->createTree('imp_folders', array(
            'checkbox' => true,
            'editvfolder' => true,
            'iterator' => IMP_Ftree_IteratorFilter::create($mask),
            'poll_info' => true
        ));

        $displayNames = $fullNames = array();

        foreach ($ftree as $val) {
            $mbox_ob = $val->mbox_ob;
            $tmp = $displayNames[] = $mbox_ob->display;

            $tmp2 = $mbox_ob->display_notranslate;
            if ($tmp != $tmp2) {
                $fullNames[strval($val)] = $tmp2;
            }
        }

        $page_output->addInlineJsVars(array(
            'ImpFolders.ajax' => $registry->getServiceLink('ajax', 'imp')->url,
            'ImpFolders.displayNames' => $displayNames,
            'ImpFolders.fullNames' => $fullNames,
            '-ImpFolders.mbox_expand' => intval($prefs->getValue('nav_expanded') == 2)
        ));

        $page_output->metaRefresh($refresh_time, $this->url());

        Horde::startBuffer();
        $tree->renderTree();
        $this->output = $head_view->render('head') .
            $actions->render('actions') .
            Horde::endBuffer();

        if (count($tree) > 10) {
            $actions->id = 1;
            $this->output .= $actions->render('actions');
        }

        /* No need for extra template - close out the tags here. */
        $this->output .= '</form>';
    }

    /**
     */
    static public function url(array $opts = array())
    {
        return Horde::url('basic.php')->add('page', 'folders');
    }

    /**
     * Obtains the size of a mailbox.
     *
     * @param IMP_Mailbox $mbox   The mailbox to obtain the size of.
     * @param boolean $formatted  Whether to return a human readable value.
     *
     * @return mixed  Either the size of the mailbox (in bytes) or a formatted
     *                string with this information.
     */
    protected function _sizeMailbox(IMP_Mailbox $mbox, $formatted = true)
    {
        $query = new Horde_Imap_Client_Fetch_Query();
        $query->size();

        try {
            $imp_imap = $mbox->imp_imap;
            $res = $imp_imap->fetch($mbox, $query, array(
                'ids' => $imp_imap->getIdsOb(Horde_Imap_Client_Ids::ALL, true)
            ));

            $size = 0;
            foreach ($res as $v) {
                $size += $v->getSize();
            }
            return ($formatted)
                ? sprintf(_("%.2fMB"), $size / (1024 * 1024))
                : $size;
        } catch (IMP_Imap_Exception $e) {
            return 0;
        }
    }

}
