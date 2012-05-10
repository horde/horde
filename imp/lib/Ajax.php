<?php
/**
 * IMP wrapper for the base AJAX framework handler.
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
class IMP_Ajax
{
    /**
     * Javascript variables to output to the page.
     *
     * @var array
     */
    protected $_jsvars = array();

    /**
     * @param string $page   Either 'compose', 'main', or 'message'.
     * @param string $title  The title of the page.
     */
    public function init($page, $title = '')
    {
        global $injector, $page_output, $prefs;

        $this->_addBaseVars();

        $page_output->addScriptFile('dimpcore.js');
        $page_output->addScriptFile('indices.js');
        $page_output->addScriptFile('contextsensitive.js', 'horde');

        switch ($page) {
        case 'compose':
            $page_output->addScriptFile('compose-base.js');
            $page_output->addScriptFile('compose-dimp.js');
            $page_output->addScriptFile('md5.js', 'horde');
            $page_output->addScriptFile('textarearesize.js', 'horde');

            if (!$prefs->isLocked('default_encrypt') &&
                ($prefs->getValue('use_pgp') ||
                 $prefs->getValue('use_smime'))) {
                $page_output->addScriptFile('redbox.js', 'horde');
                $page_output->addScriptFile('dialog.js', 'horde');
            }

            $this->_addComposeVars();
            break;

        case 'main':
            $page_output->addScriptFile('dimpbase.js');
            $page_output->addScriptFile('imp.js');
            $page_output->addScriptFile('imageunblock.js');
            $page_output->addScriptFile('itiprequest.js');
            $page_output->addScriptFile('mailbox-dimp.js');
            $page_output->addScriptFile('viewport.js');
            $page_output->addScriptFile('dragdrop2.js', 'horde');
            $page_output->addScriptFile('form_ghost.js', 'horde');
            $page_output->addScriptFile('jstorage.js', 'horde');
            $page_output->addScriptFile('redbox.js', 'horde');
            $page_output->addScriptFile('dialog.js', 'horde');
            $page_output->addScriptFile('slider2.js', 'horde');
            $page_output->addScriptFile('toggle_quotes.js', 'horde');

            if ($prefs->getValue('use_pgp') ||
                $prefs->getValue('use_smime')) {
                $page_output->addScriptFile('importencryptkey.js');
            }
            break;

        case 'message':
            $page_output->addScriptFile('message-dimp.js');
            $page_output->addScriptFile('imp.js');
            $page_output->addScriptFile('imageunblock.js');
            $page_output->addScriptFile('itiprequest.js');
            $page_output->addScriptFile('textarearesize.js', 'horde');
            $page_output->addScriptFile('toggle_quotes.js', 'horde');

            if ($prefs->getValue('use_pgp') ||
                $prefs->getValue('use_smime')) {
                $page_output->addScriptFile('importencryptkey.js');
            }

            if (IMP::canCompose()) {
                $page_output->addScriptFile('compose-base.js');
                $page_output->addScriptFile('compose-dimp.js');
                $page_output->addScriptFile('md5.js', 'horde');

                if (!$prefs->isLocked('default_encrypt') &&
                    ($prefs->getValue('use_pgp') ||
                     $prefs->getValue('use_smime'))) {
                    $page_output->addScriptFile('redbox.js', 'horde');
                    $page_output->addScriptFile('dialog.js', 'horde');
                }

                $this->_addComposeVars();
            }
            break;
        }

        $page_output->addInlineJsVars(array(
            'var DIMP' => $this->_jsvars
        ), array('top' => true));

        $page_output->header(array(
            'growler_log' => ($page == 'main'),
            'title' => $title
        ));
    }

    /**
     * Add base javascript variables to the page.
     */
    protected function _addBaseVars()
    {
        global $conf, $injector, $prefs, $registry;

        $code = $flags = array();

        /* Generate flag array. */
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

        /* Variables used in core javascript files. */
        $this->_jsvars['conf'] = array_filter(array(
            // URL variables
            'URI_COMPOSE' => strval(Horde::url('compose-dimp.php')->setRaw(true)->add('ajaxui', 1)),
            'URI_DIMP' => strval(Horde::url('index-dimp.php')),
            'URI_MESSAGE' => strval(Horde::url('message-dimp.php')->setRaw(true)->add('ajaxui', 1)),
            'URI_PORTAL' => strval(Horde::getServiceLink('portal')->setRaw(true)->add('ajaxui', 1)),
            'URI_PREFS_IMP' => strval(Horde::getServiceLink('prefs', 'imp')->setRaw(true)->add('ajaxui', 1)),
            'URI_SEARCH' => strval(Horde::url('search.php')),
            'URI_VIEW' => strval(Horde::url('view.php')),

            'FLAG_DELETED' => Horde_Imap_Client::FLAG_DELETED,
            'FLAG_DRAFT' => Horde_Imap_Client::FLAG_DRAFT,
            'FLAG_SEEN' => Horde_Imap_Client::FLAG_SEEN,

            // Other variables
            'acl' => $acl,
            'disable_compose' => !IMP::canCompose(),
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
            'pop3' => intval($injector->getInstance('IMP_Factory_Imap')->create()->pop3),
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

        /* Context menu definitions.
         * Keys:
         *   - Begin with '_mbox': A mailbox name container entry
         *   - Begin with '_sep': A separator
         *   - Begin with '_sub': All subitems wrapped in a DIV
         *   - Begin with a '*': No icon
         */
        $context = array(
            'ctx_contacts' => array(
                'new' => _("New Message"),
                'add' => _("Add to Address Book")
            ),
            'ctx_reply' => array(
                'reply' => _("To Sender"),
                'reply_all' => _("To All"),
                'reply_list' => _("To List")
            ),

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
                'sequence' => _("Arrival Time"),
                'date' => _("Message Date")
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

        /* Forward context menu. */
        $context['ctx_forward'] = array(
            'attach' => _("As Attachment"),
            'body' => _("In Body Text"),
            'both' => _("Attachment and Body Text"),
            '_sep1' => null,
            'editasnew' => _("Edit as New"),
            '_sep2' => null,
            'redirect' => _("Redirect")
        );
        if ($prefs->isLocked('forward_default')) {
            unset(
                $context['ctx_forward']['attach'],
                $context['ctx_forward']['body'],
                $context['ctx_forward']['both'],
                $context['ctx_forward']['_sep1']
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

        $this->_jsvars['context'] = $context;

        /* Gettext strings used in core javascript files. */
        $this->_jsvars['text'] = array(
            'allparts_label' => _("All Message Parts"),
            'badaddr' => _("Invalid Address"),
            'badsubject' => _("Invalid Subject"),
            'baselevel' => _("base level of the folder tree"),
            'cancel' => _("Cancel"),
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
            'loading' => _("Loading..."),
            'message' => _("Message"),
            'messages' => _("Messages"),
            'messagetitle' => _("%d - %d of %d Messages"),
            'moveto' => _("Move %s to %s"),
            'nomessages' => _("No Messages"),
            'ok' => _("Ok"),
            'onlogout' => _("Logging Out..."),
            'portal' => _("Portal"),
            'prefs' => _("User Options"),
            'rename_prompt' => _("Rename %s to:"),
            'search' => _("Search"),
            'search_time' => _("Results are %d Minutes Old"),
            'selected' => _("selected"),
            'verify' => _("Verifying..."),
            'vfolder' => _("Virtual Folder: %s"),
            'vp_empty' => _("There are no messages in this mailbox."),
            'vp_empty_search' => _("No messages matched the search query."),
        );
    }

    /**
     * Add compose javascript variables to the page.
     */
    protected function _addComposeVars()
    {
        global $browser, $conf, $prefs, $registry, $session;

        $compose_cursor = $prefs->getValue('compose_cursor');

        /* Context menu definitions. */
        $context = array(
            'ctx_msg_other' => array(
                'rr' => _("Read Receipt"),
                'saveatc' => _("Save Attachments in Sent Mailbox")
            )
        );

        if ($prefs->getValue('request_mdn') == 'never') {
            unset($context['ctx_msg_other']['rr']);
        }

        if (strpos($prefs->getValue('save_attachments'), 'prompt') === false) {
            unset($context['ctx_msg_other']['saveatc']);
        }

        $this->_jsvars['context'] += $context;

        /* Variables used in compose page. */
        $this->_jsvars['conf'] += array_filter(array(
            'attach_limit' => ($conf['compose']['attach_count_limit'] ? intval($conf['compose']['attach_count_limit']) : -1),
            'auto_save_interval_val' => intval($prefs->getValue('auto_save_drafts')),
            'bcc' => intval($prefs->getValue('compose_bcc')),
            'cc' => intval($prefs->getValue('compose_cc')),
            'close_draft' => intval($prefs->getValue('close_draft')),
            'compose_cursor' => ($compose_cursor ? $compose_cursor : 'top'),
            'drafts_mbox' => IMP_Mailbox::getPref('drafts_folder')->form_to,
            'rte_avail' => intval($browser->hasFeature('rte')),
            'spellcheck' => intval($prefs->getValue('compose_spellcheck')),
            'templates_mbox' => IMP_Mailbox::getPref('composetemplates_mbox')->form_to
        ));

        /* Gettext strings used in compose page. */
        $this->_jsvars['text'] += array(
            'compose_cancel' => _("Cancelling this message will permanently discard its contents and will delete auto-saved drafts.\nAre you sure you want to do this?"),
            'nosubject' => _("The message does not have a Subject entered.") . "\n" . _("Send message without a Subject?"),
            'remove' => _("Remove"),
            'replyall' => _("%d recipients"),
            'spell_noerror' => _("No spelling errors found."),
            'toggle_html' => _("Really discard all formatting information? This operation cannot be undone."),
            'uploading' => _("Uploading..."),
        );

        if ($session->get('imp', 'csearchavail')) {
            $this->_jsvars['conf']['URI_ABOOK'] = strval(Horde::url('contacts.php'));
        }

        if ($prefs->getValue('set_priority')) {
            $this->_jsvars['conf']['priority'] = array(
                array(
                    'l' => _("High"),
                    'v' => 'high'
                ),
                array(
                    'l' => _("Normal"),
                    's' => true,
                    'v' => 'normal'
                ),
                array(
                    'l' => _("Low"),
                    'v' => 'low'
                )
            );
        }

        if (!$prefs->isLocked('default_encrypt')) {
            $encrypt = array();
            foreach (IMP::encryptList(null, true) as $key => $val) {
                $encrypt[] = array(
                    'l' => htmlspecialchars($val),
                    'v' => $key
                );
            }

            if (!empty($encrypt)) {
                $this->_jsvars['conf']['encrypt'] = $encrypt;
            }
        }
    }

}
