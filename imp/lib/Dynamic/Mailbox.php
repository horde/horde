<?php
/**
 * Mailbox page for dynamic view.
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
class IMP_Dynamic_Mailbox extends IMP_Dynamic_Base
{
    /**
     */
    public $growlerLog = true;

    /**
     * @var boolean
     */
    public $topbar = true;

    /**
     */
    protected function _init()
    {
        global $browser, $conf, $injector, $page_output, $registry, $session;

        $page_output->addScriptFile('dimpbase.js');
        $page_output->addScriptFile('passphrase.js');
        $page_output->addScriptFile('viewport.js');
        $page_output->addScriptFile('dragdrop2.js', 'horde');
        $page_output->addScriptFile('form_ghost.js', 'horde');
        $page_output->addScriptFile('jstorage.js', 'horde');
        $page_output->addScriptFile('slider2.js', 'horde');
        $page_output->addScriptFile('toggle_quotes.js', 'horde');
        $page_output->addScriptPackage('Dialog');
        $page_output->addScriptPackage('IMP_Script_Package_Imp');

        $this->_addMailboxVars();

        $imp_imap = $injector->getInstance('IMP_Factory_Imap')->create();

        $this->view->filter_avail = IMP::applyFilters();
        $this->view->show_folders = $imp_imap->access(IMP_Imap::ACCESS_FOLDERS);
        $this->view->show_notspam = !empty($conf['notspam']['reporting']);
        $this->view->show_search = $imp_imap->access(IMP_Imap::ACCESS_SEARCH);
        $this->view->show_spam = !empty($conf['spam']['reporting']);

        $this->view->is_opera = $browser->isBrowser('opera');

        $impSubinfo = new Horde_View(array(
            'templatePath' => IMP_TEMPLATES . '/dynamic'
        ));
        $impSubinfo->addHelper('Text');
        $impSubinfo->quota = $session->get('imp', 'imap_quota');

        $topbar = $GLOBALS['injector']->getInstance('Horde_View_Topbar');
        $topbar->search = $this->view->show_search;
        $topbar->searchMenu = true;
        $topbar->subinfo = $impSubinfo->render('mailbox_subinfo');

        $blank = new Horde_Url();
        $impSidebar = new Horde_View(array(
            'templatePath' => array(
                $registry->get('templates', 'horde') . '/sidebar',
                IMP_TEMPLATES . '/dynamic'
            )
        ));
        $impSidebar->addHelper('Text');
        $impSidebar->containers = array(
            array(
                'id' => 'imp-specialmboxes'
            ),
            array(
                'rows' => array(
                    array(
                        'id' => 'folderopts_link',
                        'cssClass' => 'folderoptsImg',
                        'link' => $blank->link() . _("Folder Actions") . '</a>'
                    ),
                    array(
                        'id' => 'dropbase',
                        'style' => 'display:none',
                        'cssClass' => 'folderImg',
                        'link' => $blank->link() . _("Move to Base Level") . '</a>'
                    )
                )
            ),
            array(
                'id' => 'imp-normalmboxes'
            )
        );

        $sidebar = $GLOBALS['injector']->getInstance('Horde_View_Sidebar');
        $sidebar->newLink = $blank->link(array('id' => 'composelink',
                                               'class' => 'icon'));
        $sidebar->newText = _("New Message");
        $sidebar->content = $impSidebar->render('sidebar');

        $this->view->sidebar = $sidebar->render();

        $page_output->noDnsPrefetch();

        $this->_pages[] = 'mailbox';
    }

    /**
     */
    static public function url(array $opts = array())
    {
        return Horde::url('dynamic.php')->add('page', 'mailbox');
    }

    /**
     */
    protected function _addMailboxVars()
    {
        global $conf, $injector, $prefs, $registry;

        /* Generate flag array. */
        $flags = array();
        foreach ($injector->getInstance('IMP_Flags')->getList() as $val) {
            $flags[$val->id] = array_filter(array(
                // Indicate a flag that can be *a*ltered
                'a' => $val->canset,
                'b' => $val->bgdefault ? null : $val->bgcolor,
                'c' => $val->css,
                'f' => $val->fgcolor,
                'i' => $val->css ? null : $val->cssicon,
                'l' => $val->label,
                // Indicate a flag that can be *s*earched for
                's' => intval($val instanceof IMP_Flag_Imap),
                // Indicate a *u*ser flag
                'u' => intval($val instanceof IMP_Flag_User)
            ));
        }

        /* Does server support ACLs? */
        try {
            $injector->getInstance('IMP_Imap_Acl');
            $acl = true;
        } catch (IMP_Exception $e) {
            $acl = false;
        }

        $this->js_conf += array_filter(array(
            // URLs
            'URI_MESSAGE' => strval(IMP_Dynamic_Message::url()->setRaw(true)),
            'URI_PORTAL' => strval($registry->getServiceLink('portal')->setRaw(true)),
            'URI_PREFS_IMP' => strval($registry->getServiceLink('prefs', 'imp')->setRaw(true)),
            'URI_SEARCH' => strval(Horde::url('search.php')),

            // IMAP Flags
            'FLAG_DELETED' => Horde_Imap_Client::FLAG_DELETED,
            'FLAG_DRAFT' => Horde_Imap_Client::FLAG_DRAFT,
            'FLAG_SEEN' => Horde_Imap_Client::FLAG_SEEN,

            // Message list templates
            'msglist_template_horiz' => file_get_contents(IMP_TEMPLATES . '/dynamic/msglist_horiz.html'),
            'msglist_template_vert' => file_get_contents(IMP_TEMPLATES . '/dynamic/msglist_vert.html'),

            // Other variables
            'acl' => $acl,
            'filter_any' => intval($prefs->getValue('filter_any_mailbox')),
            'fixed_mboxes' => empty($conf['server']['fixed_folders'])
                ? array()
                : array_map(array('IMP_Mailbox', 'formTo'), array_map(array('IMP_Mailbox', 'prefFrom'), $conf['server']['fixed_folders'])),
            'flags' => $flags,
            /* Needed to maintain flag ordering. */
            'flags_o' => array_keys($flags),
            'fsearchid' => IMP_Mailbox::formTo(IMP_Search::MBOX_PREFIX . IMP_Search::DIMP_FILTERSEARCH),
            'initial_page' => IMP_Auth::getInitialPage()->mbox->form_to,
            'innocent_spammbox' => intval(!empty($conf['notspam']['spamfolder'])),
            'mbox_expand' => intval($prefs->getValue('nav_expanded') == 2),
            'name' => $registry->get('name', 'imp'),
            'poll_alter' => intval(!$prefs->isLocked('nav_poll') && !$prefs->getValue('nav_poll_all')),
            'qsearchid' => IMP_Mailbox::formTo(IMP_Search::MBOX_PREFIX . IMP_Search::DIMP_QUICKSEARCH),
            'refresh_time' => intval($prefs->getValue('refresh_time')),
            'sidebar_width' => max(intval($prefs->getValue('sidebar_width')), 150),
            'sort' => array(
                'from' => array(
                    'c' => 'msgFrom',
                    't' => _("From"),
                    'v' => Horde_Imap_Client::SORT_FROM
                ),
                'to' => array(
                    'c' => 'msgFrom',
                    'ec' => 'msgFromTo',
                    't' => _("To"),
                    'v' => Horde_Imap_Client::SORT_TO
                ),
                'subject' => array(
                    'c' => 'msgSubject',
                    't' => _("Subject"),
                    'v' => Horde_Imap_Client::SORT_SUBJECT
                ),
                'thread' => array(
                    'c' => 'msgSubject',
                    'v' => Horde_Imap_Client::SORT_THREAD
                ),
                'date' => array(
                    'c' => 'msgDate',
                    't' => _("Date"),
                    'v' => IMP::IMAP_SORT_DATE
                ),
                'sequence' => array(
                    'c' => 'msgDate',
                    'v' => Horde_Imap_Client::SORT_SEQUENCE
                ),
                'size' => array(
                    'c' => 'msgSize',
                    't' => _("Size"),
                    'v' => Horde_Imap_Client::SORT_SIZE
                )
            ),
            'spam_spammbox' => intval(!empty($conf['spam']['spamfolder']))
        ));

        $context = array(
            'ctx_container' => array(
                '_mbox' => '',
                '_sep1' => null,
                'create' => _("Create subfolder"),
                'rename' => _("Rename"),
                'delete' => _("Delete subfolders"),
                '_sep2' => null,
                'search' => _("Search"),
                'searchsub' => _("Search All Subfolders"),
                '_sep3' => null,
                'expand' => _("Expand All"),
                'collapse' => _("Collapse All")
            ),
            'ctx_datesort' => array(
                '*sequence' => _("Arrival Time"),
                '*date' => _("Message Date")
            ),
            'ctx_flag' => array(),
            'ctx_flag_search' => array(),
            'ctx_mbox_flag' => array(
                'seen' => _("Seen"),
                'unseen' => _("Unseen")
            ),
            'ctx_noactions' => array(
                '_mbox' => '',
                '_sep1' => null,
                'noaction' => _("No actions available")
            ),
            'ctx_sortopts' => array(
                'from' => _("From"),
                'to' => _("To"),
                'subject' => _("Subject"),
                'thread' => _("Thread"),
                'date' => _("Date"),
                'size' => ("Size"),
                '_sep1' => null,
                'sequence' => _("Arrival (No Sort)")
            ),
            'ctx_subjectsort' => array(
                'thread' => _("Thread Sort"),
            ),
            'ctx_template' => array(
                'edit' => _("Edit Template"),
                'new' => _("Create New Template")
            ),
            'ctx_vcontainer' => array(
                '_mbox' => _("Virtual Folders"),
                '_sep1' => null,
                'edit' => _("Edit Virtual Folders")
            ),
            'ctx_vfolder' => array(
                '_mbox' => '',
                '_sep1' => null,
                'edit' => _("Edit Virtual Folder"),
                'delete' => _("Delete Virtual Folder")
            )
        );

        /* Folder options context menu. */
        $imp_imap = $injector->getInstance('IMP_Factory_Imap')->create();
        if ($imp_imap->access(IMP_Imap::ACCESS_FOLDERS)) {
            $context['ctx_folderopts'] = array(
                'new' => _("New Mailbox"),
                'sub' => _("Hide Unsubscribed"),
                'unsub' => _("Show All Mailboxes"),
                'expand' => _("Expand All"),
                'collapse' => _("Collapse All"),
                '_sep1' => null,
                'reload' => _("Rebuild Folder List")
            );
        }

        /* Message context menu. */
        $context['ctx_message'] = array(
            '_sub1' => array(
                'resume' => _("Resume Draft"),
                'template' => _("Use Template"),
                'template_edit' => _("Edit Template"),
                'view' => _("View Message")
            ),
            'reply' => _("Reply"),
            'forward' => _("Forward"),
            'editasnew' => _("Edit as New"),
            '_sub2' => array(
                '_sep1' => null,
                'setflag' => _("Mark as") . '...',
                'unsetflag' => _("Unmark as") . '...',
            ),
            '_sep2' => null,
            'spam' => _("Report as Spam"),
            'innocent' => _("Report as Innocent"),
            'blacklist' => _("Blacklist"),
            'whitelist' => _("Whitelist"),
            'delete' => _("Delete"),
            'undelete' => _("Undelete"),
            '_sub3' => array(
                '_sep3' => null,
                'source' => _("View Source")
            )
        );

        if (empty($conf['spam']['reporting'])) {
            unset($context['ctx_message']['spam']);
        }
        if (empty($conf['notspam']['reporting'])) {
            unset($context['ctx_message']['innocent']);
        }
        if (!$registry->hasMethod('mail/blacklistFrom')) {
            unset($context['ctx_message']['blacklist']);
        }
        if (!$registry->hasMethod('mail/whitelistFrom')) {
            unset($context['ctx_message']['whitelist']);
        }
        if ($prefs->getValue('use_trash')) {
            unset($context['ctx_message']['undelete']);
        }
        if (empty($conf['user']['allow_view_source'])) {
            unset($context['ctx_message']['_sub3']);
        }

        /* Mailbox context menu. */
        $context['ctx_mbox'] = array(
            '_mbox' => '',
            '_sep1' => null,
            'create' => _("Create subfolder"),
            'rename' => _("Rename"),
            'empty' => _("Empty"),
            'delete' => _("Delete"),
            '_sep2' => null,
            'setflag' => _("Mark all as"),
            '_sep3' => null,
            'poll' => _("Check for New Mail"),
            'nopoll' => _("Do Not Check for New Mail"),
            'sub' => _("Subscribe"),
            'unsub' => _("Unsubscribe"),
            '_sep4' => null,
            'search' => _("Search"),
            'searchsub' => _("Search All Subfolders"),
            '_sub1' => array(
                '_sep5' => null,
                'expand' => _("Expand All"),
                'collapse' => _("Collapse All")
            ),
            '_sep6' => null,
            'export' => _("Export"),
            'import' => _("Import"),
            '_sub2' => array(
                '_sep7' => null,
                'acl' => _("Edit ACL")
            )
        );

        $context['ctx_mbox_exportopts'] = array(
            '*mbox' => _("Mbox"),
            '*zip' => _("Zip")
        );

        if (!$prefs->getValue('subscribe')) {
            unset($context['ctx_mbox']['sub'], $context['ctx_mbox']['unsub']);
        }

        /* Other Actions context menu. */
        $context['ctx_oa'] = array(
            'preview_hide' => _("Hide Preview"),
            'preview_show' => _("Show Preview"),
            'layout_horiz' => _("Horizontal Layout"),
            'layout_vert' => _("Vertical Layout"),
            '_sub1' => array(
                '_sep1' => null,
                'setflag' => _("Mark as") . '...',
                'unsetflag' => _("Unmark as") . '...',
            ),
            'blacklist' => _("Blacklist"),
            'whitelist' => _("Whitelist"),
            '_sub2' => array(
                '_sep2' => null,
                'purge_deleted' => _("Purge Deleted"),
                'undelete' => _("Undelete")
            ),
            'show_deleted' => _("Show Deleted"),
            'hide_deleted' => _("Hide Deleted"),
            '_sep3' => null,
            'help' => _("Help")
        );
        if ($prefs->getValue('use_trash')) {
            unset($context['ctx_oa']['_sub2']);
        }
        if ($prefs->isLocked('delhide')) {
            unset($context['ctx_oa']['hide_deleted']);
        }

        /* Preview context menu. */
        $context['ctx_preview'] = array(
            'save' => _("Save"),
            'viewsource' => _("View Source"),
            'allparts' => _("All Parts")
        );

        if (empty($conf['user']['allow_view_source'])) {
            unset($context['ctx_preview']['viewsource']);
        }

        /* Search related context menus. */
        if ($imp_imap->access(IMP_Imap::ACCESS_SEARCH)) {
            $context['ctx_filteropts'] = array(
                '*filter' => _("Filter By"),
                '*flag' => _("Show Only"),
                '*flagnot' => _("Don't Show")
            );
            $context['ctx_qsearchby'] = array(
                '*all' => _("Entire Message"),
                '*body' => _("Body"),
                '*from' => _("From"),
                '*recip' => _("Recipients (To/Cc/Bcc)"),
                '*subject' => _("Subject")
            );
            $context['ctx_qsearchopts'] = array(
                '*by' => _("Search By"),
                '_sep1' => null,
                '*advanced' => _("Advanced Search...")
            );
            /* Generate filter array. */
            $imp_search = $injector->getInstance('IMP_Search');
            $imp_search->setIteratorFilter(IMP_Search::LIST_FILTER);

            $context['ctx_filter'] = array();
            foreach (iterator_to_array($imp_search) as $key => $val) {
                if ($val->enabled) {
                    $context['ctx_filter']['*' . $key] = $val->label;
                }
            }
        }

        $this->js_context = array_merge($context, $this->js_context);

        $this->js_text += array(
            'badaddr' => _("Invalid Address"),
            'badsubject' => _("Invalid Subject"),
            'baselevel' => _("base level of the folder tree"),
            'check' => _("Checking..."),
            'copyto' => _("Copy %s to %s"),
            'create_prompt' => _("Create mailbox:"),
            'createsub_prompt' => _("Create subfolder of %s:"),
            'delete_mbox' => _("Permanently delete %s?"),
            'delete_mbox_subfolders' => _("Delete all subfolders of %s?"),
            'download_mbox' => _("All messages in this mailbox will be downloaded into one MBOX file. This may take some time. Are you sure you want to continue?"),
            'empty_mbox' => _("Permanently delete all %d messages in %s?"),
            'hidealog' => Horde::highlightAccessKey(_("Hide Alerts _Log"), Horde::getAccessKey(_("Alerts _Log"), true)),
            'import_mbox' => _("Mbox or .eml file:"),
            'listmsg_wait' => _("The server is still generating the message list."),
            'listmsg_timeout' => _("The server was unable to generate the message list."),
            'message' => _("Message"),
            'messages' => _("Messages"),
            'moveto' => _("Move %s to %s"),
            'nomessages' => _("No Messages"),
            'onlogout' => _("Logging Out..."),
            'portal' => _("Portal"),
            'prefs' => _("User Options"),
            'rename_prompt' => _("Rename %s to:"),
            'search' => _("Search"),
            'search_time' => _("Results are %d Minutes Old"),
            'selected' => _("selected"),
            'vfolder' => _("Virtual Folder: %s"),
            'vp_empty' => _("There are no messages in this mailbox."),
            'vp_empty_search' => _("No messages matched the search query.")
        );
    }

}
