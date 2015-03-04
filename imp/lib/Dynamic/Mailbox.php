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
 * Mailbox page for dynamic view.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2012-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Dynamic_Mailbox extends IMP_Dynamic_Base
{
    /**
     */
    protected function _init()
    {
        global $injector, $page_output, $registry, $session;

        $page_output->addScriptFile('base.js');
        $page_output->addScriptFile('passphrase.js');
        $page_output->addScriptFile('viewport.js');
        $page_output->addScriptFile('external/CustomElements.js');
        $page_output->addScriptFile('external/time-elements.js');
        $page_output->addScriptFile('external/tinycon.js');
        $page_output->addScriptFile('dragdrop2.js', 'horde');
        $page_output->addScriptFile('form_ghost.js', 'horde');
        $page_output->addScriptFile('jstorage.js', 'horde');
        $page_output->addScriptFile('slider2.js', 'horde');
        $page_output->addScriptFile('toggle_quotes.js', 'horde');
        $page_output->addScriptPackage('Horde_Core_Script_Package_Dialog');
        $page_output->addScriptPackage('IMP_Script_Package_Imp');

        $page_output->addThemeStylesheet('mailbox.css');
        $page_output->addThemeStylesheet('message_view.css');

        $imp_imap = $injector->getInstance('IMP_Factory_Imap')->create();

        if ($imp_imap->access(IMP_Imap::ACCESS_FLAGS)) {
            $page_output->addScriptFile('colorpicker.js', 'horde');
            $this->view->picker_img = Horde_Themes_Image::tag('colorpicker.png', array('alt' => _("Color Picker")));
        }

        if ($imp_imap->access(IMP_Imap::ACCESS_REMOTE)) {
            $page_output->addScriptFile('external/base64.js');
        }

        $this->_addMailboxVars();

        $this->view->show_innocent = !empty($imp_imap->config->innocent_params);
        $this->view->show_search = $imp_imap->access(IMP_Imap::ACCESS_SEARCH);
        $this->view->show_spam = !empty($imp_imap->config->spam_params);

        $impSubinfo = new Horde_View(array(
            'templatePath' => IMP_TEMPLATES . '/dynamic'
        ));
        $impSubinfo->addHelper('Text');
        $impSubinfo->quota = (bool)$imp_imap->config->quota;

        $topbar = $injector->getInstance('Horde_View_Topbar');
        $topbar->search = $this->view->show_search;
        $topbar->searchMenu = true;
        $topbar->subinfo = $impSubinfo->render('mailbox_subinfo');
        $page_output->topbar = true;

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
            )
        );
        if ($imp_imap->isImap()) {
            $impSidebar->containers[] = array(
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
            );
            $impSidebar->containers[] = array(
                'id' => 'imp-normalmboxes'
            );
        }

        $sidebar = $injector->getInstance('Horde_View_Sidebar');
        $sidebar->newLink = $blank->link(array('id' => 'composelink',
                                               'class' => 'icon'));
        $sidebar->newText = _("New Message");
        $sidebar->content = $impSidebar->render('sidebar');

        $this->view->sidebar = $sidebar->render();

        $this->view->max_fsize = intval($session->get('imp', 'file_upload'));

        $page_output->noDnsPrefetch();

        $this->_pages[] = 'mailbox';
    }

    /**
     */
    public static function url(array $opts = array())
    {
        return Horde::url('dynamic.php')->add('page', 'mailbox');
    }

    /**
     */
    protected function _addMailboxVars()
    {
        global $conf, $injector, $prefs, $registry;

        /* Does server support ACLs? */
        $imp_imap = $injector->getInstance('IMP_Factory_Imap')->create();
        $acl = $imp_imap->access(IMP_Imap::ACCESS_ACL);

        $subscribe = $prefs->getValue('subscribe');

        $this->js_conf += array_filter(array(
            // URLs
            'URI_LISTINFO' => strval(IMP_Basic_Listinfo::url()->setRaw(true)),
            'URI_MESSAGE' => strval(IMP_Dynamic_Message::url()->setRaw(true)),
            'URI_PORTAL' => strval($registry->getServiceLink('portal')->setRaw(true)),
            'URI_PREFS_IMP' => strval($registry->getServiceLink('prefs', 'imp')->setRaw(true)),
            'URI_SEARCH' => strval(IMP_Basic_Search::url()->setRaw(true)),
            'URI_THREAD' => strval(IMP_Basic_Thread::url()->setRaw(true)),

            // IMAP Flags
            'FLAG_DELETED' => Horde_Imap_Client::FLAG_DELETED,
            'FLAG_DRAFT' => Horde_Imap_Client::FLAG_DRAFT,
            'FLAG_INNOCENT' => Horde_Imap_Client::FLAG_NOTJUNK,
            'FLAG_SEEN' => Horde_Imap_Client::FLAG_SEEN,
            'FLAG_SPAM' => Horde_Imap_Client::FLAG_JUNK,

            // Message list templates
            'msglist_template_horiz' => file_get_contents(IMP_TEMPLATES . '/dynamic/msglist_horiz.html'),
            'msglist_template_vert' => file_get_contents(IMP_TEMPLATES . '/dynamic/msglist_vert.html'),

            // Other variables
            'acl' => $acl,
            'filter_any' => intval($prefs->getValue('filter_any_mailbox')),
            'fsearchid' => IMP_Mailbox::formTo(IMP_Search::MBOX_PREFIX . IMP_Search::FILTERSEARCH),
            'initial_page' => is_null($initial_page = IMP::getInitialPage()->mbox) ? null : $initial_page->form_to,
            'mbox_expand' => intval($prefs->getValue('nav_expanded') == 2),
            'name' => $registry->get('name', 'imp'),
            'poll_alter' => intval(!$prefs->isLocked('nav_poll') && !$prefs->getValue('nav_poll_all')),
            'qsearchid' => IMP_Mailbox::formTo(IMP_Search::MBOX_PREFIX . IMP_Search::QUICKSEARCH),
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
                'msgarrival' => array(
                    'c' => 'msgDate',
                    'v' => Horde_Imap_Client::SORT_ARRIVAL
                ),
                'msgdate' => array(
                    'c' => 'msgDate',
                    'v' => Horde_Imap_Client::SORT_DATE
                ),
                'sequence' => array(
                    'v' => Horde_Imap_Client::SORT_SEQUENCE
                ),
                'size' => array(
                    'c' => 'msgSize',
                    't' => _("Size"),
                    'v' => Horde_Imap_Client::SORT_SIZE
                )
            ),
            'subscribe' => intval($subscribe)
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
                '_sep3' => null,
                'expand' => _("Expand All"),
                'collapse' => _("Collapse All")
            ),
            'ctx_datesort' => array(
                '*msgarrival' => _("Arrival Time"),
                '*msgdate' => _("Message Date")
            ),
            'ctx_flag' => array(),
            'ctx_flagunset' => array(),
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
            'ctx_remoteauth' => array(
                '_mbox' => '',
                '_sep1' => null,
                'create' => _("Create Mailbox"),
                'logout' => _("Log Out")
            ),
            'ctx_sortopts' => array(
                'from' => _("From"),
                'to' => _("To"),
                'subject' => _("Subject"),
                'thread' => _("Thread"),
                'msgarrival' => _("Date (Arrival)"),
                'msgdate' => _("Date (Message)"),
                'size' => _("Size"),
                '_sep1' => null,
                'sequence' => _("No Sort")
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
        if ($imp_imap->access(IMP_Imap::ACCESS_FOLDERS)) {
            $context['ctx_folderopts'] = array(
                'new' => _("Create Mailbox"),
                'sub' => _("Hide Unsubscribed"),
                'unsub' => _("Show All Mailboxes"),
                'expand' => _("Expand All"),
                'collapse' => _("Collapse All"),
                '_sep1' => null,
                'reload' => _("Rebuild Folder List")
            );
        }

        if (!$subscribe) {
            unset(
                $context['ctx_folderopts']['sub'],
                $context['ctx_folderopts']['unsub']
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
            'addfilter' => _("Create Filter"),
            'delete' => _("Delete"),
            'undelete' => _("Undelete"),
            '_sub3' => array(
                '_sep3' => null,
                'source' => _("View Source")
            )
        );

        if (empty($imp_imap->config->spam_params)) {
            unset($context['ctx_message']['spam']);
        }
        if (empty($imp_imap->config->innocent_params)) {
            unset($context['ctx_message']['innocent']);
        }
        if (!$registry->hasLink('mail/newEmailFilter')) {
            unset($context['ctx_message']['addfilter']);
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
            '_sub1' => array(
                '_sep5' => null,
                'expand' => _("Expand All"),
                'collapse' => _("Collapse All")
            ),
            '_sep6' => null,
            'export' => _("Export"),
            'import' => _("Import"),
            '_sep7' => null,
            'size' => _("Mailbox Size"),
            '_sub2' => array(
                '_sep8' => null,
                'acl' => _("Edit ACL")
            ),
        );

        if (!$subscribe) {
            unset(
                $context['ctx_mbox']['sub'],
                $context['ctx_mbox']['unsub']
            );
        }
        if (!$imp_imap->access(IMP_Imap::ACCESS_IMPORT)) {
            unset($context['ctx_mbox']['import']);
        }
        if (!$imp_imap->access(IMP_Imap::ACCESS_FLAGS)) {
            unset(
                $context['ctx_mbox']['_sep2'],
                $context['ctx_mbox']['setflag'],
                $context['ctx_mbox']['_sep3'],
                $context['ctx_mbox']['poll'],
                $context['ctx_mbox']['nopoll']
            );
        }
        if (!$imp_imap->access(IMP_Imap::ACCESS_FOLDERS)) {
            unset(
                $context['ctx_mbox']['_sep7'],
                $context['ctx_mbox']['size']
            );
        }
        if (!$imp_imap->access(IMP_Imap::ACCESS_SEARCH)) {
            unset(
                $context['ctx_mbox']['_sep4'],
                $context['ctx_mbox']['search']
            );
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
            '_sub3' => array(
                '_sep3' => null,
                'clear_sort' => _("Clear Sort")
            )
        );
        if ($prefs->isLocked('delhide')) {
            unset($context['ctx_oa']['hide_deleted']);
        }

        /* Shared between message and other actions menus. */
        if (!$registry->hasMethod('mail/blacklistFrom')) {
            unset(
                $context['ctx_message']['blacklist'],
                $context['ctx_oa']['blacklist']
            );
        }
        if (!$registry->hasMethod('mail/whitelistFrom')) {
            unset(
                $context['ctx_message']['whitelist'],
                $context['ctx_oa']['whitelist']
            );
        }

        /* Preview context menu. */
        $context['ctx_preview'] = array(
            'save' => _("Save"),
            'viewsource' => _("View Source"),
            'allparts' => _("All Parts"),
            'thread' => _("View Thread"),
            'listinfo' => _("List Info")
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
            if (IMP_Filter::canApplyFilters()) {
                $context['ctx_filteropts']['_sub1'] = array(
                    '_sep1' => null,
                    'applyfilters' => _("Apply Filters")
                );
            }

            $context['ctx_qsearchopts'] = array(
                '*all' => _("Entire Message"),
                '*body' => _("Body"),
                '*from' => _("From"),
                '*recip' => _("Recipients (To/Cc/Bcc)"),
                '*subject' => _("Subject"),
                '_sep1' => null,
                '*advanced' => _("Advanced Search...")
            );
            /* Generate filter array. */
            $iterator = IMP_Search_IteratorFilter::create(
                IMP_Search_IteratorFilter::FILTER
            );

            $context['ctx_filter'] = array();
            foreach ($iterator as $val) {
                if ($val->enabled) {
                    $context['ctx_filter']['*' . $val->id] = $val->label;
                }
            }
        }

        /* Remote accounts context menu. */
        if ($imp_imap->access(IMP_Imap::ACCESS_REMOTE)) {
            $context['ctx_rcontainer'] = array(
                '*prefs' => _("Manage Remote Accounts")
            );
        }

        $this->js_context = array_merge($context, $this->js_context);

        $this->js_text += array(
            'atc_downloadall' => _("Download All (%s)"),
            'badaddr' => _("Invalid Address"),
            'badsubject' => _("Invalid Subject"),
            'baselevel' => _("base level of the folder tree"),
            'check' => _("Checking..."),
            'copyto' => _("Copy %s to %s"),
            'create_prompt' => _("Create mailbox:"),
            'createsub_prompt' => _("Create subfolder of %s:"),
            'delete_mbox' => _("Permanently delete %s?"),
            'download_mbox' => _("All messages in this mailbox will be downloaded into the format that you choose. Depending on the size of the mailbox, this action may take awhile."),
            'empty_mbox' => _("Permanently delete all %d messages in %s?"),
            'import_mbox' => _("Mbox or .eml file:"),
            'import_mbox_loading' => _("Importing (this may take some time)..."),
            'listmsg_wait' => _("The server is still generating the message list."),
            'listmsg_timeout' => _("The server was unable to generate the message list."),
            'loading' => _("Loading..."),
            'message_0' => _("No messages"),
            'message_1' => _("1 message"),
            'message_2' => _("%d messages"),
            'mboxsize' => _("%s is: %s."),
            'moveto' => _("Move %s to %s"),
            'newflag_name' => _("Flag Name:"),
            'newflag_wait' => _("Creating New Flag..."),
            'onlogout' => _("Logging Out..."),
            'portal' => _("Portal"),
            'prefs' => _("User Options"),
            'remote_password' => _("Password for %s:"),
            'rename_prompt' => _("Rename %s to:"),
            'search' => _("Search"),
            'search_input' => _("Search (%s)"),
            'search_time' => _("Results are %d Minutes Old"),
            'selected' => _("%s selected."),
            'slidertext' => _("Messages %d - %d"),
            'vfolder' => _("Virtual Folder: %s"),
            'vp_empty' => _("There are no messages in this mailbox."),
            'vp_empty_search' => _("No messages matched the search query.")
        );

        if ($subscribe) {
            $this->js_text += array(
                'subscribe_mbox' => _("Subscribe to %s?"),
                'subscribe_mbox_subfolders' => _("Subscribe to all subfolders of %s?"),
                'unsubscribe_mbox' => _("Unsubscribe to %s?"),
                'unsubscribe_mbox_subfolders' => _("Unsubscribe to all subfolders of %s?"),
            );
        }
    }

}
