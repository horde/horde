<?php
/**
 * Traditional (imp) mailbox display page.
 *
 * Copyright 1999-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */

function _outputSummaries($msgs, Horde_View $view)
{
    /* Allow user to alter template array. */
    try {
        $msgs = Horde::callHook('mailboxarray', array($msgs, 'imp'), 'imp');
    } catch (Horde_Exception_HookNotSet $e) {}

    $view->messages = $msgs;
    echo $view->render('mailbox');
}


require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('imp', array(
    'impmode' => Horde_Registry::VIEW_BASIC
));

if (!IMP::mailbox()) {
    throw new IMP_Exception(_("Invalid mailbox."));
}

$registry->setTimeZone();

/* Call the mailbox redirection hook, if requested. */
try {
    $redirect = Horde::callHook('mbox_redirect', array(IMP::mailbox()), 'imp');
    if (!empty($redirect)) {
        Horde::url($redirect, true)->redirect();
    }
} catch (Horde_Exception_HookNotSet $e) {}

/* Is this a search mailbox? */
$search_mbox = IMP::mailbox()->search;
$vars = $injector->getInstance('Horde_Variables');

/* There is a chance that this page is loaded directly via message.php. If so,
 * don't re-include config files, and the following variables will already be
 * set: $actionID, $start. */
$mailbox_url = Horde::url('mailbox.php');
$mailbox_imp_url = IMP::mailbox()->url('mailbox.php')->add('newmail', 1);
if (!Horde_Util::nonInputVar('from_message_page')) {
    $actionID = $vars->actionID;
    $start = $vars->start;
}

$flag_filter_prefix = "flag\0";
$imp_flags = $injector->getInstance('IMP_Flags');
$imp_imap = $injector->getInstance('IMP_Factory_Imap')->create();
$imp_search = $injector->getInstance('IMP_Search');
$indices = new IMP_Indices($vars->indices);

/* Run through the action handlers */
if ($actionID && ($actionID != 'message_missing')) {
    try {
        $injector->getInstance('Horde_Token')->validate($vars->mailbox_token, 'imp.mailbox');
    } catch (Horde_Token_Exception $e) {
        $notification->push($e);
        $actionID = null;
    }
}

/* We know we are going to be exclusively dealing with this mailbox, so
 * select it on the IMAP server (saves some STATUS calls). Open R/W to clear
 * the RECENT flag. This call will catch invalid mailboxes. */
if (!$search_mbox) {
    $imp_imap->openMailbox(IMP::mailbox(), Horde_Imap_Client::OPEN_READWRITE);
}

/* Determine if mailbox is readonly. */
$readonly = IMP::mailbox()->readonly;

