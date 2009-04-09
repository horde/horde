<?php
/**
 * DIMP Base Class - provides dynamic view functions.
 *
 * Copyright 2005-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package IMP
 */
class DIMP
{
    /**
     * Charset cache.
     */
    static protected $_charset;

    /**
     * Output a dimp-style action (menubar) link.
     *
     * @param array $params  A list of parameters.
     * <pre>
     * 'app' - The application to load the icon from.
     * 'class' - The CSS classname to use for the link.
     * 'icon' - The icon CSS classname.
     * 'id' - The DOM ID of the link.
     * 'title' - The title string.
     * 'tooltip' - Tooltip text to use.
     * </pre>
     *
     * @return string  An HTML link to $url.
     */
    static public function actionButton($params = array())
    {
        $tooltip = (empty($params['tooltip'])) ? '' : $params['tooltip'];

        if (empty($params['title'])) {
            if (!isset(self::$_charset)) {
                self::$_charset = NLS::getCharset();
            }
            $old_error = error_reporting(0);
            $tooltip = nl2br(htmlspecialchars($tooltip, ENT_QUOTES, self::$_charset));
            $title = $ak = '';
        } else {
            $title = $params['title'];
            $ak = Horde::getAccessKey($title);
        }

        return Horde::link('', $tooltip,
                           empty($params['class']) ? '' : $params['class'],
                           '', '', '', $ak,
                           empty($params['id']) ? array() : array('id' => $params['id']),
                           !empty($title))
            . (!empty($params['icon'])
                  ? '<span class="iconImg dimpaction' . $params['icon'] . '"></span>'
                  : '')
            . $title . '</a>';
    }

    /**
     * Output everything up to but not including the <body> tag.
     *
     * @param string $title   The title of the page.
     * @param array $scripts  Any additional scripts that need to be loaded.
     *                        Each entry contains the three elements necessary
     *                        for a Horde::addScriptFile() call.
     */
    static public function header($title, $scripts = array())
    {
        // Don't autoload any javascript files.
        Horde::disableAutoloadHordeJS();

        // Need to include script files before we start output
        Horde::addScriptFile('prototype.js', 'horde', true);
        Horde::addScriptFile('effects.js', 'horde', true);

        // ContextSensitive must be loaded before DimpCore.
        while (list($key, $val) = each($scripts)) {
            if (($val[0] == 'ContextSensitive.js') &&
                ($val[1] == 'imp')) {
                Horde::addScriptFile($val[0], $val[1], $val[2]);
                unset($scripts[$key]);
                break;
            }
        }
        Horde::addScriptFile('DimpCore.js', 'imp', true);

        // Add other scripts now
        foreach ($scripts as $val) {
            call_user_func_array(array('Horde', 'addScriptFile'), $val);
        }

        $page_title = $GLOBALS['registry']->get('name');
        if (!empty($title)) {
            $page_title .= ' :: ' . $title;
        }

        if (isset($GLOBALS['language'])) {
            header('Content-type: text/html; charset=' . NLS::getCharset());
            header('Vary: Accept-Language');
        }

        echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">' . "\n" .
             (!empty($GLOBALS['language']) ? '<html lang="' . strtr($GLOBALS['language'], '_', '-') . '"' : '<html') . ">\n".
             "<head>\n";

        // TODO: Make dimp work with IE 8 standards mode
        if ($GLOBALS['browser']->isBrowser('msie') &&
            ($GLOBALS['browser']->getMajor() == 8)) {
            echo '<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />' . "\n";
        }

        echo '<title>' . htmlspecialchars($page_title) . "</title>\n" .
             '<link href="' . $GLOBALS['registry']->getImageDir() . "/favicon.ico\" rel=\"SHORTCUT ICON\" />\n".
             IMP::wrapInlineScript(DIMP::_includeDIMPJSVars());


        IMP::includeStylesheetFiles(true, 'dimp');

        echo "</head>\n";

        // Send what we have currently output so the browser can start
        // loading CSS/JS. See:
        // http://developer.yahoo.com/performance/rules.html#flush
        flush();
    }

