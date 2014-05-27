<?php
/**
 * Copyright 1999-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 1999-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Basic view mailbox page.
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 1999-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Basic_Mailbox extends IMP_Basic_Base
{
    const FLAG_FILTER_PREFIX = "flag\0";

    /**
     */
    protected function _init()
    {
        global $injector, $notification, $page_output, $prefs, $registry, $session;

        $mailbox = $this->indices->mailbox;

        /* Call the mailbox redirection hook, if requested. */
        try {
            $redirect = $injector->getInstance('Horde_Core_Hooks')->callHook(
                'mbox_redirect',
                'imp',
                array($mailbox)
            );
            if (!empty($redirect)) {
                Horde::url($redirect, true)->redirect();
            }
        } catch (Horde_Exception_HookNotSet $e) {}

        $mailbox_url = Horde::url('basic.php')->add('page', 'mailbox');
        $mailbox_imp_url = $mailbox->url('mailbox')->add('newmail', 1);

        $imp_flags = $injector->getInstance('IMP_Flags');
        $imp_imap = $mailbox->imp_imap;
        $imp_search = $injector->getInstance('IMP_Search');

        /* Run through the action handlers */
        if (($actionID = $this->vars->actionID) &&
            ($actionID != 'message_missing')) {
            try {
                $session->checkToken($this->vars->token);
            } catch (Horde_Exception $e) {
                $notification->push($e);
                $actionID = null;
            }
        }

        /* We know we are going to be exclusively dealing with this mailbox,
         * so select it on the IMAP server (saves some STATUS calls). Open
         * R/W to clear the RECENT flag. This call will catch invalid
         * mailboxes. */
        $imp_imap->openMailbox($mailbox, Horde_Imap_Client::OPEN_READWRITE);
        $imp_mailbox = $mailbox->list_ob;

        /* Determine if mailbox is readonly. */
        $readonly = $mailbox->readonly;

        switch ($actionID) {
        case 'change_sort':
            $mailbox->setSort($this->vars->sortby, $this->vars->sortdir);
            break;

        case 'blacklist':
            $injector->getInstance('IMP_Filter')->blacklistMessage($this->indices);
            break;

        case 'whitelist':
            $injector->getInstance('IMP_Filter')->whitelistMessage($this->indices);
            break;

        case 'spam_report':
            $injector->getInstance('IMP_Factory_Spam')->create(IMP_Spam::SPAM)->report($this->indices);
            break;

        case 'innocent_report':
            $injector->getInstance('IMP_Factory_Spam')->create(IMP_Spam::INNOCENT)->report($this->indices);
            break;

        case 'message_missing':
            $notification->push(_("Requested message not found."), 'horde.error');
            break;

        case 'fwd_digest':
        case 'redirect_messages':
        case 'template_edit':
            if (count($this->indices)) {
                $compose_actions = array(
                    'fwd_digest' => 'fwd_digest',
                    'redirect_messages' => 'redirect_compose',
                    'template_edit' => 'template_edit'
                );

                $clink = new IMP_Compose_Link($this->vars);
                $options = array_merge(array(
                    'actionID' => $compose_actions[$actionID],
                    'muid' => strval($this->indices)
                ), $clink->args);

                if ($prefs->getValue('compose_popup')) {
                    $page_output->addInlineScript(array(
                        Horde::popupJs(IMP_Basic_Compose::url(), array('novoid' => true, 'params' => array_merge(array('popup' => 1), $options)))
                    ), true);
                } else {
                    IMP_Basic_Compose::url()->add($options)->redirect();
                }
            }
            break;

        case 'delete_messages':
            $injector->getInstance('IMP_Message')->delete($this->indices, array(
                'mailboxob' => $imp_mailbox
            ));
            break;

        case 'undelete_messages':
            $injector->getInstance('IMP_Message')->undelete($this->indices);
            break;

        case 'move_messages':
        case 'copy_messages':
            if (isset($this->vars->targetMbox) &&
                count($this->indices) &&
                (!$readonly || $actionID == 'copy_messages')) {
                $targetMbox = IMP_Mailbox::formFrom($this->vars->targetMbox);
                if (!empty($this->vars->newMbox) && ($this->vars->newMbox == 1)) {
                    $targetMbox = IMP_Mailbox::get($this->vars->targetMbox)->namespace_append;
                    $newMbox = true;
                } else {
                    $targetMbox = IMP_Mailbox::formFrom($this->vars->targetMbox);
                    $newMbox = false;
                }
                $injector->getInstance('IMP_Message')->copy($targetMbox, ($actionID == 'move_messages') ? 'move' : 'copy', $this->indices, array(
                    'create' => $newMbox,
                    'mailboxob' => $imp_mailbox
                ));
            }
            break;

        case 'flag_messages':
            if (!$readonly && $this->vars->flag && count($this->indices)) {
                $flag = $imp_flags->parseFormId($this->vars->flag);
                $injector->getInstance('IMP_Message')->flag(array(
                    ($flag['set'] ? 'add' : 'remove') => array($flag['flag'])
                ), $this->indices);
            }
            break;

        case 'filter_messages':
            if (!$readonly) {
                $filter = IMP_Mailbox::formFrom($this->vars->filter);
                $q_ob = null;

                if (strpos($filter, self::FLAG_FILTER_PREFIX) === 0) {
                    /* Flag filtering. */
                    $flag_filter = $imp_flags->parseFormId(substr($filter, strpos($filter, "\0") + 1));

                    try {
                        $q_ob = $imp_search->createQuery(array(
                            new IMP_Search_Element_Flag(
                                $flag_filter['flag'],
                                $flag_filter['set']
                            )),
                            array(
                                'mboxes' => array($mailbox),
                                'type' => IMP_Search::CREATE_QUERY
                            )
                        );
                    } catch (InvalidArgumentException $e) {}
                } else {
                    /* Pre-defined filters. */
                    try {
                        $q_ob = $imp_search->applyFilter($filter, array($mailbox));
                    } catch (InvalidArgumentException $e) {}
                }

                if ($q_ob) {
                    IMP_Mailbox::get($q_ob)->url('mailbox')->redirect();
                    exit;
                }
            }
            break;

        case 'hide_deleted':
            $mailbox->setHideDeletedMsgs(!$prefs->getValue('delhide'));
            break;

        case 'expunge_mailbox':
            $injector->getInstance('IMP_Message')->expungeMailbox(array(strval($mailbox) => 1), array(
                'mailboxob' => $imp_mailbox
            ));
            break;

        case 'filter':
            $mailbox->filter();
            break;

        case 'empty_mailbox':
            $injector->getInstance('IMP_Message')->emptyMailbox(array(strval($mailbox)));
            break;

        case 'view_messages':
            $mailbox->url(IMP_Basic_Thread::url(), null, false)->add(array(
                'mode' => 'msgview',
                'muid' => strval($this->indices)
            ))->redirect();
            break;
        }

        /* Token to use in requests. */
        $token = $session->getToken();
        $search_mbox = $mailbox->search;

        /* Deal with filter options. */
        if (!$readonly &&
            IMP_Filter::canApplyFilters() &&
            !$mailbox->filterOnDisplay() &&
            ($mailbox->inbox ||
            ($prefs->getValue('filter_any_mailbox') && !$search_mbox))) {
            $filter_url = $mailbox_imp_url->copy()->add(array(
                'actionID' => 'filter',
                'token' => $token
            ));
        }

        /* Generate folder options list. */
        if ($imp_imap->access(IMP_Imap::ACCESS_FOLDERS)) {
            $iterator = new IMP_Ftree_IteratorFilter(
                $injector->getInstance('IMP_Ftree')
            );
            $iterator->add($iterator::NONIMAP);

            $folder_options = new IMP_Ftree_Select(array(
                'heading' => _("Messages to"),
                'inc_notepads' => true,
                'inc_tasklists' => true,
                'iterator' => $iterator,
                'new_mbox' => true
            ));
        }

        /* Build the list of messages in the mailbox. */
        $pageOb = $imp_mailbox->buildMailboxPage($this->vars->mpage, $this->vars->start);
        $show_preview = $prefs->getValue('preview_enabled');

        $mbox_info = $imp_mailbox->getMailboxArray(range($pageOb['begin'], $pageOb['end']), array(
            'headers' => true,
            'preview' => (int)$show_preview,
            'type' => $prefs->getValue('atc_flag')
        ));

        /* Determine sorting preferences. */
        $sortpref = $mailbox->getSort();
        $thread_sort = ($sortpref->sortby == Horde_Imap_Client::SORT_THREAD);

        /* Determine if we are going to show the Hide/Purge Deleted Message
         * links. */
        if (!($use_trash = $prefs->getValue('use_trash')) &&
            !$mailbox->vinbox) {
            $showdelete = array(
                'hide' => true,
                'purge' => $mailbox->access_expunge
            );
        } else {
            $showdelete = array(
                'hide' => false,
                'purge' => false
            );
        }
        if ($showdelete['hide'] && !$prefs->isLocked('delhide')) {
            if ($prefs->getValue('delhide')) {
                $deleted_prompt = _("Show Deleted");
            } else {
                $deleted_prompt = _("Hide Deleted");
            }
        }

        /* Generate paging links. */
        if ($pageOb['pagecount']) {
            if ($pageOb['page'] == 1) {
                $url_first = $url_prev = null;
                $pages_first = 'navfirstgreyImg';
                $pages_prev = 'navleftgreyImg';
            } else {
                $url_first = $mailbox_imp_url->copy()->add('mpage', 1);
                $pages_first = 'navfirstImg';
                $url_prev = $mailbox_imp_url->copy()->add('mpage', $pageOb['page'] - 1);
                $pages_prev = 'navleftImg';
            }

            if ($pageOb['page'] == $pageOb['pagecount']) {
                $url_last = $url_next = null;
                $pages_last = 'navlastgreyImg';
                $pages_next = 'navrightgreyImg';
            } else {
                $url_next = $mailbox_imp_url->copy()->add('mpage', $pageOb['page'] + 1);
                $pages_next = 'navrightImg';
                $url_last = $mailbox_imp_url->copy()->add('mpage', $pageOb['pagecount']);
                $pages_last = 'navlastImg';
            }
        }

        /* Generate RSS link. */
        if ($mailbox->inbox) {
            $rss_box = '';
        } else {
            $ns_info = $mailbox->namespace_info;
            if (is_null($ns_info)) {
                $rss_box = null;
            } else {
                $rss_box = str_replace(
                    rawurlencode($ns_info->delimiter),
                    '/',
                    rawurlencode($ns_info->delimiter . (($ns_info->type == $ns_info::NS_PERSONAL) ? $ns_info->stripNamespace($mailbox) : $mailbox))
                );
            }
        }

        if (!is_null($rss_box)) {
            $page_output->addLinkTag(array(
                'href' => Horde::url('rss.php', true, -1) . $rss_box
            ));
        }

        /* If user wants the mailbox to be refreshed, set time here. */
        $refresh_url = $mailbox_imp_url->copy()->add('mpage', $pageOb['page']);
        if (isset($filter_url)) {
            $filter_url->add('mpage', $pageOb['page']);
        }

        /* Determine if we are showing previews. */
        $preview_tooltip = $show_preview
            ? $prefs->getValue('preview_show_tooltip')
            : false;
        if (!$preview_tooltip) {
            $strip_preview = $prefs->getValue('preview_strip_nl');
        }

        $unread = $imp_mailbox->unseenMessages(Horde_Imap_Client::SEARCH_RESULTS_COUNT);

        $page_output->addInlineJsVars(array(
            'ImpMailbox.pop3' => intval(!$mailbox->is_imap),
            'ImpMailbox.text' => array(
                'delete_messages' => _("Are you sure you wish to PERMANENTLY delete these messages?"),
                'delete_all' => _("Are you sure you wish to delete all mail in this mailbox?"),
                'delete_vfolder' => _("Are you sure you want to delete this Virtual Folder Definition?"),
                'innocent_report' => _("Are you sure you wish to report this message as innocent?"),
                'moveconfirm' => _("Are you sure you want to move the message(s)? (Some message information might get lost, like message headers, text formatting or attachments!)"),
                'newmbox' => _("You are copying/moving to a new mailbox.") . "\n" . _("Please enter a name for the new mailbox:") . "\n",
                'no' => _("No"),
                'selectone' => _("You must select at least one message first."),
                'selectonlyone' => _("You must select only one message for this action."),
                'spam_report' => _("Are you sure you wish to report this message as spam?"),
                'submit' => _("You must select at least one message first."),
                'target_mbox' => _("You must select a target mailbox first.")
            ),
            'ImpMailbox.unread' => intval($unread)
        ));

        $pagetitle = $this->title = $mailbox->label;

        if ($mailbox->editvfolder) {
            $query_text = wordwrap($imp_search[$mailbox]->querytext);
            $pagetitle .= ' [' . Horde::linkTooltip('#', $query_text, '', '', '', $query_text) . _("Virtual Folder") . '</a>]';
            $this->title .= ' [' . _("Virtual Folder") . ']';
        } elseif ($mailbox->editquery) {
            $query_text = wordwrap($imp_search[$mailbox]->querytext);
            $pagetitle = Horde::linkTooltip('#', $query_text, '', '', '', $query_text) . $pagetitle . '</a>';
        } else {
            $pagetitle = $this->title = htmlspecialchars($this->title);
        }

        /* Generate mailbox summary string. */
        $subinfo = new IMP_View_Subinfo(array('mailbox' => $mailbox));
        $subinfo->value = $pagetitle . ' (';
        if (empty($pageOb['end'])) {
            $subinfo->value .= _("No Messages");
        } else {
            $subinfo->value .= ($pageOb['pagecount'] > 1)
                ? sprintf(_("%d Messages"), $pageOb['msgcount']) . ' / ' . sprintf(_("Page %d of %d"), $pageOb['page'], $pageOb['pagecount'])
                : sprintf(_("%d Messages"), $pageOb['msgcount']);
        }
        $subinfo->value .= ')';
        $injector->getInstance('Horde_View_Topbar')->subinfo = $subinfo->render();

        $page_output->addScriptFile('hordecore.js', 'horde');
        $page_output->addScriptFile('mailbox.js');
        $page_output->addScriptPackage('Horde_Core_Script_Package_Dialog');

        $page_output->metaRefresh($prefs->getValue('refresh_time'), $refresh_url);

        /* Prepare the header template. */
        $view = new Horde_View(array(
            'templatePath' => IMP_TEMPLATES . '/basic/mailbox'
        ));
        $view->addHelper('FormTag');
        $view->addHelper('Horde_Core_View_Helper_Accesskey');
        $view->addHelper('Tag');

        $hdr_view = clone $view;
        $hdr_view->readonly = $readonly;
        $hdr_view->refresh_url = $refresh_url;
        if (isset($filter_url)) {
            $hdr_view->filter_url = $filter_url;
        }
        if ($mailbox->access_search) {
            if (!$search_mbox) {
                $hdr_view->search_url = $mailbox->url(IMP_Basic_Searchbasic::url());
            } else {
                if ($mailbox->editvfolder) {
                    $edit_search = _("Edit Virtual Folder");
                } elseif ($mailbox->query) {
                    if ($mailbox->editquery) {
                        $edit_search = _("Edit Search Query");
                    } else {
                        /* Basic search results. */
                        $search_mailbox = IMP_Mailbox::get($imp_search[$mailbox]->mboxes[0]);
                        $hdr_view->search_url = $search_mailbox->url(IMP_Basic_Searchbasic::url());
                        $hdr_view->searchclose = $search_mailbox->url('mailbox');
                    }
                }

                if (isset($edit_search)) {
                    $hdr_view->edit_search_url = $imp_search->editUrl($mailbox);
                    $hdr_view->edit_search_title = $edit_search;
                }
            }
        }

        if ($mailbox->access_empty) {
            $hdr_view->empty = $mailbox_imp_url->copy()->add(array(
                'actionID' => 'empty_mailbox',
                'token' => $token
            ));
        }

        $this->output = $hdr_view->render('header');

        /* If no messages, exit immediately. */
        if (empty($pageOb['end'])) {
            if ($pageOb['anymsg'] && isset($deleted_prompt)) {
                /* Show 'Show Deleted' prompt if mailbox has no viewable
                 * message but has hidden, deleted messages. */
                $del_view = clone $view;
                $del_view->hide = Horde::widget(array(
                    'url' => $refresh_url->copy()->add(array(
                        'actionID' => 'hide_deleted',
                        'token' => $token
                    )),
                    'class' => 'hideAction',
                    'title' => $deleted_prompt
                ));
                if ($mailbox->access_expunge) {
                    $del_view->purge = Horde::widget(array(
                        'url' => $refresh_url->copy()->add(array(
                            'actionID' => 'expunge_mailbox',
                            'token' => $token
                        )),
                        'class' => 'purgeAction',
                        'title' => _("Pur_ge Deleted")
                    ));
                }

                $this->output .= $del_view->render('actions_deleted');
            }

            $empty_view = clone $view;
            $empty_view->search_mbox = $search_mbox;

            $this->output .= $empty_view->render('empty_mailbox');
            return;
        }

        $clink_ob = new IMP_Compose_Link();
        $clink = $clink_ob->link();

        /* Display the navbar and actions if there is at least 1 message in
         * mailbox. */
        if ($pageOb['msgcount']) {
            /* Prepare the navbar template. */
            $n_view = clone $view;
            $n_view->id = 1;
            $n_view->readonly = $readonly;

            $filtermsg = false;
            if ($mailbox->access_flags) {
                $args = array(
                    'imap' => true,
                    'mailbox' => $search_mbox ? null : $mailbox
                );

                $form_set = $form_unset = array();
                foreach ($imp_flags->getList($args) as $val) {
                    if ($val->canset) {
                        $form_set[] = array(
                            'f' => $val->form_set,
                            'l' => $val->label,
                            'v' => IMP_Mailbox::formTo(self::FLAG_FILTER_PREFIX . $val->form_set)
                        );
                        $form_unset[] = array(
                            'f' => $val->form_unset,
                            'l' => $val->label,
                            'v' => IMP_Mailbox::formTo(self::FLAG_FILTER_PREFIX . $val->form_unset)
                        );
                    }
                }

                $n_view->flaglist_set = $form_set;
                $n_view->flaglist_unset = $form_unset;

                if (!$search_mbox && $mailbox->access_search) {
                    $filtermsg = $n_view->flag_filter = true;
                }
            }

            if (!$search_mbox && $mailbox->access_filters) {
                $filters = array();
                $iterator = IMP_Search_IteratorFilter::create(
                    IMP_Search_IteratorFilter::FILTER
                );

                foreach ($iterator as $val) {
                    $filters[] = array(
                        'l' => $val->label,
                        'v' => IMP_Mailbox::formTo($val)
                    );
                }

                if (!empty($filters)) {
                    $filtermsg = true;
                    $n_view->filters = $filters;
                }
            }

            $n_view->filtermsg = $filtermsg;

            if ($imp_imap->access(IMP_Imap::ACCESS_FOLDERS)) {
                $n_view->move = Horde::widget(array(
                    'url' => '#',
                    'class' => 'moveAction',
                    'title' => _("Move"),
                    'nocheck' => true
                ));
                $n_view->copy = Horde::widget(array(
                    'url' => '#',
                    'class' => 'copyAction',
                    'title' => _("Copy"),
                    'nocheck' => true
                ));
                $n_view->folder_options = $folder_options;
            }

            $n_view->mailbox_url = $mailbox_url;
            $n_view->mailbox = $mailbox->form_to;
            if ($pageOb['pagecount'] > 1) {
                $n_view->multiple_page = true;
                $n_view->pages_first = $pages_first;
                $n_view->url_first = $url_first;
                $n_view->pages_prev = $pages_prev;
                $n_view->url_prev = $url_prev;
                $n_view->pages_next = $pages_next;
                $n_view->url_next = $url_next;
                $n_view->pages_last = $pages_last;
                $n_view->url_last = $url_last;
                $n_view->page_val = $pageOb['page'];
                $n_view->page_size = Horde_String::length($pageOb['pagecount']);
            }

            $this->output .= $n_view->render('navbar');

            /* Prepare the actions template. */
            $a_view = clone $view;
            if ($mailbox->access_deletemsgs) {
                $del_class = ($use_trash && $mailbox->trash)
                    ? 'permdeleteAction'
                    : 'deleteAction';
                $a_view->delete = Horde::widget(array(
                    'url' => '#',
                    'class' => $del_class,
                    'title' => _("_Delete")
                ));
            }

            if ($showdelete['purge'] || $mailbox->vtrash) {
                $a_view->undelete = Horde::widget(array(
                    'url' => '#',
                    'class' => 'undeleteAction',
                    'title' => _("_Undelete")
                ));
            }

            $mboxactions = array();
            if ($showdelete['purge']) {
                $mailbox_link = $mailbox_imp_url->copy()->add('mpage', $pageOb['page']);
                if (isset($deleted_prompt)) {
                    $mboxactions[] = Horde::widget(array(
                        'url' => $mailbox_link->copy()->add(array(
                            'actionID' => 'hide_deleted',
                            'token' => $token
                        )),
                        'class' => 'hideAction',
                        'title' => $deleted_prompt
                    ));
                }
                $mboxactions[] = Horde::widget(array(
                    'url' => $mailbox_link->copy()->add(array(
                        'actionID' => 'expunge_mailbox',
                        'token' => $token
                    )),
                    'class' => 'purgeAction',
                    'title' => _("Pur_ge Deleted")
                ));
            }

            if (!$sortpref->sortby_locked &&
                ($sortpref->sortby != Horde_Imap_Client::SORT_SEQUENCE)) {
                $mboxactions[] = Horde::widget(array(
                    'url' => $mailbox_imp_url->copy()->add(array(
                        'sortby' => Horde_Imap_Client::SORT_SEQUENCE,
                        'actionID' => 'change_sort',
                        'token' => $token
                    )),
                    'title' => _("Clear Sort")
                ));
            }

            if ($mailbox->templates) {
                $a_view->templateedit = Horde::widget(array(
                    'url' => '#',
                    'class' => 'templateeditAction',
                    'title' => _("Edit Template")
                ));
                $mboxactions[] = Horde::widget(array(
                    'url' => $clink->copy()->add(array(
                        'actionID' => 'template_new'
                    )),
                    'title' => _("Create New Template")
                ));
            }

            $a_view->mboxactions = $mboxactions;

            if ($registry->hasMethod('mail/blacklistFrom')) {
                $a_view->blacklist = Horde::widget(array(
                    'url' => '#',
                    'class' => 'blacklistAction',
                    'title' => _("_Blacklist")
                ));
            }

            if ($registry->hasMethod('mail/whitelistFrom')) {
                $a_view->whitelist = Horde::widget(array(
                    'url' => '#',
                    'class' => 'whitelistAction',
                    'title' => _("_Whitelist")
                ));
            }

            if (IMP_Compose::canCompose()) {
                $a_view->forward = Horde::widget(array(
                    'url' => '#',
                    'class' => 'forwardAction',
                    'title' => _("Fo_rward")
                ));
                $a_view->redirect = Horde::widget(array(
                    'url' => '#',
                    'class' => 'redirectAction',
                    'title' => _("Redirect")
                ));
            }

            if ($mailbox->spam_show) {
                $a_view->spam = Horde::widget(array(
                    'url' => '#',
                    'class' => 'spamAction',
                    'title' => _("Report as Spam")
                ));
            }

            if ($mailbox->innocent_show) {
                $a_view->innocent = Horde::widget(array(
                    'url' => '#',
                    'class' => 'innocentAction',
                    'title' => _("Report as Innocent")
                ));
            }

            $a_view->view_messages = Horde::widget(array(
                'url' => '#',
                'class' => 'viewAction',
                'title' => _("View Messages")
            ));

            $this->output .= $a_view->render('actions');
        }

        /* Define some variables now so we don't have to keep redefining in
         * the foreach loop or the templates. */
        $lastMbox = '';
        $mh_count = 0;
        $sortImg = $sortpref->sortdir
            ? 'sortup'
            : 'sortdown';
        $headers = array(
            Horde_Imap_Client::SORT_TO => array(
                'id' => 'mboxto',
                'stext' => _("Sort by To Address"),
                'text' => _("To")
            ),
            Horde_Imap_Client::SORT_FROM => array(
                'id' => 'mboxfrom',
                'stext' => _("Sort by From Address"),
                'text' => _("Fro_m")
            ),
            Horde_Imap_Client::SORT_THREAD => array(
                'id' => 'mboxthread',
                'stext' => _("Sort by Thread"),
                'text' => _("_Thread")
            ),
            Horde_Imap_Client::SORT_SUBJECT => array(
                'id' => 'mboxsubject',
                'stext' => _("Sort by Subject"),
                'text' => _("Sub_ject")
            ),
            IMP::IMAP_SORT_DATE => array(
                'id' => 'mboxdate',
                'stext' => _("Sort by Date"),
                'text' => _("Dat_e")
            ),
            Horde_Imap_Client::SORT_SIZE => array(
                'id' => 'mboxsize',
                'stext' => _("Sort by Message Size"),
                'text' => _("Si_ze")
            )
        );

        /* If this is the Drafts or Sent-Mail mailbox, sort by To instead of
         * From. */
        if ($mailbox->special_outgoing) {
            unset($headers[Horde_Imap_Client::SORT_FROM]);
        } else {
            unset($headers[Horde_Imap_Client::SORT_TO]);
        }

        /* Determine which of Subject/Thread to emphasize. */
        if (!$mailbox->access_sortthread || $sortpref->sortby_locked) {
            unset($headers[Horde_Imap_Client::SORT_THREAD]);
            if ($sortpref->sortby_locked && $thread_sort) {
                $sortpref->sortby = Horde_Imap_Client::SORT_SUBJECT;
            }
        } else {
            if ($thread_sort) {
                $extra = Horde_Imap_Client::SORT_SUBJECT;
                $standard = Horde_Imap_Client::SORT_THREAD;
            } else {
                $extra = Horde_Imap_Client::SORT_THREAD;
                $standard = Horde_Imap_Client::SORT_SUBJECT;
            }
            $headers[$standard]['altsort'] = Horde::widget(array(
                'url' => $mailbox_imp_url->copy()->add(array(
                    'actionID' => 'change_sort',
                    'token' => $token,
                    'sortby' => $extra
                )),
                'title' => $headers[$extra]['text']
            ));
            unset($headers[$extra]);
        }

        foreach ($headers as $key => $val) {
            $ptr = &$headers[$key];
            if ($sortpref->sortby == $key) {
                $csl_icon = '<span class="iconImg ' . $sortImg . '"></span>';
                if ($sortpref->sortdir_locked) {
                    $ptr['change_sort_link'] = $csl_icon;
                    $ptr['change_sort_widget'] = Horde::stripAccessKey($val['text']);
                } else {
                    $tmp = $mailbox_imp_url->copy()->add(array(
                        'sortby' => $key,
                        'sortdir' => intval(!$sortpref->sortdir),
                        'actionID' => 'change_sort',
                        'token' => $token
                    ));
                    $ptr['change_sort_link'] = Horde::link($tmp, $val['stext'], null, null, null, $val['stext']) . $csl_icon . '</a>';
                    $ptr['change_sort_widget'] = Horde::widget(array('url' => $tmp, 'title' => $val['text']));
                }
            } else {
                $ptr['change_sort_link'] = null;
                $ptr['change_sort_widget'] = $sortpref->sortby_locked
                    ? Horde::stripAccessKey($val['text'])
                    : Horde::widget(array(
                        'url' => $mailbox_imp_url->copy()->add(array(
                            'actionID' => 'change_sort',
                            'token' => $token,
                            'sortby' => $key
                        )),
                        'title' => $val['text']
                    ));
            }
            $ptr['class'] = 'horde-split-left';
        }

        /* Output the form start. */
        $f_view = clone $view;
        $f_view->mailbox = $mailbox->form_to;
        $f_view->mailbox_url = $mailbox_url;
        $f_view->page = $pageOb['page'];
        $f_view->token = $token;
        $this->output .= $f_view->render('form_start');

        /* Prepare the message headers template. */
        $mh_view = clone $view;
        $mh_view->headers = $headers;

        if (!$search_mbox) {
            $mh_view->show_checkbox = !$mh_count++;
            $this->output .= $mh_view->render('message_headers');
        }

        /* Initialize repetitively used variables. */
        $fromlinkstyle = $prefs->getValue('from_link');
        $imp_ui = new IMP_Mailbox_Ui($mailbox);

        /* Display message information. */
        $msgs = array();
        $search_view = clone $view;
        $summary_view = clone $view;

        while (list(,$ob) = each($mbox_info['overview'])) {
            if ($search_mbox) {
                if (empty($lastMbox) || ($ob['mailbox'] != $lastMbox)) {
                    if (!empty($lastMbox)) {
                        $this->_outputSummaries($msgs, $summary_view);
                        $msgs = array();
                    }

                    $mbox = IMP_Mailbox::get($ob['mailbox']);
                    $search_view->mbox_link = Horde::link($mbox->url($mailbox_url), sprintf(_("View messages in %s"), $mbox->display), 'smallheader') . $mbox->display_html . '</a>';
                    $this->output .= $search_view->render('searchmbox');

                    $mh_view->show_checkbox = !$mh_count++;
                    $this->output .= $mh_view->render('message_headers');
                }
            }

            $lastMbox = $ob['mailbox'];

            /* Initialize the data fields. */
            $msg = array(
                'bg' => '',
                'buid' => $imp_mailbox->getBuid($ob['mailbox'], $ob['uid']),
                'class' => '',
                'date' => $imp_ui->getDate($ob['envelope']->date),
                'preview' => '',
                'status' => '',
                'size' => IMP::sizeFormat($ob['size'])
            );

            /* Generate the target link. */
            if ($mailbox->drafts || $mailbox->templates) {
                $target = $clink->copy()->add(array(
                    'actionID' => ($mailbox->drafts ? 'draft' : 'template'),
                    'buid' => $msg['buid'],
                    'mailbox' => $mailbox
                ));
            } else {
                $target = $mailbox->url('message', $msg['buid']);
            }

            /* Get all the flag information. */
            $flag_parse = $imp_flags->parse(array(
                'flags' => $ob['flags'],
                'headers' => $ob['headers'],
                'runhook' => $ob,
                'personal' => $ob['envelope']->to
            ));

            $css_class = $subject_flags = array();
            foreach ($flag_parse as $val) {
                if ($val instanceof IMP_Flag_User) {
                    $subject_flags[] = $val;
                } else {
                    if (!$val->bgdefault) {
                        $msg['bg'] = $val->bgcolor;
                    }
                    $css_class[] = $val->css;
                    $msg['status'] .= $val->span;
                }
            }
            $msg['class'] = implode(' ', $css_class);

            /* Show message preview? */
            if ($show_preview && isset($ob['preview'])) {
                if (empty($ob['preview'])) {
                    $ptext = '[[' . _("No Preview Text") . ']]';
                } else {
                    $ptext = empty($strip_preview)
                        ? str_replace("\r", '', $ob['preview'])
                        : preg_replace(array('/\n/', '/(\s)+/'), array(' ', '$1'), str_replace("\r", "\n", $ob['preview']));

                    if (!$preview_tooltip) {
                        $ptext = $injector->getInstance('Horde_Core_Factory_TextFilter')->filter($ptext, 'text2html', array(
                            'parselevel' => Horde_Text_Filter_Text2html::NOHTML
                        ));
                    }

                    $maxlen = $prefs->getValue('preview_maxlen');
                    if (Horde_String::length($ptext) > $maxlen) {
                        $ptext = Horde_String::truncate($ptext, $maxlen);
                    } elseif (empty($ob['previewcut'])) {
                        $ptext .= '[[' . _("END") . ']]';
                    }
                }
                $msg['preview'] = $ptext;
            }

            /* Format the From: Header. */
            $getfrom = $imp_ui->getFrom($ob['envelope']);
            $msg['from'] = htmlspecialchars($getfrom['from'], ENT_QUOTES, 'UTF-8');
            switch ($fromlinkstyle) {
            case 0:
                $from_tmp = array();
                foreach ($getfrom['from_list']->base_addresses as $from_ob) {
                    $from_tmp[] = call_user_func_array(array('Horde', $preview_tooltip ? 'linkTooltip' : 'link'), array($clink->copy()->add(array('actionID' => 'mailto_link', 'to' => strval($from_ob))), sprintf(_("New Message to %s"), $from_ob->label))) . htmlspecialchars($from_ob->label, ENT_QUOTES, 'UTF-8') . '</a>';
                }

                if (!empty($from_tmp)) {
                    $msg['from'] = implode(', ', $from_tmp);
                }
                break;

            default:
                $from_uri = $mailbox->url('message', $msg['buid']);
                $msg['from'] = Horde::link($from_uri) . $msg['from'] . '</a>';
                break;
            }

            /* Format the Subject: Header. */
            $msg['subject'] = $imp_ui->getSubject($ob['envelope']->subject, true);
            $msg['subject'] = $preview_tooltip
                ? substr(Horde::linkTooltip($target, $msg['preview'], '', '', '', $msg['preview']), 0, -1) . ' class="mboxSubject">' . $msg['subject'] . '</a>'
                : substr(Horde::link($target, $imp_ui->getSubject($ob['envelope']->subject)), 0, -1) . ' class="mboxSubject">' . $msg['subject'] . '</a>' . (!empty($msg['preview']) ? '<br /><small>' . $msg['preview'] . '</small>' : '');

            /* Add subject flags. */
            foreach ($subject_flags as $val) {
                $flag_label = Horde_String::truncate($val->label, 12);

                $msg['subject'] = '<span class="' . $val->css . '" style="' . ($val->bgdefault ? '' : 'background:' . htmlspecialchars($val->bgcolor) . ';') . 'color:' . htmlspecialchars($val->fgcolor) . '" title="' . htmlspecialchars($val->label) . '">' . htmlspecialchars($flag_label) . '</span>' . $msg['subject'];
            }

            /* Set up threading tree now. */
            if ($thread_sort) {
                $t_ob = $imp_mailbox->getThreadOb($ob['idx']);
                $msg['subject'] = ($sortpref->sortdir ? $t_ob->reverse_img : $t_ob->img) . ' ' . $msg['subject'];
            }

            $msgs[$msg['buid']] = $msg;
        }

        $this->_outputSummaries($msgs, $summary_view);

        $this->output .= '</form>';

        /* If there are 20 messages or less, don't show the actions/navbar
         * again. */
        if (($pageOb['end'] - $pageOb['begin']) >= 20) {
            $this->output .= $a_view->render('actions');
            $n_view->id = 2;
            $this->output .= $n_view->render('navbar');
        }
    }

    /**
     * @param array $opts  Options:
     *   - mailbox: (string) The mailbox to link to.
     */
    static public function url(array $opts = array())
    {
        $opts = array_merge(array('mailbox' => 'INBOX'), $opts);

        return IMP_Mailbox::get($opts['mailbox'])->url('basic')->add('page', 'mailbox');
    }

    /**
     */
    protected function _outputSummaries($msgs, Horde_View $view)
    {
        /* Allow user to alter template array. */
        try {
            $msgs = $GLOBALS['injector']->getInstance('Horde_Core_Hooks')->callHook(
                'mailboxarray',
                'imp',
                array($msgs)
            );
        } catch (Horde_Exception_HookNotSet $e) {}

        $view->messages = $msgs;
        $this->output .= $view->render('mailbox');
    }

}