switch ($actionID) {
case 'change_sort':
    IMP::mailbox()->setSort($vars->sortby, $vars->sortdir);
    break;

case 'blacklist':
    $injector->getInstance('IMP_Filter')->blacklistMessage($indices);
    break;

case 'whitelist':
    $injector->getInstance('IMP_Filter')->whitelistMessage($indices);
    break;

case 'spam_report':
    IMP_Spam::reportSpam($indices, 'spam');
    break;

case 'notspam_report':
    IMP_Spam::reportSpam($indices, 'notspam');
    break;

case 'message_missing':
    $notification->push(_("Requested message not found."), 'horde.error');
    break;

case 'fwd_digest':
case 'redirect_messages':
case 'template_edit':
    if (count($indices)) {
        $compose_actions = array(
            'fwd_digest' => 'fwd_digest',
            'redirect_messages' => 'redirect_compose',
            'template_edit' => 'template_edit'
        );

        $options = array_merge(array(
            'actionID' => $compose_actions[$actionID],
            'msglist' => strval($indices)
        ), IMP::getComposeArgs($vars));

        if ($prefs->getValue('compose_popup')) {
            $page_output->addInlineScript(array(
                Horde::popupJs(Horde::url('compose.php'), array('novoid' => true, 'params' => array_merge(array('popup' => 1), $options)))
            ), true);
        } else {
            Horde::url('compose.php', true)->add($options)->redirect();
        }
    }
    break;

case 'delete_messages':
    $injector->getInstance('IMP_Message')->delete($indices);
    break;

case 'undelete_messages':
    $injector->getInstance('IMP_Message')->undelete($indices);
    break;

case 'move_messages':
case 'copy_messages':
    if (isset($vars->targetMbox) &&
        count($indices) &&
        (!$readonly || $actionID == 'copy_messages')) {
        $targetMbox = IMP_Mailbox::formFrom($vars->targetMbox);
        if (!empty($vars->newMbox) && ($vars->newMbox == 1)) {
            $targetMbox = IMP_Mailbox::get($vars->targetMbox)->namespace_append;
            $newMbox = true;
        } else {
            $targetMbox = IMP_Mailbox::formFrom($vars->targetMbox);
            $newMbox = false;
        }
        $injector->getInstance('IMP_Message')->copy($targetMbox, ($actionID == 'move_messages') ? 'move' : 'copy', $indices, array('create' => $newMbox));
    }
    break;

case 'flag_messages':
    if (!$readonly && $vars->flag && count($indices)) {
        $flag = $imp_flags->parseFormId($vars->flag);
        $injector->getInstance('IMP_Message')->flag(array($flag['flag']), $indices, $flag['set']);
    }
    break;

case 'filter_messages':
    if (!$readonly) {
        $filter = IMP_Mailbox::formFrom($vars->filter);
        $q_ob = null;

        if (strpos($filter, $flag_filter_prefix) === 0) {
            /* Flag filtering. */
            $flag_filter = $imp_flags->parseFormId(substr($filter, strpos($filter, "\0") + 1));

            try {
                $q_ob = $imp_search->createQuery(array(
                    new IMP_Search_Element_Flag(
                        $flag_filter['flag'],
                        $flag_filter['set']
                    )),
                    array(
                        'mboxes' => array(IMP::mailbox()),
                        'type' => IMP_Search::CREATE_QUERY
                    )
                );
            } catch (InvalidArgumentException $e) {}
        } else {
            /* Pre-defined filters. */
            try {
                $q_ob = $imp_search->applyFilter($filter, array(IMP::mailbox()));
            } catch (InvalidArgumentException $e) {}
        }

        if ($q_ob) {
            IMP_Mailbox::get($q_ob)->url('mailbox.php')->redirect();
            exit;
        }
    }
    break;

case 'hide_deleted':
    IMP::mailbox()->setHideDeletedMsgs(!$prefs->getValue('delhide'));
    break;

case 'expunge_mailbox':
    $injector->getInstance('IMP_Message')->expungeMailbox(array(strval(IMP::mailbox()) => 1));
    break;

case 'filter':
    IMP::mailbox()->filter();
    break;

case 'empty_mailbox':
    $injector->getInstance('IMP_Message')->emptyMailbox(array(strval(IMP::mailbox())));
    break;

case 'view_messages':
    IMP::mailbox()->url('thread.php', null, null, false)->add(array('mode' => 'msgview', 'msglist' => strval($indices)))->redirect();
}

/* Token to use in requests */
$mailbox_token = $injector->getInstance('Horde_Token')->get('imp.mailbox');

/* Deal with filter options. */
if (!$readonly &&
    IMP::applyFilters() &&
    !IMP::mailbox()->filterOnDisplay() &&
    (IMP::mailbox()->inbox ||
     ($prefs->getValue('filter_any_mailbox') && !$search_mbox))) {
    $filter_url = $mailbox_imp_url->copy()->add(array(
        'actionID' => 'filter',
        'mailbox_token' => $mailbox_token
    ));
}

/* Generate folder options list. */
if ($imp_imap->access(IMP_Imap::ACCESS_FOLDERS)) {
    $folder_options = IMP::flistSelect(array(
        'heading' => _("Messages to"),
        'inc_notepads' => true,
        'inc_tasklists' => true,
        'new_mbox' => true
    ));
}