    /**
     * Outputs the javascript code which defines all javascript variables
     * that are dependent on the local user's account.
     *
     * @return string  TODO
     */
    static protected function _includeDIMPJSVars()
    {
        global $browser, $conf, $prefs, $registry;

        $compose_mode = (strstr($_SERVER['PHP_SELF'], 'compose-dimp.php') || strstr($_SERVER['PHP_SELF'], 'message-dimp.php'));
        $horde_webroot = $registry->get('webroot', 'horde');

        $app_urls = $code = array();

        foreach (DIMP::menuList() as $app) {
            $app_urls[$app] = Horde::url($registry->getInitialPage($app), true);
        }

        require IMP_BASE . '/config/portal.php';
        foreach ($dimp_block_list as $block) {
            if (is_a($block['ob'], 'Horde_Block')) {
                $app = $block['ob']->getApp();
                if (empty($app_urls[$app])) {
                    $app_urls[$app] = Horde::url($registry->getInitialPage($app), true);
                }
            }
        }

        /* Generate flag array. */
        $flags = array();
        $imp_flags = &IMP_Imap_Flags::singleton();
        foreach ($imp_flags->getList() as $val) {
            $flags[$val['flag']] = array_filter(array(
                'b' => isset($val['b']) ? $val['b'] : null,
                'c' => $val['c'],
                'l' => $val['l'],
                'n' => isset($val['n']) ? $val['n'] : null,
                // Indicate if this is a user *P*ref flag
                'p' => intval($val['t'] == 'imapp')
            ));
        }

        /* Variables used in core javascript files. */
        $code['conf'] = array_filter(array(
            'URI_DIMP_INBOX' => Horde::applicationUrl('index-dimp.php', true, -1),
            'URI_IMP' => Horde::applicationUrl('ajax.php', true, -1),
            'URI_PREFS' => Horde::url($horde_webroot . '/services/prefs/', true, -1),
            'URI_VIEW' => Horde::applicationUrl('view.php', true, -1),

            'SESSION_ID' => defined('SID') ? SID : '',

            'app_urls' => $app_urls,
            'timeout_url' => Auth::addLogoutParameters($horde_webroot . '/login.php', AUTH_REASON_SESSION),
            'message_url' => Horde::applicationUrl('message-dimp.php'),
            'compose_url' => Horde::applicationUrl('compose-dimp.php'),
            'prefs_url' => str_replace('&amp;', '&', Horde::getServiceLink('options', 'imp')),

            'sortthread' => Horde_Imap_Client::SORT_THREAD,
            'sortdate' => Horde_Imap_Client::SORT_DATE,

            'popup_width' => 820,
            'popup_height' => 610,

            'flags' => $flags,

            'spam_folder' => IMP::folderPref($prefs->getValue('spam_folder'), true),
            'spam_reporting' => intval(!empty($conf['spam']['reporting'])),
            'spam_spamfolder' => intval(!empty($conf['spam']['spamfolder'])),
            'ham_reporting' => intval(!empty($conf['notspam']['reporting'])),
            'ham_spamfolder' => intval(!empty($conf['notspam']['spamfolder'])),
            'refresh_time' => intval($prefs->getValue('refresh_time')),
            'search_all' => intval(!empty($conf['dimp']['search']['search_all'])),

            'fixed_folders' => empty($conf['server']['fixed_folders'])
                ? array()
                : array_map(array('DIMP', '_appendedFolderPref'), $conf['server']['fixed_folders']),

            'disable_compose' => (!empty($conf['hooks']['disable_compose']) && Horde::callHook('_imp_hook_disable_compose', array(), 'imp')),

            'name' => $registry->get('name', 'dimp'),

            'preview_pref' => intval($prefs->getValue('dimp_show_preview')),
            'toggle_pref' => intval($prefs->getValue('dimp_toggleheaders')),

            'is_ie6' => intval($browser->isBrowser('msie') && ($browser->getMajor() < 7)),

            'buffer_pages' => intval($conf['dimp']['viewport']['buffer_pages']),
            'limit_factor' => intval($conf['dimp']['viewport']['limit_factor']),
            'viewport_wait' => intval($conf['dimp']['viewport']['viewport_wait']),
            'login_view' => $prefs->getValue('dimp_login_view'),
            'background_inbox' => intval(!empty($conf['dimp']['viewport']['background_inbox'])),
            'splitbar_pos' => $prefs->getValue('dimp_splitbar'),

            // Turn debugging on?
            'debug' => intval(!empty($conf['dimp']['js']['debug'])),
        ));

        /* Gettext strings used in core javascript files. */
        $code['text'] = array_map('addslashes', array(
            'portal' => ("Portal"),
            'prefs' => _("User Options"),
            'search' => _("Search"),
            'resfound' => _("messages found"),
            'message' => _("Message"),
            'messages' => _("Messages"),
            'of' => _("of"),
            'nomessages' => _("No Messages"),
            'ok' => _("Ok"),
            'copyto' => _("Copy %s to %s"),
            'moveto' => _("Move %s to %s"),
            'baselevel' => _("base level of the folder tree"),
            'cancel' => _("Cancel"),
            'loading' => _("Loading..."),
            'check' => _("Checking..."),
            'verify' => _("Verifying..."),
            'onlogout' => _("Logging Out..."),
            'badsubject' => _("Invalid Subject"),
            'ajax_timeout' => _("There has been no contact with the remote server for several minutes. The server may be temporarily unavailable or network problems may be interrupting your session. You will not see any updates until the connection is restored."),
            'ajax_recover' => _("The connection to the remote server has been restored."),
            'listmsg_wait' => _("The server is still generating the message list."),
            'listmsg_timeout' => _("The server was unable to generate the message list. Please try again later."),
            'popup_block' => _("A popup window could not be opened. Your browser may be blocking popups."),
            'hide_preview' => _("Hide Preview"),
            'show_preview' => _("Show Preview"),
            'rename_prompt' => _("Rename folder to:"),
            'create_prompt' => _("Create folder:"),
            'createsub_prompt' => _("Create subfolder:"),
            'empty_folder' => _("Permanently delete all messages in %s?"),
            'delete_folder' => _("Permanently delete %s?"),
            'hidealog' => _("Hide Alerts Log"),
            'alog_error' => _("Error"),
            'alog_message' => _("Message"),
            'alog_success' => _("Success"),
            'alog_warning' => _("Warning"),
        ));

        /* Gettext strings with individual escaping. */
        $code['text']['getmail'] = Horde::highlightAccessKey(addslashes(_("_Get Mail")), Horde::getAccessKey(_("_Get Mail"), true));
        $code['text']['showalog'] = Horde::highlightAccessKey(addslashes(_("_Alerts Log")), Horde::getAccessKey(_("_Alerts Log"), true));

        if ($compose_mode) {
            /* Variables used in compose page. */
            $compose_cursor = $GLOBALS['prefs']->getValue('compose_cursor');
            $code['conf_compose'] = array_filter(array(
                'rte_avail' => intval($browser->hasFeature('rte')),
                'cc' => intval($prefs->getValue('compose_cc')),
                'bcc' => intval($prefs->getValue('compose_bcc')),
                'attach_limit' => ($conf['compose']['attach_count_limit'] ? intval($conf['compose']['attach_count_limit']) : -1),
                'close_draft' => intval($prefs->getValue('close_draft')),
                'compose_cursor' => ($compose_cursor ? $compose_cursor : 'top'),
                'spellcheck' => intval($prefs->getValue('compose_spellcheck')),
            ));

            if ($registry->hasMethod('contacts/search')) {
                $code['conf_compose']['abook_url'] = Horde::applicationUrl('contacts.php');
            }

            /* Gettext strings used in compose page. */
            $code['text_compose'] = array_map('addslashes', array(
                'cancel' => _("Cancelling this message will permanently discard its contents and will delete auto-saved drafts.\nAre you sure you want to do this?"),
                 'nosubject' => _("The message does not have a Subject entered.") . "\n" . _("Send message without a Subject?"),
                'fillform' => _("You have already changed the message body, are you sure you want to drop the changes?"),
                'remove' => _("Remove"),
                'uploading' => _("Uploading..."),
                'attachment_limit' => _("The attachment limit has been reached."),
                'sending' => _("Sending..."),
                'saving' => _("Saving..."),
                'toggle_html' => _("Really discard all formatting information? This operation cannot be undone."),
            ));
        }

        return array('var DIMP = ' . Horde_Serialize::serialize($code, Horde_Serialize::JSON, NLS::getCharset()) . ';');
    }

