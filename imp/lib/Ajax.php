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
class IMP_Ajax extends Horde_Core_Ajax
{
    /**
     * Javascript variables to output to the page.
     *
     * @var array
     */
    protected $_jsvars = array();

    /**
     */
    public function init(array $opts = array())
    {
        parent::init(array_merge($opts, array('app' => 'imp')));
    }

    /**
     * @param string $page   Either 'compose', 'main', or 'message'.
     * @param string $title  The title of the page.
     */
    public function header($page, $title = '')
    {
        global $prefs;

        $inlinescript = false;

        $this->init(array(
            'growler_log' => ($page == 'main')
        ));

        $this->_addBaseVars();

        Horde::addScriptFile('dimpcore.js', 'imp');
        Horde::addScriptFile('indices.js', 'imp');
        Horde::addScriptFile('contextsensitive.js', 'horde');

        switch ($page) {
        case 'compose':
            Horde::addScriptFile('compose-base.js', 'imp');
            Horde::addScriptFile('compose-dimp.js', 'imp');
            Horde::addScriptFile('md5.js', 'horde');
            Horde::addScriptFile('textarearesize.js', 'horde');

            if (!$prefs->isLocked('default_encrypt') &&
                ($prefs->getValue('use_pgp') ||
                 $prefs->getValue('use_smime'))) {
                Horde::addScriptFile('dialog.js', 'imp');
                Horde::addScriptFile('redbox.js', 'horde');
            }

            $this->_addComposeVars();
            break;

        case 'main':
            Horde::addScriptFile('dimpbase.js', 'imp');
            Horde::addScriptFile('dialog.js', 'imp');
            Horde::addScriptFile('imp.js', 'imp');
            Horde::addScriptFile('imageunblock.js', 'imp');
            Horde::addScriptFile('itiprequest.js', 'imp');
            Horde::addScriptFile('jstorage.js', 'imp');
            Horde::addScriptFile('mailbox-dimp.js', 'imp');
            Horde::addScriptFile('viewport.js', 'imp');
            Horde::addScriptFile('dragdrop2.js', 'horde');
            Horde::addScriptFile('form_ghost.js', 'horde');
            Horde::addScriptFile('redbox.js', 'horde');
            Horde::addScriptFile('slider2.js', 'horde');
            Horde::addScriptFile('toggle_quotes.js', 'horde');

            if ($prefs->getValue('use_pgp') ||
                $prefs->getValue('use_smime')) {
                Horde::addScriptFile('importencryptkey.js', 'imp');
            }
            break;

        case 'message':
            Horde::addScriptFile('message-dimp.js', 'imp');
            Horde::addScriptFile('imp.js', 'imp');
            Horde::addScriptFile('imageunblock.js', 'imp');
            Horde::addScriptFile('itiprequest.js', 'imp');
            Horde::addScriptFile('textarearesize.js', 'horde');
            Horde::addScriptFile('toggle_quotes.js', 'horde');

            if ($prefs->getValue('use_pgp') ||
                $prefs->getValue('use_smime')) {
                Horde::addScriptFile('importencryptkey.js', 'imp');
            }

            if (IMP::canCompose()) {
                Horde::addScriptFile('compose-base.js', 'imp');
                Horde::addScriptFile('compose-dimp.js', 'imp');
                Horde::addScriptFile('md5.js', 'horde');

                if (!$prefs->isLocked('default_encrypt') &&
                    ($prefs->getValue('use_pgp') ||
                     $prefs->getValue('use_smime'))) {
                    Horde::addScriptFile('dialog.js', 'imp');
                    Horde::addScriptFile('redbox.js', 'horde');
                }

                $this->_addComposeVars();
            }
            break;

        default:
            $inlinescript = true;
            break;
        }

        Horde::addInlineJsVars(array(
            'var DIMP' => $this->_jsvars
        ), array('top' => true));

        parent::header(array(
            'css' => array(
                'sub' => 'dimp'
            ),
            'inlinescript' => $inlinescript,
            'title' => $title
        ));
    }

    /**
     * Add base javascript variables to the page.
     */
    protected function _addBaseVars()
    {
        global $conf, $injector, $prefs, $registry;

        $code = $filters = $flags = array();

        /* Generate filter array. */
        $imp_search = $injector->getInstance('IMP_Search');
        $imp_search->setIteratorFilter(IMP_Search::LIST_FILTER);
        foreach (iterator_to_array($imp_search) as $key => $val) {
            if ($val->enabled) {
                $filters[$key] = $val->label;
            }
        }

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
            'filters' => $filters,
            /* Needed to maintain flag ordering. */
            'filters_o' => array_keys($filters),
            'fixed_folders' => empty($conf['server']['fixed_folders'])
                ? array()
                : array_map(array('IMP_Mailbox', 'formTo'), array_map(array('IMP_Mailbox', 'prefFrom'), $conf['server']['fixed_folders'])),
            'flags' => $flags,
            /* Needed to maintain flag ordering. */
            'flags_o' => array_keys($flags),
            'fsearchid' => IMP_Mailbox::formTo(IMP_Search::MBOX_PREFIX . IMP_Search::DIMP_FILTERSEARCH),
            'ham_spammbox' => intval(!empty($conf['notspam']['spamfolder'])),
            'initial_page' => IMP_Auth::getInitialPage()->mbox->form_to,
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

        /* Gettext strings used in core javascript files. */
        $this->_jsvars['text'] = array(
            'allparts_label' => _("All Message Parts"),
            'badaddr' => _("Invalid Address"),
            'badsubject' => _("Invalid Subject"),
            'baselevel' => _("base level of the folder tree"),
            'cancel' => _("Cancel"),
            'check' => _("Checking..."),
            'copyto' => _("Copy %s to %s"),
            'create_prompt' => _("Create folder:"),
            'createsub_prompt' => _("Create subfolder of %s:"),
            'delete_folder' => _("Permanently delete %s?"),
            'download_folder' => _("All messages in this mailbox will be downloaded into one MBOX file. This may take some time. Are you sure you want to continue?"),
            'empty_folder' => _("Permanently delete all %d messages in %s?"),
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
        global $browser, $conf, $prefs, $registry;

        $compose_cursor = $prefs->getValue('compose_cursor');

        /* Variables used in compose page. */
        $this->_jsvars['conf_compose'] = array_filter(array(
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
        $this->_jsvars['text_compose'] = array(
            'cancel' => _("Cancelling this message will permanently discard its contents and will delete auto-saved drafts.\nAre you sure you want to do this?"),
            'nosubject' => _("The message does not have a Subject entered.") . "\n" . _("Send message without a Subject?"),
            'remove' => _("Remove"),
            'replyall' => _("%d recipients"),
            'spell_noerror' => _("No spelling errors found."),
            'toggle_html' => _("Really discard all formatting information? This operation cannot be undone."),
            'uploading' => _("Uploading..."),
        );

        if ($registry->hasMethod('contacts/search')) {
            $this->_jsvars['conf_compose']['URI_ABOOK'] = strval(Horde::url('contacts.php'));
        }

        if ($prefs->getValue('set_priority')) {
            $this->_jsvars['conf_compose']['priority'] = array(
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
                $this->_jsvars['conf_compose']['encrypt'] = $encrypt;
            }
        }
    }

}