/* Build the list of messages in the mailbox. */
$imp_mailbox = IMP::mailbox()->getListOb();
$pageOb = $imp_mailbox->buildMailboxPage($vars->page, $start);
$show_preview = $prefs->getValue('preview_enabled');

$mbox_info = $imp_mailbox->getMailboxArray(range($pageOb['begin'], $pageOb['end']), array(
    'headers' => true,
    'preview' => (int)$show_preview,
    'type' => $prefs->getValue('atc_flag')
));

/* Determine sorting preferences. */
$sortpref = IMP::mailbox()->getSort();
$thread_sort = ($sortpref->sortby == Horde_Imap_Client::SORT_THREAD);

/* Determine if we are going to show the Hide/Purge Deleted Message links. */
if (!$prefs->getValue('use_trash') && !IMP::mailbox()->vinbox) {
    $showdelete = array(
        'hide' => true,
        'purge' => IMP::mailbox()->access_expunge
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
        $url_first = $mailbox_imp_url->copy()->add('page', 1);
        $pages_first = 'navfirstImg';
        $url_prev = $mailbox_imp_url->copy()->add('page', $pageOb['page'] - 1);
        $pages_prev = 'navleftImg';
    }

    if ($pageOb['page'] == $pageOb['pagecount']) {
        $url_last = $url_next = null;
        $pages_last = 'navlastgreyImg';
        $pages_next = 'navrightgreyImg';
    } else {
        $url_next = $mailbox_imp_url->copy()->add('page', $pageOb['page'] + 1);
        $pages_next = 'navrightImg';
        $url_last = $mailbox_imp_url->copy()->add('page', $pageOb['pagecount']);
        $pages_last = 'navlastImg';
    }
}

/* Generate RSS link. */
if (IMP::mailbox()->inbox) {
    $rss_box = '';
} else {
    $rss_box = IMP::mailbox();
    $ns_info = $imp_imap->getNamespace(IMP::mailbox());
    if ($ns_info !== null) {
        if (!empty($ns_info['name']) &&
            ($ns_info['type'] == Horde_Imap_Client::NS_PERSONAL) &&
            substr(IMP::mailbox(), 0, strlen($ns_info['name'])) == $ns_info['name']) {
            $rss_box = substr(IMP::mailbox(), strlen($ns_info['name']));
        }
        $rss_box = str_replace(rawurlencode($ns_info['delimiter']), '/', rawurlencode($ns_info['delimiter'] . $rss_box));
    } else {
        $rss_box = null;
    }
}

if (!is_null($rss_box)) {
    $page_output->addLinkTag(array(
        'href' => Horde::url('rss.php') . $rss_box
    ));
}

/* If user wants the mailbox to be refreshed, set time here. */
$refresh_url = $mailbox_imp_url->copy()->add('page', $pageOb['page']);
if (isset($filter_url)) {
    $filter_url->add('page', $pageOb['page']);
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
    'ImpMailbox.text' => array(
        'delete' => _("Are you sure you wish to PERMANENTLY delete these messages?"),
        'delete_all' => _("Are you sure you wish to delete all mail in this mailbox?"),
        'delete_vfolder' => _("Are you sure you want to delete this Virtual Folder Definition?"),
        'selectone' => _("You must select at least one message first."),
        'selectonlyone' => _("You must select only one message for this action."),
        'submit' => _("You must select at least one message first.")
    ),
    'ImpMailbox.unread' => intval($unread)
));

$pagetitle = $title = IMP::mailbox()->label;

if (IMP::mailbox()->editvfolder) {
    $query_text = wordwrap($imp_search[IMP::mailbox()]->querytext);
    $pagetitle .= ' [' . Horde::linkTooltip('#', $query_text, '', '', '', $query_text) . _("Virtual Folder") . '</a>]';
    $title .= ' [' . _("Virtual Folder") . ']';
} elseif (IMP::mailbox()->editquery) {
    $query_text = wordwrap($imp_search[IMP::mailbox()]->querytext);
    $pagetitle = Horde::linkTooltip('#', $query_text, '', '', '', $query_text) . $pagetitle . '</a>';
} else {
    $pagetitle = $title = htmlspecialchars($title);
}