    /**
     * Return an appended IMP folder string
     */
    static private function _appendedFolderPref($folder)
    {
        return IMP::folderPref($folder, true);
    }

    /**
     * Return the javascript code necessary to display notification popups.
     *
     * @return string  The notification JS code.
     */
    static public function notify()
    {
        $GLOBALS['notification']->notify(array('listeners' => 'status'));
        $msgs = $GLOBALS['imp_notify']->getStack(true);
        if (!count($msgs)) {
            return '';
        }

        return 'DimpCore.showNotifications(' . Horde_Serialize::serialize($msgs, Horde_Serialize::JSON) . ')';
    }

    /**
     * Formats the response to send to javascript code when dealing with
     * folder operations.
     *
     * @param IMP_Tree $imptree  An IMP_Tree object.
     * @param array $changes     An array with three sub arrays - to be used
     *                           instead of the return from
     *                           $imptree->eltDiff():
     *                           'a' - a list of folders to add
     *                           'c' - a list of changed folders
     *                           'd' - a list of folders to delete
     *
     * @return array  The object used by the JS code to update the folder tree.
     */
    static public function getFolderResponse($imptree, $changes = null)
    {
        if (is_null($changes)) {
            $changes = $imptree->eltDiff();
        }
        if (empty($changes)) {
            return false;
        }

        $result = array();

        if (!empty($changes['a'])) {
            $result['a'] = array();
            foreach ($changes['a'] as $val) {
                $result['a'][] = DIMP::_createFolderElt($imptree->element($val));
            }
        }

        if (!empty($changes['c'])) {
            $result['c'] = array();
            foreach ($changes['c'] as $val) {
                // Skip the base element, since any change there won't ever be
                // updated on-screen.
                if ($val != IMP_Imap_Tree::BASE_ELT) {
                    $result['c'][] = DIMP::_createFolderElt($imptree->element($val));
                }
            }
        }

        if (!empty($changes['d'])) {
            $result['d'] = array_map('rawurlencode', array_reverse($changes['d']));
        }

        return $result;
    }

    /**
     * Create an object used by DimpCore to generate the folder tree.
     *
     * @param array $elt  The output from IMP_Tree::element().
     *
     * @return stdClass  The element object. Contains the following items:
     * <pre>
     * 'ch' (children) = Does the folder contain children? [boolean]
     *                   [DEFAULT: no]
     * 'cl' (class) = The CSS class. [string] [DEFAULT: 'base']
     * 'co' (container) = Is this folder a container element? [boolean]
     *                    [DEFAULT: no]
     * 'i' (icon) = A user defined icon to use. [string] [DEFAULT: none]
     * 'l' (label) = The folder display label. [string]
     * 'm' (mbox) = The mailbox value. [string]
     * 'pa' (parent) = The parent element. [string]
     * 'po' (polled) = Is the element polled? [boolean] [DEFAULT: no]
     * 's' (special) = Is this a "special" element? [boolean] [DEFAULT: no]
     * 'u' (unseen) = The number of unseen messages. [integer]
     * 'v' (virtual) = Is this a virtual folder? [boolean] [DEFAULT: no]
     * </pre>
     */
    static private function _createFolderElt($elt)
    {
        $ob = new stdClass;
        if ($elt['children']) {
           $ob->ch = 1;
        }
        $ob->l = $elt['base_elt']['l'];
        $ob->m = rawurlencode($elt['value']);
        $ob->pa = rawurlencode($elt['parent']);
        if ($elt['polled']) {
            $ob->po = 1;
        }
        if ($elt['vfolder']) {
            $ob->v = 1;
        }

        if ($elt['container']) {
            $ob->co = 1;
            $ob->cl = 'exp';
        } else {
            if ($elt['polled']) {
                $ob->u = $elt['unseen'];
            }

            switch ($elt['special']) {
            case IMP_Imap_Tree::SPECIAL_INBOX:
                $ob->cl = 'inbox';
                $ob->s = 1;
                break;

            case IMP_Imap_Tree::SPECIAL_TRASH:
                $ob->cl = 'trash';
                $ob->s = 1;
                break;

            case IMP_Imap_Tree::SPECIAL_SPAM:
                $ob->cl = 'spam';
                $ob->s = 1;
                break;

            case IMP_Imap_Tree::SPECIAL_DRAFT:
                $ob->cl = 'drafts';
                $ob->s = 1;
                break;

            case IMP_Imap_Tree::SPECIAL_SENT:
                $ob->cl = 'sent';
                $ob->s = 1;
                break;

            default:
                if ($elt['vfolder']) {
                    if ($GLOBALS['imp_search']->isVTrashFolder($elt['value'])) {
                        $ob->cl = 'trash';
                    } elseif ($GLOBALS['imp_search']->isVINBOXFolder($elt['value'])) {
                        $ob->cl = 'inbox';
                    }
                } elseif ($elt['children']) {
                    $ob->cl = 'exp';
                }
                break;
            }
        }

        if ($elt['user_icon']) {
            $ob->cl = 'customimg';
            $dir = empty($elt['icondir'])
                ? $GLOBALS['registry']->getImageDir()
                : $elt['icondir'];
            $ob->i = empty($dir)
                ? $elt['icon']
                : $dir . '/' . $elt['icon'];
        }

        return $ob;
    }