/* Generate mailbox summary string. */
$subinfo = $injector->getInstance('IMP_View_Subinfo');
$subinfo->value = $pagetitle . ' (';
if (empty($pageOb['end'])) {
    $subinfo->value .= _("No Messages");
} elseif ($pageOb['pagecount'] > 1) {
    $subinfo->value .=
          sprintf(_("%d Messages"), $pageOb['msgcount'])
        . ' / '
        .  sprintf(_("Page %d of %d"), $pageOb['page'], $pageOb['pagecount']);
} else {
    $subinfo->value .= sprintf(_("%d Messages"), $pageOb['msgcount']);
}
$subinfo->value .= ')';
$injector->getInstance('Horde_View_Topbar')->subinfo = $subinfo->render();

$page_output->addScriptFile('hordecore.js', 'horde');
$page_output->addScriptFile('mailbox.js');
$page_output->addScriptPackage('Dialog');

$page_output->metaRefresh($prefs->getValue('refresh_time'), $refresh_url);
IMP::header($title);
IMP::status();

/* Prepare the header template. */
$view = new Horde_View(array(
    'templatePath' => IMP_TEMPLATES . '/basic/mailbox'
));
$view->addHelper('FormTag');
$view->addHelper('Horde_Core_View_Helper_Accesskey');
$view->addHelper('Tag');
$view->addHelper('Text');

$hdr_view = clone $view;
$hdr_view->readonly = $readonly;
$hdr_view->refresh_url = $refresh_url;
if (isset($filter_url)) {
    $hdr_view->filter_url = $filter_url;
}
if ($imp_imap->access(IMP_Imap::ACCESS_SEARCH)) {
    if (!$search_mbox) {
        $hdr_view->search_url = IMP::mailbox()->url('search-basic.php');
    } else {
        if (IMP::mailbox()->editvfolder) {
            $edit_search = _("Edit Virtual Folder");
        } elseif (IMP::mailbox()->query) {
            if (IMP::mailbox()->editquery) {
                $edit_search = _("Edit Search Query");
            } else {
                /* Basic search results. */
                $search_mailbox = IMP_Mailbox::get($imp_search[IMP::mailbox()]->mboxes[0]);
                $hdr_view->search_url = $search_mailbox->url('search-basic.php');
                $hdr_view->searchclose = $search_mailbox->url('mailbox.php');
            }
        }

        if (isset($edit_search)) {
            $hdr_view->edit_search_url = $imp_search->editUrl(IMP::mailbox());
            $hdr_view->edit_search_title = $edit_search;
        }
    }
}

if (IMP::mailbox()->access_empty) {
    $hdr_view->empty = $mailbox_imp_url->copy()->add(array(
        'actionID' => 'empty_mailbox',
        'mailbox_token' => $mailbox_token
    ));
}

echo $hdr_view->render('header');

/* If no messages, exit immediately. */
if (empty($pageOb['end'])) {
    if ($pageOb['anymsg'] && isset($deleted_prompt)) {
        /* Show 'Show Deleted' prompt if mailbox has no viewable message but
         * has hidden, deleted messages. */
        $del_view = clone $view;
        $del_view->hide = Horde::widget(array(
            'url' => $refresh_url->copy()->add(array(
                'actionID' => 'hide_deleted',
                'mailbox_token' => $mailbox_token
            )),
            'class' => 'hideAction',
            'title' => $deleted_prompt
        ));
        if (IMP::mailbox()->access_expunge) {
            $del_view->purge = Horde::widget(array(
                'url' => $refresh_url->copy()->add(array(
                    'actionID' => 'expunge_mailbox',
                    'mailbox_token' => $mailbox_token
                )),
                'class' => 'purgeAction',
                'title' => _("Pur_ge Deleted")
            ));
        }

        echo $del_view->render('actions_deleted');
    }

    $empty_view = clone $view;
    $empty_view->search_mbox = $search_mbox;
    echo $empty_view->render('empty_mailbox');
    $page_output->footer();
    exit;
}