    /**
     * Return information about the current attachments for a message
     *
     * @param IMP_Compose $imp_compose  An IMP_Compose object.
     *
     * @return array  An array of arrays with the following keys:
     * <pre>
     * 'number' - The current attachment number
     * 'name' - The HTML encoded attachment name
     * 'type' - The MIME type of the attachment
     * 'size' - The size of the attachment in KB (string)
     * </pre>
     */
    static public function getAttachmentInfo($imp_compose)
    {
        $fwd_list = array();

        if ($imp_compose->numberOfAttachments()) {
            foreach ($imp_compose->getAttachments() as $atc_num => $data) {
                $mime = $data['part'];

                $fwd_list[] = array(
                    'number' => $atc_num,
                    'name' => htmlspecialchars($mime->getName(true)),
                    'type' => $mime->getType(),
                    'size' => $mime->getSize()
                );
            }
        }

        return $fwd_list;
    }

    /**
     * Return a list of DIMP specific menu items.
     *
     * @return array  The array of menu items.
     */
    static public function menuList()
    {
        if (isset($GLOBALS['conf']['dimp']['menu']['apps'])) {
            $apps = $GLOBALS['conf']['dimp']['menu']['apps'];
            if (is_array($apps) && count($apps)) {
                return $apps;
            }
        }
        return array();
    }

}