/* Display the navbar and actions if there is at least 1 message in mailbox. */
if ($pageOb['msgcount']) {
    $use_trash = $prefs->getValue('use_trash');

    /* Prepare the navbar template. */
    $n_view = clone $view;
    $n_view->id = 1;
    $n_view->forminput = Horde_Util::formInput();
    $n_view->readonly = $readonly;

    $filtermsg = false;
    if ($imp_imap->access(IMP_Imap::ACCESS_FLAGS)) {
        $args = array(
            'imap' => true,
            'mailbox' => $search_mbox ? null : IMP::mailbox()
        );

        $form_set = $form_unset = array();
        foreach ($imp_flags->getList($args) as $val) {
            if ($val->canset) {
                $form_set[] = array(
                    'f' => $val->form_set,
                    'l' => $val->label,
                    'v' => IMP_Mailbox::formTo($flag_filter_prefix . $val->form_set)
                );
                $form_unset[] = array(
                    'f' => $val->form_unset,
                    'l' => $val->label,
                    'v' => IMP_Mailbox::formTo($flag_filter_prefix . $val->form_unset)
                );
            }
        }

        $n_view->flaglist_set = $form_set;
        $n_view->flaglist_unset = $form_unset;

        if (!$search_mbox && $imp_imap->access(IMP_Imap::ACCESS_SEARCH)) {
            $filtermsg = $n_view->flag_filter = true;
        }
    }

    if (!$search_mbox && IMP::mailbox()->access_filters) {
        $filters = array();
        $imp_search->setIteratorFilter(IMP_Search::LIST_FILTER);
        foreach ($imp_search as $val) {
            $filters[] = array(
                'l' => htmlspecialchars($val->label),
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
    $n_view->mailbox = IMP::mailbox()->form_to;
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

    echo $n_view->render('navbar');

    /* Prepare the actions template. */
    $a_view = clone $view;
    if (IMP::mailbox()->access_deletemsgs) {
        $del_class = ($use_trash && IMP::mailbox()->trash)
            ? 'permdeleteAction'
            : 'deleteAction';
        $a_view->delete = Horde::widget(array(
            'url' => '#',
            'class' => $del_class,
            'title' => _("_Delete")
        ));
    }

    if ($showdelete['purge'] || IMP::mailbox()->vtrash) {
        $a_view->undelete = Horde::widget(array(
            'url' => '#',
            'class' => 'undeleteAction',
            'title' => _("_Undelete")
        ));
    }

    $mboxactions = array();
    if ($showdelete['purge']) {
        $mailbox_link = $mailbox_imp_url->copy()->add('page', $pageOb['page']);
        if (isset($deleted_prompt)) {
            $mboxactions[] = Horde::widget(array(
                'url' => $mailbox_link->copy()->add(array(
                    'actionID' => 'hide_deleted',
                    'mailbox_token' => $mailbox_token
                )),
                'class' => 'hideAction',
                'title' => $deleted_prompt
            ));
        }
        $mboxactions[] = Horde::widget(array(
            'url' => $mailbox_link->copy()->add(array(
                'actionID' => 'expunge_mailbox',
                'mailbox_token' => $mailbox_token
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
                'mailbox_token' => $mailbox_token
            )),
            'title' => _("Clear Sort")
        ));
    }

    if (IMP::mailbox()->templates) {
        $a_view->templateedit = Horde::widget(array(
            'url' => '#',
            'class' => 'templateeditAction',
            'title' => _("Edit Template")
        ));
        $mboxactions[] = Horde::widget(array(
            'url' => IMP::composeLink(array(), array(
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

    if (IMP::canCompose()) {
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

    if ($conf['spam']['reporting'] &&
        ($conf['spam']['spamfolder'] || !IMP::mailbox()->spam)) {
        $a_view->spam = Horde::widget(array(
            'url' => '#',
            'class' => 'spamAction',
            'title' => _("Report as Spam")
        ));
    }

    if ($conf['notspam']['reporting'] &&
        (!$conf['notspam']['spamfolder'] || IMP::mailbox()->spam)) {
        $a_view->notspam = Horde::widget(array(
            'url' => '#',
            'class' => 'notspamAction',
            'title' => _("Report as Innocent")
        ));
    }

    $a_view->view_messages = Horde::widget(array(
        'url' => '#',
        'class' => 'viewAction',
        'title' => _("View Messages")
    ));

    echo $a_view->render('actions');
}

/* Define some variables now so we don't have to keep redefining in the
   foreach () loop or the templates. */
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

/* If this is the Drafts or Sent-Mail mailbox, sort by To instead of From. */
if (IMP::mailbox()->special_outgoing) {
    unset($headers[Horde_Imap_Client::SORT_FROM]);
} else {
    unset($headers[Horde_Imap_Client::SORT_TO]);
}

/* Determine which of Subject/Thread to emphasize. */
if (!IMP::mailbox()->access_sortthread || $sortpref->sortby_locked) {
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
            'mailbox_token' => $mailbox_token,
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
                'mailbox_token' => $mailbox_token
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
                    'mailbox_token' => $mailbox_token,
                    'sortby' => $key
                )),
                'title' => $val['text']
            ));
    }
    $ptr['class'] = 'horde-split-left';
}

/* Output the form start. */
$f_view = clone $view;
$f_view->forminput = Horde_Util::formInput();
$f_view->mailbox = IMP::mailbox()->form_to;
$f_view->mailbox_token = $mailbox_token;
$f_view->mailbox_url = $mailbox_url;
$f_view->page = $pageOb['page'];
echo $f_view->render('form_start');

/* Prepare the message headers template. */
$mh_view = clone $view;
$mh_view->headers = $headers;

if (!$search_mbox) {
    $mh_view->show_checkbox = !$mh_count++;
    echo $mh_view->render('message_headers');
}

/* Initialize repetitively used variables. */
$fromlinkstyle = $prefs->getValue('from_link');
$imp_ui = new IMP_Ui_Mailbox(IMP::mailbox());

/* Display message information. */
$msgs = array();
$search_view = clone $view;
$summary_view = clone $view;

while (list(,$ob) = each($mbox_info['overview'])) {
    if ($search_mbox) {
        if (empty($lastMbox) || ($ob['mailbox'] != $lastMbox)) {
            if (!empty($lastMbox)) {
                _outputSummaries($msgs, $summary_view);
                $msgs = array();
            }

            $mbox = IMP_Mailbox::get($ob['mailbox']);

            $mbox_link = $mailbox_url->copy()->add('mailbox', IMP::base64urlEncode($ob['mailbox']));

            $search_view->mbox_link = Horde::link($mbox_link, sprintf(_("View messages in %s"), $mbox->display), 'smallheader') . $mbox->display_html . '</a>';
            echo $search_view->render('searchmbox');

            $mh_view->show_checkbox = !$mh_count++;
            echo $mh_view->render('message_headers');
        }
    }

    $lastMbox = $ob['mailbox'];

    /* Initialize the data fields. */
    $msg = array(
        'bg' => '',
        'class' => '',
        'date' => $imp_ui->getDate($ob['envelope']->date),
        'preview' => '',
        'status' => '',
        'size' => $imp_ui->getSize($ob['size']),
        'uid' => new IMP_Indices($ob['mailbox'], $ob['uid'])
    );

    /* Since this value will be used for an ID element, it cannot contain
     * certain characters.  Replace those unavailable chars with '_', and
     * double existing underscores to ensure we don't have a duplicate ID. */
    $msg['id'] = preg_replace('/[^0-9a-z\-_:\.]/i', '_', str_replace('_', '__', rawurlencode($ob['uid'] . $ob['mailbox'])));

    /* Generate the target link. */
    if (IMP::mailbox()->drafts || IMP::mailbox()->templates) {
        $target = IMP::composeLink(array(), array(
            'actionID' => (IMP::mailbox()->drafts ? 'draft' : 'template'),
            'thismailbox' => $ob['mailbox'],
            'uid' => $ob['uid']
        ));
    } else {
        $target = IMP::mailbox()->url('message.php', $ob['uid'], $ob['mailbox']);
    }

    /* Get all the flag information. */
    try {
        $ob['flags'] = array_merge($ob['flags'], Horde::callHook('msglist_flags', array($ob, 'imp'), 'imp'));
    } catch (Horde_Exception_HookNotSet $e) {}

    $flag_parse = $imp_flags->parse(array(
        'flags' => $ob['flags'],
        'headers' => $ob['headers'],
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
            if (!empty($strip_preview)) {
                $ptext = preg_replace(array('/\n/', '/(\s)+/'), array(' ', '$1'), str_replace("\r", "\n", $ob['preview']));
            } else {
                $ptext = str_replace("\r", '', $ob['preview']);
            }

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
            $from_tmp[] = call_user_func_array(array('Horde', $preview_tooltip ? 'linkTooltip' : 'link'), array(IMP::composeLink(array(), array('actionID' => 'mailto_link', 'to' => strval($from_ob))), sprintf(_("New Message to %s"), $from_ob->label))) . htmlspecialchars($from_ob->label, ENT_QUOTES, 'UTF-8') . '</a>';
        }

        if (!empty($from_tmp)) {
            $msg['from'] = implode(', ', $from_tmp);
        }
        break;

    case 1:
        $from_uri = IMP::mailbox()->url('message.php', $ob['uid'], $ob['mailbox']);
        $msg['from'] = Horde::link($from_uri) . $msg['from'] . '</a>';
        break;
    }

    /* Format the Subject: Header. */
    $msg['subject'] = $imp_ui->getSubject($ob['envelope']->subject, true);
    if ($preview_tooltip) {
        $msg['subject'] = substr(Horde::linkTooltip($target, $msg['preview'], '', '', '', $msg['preview']), 0, -1) . ' class="mboxSubject">' . $msg['subject'] . '</a>';
    } else {
        $msg['subject'] = substr(Horde::link($target, $imp_ui->getSubject($ob['envelope']->subject)), 0, -1) . ' class="mboxSubject">' . $msg['subject'] . '</a>' . (!empty($msg['preview']) ? '<br /><small>' . $msg['preview'] . '</small>' : '');
    }

    /* Add subject flags. */
    foreach ($subject_flags as $val) {
        $flag_label = Horde_String::truncate($val->label, 12);

        $msg['subject'] = '<span class="' . $val->css . '" style="' . ($val->bgdefault ? '' : 'background:' . htmlspecialchars($val->bgcolor) . ';') . 'color:' . htmlspecialchars($val->fgcolor) . '" title="' . htmlspecialchars($val->label) . '">' . htmlspecialchars($flag_label) . '</span>' . $msg['subject'];
    }

    /* Set up threading tree now. */
    if ($thread_sort) {
        $t_ob = $imp_mailbox[$ob['idx']]['t'];
        $msg['subject'] = ($sortpref->sortdir ? $t_ob->reverse_img : $t_ob->img) . ' ' . $msg['subject'];
    }

    $msgs[$ob['uid']] = $msg;
}

_outputSummaries($msgs, $summary_view);

echo '</form>';

/* If there are 20 messages or less, don't show the actions/navbar again. */
if (($pageOb['end'] - $pageOb['begin']) >= 20) {
    echo $a_view->render('actions');
    $n_view->id = 2;
    echo $n_view->render('navbar');
}

$page_output->footer();
