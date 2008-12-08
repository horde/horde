<?php
/**
 * IMP Base Class.
 *
 * Copyright 1999-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Jon Parise <jon@horde.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package IMP
 */
class IMP
{
    /* Encrypt constants. */
    const ENCRYPT_NONE = 1;
    const PGP_ENCRYPT = 2;
    const PGP_SIGN = 3;
    const PGP_SIGNENC = 4;
    const SMIME_ENCRYPT = 5;
    const SMIME_SIGN = 6;
    const SMIME_SIGNENC = 7;
    const PGP_SYM_ENCRYPT = 8;
    const PGP_SYM_SIGNENC = 9;

    /* IMAP flag constants. */
    const FLAG_ALL = 0;
    const FLAG_UNSEEN = 1;
    const FLAG_DELETED = 2;
    const FLAG_ANSWERED = 4;
    const FLAG_FLAGGED = 8;
    const FLAG_DRAFT = 16;
    const FLAG_PERSONAL = 32;

    /* IMP Mailbox view constants. */
    const MAILBOX_START_FIRSTUNSEEN = 1;
    const MAILBOX_START_LASTUNSEEN = 2;
    const MAILBOX_START_FIRSTPAGE = 3;
    const MAILBOX_START_LASTPAGE = 4;

    /* IMP mailbox labels. */
    const SEARCH_MBOX = '**search_';

    /* IMP internal indexing strings. */
    // String used to separate messages.
    const MSG_SEP = "\0";
    // String used to separate indexes.
    const IDX_SEP = "\1";

    /* Are we currently in "print" mode? */
    static public $printMode = false;

    /* displayFolder() cache. */
    static private $_displaycache = array();

    /* hideDeletedMsgs() cache. */
    static private $_delhide = null;

    /* getAuthKey() cache. */
    static private $_authkey = null;

    /* filesystemGC() cache. */
    static private $_dirlist = array();

    /* Inline script cache. */
    static private $_inlineScript = array();

    /**
     * Makes sure the user has been authenticated to view the page.
     *
     * @param boolean $return     If this is true, return false instead of
     *                            exiting/redirecting if authentication fails.
     * @param boolean $hordeauth  Just check for Horde auth and don't bother
     *                            the IMAP server.
     *
     * @return boolean  True on success, false on error.
     */
    static public function checkAuthentication($return = false,
                                               $hordeauth = false)
    {
        if ($hordeauth) {
            $reason = Auth::isAuthenticated();
        } else {
            $auth_imp = &Auth::singleton(array('imp', 'imp'));
            $reason = $auth_imp->authenticate(null, array(), false);
        }

        if ($reason === true) {
            return true;
        } elseif ($return) {
            return false;
        }

        if (Util::getFormData('popup')) {
            Util::closeWindowJS();
        } else {
            $url = Util::addParameter(Auth::addLogoutParameters(self::logoutUrl()), 'url', Horde::selfUrl(true));
            header('Location: ' . $url);
        }
        exit;
    }

    /**
     * Get a token for protecting a form.
     *
     * @param string $slug  TODO
     *
     * @return  TODO
     */
    static public function getRequestToken($slug)
    {
        $token = Horde_Token::generateId($slug);
        $_SESSION['horde_form_secrets'][$token] = time();
        return $token;
    }

    /**
     * Check if a token for a form is valid.
     *
     * @param string $slug   TODO
     * @param string $token  TODO
     *
     * @return  TODO
     */
    static public function checkRequestToken($slug, $token)
    {
        if (empty($_SESSION['horde_form_secrets'][$token])) {
            return PEAR::raiseError(_("We cannot verify that this request was really sent by you. It could be a malicious request. If you intended to perform this action, you can retry it now."));
        }

        if ($_SESSION['horde_form_secrets'][$token] + $GLOBALS['conf']['server']['token_lifetime'] < time()) {
            return PEAR::raiseError(sprintf(_("This request cannot be completed because the link you followed or the form you submitted was only valid for %d minutes. Please try again now."), round($GLOBALS['conf']['server']['token_lifetime'] / 60)));
        }

        return true;
    }

    /**
     * Returns the plain text label that is displayed for the current mailbox,
     * replacing IMP::SEARCH_MBOX with an appropriate string and removing
     * namespace and folder prefix information from what is shown to the user.
     *
     * @param string $mbox  The mailbox to use for the label.
     *
     * @return string  The plain text label.
     */
    static public function getLabel($mbox)
    {
        return ($GLOBALS['imp_search']->isSearchMbox($mbox))
            ? $GLOBALS['imp_search']->getLabel($mbox)
            : self::displayFolder($mbox);
    }

    /**
     * Adds a contact to the user defined address book.
     *
     * @param string $newAddress  The contact's email address.
     * @param string $newName     The contact's name.
     *
     * @return string  A link or message to show in the notification area.
     */
    static public function addAddress($newAddress, $newName)
    {
        global $registry, $prefs;

        if (empty($newName)) {
            $newName = $newAddress;
        }

        $result = $registry->call('contacts/import',
                                  array(array('name' => $newName, 'email' => $newAddress),
                                        'array', $prefs->getValue('add_source')));
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        $contact_link = $registry->link('contacts/show', array('uid' => $result, 'source' => $prefs->getValue('add_source')));
        if (!empty($contact_link) && !is_a($contact_link, 'PEAR_Error')) {
            $contact_link = Horde::link(Horde::url($contact_link), sprintf(_("Go to address book entry of \"%s\""), $newName)) . @htmlspecialchars($newName, ENT_COMPAT, NLS::getCharset()) . '</a>';
        } else {
            $contact_link = @htmlspecialchars($newName, ENT_COMPAT, NLS::getCharset());
        }

        return $contact_link;
    }

    /**
     * Wrapper around IMP_Folder::flist() which generates the body of a
     * &lt;select&gt; form input from the generated folder list. The
     * &lt;select&gt; and &lt;/select&gt; tags are NOT included in the output
     * of this function.
     *
     * @param array $options  Optional parameters:
     * <pre>
     * 'abbrev' - (boolean) Abbreviate long mailbox names by replacing the
     *            middle of the name with '...'?
     *            DEFAULT: Yes
     * 'filter' - (array) An array of mailboxes to ignore.
     *            DEFAULT: Display all
     * 'heading' - (string) The label for an empty-value option at the top of
     *             the list.
     *             DEFAULT: ''
     * 'inc_notepads' - (boolean) Include user's editable notepads in list?
     *                   DEFAULT: No
     * 'inc_tasklists' - (boolean) Include user's editable tasklists in list?
     *                   DEFAULT: No
     * 'inc_vfolder' - (boolean) Include user's virtual folders in list?
     *                   DEFAULT: No
     * 'new_folder' - (boolean) Display an option to create a new folder?
     *                DEFAULT: No
     * 'selected' - (string) The mailbox to have selected by default.
     *             DEFAULT: None
     * </pre>
     *
     * @return string  A string containing <option> elements for each mailbox
     *                 in the list.
     */
    static public function flistSelect($options = array())
    {
        require_once 'Horde/Text.php';

        $imp_folder = &IMP_Folder::singleton();

        /* Don't filter here - since we are going to parse through every
         * member of the folder list below anyway, we can filter at that time.
         * This allows us the have a single cached value for the folder list
         * rather than a cached value for each different mailbox we may
         * visit. */
        $mailboxes = $imp_folder->flist();
        $text = '';

        if (!empty($options['heading']) &&
            (strlen($options['heading']) > 0)) {
            $text .= '<option value="">' . $options['heading'] . "</option>\n";
        }

        if (!empty($options['new_folder']) &&
            (!empty($GLOBALS['conf']['hooks']['permsdenied']) ||
             (self::hasPermission('create_folders') &&
              self::hasPermission('max_folders')))) {
            $text .= '<option value="" disabled="disabled">- - - - - - - - -</option>' . "\n";
            $text .= '<option value="*new*">' . _("New Folder") . "</option>\n";
            $text .= '<option value="" disabled="disabled">- - - - - - - - -</option>' . "\n";
        }

        /* Add the list of mailboxes to the lists. */
        $filter = empty($options['filter']) ? array() : array_flip($filter);
        foreach ($mailboxes as $mbox) {
            if (isset($filter[$mbox['val']])) {
                continue;
            }

            $val = isset($filter[$mbox['val']]) ? '' : htmlspecialchars($mbox['val']);
            $sel = ($mbox['val'] && !empty($options['selected']) && ($mbox['val'] === $options['selected'])) ? ' selected="selected"' : '';
            $label = empty($options['abbrev']) ? $mbox['label'] : $mbox['abbrev'];
            $text .= sprintf('<option value="%s"%s>%s</option>%s', $val, $sel, Text::htmlSpaces($label), "\n");
        }

        /* Add the list of virtual folders to the list. */
        if (!empty($options['inc_vfolder'])) {
            $vfolders = $GLOBALS['imp_search']->listQueries(true);
            if (!empty($vfolders)) {
                $vfolder_sel = $GLOBALS['imp_search']->searchMboxID();
                $text .= '<option value="" disabled="disabled">- - - - - - - - -</option>' . "\n";
                foreach ($vfolders as $id => $val) {
                    $text .= sprintf('<option value="%s"%s>%s</option>%s', $GLOBALS['imp_search']->createSearchID($id), ($vfolder_sel == $id) ? ' selected="selected"' : '', Text::htmlSpaces($val), "\n");
                }
            }
        }

        /* Add the list of editable tasklists to the list. */
        if (!empty($options['inc_tasklists']) &&
            !empty($_SESSION['imp']['tasklistavail'])) {
            $tasklists = $GLOBALS['registry']->call('tasks/listTasklists',
                                                    array(false, PERMS_EDIT));

            if (!is_a($tasklists, 'PEAR_Error') && count($tasklists)) {
                $text .= '<option value="" disabled="disabled">&nbsp;</option><option value="" disabled="disabled">- - ' . _("Task Lists") . ' - -</option>' . "\n";

                foreach ($tasklists as $id => $tasklist) {
                    $text .= sprintf('<option value="%s">%s</option>%s',
                                     '_tasklist_' . $id,
                                     Text::htmlSpaces($tasklist->get('name')),
                                     "\n");
                }
            }
        }

        /* Add the list of editable notepads to the list. */
        if (!empty($options['inc_notepads']) &&
            !empty($_SESSION['imp']['notepadavail'])) {
            $notepads = $GLOBALS['registry']->call('notes/listNotepads',
                                                    array(false, PERMS_EDIT));

            if (!is_a($notepads, 'PEAR_Error') && count($notepads)) {
                $text .= '<option value="" disabled="disabled">&nbsp;</option><option value="" disabled="disabled">- - ' . _("Notepads") . ' - -</option>' . "\n";

                foreach ($notepads as $id => $notepad) {
                    $text .= sprintf('<option value="%s">%s</option>%s',
                                     '_notepad_' . $id,
                                     Text::htmlSpaces($notepad->get('name')),
                                     "\n");
                }
            }
        }

        return $text;
    }

    /**
     * Checks for To:, Subject:, Cc:, and other compose window arguments and
     * pass back either a URI fragment or an associative array with any of
     * them which are present.
     *
     * @param string $format  Either 'uri' or 'array'.
     *
     * @return string  A URI fragment or an associative array with any compose
     *                 arguments present.
     */
    static public function getComposeArgs()
    {
        $args = array();
        $fields = array('to', 'cc', 'bcc', 'message', 'body', 'subject');

        foreach ($fields as $val) {
            if (($$val = Util::getFormData($val))) {
                $args[$val] = $$val;
            }
        }

        /* Decode mailto: URLs. */
        if (isset($args['to']) && (strpos($args['to'], 'mailto:') === 0)) {
            $mailto = @parse_url($args['to']);
            if (is_array($mailto)) {
                $args['to'] = isset($mailto['path']) ? $mailto['path'] : '';
                if (!empty($mailto['query'])) {
                    parse_str($mailto['query'], $vals);
                    foreach ($fields as $val) {
                        if (isset($vals[$val])) {
                            $args[$val] = $vals[$val];
                        }
                    }
                }
            }
        }

        return $args;
    }

    /**
     * Open an (IMP) compose window.
     *
     * @return boolean  True if window was opened.
     */
    static public function openComposeWin($options = array())
    {
        if ($GLOBALS['prefs']->getValue('compose_popup')) {
            return false;
        }

        $options += self::getComposeArgs();
        $url = Util::addParameter(Horde::applicationUrl('compose.php', true), $options, null, false);
        header('Location: ' . $url);
        return true;
    }

    /**
     * Prepares the arguments to use for composeLink().
     *
     * @param mixed $args   List of arguments to pass to compose.php. If this
     *                      is passed in as a string, it will be parsed as a
     *                      toaddress?subject=foo&cc=ccaddress (mailto-style)
     *                      string.
     * @param array $extra  Hash of extra, non-standard arguments to pass to
     *                      compose.php.
     *
     * @return array  The array of args to use for composeLink().
     */
    static public function composeLinkArgs($args = array(), $extra = array())
    {
        if (is_string($args)) {
            $string = $args;
            $args = array();
            if (($pos = strpos($string, '?')) !== false) {
                parse_str(substr($string, $pos + 1), $args);
                $args['to'] = substr($string, 0, $pos);
            } else {
                $args['to'] = $string;
            }
        }

        /* Merge the two argument arrays. */
        return (is_array($extra) && !empty($extra))
            ? array_merge($args, $extra)
            : $args;
    }

    /**
     * Returns the appropriate link to call the message composition screen.
     *
     * @param mixed $args   List of arguments to pass to compose.php. If this
     *                      is passed in as a string, it will be parsed as a
     *                      toaddress?subject=foo&cc=ccaddress (mailto-style)
     *                      string.
     * @param array $extra  Hash of extra, non-standard arguments to pass to
     *                      compose.php.
     *
     * @return string  The link to the message composition screen.
     */
    static public function composeLink($args = array(), $extra = array())
    {
        $args = self::composeLinkArgs($args, $extra);
        $is_mimp = ($_SESSION['imp']['view'] == 'mimp');

        if (!$is_mimp &&
            $GLOBALS['prefs']->getValue('compose_popup') &&
            $GLOBALS['browser']->hasFeature('javascript')) {
            Horde::addScriptFile('prototype.js', 'horde', true);
            Horde::addScriptFile('popup.js', 'imp', true);
            if (isset($args['to'])) {
                $args['to'] = addcslashes($args['to'], '\\"');
            }
            return "javascript:" . self::popupIMPString('compose.php', $args);
        } else {
            return Util::addParameter(Horde::applicationUrl($is_mimp ? 'compose-mimp.php' : 'compose.php'), $args);
        }
    }

    /**
     * Generates an URL to the logout screen that includes any known
     * information, such as username, server, etc., that can be filled in on
     * the login form.
     *
     * @return string  Logout URL with logout parameters added.
     */
    static public function logoutUrl()
    {
        // TODO
        $params = array(
            'imapuser' => isset($_SESSION['imp']['user']) ?
                          $_SESSION['imp']['user'] :
                          Util::getFormData('imapuser'),
            'server'   => isset($_SESSION['imp']['server']) ?
                          $_SESSION['imp']['server'] :
                          Util::getFormData('server'),
            'port'     => isset($_SESSION['imp']['port']) ?
                          $_SESSION['imp']['port'] :
                          Util::getFormData('port'),
            'protocol' => isset($_SESSION['imp']['protocol']) ?
                          $_SESSION['imp']['protocol'] :
                          Util::getFormData('protocol'),
            'language' => isset($_SESSION['imp']['language']) ?
                          $_SESSION['imp']['language'] :
                          Util::getFormData('language'),
            'smtphost' => isset($_SESSION['imp']['smtphost']) ?
                          $_SESSION['imp']['smtphost'] :
                          Util::getFormData('smtphost'),
            'smtpport' => isset($_SESSION['imp']['smtpport']) ?
                          $_SESSION['imp']['smtpport'] :
                          Util::getFormData('smtpport'),
        );

        return Util::addParameter($GLOBALS['registry']->get('webroot', 'imp') . '/login.php', array_diff($params, array('')), null, false);
    }

    /**
     * If there is information available to tell us about a prefix in front of
     * mailbox names that shouldn't be displayed to the user, then use it to
     * strip that prefix out.
     *
     * @param string $folder  The folder name to display (UTF7-IMAP).
     *
     * @return string  The folder, with any prefix gone.
     */
    static public function displayFolder($folder)
    {
        $cache = self::$_displaycache;

        if (isset($cache[$folder])) {
            return $cache[$folder];
        }

        if ($folder == 'INBOX') {
            $cache[$folder] = _("Inbox");
        } else {
            $namespace_info = $GLOBALS['imp_imap']->getNamespace($folder);
            if (!is_null($namespace_info) &&
                !empty($namespace_info['name']) &&
                ($namespace_info['type'] == 'personal') &&
                substr($folder, 0, strlen($namespace_info['name'])) == $namespace_info['name']) {
                $cache[$folder] = substr($folder, strlen($namespace_info['name']));
            } else {
                $cache[$folder] = $folder;
            }

            $cache[$folder] = String::convertCharset($cache[$folder], 'UTF7-IMAP');
        }

        return $cache[$folder];
    }

    /**
     * Filters a string, if requested.
     *
     * @param string $text  The text to filter.
     *
     * @return string  The filtered text (if requested).
     */
    static public function filterText($text)
    {
        global $conf, $prefs;

        if ($prefs->getValue('filtering') && strlen($text)) {
            require_once 'Horde/Text/Filter.php';
            $text = Text_Filter::filter($text, 'words', array('words_file' => $conf['msgsettings']['filtering']['words'], 'replacement' => $conf['msgsettings']['filtering']['replacement']));
        }

        return $text;
    }

    /**
     * Returns the specified permission for the current user.
     *
     * @param string $permission  A permission.
     * @param boolean $value      If true, the method returns the value of a
     *                            scalar permission, otherwise whether the
     *                            permission limit has been hit already.
     *
     * @return mixed  The value of the specified permission.
     */
    static public function hasPermission($permission, $value = false)
    {
        if (!$GLOBALS['perms']->exists('imp:' . $permission)) {
            return true;
        }

        $allowed = $GLOBALS['perms']->getPermissions('imp:' . $permission);
        if (is_array($allowed)) {
            switch ($permission) {
            case 'create_folders':
                $allowed = (bool)count(array_filter($allowed));
                break;

            case 'max_folders':
            case 'max_recipients':
            case 'max_timelimit':
                $allowed = max($allowed);
                break;
            }
        }
        if (($permission == 'max_folders') && !$value) {
            $folder = &IMP_Folder::singleton();
            $allowed = $allowed > count($folder->flist_IMP(array(), false));
        }

        return $allowed;
    }

    /**
     * Build IMP's list of menu items.
     *
     * @param string $type  Return type: either 'object' or 'string'.
     *
     * @return mixed  Either a Horde_Menu object or the rendered menu text.
     */
    static public function getMenu($type = 'object')
    {
        global $conf, $prefs, $registry;

        require_once 'Horde/Menu.php';

        $menu_search_url = Horde::applicationUrl('search.php');
        $menu_mailbox_url = Horde::applicationUrl('mailbox.php');

        $spam_folder = self::folderPref($prefs->getValue('spam_folder'), true);

        $menu = new Menu(HORDE_MENU_MASK_ALL & ~HORDE_MENU_MASK_LOGIN);

        $menu->add(self::generateIMPUrl($menu_mailbox_url, 'INBOX'), _("_Inbox"), 'folders/inbox.png');

        if ($_SESSION['imp']['protocol'] != 'pop') {
            if ($prefs->getValue('use_trash') &&
                $prefs->getValue('empty_trash_menu')) {
                $mailbox = null;
                if ($prefs->getValue('use_vtrash')) {
                    $mailbox = $GLOBALS['imp_search']->createSearchID($prefs->getValue('vtrash_id'));
                } else {
                    $trash_folder = self::folderPref($prefs->getValue('trash_folder'), true);
                    if (!is_null($trash_folder)) {
                        $mailbox = $trash_folder;
                    }
                }

                if (!empty($mailbox) && !$imp_imap->isReadOnly($mailbox)) {
                    $menu_trash_url = Util::addParameter(self::generateIMPUrl($menu_mailbox_url, $mailbox), array('actionID' => 'empty_mailbox', 'mailbox_token' => self::getRequestToken('imp.mailbox')));
                    $menu->add($menu_trash_url, _("Empty _Trash"), 'empty_trash.png', null, null, "return window.confirm('" . addslashes(_("Are you sure you wish to empty your trash folder?")) . "');", '__noselection');
                }
            }

            if (!empty($spam_folder) &&
                $prefs->getValue('empty_spam_menu')) {
                $menu_spam_url = Util::addParameter(self::generateIMPUrl($menu_mailbox_url, $spam_folder), array('actionID' => 'empty_mailbox', 'mailbox_token' => self::getRequestToken('imp.mailbox')));
                $menu->add($menu_spam_url, _("Empty _Spam"), 'empty_spam.png', null, null, "return window.confirm('" . addslashes(_("Are you sure you wish to empty your spam folder?")) . "');", '__noselection');
            }
        }

        if (empty($GLOBALS['conf']['hooks']['disable_compose']) ||
            !Horde::callHook('_imp_hook_disable_compose', array(false), 'imp')) {
            $menu->add(self::composeLink(array('mailbox' => $GLOBALS['imp_mbox']['mailbox'])), _("_New Message"), 'compose.png');
        }

        if ($conf['user']['allow_folders']) {
            $menu->add(Util::nocacheUrl(Horde::applicationUrl('folders.php')), _("_Folders"), 'folders/folder.png');
        }

        $menu->add($menu_search_url, _("_Search"), 'search.png', $registry->getImageDir('horde'));

        if (($_SESSION['imp']['protocol'] != 'pop') &&
            $prefs->getValue('fetchmail_menu')) {
            if ($prefs->getValue('fetchmail_popup')) {
                $menu->add(Horde::applicationUrl('fetchmail.php'), _("F_etch Mail"), 'fetchmail.png', null, 'fetchmail', 'window.open(this.href, \'fetchmail\', \'toolbar=no,location=no,status=yes,scrollbars=yes,resizable=yes,width=300,height=450,left=100,top=100\'); return false;');
            } else {
                $menu->add(Horde::applicationUrl('fetchmail.php'), _("F_etch Mail"), 'fetchmail.png');
            }
        }

        if ($prefs->getValue('filter_menuitem')) {
            $menu->add(Horde::applicationUrl('filterprefs.php'), _("Fi_lters"), 'filters.png');
        }

        /* Logout. If IMP can auto login or IMP is providing authentication,
         * then we only show the logout link if the sidebar isn't shown or if
         * the configuration says to always show the current user a logout
         * link. */
        $impAuth = ((Auth::getProvider() == 'imp') || $_SESSION['imp']['autologin']);
        if (!$impAuth ||
            !$prefs->getValue('show_sidebar') ||
            Horde::showService('logout')) {
            /* If IMP provides authentication and the sidebar isn't always on,
             * target the main frame for logout to hide the sidebar while
             * logged out. */
            $logout_target = ($impAuth) ? '_parent' : null;

            /* If IMP doesn't provide Horde authentication then we need to use
             * IMP's logout screen since logging out should *not* end a Horde
             * session. */
            $logout_url = self::getLogoutUrl();

            $id = $menu->add($logout_url, _("_Log out"), 'logout.png', $registry->getImageDir('horde'), $logout_target);
            $menu->setPosition($id, HORDE_MENU_POS_LAST);
        }

        return ($type == 'object') ? $menu : $menu->render();
    }

    /**
     * Outputs IMP's menu to the current output stream.
     */
    static public function menu()
    {
        $t = new IMP_Template();
        $t->set('forminput', Util::formInput());
        $t->set('webkit', $GLOBALS['browser']->isBrowser('konqueror'));
        $t->set('use_folders', ($_SESSION['imp']['protocol'] != 'pop') && $GLOBALS['conf']['user']['allow_folders'], true);
        if ($t->get('use_folders')) {
            $t->set('accesskey', $GLOBALS['prefs']->getValue('widget_accesskey') ? Horde::getAccessKey(_("Open Fo_lder")) : '', true);
            $t->set('flist', self::flistSelect(array('selected' => $GLOBALS['imp_mbox']['mailbox'], 'inc_vfolder' => true)));

            $menu_view = $GLOBALS['prefs']->getValue('menu_view');
            $link = Horde::link('#', '', '', '', 'folderSubmit(true); return false;');
            $t->set('flink', sprintf('<ul><li class="rightFloat">%s%s<br />%s</a></li></ul>', $link, ($menu_view != 'text') ? Horde::img('folders/folder_open.png', _("Open Folder"), ($menu_view == 'icon') ? array('title' => _("Open Folder")) : array()) : '', ($menu_view != 'icon') ? Horde::highlightAccessKey(_("Open Fo_lder"), $t->get('accesskey')) : ''));
        }
        $t->set('menu_string', self::getMenu('string'));

        echo $t->fetch(IMP_TEMPLATES . '/menu.html');
    }

    /**
     * Outputs IMP's status/notification bar.
     */
    static public function status()
    {
        global $notification;

        /* Displau IMAP alerts. */
        foreach ($GLOBALS['imp_imap']->ob->alerts() as $alert) {
            $notification->push($alert, 'horde.warning');
        }

        $notification->notify(array('listeners' => array('status', 'audio')));
    }

    /**
     * Outputs IMP's quota information.
     */
    static public function quota()
    {
        $quotadata = self::quotaData(true);
        if (!empty($quotadata)) {
            $t = new IMP_Template();
            $t->set('class', $quotadata['class']);
            $t->set('message', $quotadata['message']);
            echo $t->fetch(IMP_TEMPLATES . '/quota/quota.html');
        }
    }

    /**
     * Returns data needed to output quota.
     *
     * @param boolean $long  Output long messages?
     *
     * @return array  Array with these keys: class, message, percent.
     */
    static public function quotaData($long = true)
    {
        if (!isset($_SESSION['imp']['quota']) ||
            !is_array($_SESSION['imp']['quota'])) {
            return false;
        }

        $quotaDriver = &IMP_Quota::singleton($_SESSION['imp']['quota']['driver'], $_SESSION['imp']['quota']['params']);
        if ($quotaDriver === false) {
            return false;
        }

        $quota = $quotaDriver->getQuota();
        if (is_a($quota, 'PEAR_Error')) {
            Horde::logMessage($quota, __FILE__, __LINE__, PEAR_LOG_ERR);
            return false;
        }

        $strings = $quotaDriver->getMessages();
        $ret = array('percent' => 0);

        if ($quota['limit'] != 0) {
            $quota['usage'] = $quota['usage'] / (1024 * 1024.0);
            $quota['limit'] = $quota['limit'] / (1024 * 1024.0);
            $ret['percent'] = ($quota['usage'] * 100) / $quota['limit'];
            if ($ret['percent'] >= 90) {
                $ret['class'] = 'quotaalert';
            } elseif ($ret['percent'] >= 75) {
                $ret['class'] = 'quotawarn';
            } else {
                $ret['class'] = 'control';
            }
            if ($long) {
                $ret['message'] = sprintf($strings['long'], $quota['usage'],
                                          $quota['limit'], $ret['percent']);
            } else {
                $ret['message'] = sprintf($strings['short'], $ret['percent'],
                                          $quota['limit']);
            }
        } else {
            // Hide unlimited quota message?
            if (!empty($_SESSION['imp']['quota']['hide_when_unlimited'])) {
                return false;
            }

            $ret['class'] = 'control';
            if ($quota['usage'] != 0) {
                $quota['usage'] = $quota['usage'] / (1024 * 1024.0);
                if ($long) {
                    $ret['message'] = sprintf($strings['nolimit_long'],
                                              $quota['usage']);
                } else {
                    $ret['message'] = sprintf($strings['nolimit_short'],
                                              $quota['usage']);
                }
            } else {
                if ($long) {
                    $ret['message'] = sprintf(_("Quota status: NO LIMIT"));
                } else {
                    $ret['message'] = _("No limit");
                }
            }
        }

        return $ret;
    }

    /**
     * Outputs the necessary javascript code to display the new mail
     * notification message.
     *
     * @param mixed $var  Either an associative array with mailbox names as
     *                    the keys and the message count as the values or
     *                    an integer indicating the number of new messages
     *                    in the current mailbox.
     *
     * @return string  The javascript for the popup message.
     */
    static public function getNewMessagePopup($var)
    {
        $t = new IMP_Template();
        $t->setOption('gettext', true);
        if (is_array($var)) {
            if (empty($var)) {
                return;
            }
            $folders = array();
            foreach ($var as $mb => $nm) {
                $folders[] = array(
                    'url' => Util::addParameter(self::generateIMPUrl('mailbox.php', $mb), 'no_newmail_popup', 1),
                    'name' => htmlspecialchars(self::displayFolder($mb)),
                    'new' => (int)$nm,
                );
            }
            $t->set('folders', $folders);

            if (($_SESSION['imp']['protocol'] != 'pop') &&
                $GLOBALS['prefs']->getValue('use_vinbox') &&
                ($vinbox_id = $GLOBALS['prefs']->getValue('vinbox_id'))) {
                $t->set('vinbox', Horde::link(Util::addParameter(self::generateIMPUrl('mailbox.php', $GLOBALS['imp_search']->createSearchID($vinbox_id)), 'no_newmail_popup', 1)));
            }
        } else {
            $t->set('msg', ($var == 1) ? _("You have 1 new message.") : sprintf(_("You have %s new messages."), $var));
        }
        $t_html = str_replace("\n", ' ', $t->fetch(IMP_TEMPLATES . '/newmsg/alert.html'));

        Horde::addScriptFile('prototype.js', 'horde', true);
        Horde::addScriptFile('effects.js', 'horde', true);
        Horde::addScriptFile('redbox.js', 'horde', true);
        return 'RedBox.overlay = false; RedBox.showHtml(\'' . addcslashes($t_html, "'/") . '\');';
    }

    /**
     * Generates the URL to the prefs page.
     *
     * @param boolean $full  Generate full URL?
     *
     * @return string  The URL to the IMP prefs page.
     */
    static public function prefsURL($full = false)
    {
        return Util::addParameter(Horde::url($GLOBALS['registry']->get('webroot', 'horde') . '/services/prefs.php', $full), array('app' => 'imp'));
    }

    /**
     * Get message indices list.
     *
     * @param mixed $indices  The following inputs are allowed:
     * <pre>
     * 1. An array of messages indices in the following format:
     *    msg_id IMP::IDX_SEP msg_folder
     *      msg_id      = Message index of the message
     *      IMP::IDX_SEP = IMP constant used to separate index/folder
     *      msg_folder  = The full folder name containing the message index
     * 2. An array with the full folder name as keys and an array of message
     *    indices as the values.
     * </pre>
     *
     * @return mixed  Returns an array with the folder as key and an array
     *                of message indices as the value (See #2 above).
     *                Else, returns false.
     */
    static public function parseIndicesList($indices)
    {
        if (!is_array($indices) || empty($indices)) {
            return array();
        }

        $msgList = array();

        reset($indices);
        if (!is_array(current($indices))) {
            /* Build the list of indices/mailboxes if input is format #1. */
            foreach ($indices as $msgIndex) {
                if (strpos($msgIndex, self::IDX_SEP) === false) {
                    return false;
                } else {
                    list($val, $key) = explode(self::IDX_SEP, $msgIndex);
                    $msgList[$key][] = $val;
                }
            }
        } else {
            /* We are dealing with format #2. */
            foreach ($indices as $key => $val) {
                if ($GLOBALS['imp_search']->isSearchMbox($key)) {
                    $msgList += self::parseIndicesList($val);
                } else {
                    /* Make sure we don't have any duplicate keys. */
                    $msgList[$key] = is_array($val) ? array_keys(array_flip($val)) : array($val);
                }
            }
        }

        return $msgList;
    }

    /**
     * Either sets or checks the value of the logintasks flag.
     *
     * @param integer $set  The value of the flag.
     *
     * @return integer  The value of the flag.
     *                  0 = No login tasks pending
     *                  1 = Login tasks pending
     *                  2 = Login tasks pending, previous tasks interrupted
     */
    static public function loginTasksFlag($set = null)
    {
        if (!is_null($set)) {
            $_SESSION['imp']['logintasks'] = $set;
        }

        return isset($_SESSION['imp']['logintasks'])
            ? $_SESSION['imp']['logintasks']
            : 0;
    }

    /**
     * Convert a preference value to/from the value stored in the preferences.
     *
     * Preferences that need to call this function before storing/retrieving:
     *   trash_folder, spam_folder, drafts_folder, sent_mail_folder
     * To allow folders from the personal namespace to be stored without this
     * prefix for portability, we strip the personal namespace. To tell apart
     * folders from the personal and any empty namespace, we prefix folders
     * from the empty namespace with the delimiter.
     *
     * @param string $mailbox  The folder path.
     * @param boolean $append  True - convert from preference value.
     *                         False - convert to preference value.
     *
     * @return string  The folder name.
     */
    static public function folderPref($folder, $append)
    {
        $def_ns = $GLOBALS['imp_imap']->defaultNamespace();
        $empty_ns = $GLOBALS['imp_imap']->getNamespace('');

        if ($append) {
            /* Converting from preference value. */
            if (!is_null($empty_ns) &&
                strpos($folder, $empty_ns['delimiter']) === 0) {
                /* Prefixed with delimiter => from empty namespace. */
                $folder = substr($folder, strlen($empty_ns['delimiter']));
            } elseif (($ns = $GLOBALS['imp_imap']->getNamespace($folder, false)) == null) {
                /* No namespace prefix => from personal namespace. */
                $folder = $def_ns['name'] . $folder;
            }
        } elseif (!$append && (($ns = $GLOBALS['imp_imap']->getNamespace($folder)) !== null)) {
            /* Converting to preference value. */
            if ($ns['name'] == $def_ns['name']) {
                /* From personal namespace => strip namespace. */
                $folder = substr($folder, strlen($def_ns['name']));
            } elseif ($ns['name'] == $empty_ns['name']) {
                /* From empty namespace => prefix with delimiter. */
                $folder = $empty_ns['delimiter'] . $folder;
            }
        }
        return $folder;
    }

    /**
     * Make sure a user-entered mailbox contains namespace information.
     *
     * @param string $mbox  The user-entered mailbox string.
     *
     * @return string  The mailbox string with any necessary namespace info
     *                 added.
     */
    static public function appendNamespace($mbox)
    {
        $ns_info = $GLOBALS['imp_imap']->getNamespace($mbox, false);
        if (is_null($ns_info)) {
            $ns_info = $GLOBALS['imp_imap']->defaultNamespace();
        }
        return $ns_info['name'] . $mbox;
    }

    /**
     * Generates a URL with necessary mailbox/index information.
     *
     * @param string $page      Page name to link to.
     * @param string $mailbox   The base mailbox to use on the linked page.
     * @param string $index     The index to use on the linked page.
     * @param string $tmailbox  The mailbox associated with $index.
     * @param boolean $encode   Encode the argument separator?
     *
     * @return string  URL to $page with any necessary mailbox information
     *                 added to the parameter list of the URL.
     */
    static public function generateIMPUrl($page, $mailbox, $index = null,
                                          $tmailbox = null, $encode = true)
    {
        return Util::addParameter(Horde::applicationUrl($page), self::getIMPMboxParameters($mailbox, $index, $tmailbox), null, $encode);
    }

    /**
     * Returns a list of parameters necessary to indicate current mailbox
     * status.
     *
     * @param string $mailbox   The mailbox to use on the linked page.
     * @param string $index     The index to use on the linked page.
     * @param string $tmailbox  The mailbox associated with $index to use on
     *                          the linked page.
     *
     * @return array  The list of parameters needed to indicate the current
     *                mailbox status.
     */
    static public function getIMPMboxParameters($mailbox, $index = null,
                                                $tmailbox = null)
    {
        $params = array('mailbox' => $mailbox);
        if (!is_null($index)) {
            $params['index'] = $index;
            if ($mailbox != $tmailbox) {
                $params['thismailbox'] = $tmailbox;
            }
        }
        return $params;
    }

    /**
     * Determine whether we're hiding deleted messages.
     *
     * @param boolean $force  Force a redetermination of the return value
     *                        (return value is normally cached after the first
     *                        call).
     *
     * @return boolean  True if deleted messages should be hidden.
     */
    static public function hideDeletedMsgs($force = false)
    {
        $delhide = &self::$_delhide;

        if (is_null($delhide) || $force) {
            if ($GLOBALS['prefs']->getValue('use_vtrash')) {
                $delhide = !$GLOBALS['imp_search']->isVTrashFolder();
            } else {
                $sortpref = self::getSort();
                $delhide = ($GLOBALS['prefs']->getValue('delhide') &&
                            !$GLOBALS['prefs']->getValue('use_trash') &&
                            ($GLOBALS['imp_search']->isSearchMbox() ||
                             ($sortpref['by'] != Horde_Imap_Client::SORT_THREAD)));
            }
        }

        return $delhide;
    }

    /**
     * Return a list of valid encrypt HTML option tags.
     *
     * @param string $default  The default encrypt option.
     *
     * @return string  The list of option tags.
     */
    static public function encryptList($default = null)
    {
        if (is_null($default)) {
            $default = $GLOBALS['prefs']->getValue('default_encrypt');
        }

        $enc_opts = array();
        $output = '';

        if (!empty($GLOBALS['conf']['utils']['gnupg']) &&
            $GLOBALS['prefs']->getValue('use_pgp')) {
            $enc_opts = array(
                self::PGP_ENCRYPT => _("PGP Encrypt Message"),
                self::PGP_SIGN => _("PGP Sign Message"),
                self::PGP_SIGNENC => _("PGP Sign/Encrypt Message"),
                self::PGP_SYM_ENCRYPT => _("PGP Encrypt Message with passphrase"),
                self::PGP_SYM_SIGNENC => _("PGP Sign/Encrypt Message with passphrase")
            );
        }
        if ($GLOBALS['prefs']->getValue('use_smime')) {
            $enc_opts = array_merge($enc_opts, array(
                self::SMIME_ENCRYPT => _("S/MIME Encrypt Message"),
                self::SMIME_SIGN => _("S/MIME Sign Message"),
                self::SMIME_SIGNENC => _("S/MIME Sign/Encrypt Message")
            ));
        }

        $enc_opts = array_merge(array(self::ENCRYPT_NONE => _("No Encryption")), $enc_opts);

        foreach ($enc_opts as $key => $val) {
             $output .= '<option value="' . $key . '"' . (($default == $key) ? ' selected="selected"' : '') . '>' . $val . '</option>' . "\n";
        }

        return $output;
    }

    /**
     * Return the sorting preference for the current mailbox.
     *
     * @param string $mbox  The mailbox to use (defaults to current mailbox
     *                      in the session).
     *
     * @return array  An array with the following keys:
     *                'by'  - Sort type (Horde_Imap_Client constant)
     *                'dir' - Sort direction
     *                'limit' - Was the sort limit reached?
     */
    static public function getSort($mbox = null)
    {
        if (is_null($mbox)) {
            $mbox = $GLOBALS['imp_mbox']['mailbox'];
        }

        $search_mbox = $GLOBALS['imp_search']->isSearchMbox($mbox);
        $prefmbox = $search_mbox ? $mbox : self::folderPref($mbox, false);

        $sortpref = @unserialize($GLOBALS['prefs']->getValue('sortpref'));
        $entry = (isset($sortpref[$prefmbox])) ? $sortpref[$prefmbox] : array();

        $ob = array(
            'by' => isset($entry['b']) ? $entry['b'] : $GLOBALS['prefs']->getValue('sortby'),
            'dir' => isset($entry['d']) ? $entry['d'] : $GLOBALS['prefs']->getValue('sortdir'),
            'limit' => false
        );

        /* Can't do threaded searches in search mailboxes. */
        if (!self::threadSortAvailable($mbox)) {
            if ($ob['by'] == Horde_Imap_Client::SORT_THREAD) {
                $ob['by'] = Horde_Imap_Client::SORT_DATE;
            }
        }

        if (!$search_mbox &&
            !empty($GLOBALS['conf']['server']['sort_limit'])) {
            try {
                $status = $GLOBALS['imp_imap']->ob->status($mbox, Horde_Imap_Client::STATUS_MESSAGES);
                if ($status['messages'] > $GLOBALS['conf']['server']['sort_limit']) {
                    $ob['limit'] = true;
                    $ob['by'] = Horde_Imap_Client::SORT_ARRIVAL;
                }
            } catch (Horde_Imap_Client_Exception $e) {}
        }

        if (!$ob['limit']) {
            if (self::isSpecialFolder($mbox)) {
                /* If the preference is to sort by From Address, when we are
                 * in the Drafts or Sent folders, sort by To Address. */
                if ($ob['by'] == Horde_Imap_Client::SORT_FROM) {
                    $ob['by'] = Horde_Imap_Client::SORT_TO;
                }
            } elseif ($ob['by'] == Horde_Imap_Client::SORT_TO) {
                $ob['by'] = Horde_Imap_Client::SORT_FROM;
            }
        }

        return $ob;
    }

    /**
     * Determines if thread sorting is available.
     *
     * @param string $mbox  The mailbox to check.
     *
     * @return boolean  True if thread sort is available for this mailbox.
     */
    static public function threadSortAvailable($mbox)
    {
        return !$GLOBALS['imp_search']->isSearchMbox($mbox) &&
               (!$GLOBALS['prefs']->getValue('use_trash') ||
                !$GLOBALS['prefs']->getValue('use_vtrash') ||
                $GLOBALS['imp_search']->isVTrashFolder($mbox));
    }

    /**
     * Set the sorting preference for the current mailbox.
     * TODO: Purge non-existant search sorts (i.e. non VFolder entries).
     *
     * @param integer $by      The sort type.
     * @param integer $dir     The sort direction.
     * @param string $mbox     The mailbox to use (defaults to current mailbox
     *                         in the session).
     * @param boolean $delete  Delete the entry?
     */
    static public function setSort($by = null, $dir = null, $mbox = null,
                                   $delete = false)
    {
        $entry = array();
        $sortpref = @unserialize($GLOBALS['prefs']->getValue('sortpref'));

        if (is_null($mbox)) {
            $mbox = $GLOBALS['imp_mbox']['mailbox'];
        }

        $prefmbox = $GLOBALS['imp_search']->isSearchMbox()
            ? $mbox
            : self::folderPref($mbox, false);

        if ($delete) {
            unset($sortpref[$prefmbox]);
        } else {
            if (!is_null($by)) {
                $entry['b'] = $by;
            }
            if (!is_null($dir)) {
                $entry['d'] = $dir;
            }

            if (!empty($entry)) {
                $sortpref[$prefmbox] = isset($sortpref[$prefmbox])
                    ? array_merge($sortpref[$prefmbox], $entry)
                    : $entry;
            }
        }

        if ($delete || !empty($entry)) {
            $GLOBALS['prefs']->setValue('sortpref', @serialize($sortpref));
        }
    }

    /**
     * Add inline javascript to the output buffer.
     *
     * @param mixed $script    The script text to add (can be stored in an
     *                         array also).
     * @param string $onload   Load the script after the page has loaded?
     *                         Either 'dom' (on dom:loaded), 'load'.
     */
    static public function addInlineScript($script, $onload = false)
    {
        if (is_array($script)) {
            $script = implode(';', $script);
        }

        $script = trim($script);
        if (empty($script)) {
            return;
        }

        switch ($onload) {
        case 'dom':
            $script = 'document.observe("dom:loaded", function() {' . $script . '});';
            break;

        case 'load':
            $script = 'Event.observe(window, "load", function() {' . $script . '});';
            break;
        }

        self::$_inlineScript[] = $script;

        // If headers have already been sent, we need to output a
        // <script> tag directly.
        if (ob_get_length() || headers_sent()) {
            self::outputInlineScript();
        }
    }

    /**
     * Print pending inline javascript to the output buffer.
     */
    static public function outputInlineScript()
    {
        if (!empty(self::$_inlineScript)) {
            echo self::wrapInlineScript(self::$_inlineScript);
        }

        self::$_inlineScript = array();
    }

    /**
     * Print inline javascript to output buffer after wrapping with necessary
     * javascript tags.
     *
     * @param array $script  The script to output.
     *
     * @return string  The script with the necessary HTML javascript tags
     *                 appended.
     */
    static public function wrapInlineScript($script)
    {
        return '<script type="text/javascript">//<![CDATA[' . "\n" . implode("\n", $script) . "\n//]]></script>\n";
    }

    /**
     * Is $mbox a 'special' folder (e.g. 'drafts' or 'sent-mail' folder)?
     *
     * @param string $mbox  The mailbox to query.
     *
     * @return boolean  Is $mbox a 'special' folder?
     */
    static public function isSpecialFolder($mbox)
    {
        /* Get the identities. */
        require_once 'Horde/Identity.php';
        $identity = &Identity::singleton(array('imp', 'imp'));

        return (($mbox == self::folderPref($GLOBALS['prefs']->getValue('drafts_folder'), true)) || in_array($mbox, $identity->getAllSentmailFolders()));
    }

    /**
     * Process mailbox/index information for current page load.
     *
     * @return array  Array with the following elements:
     * <pre>
     * 'mailbox' - The current active mailbox (may be search mailbox).
     * 'thismailbox' - The real IMAP mailbox of the current index.
     * 'index' - The IMAP message index.
     * </pre>
     */
    static public function getCurrentMailboxInfo()
    {
        $mbox = Util::getFormData('mailbox');
        return array(
            'mailbox' => empty($mbox) ? 'INBOX' : $mbox,
            'thismailbox' => Util::getFormData('thismailbox', $mbox),
            'index' => Util::getFormData('index')
        );
    }

    /**
     * Returns the proper logout URL for logging out of IMP.
     *
     * @return string  The logout URL.
     */
    static public function getLogoutUrl()
    {
        return ((Auth::getProvider() == 'imp') || $_SESSION['imp']['autologin'])
            ? Horde::getServiceLink('logout', 'horde', true)
            : Auth::addLogoutParameters($GLOBALS['registry']->get('webroot', 'imp') . '/login.php', AUTH_REASON_LOGOUT);
    }

    /**
     * Output the javascript needed to call the popup_imp JS function.
     *
     * @param string $url      The IMP page to load.
     * @param array $params    An array of paramters to pass to the URL.
     * @param integer $width   The width of the popup window.
     * @param integer $height  The height of the popup window.
     *
     * @return string  The javascript needed to call the popup code.
     */
    static public function popupIMPString($url, $params = array(),
                                          $width = 700, $height = 650)
    {
        return "popup_imp('" . Horde::applicationUrl($url) . "'," . $width . "," . $height . ",'" . $GLOBALS['browser']->escapeJSCode(str_replace('+', '%20', substr(Util::addParameter('', $params, null, false), 1))) . "');";
    }

    /**
     * Do necessary escaping to output JSON in a HTML parameter.
     *
     * @param mixed $json  The data to JSON-ify.
     *
     * @return string  The escaped string.
     */
    static public function escapeJSON($json)
    {
        return '/*-secure-' . rawurlencode(Horde_Serialize::serialize($json, SERIALIZE_JSON, NLS::getCharset())) . '*/';
    }

    /**
     * Log login related message.
     *
     * @param string $status  Either 'login', 'logout', or 'failed'.
     * @param string $file    The file where the error occurred.
     * @param integer $line   The line where the error occurred.
     * @param integer $level  The logging level.
     */
    static public function loginLogMessage($status, $file, $line,
                                           $level = PEAR_LOG_ERR)
    {
        switch ($status) {
        case 'login':
            $status_msg = 'Login success';
            break;

        case 'logout':
            $status_msg = 'Logout';
            break;

        case 'failed':
            $status_msg = 'FAILED LOGIN';
        }

        $imp_imap = &$GLOBALS['imp_imap']->ob;

        $msg = sprintf(
            $status_msg . ' for %s [%s]%s to {%s:%s [%s]}',
            $_SESSION['imp']['uniquser'],
            $_SERVER['REMOTE_ADDR'],
            (empty($_SERVER['HTTP_X_FORWARDED_FOR'])) ? '' : ' (forwarded for [' . $_SERVER['HTTP_X_FORWARDED_FOR'] . '])',
            $imp_imap->getParam('hostspec'),
            $imp_imap->getParam('port'),
            $_SESSION['imp']['protocol']
        );

        Horde::logMessage($msg, $file, $line, $level);
    }

    /**
     * Determines if the tidy extension is available and is the correct
     * version.  Returns the config array.
     *
     * @param integer $size  Size of the HTML data, in bytes.
     *
     * @return mixed  The config array, or false if tidy is not available.
     */
    static public function getTidyConfig($size)
    {
        if (!Util::extensionExists('tidy') ||
            function_exists('tidy_load_config') &&
            ($size > 250000)) {
            return false;
        }

        return array(
            'wrap' => 0,
            'indent' => true,
            'indent-spaces' => 4,
            'tab-size' => 4,
            'output-xhtml' => true,
            'enclose-block-text' => true,
            'hide-comments' => true,
            'numeric-entities' => true
        );
    }

    /**
     * Send response data to browser.
     *
     * @param mixed $data  The data to serialize and send to the browser.
     * @param string $ct   The content-type to send the data with.  Either
     *                     'json', 'js-json', 'html', 'plain', and 'xml'.
     */
    static public function sendHTTPResponse($data, $ct)
    {
        $charset = NLS::getCharset();

        // Output headers and encoded response.
        switch ($ct) {
        case 'json':
        case 'js-json':
            // JSON responses are a structured object which always
            // includes the response in a member named 'response', and an
            // additional array of messages in 'msgs' which may be updates
            // for the server or notification messages.
            $s_data = Horde_Serialize::serialize($data, SERIALIZE_JSON, $charset);

            // Make sure no null bytes sneak into the JSON output stream.
            // Null bytes cause IE to stop reading from the input stream,
            // causing malformed JSON data and a failed request.  These
            // bytes don't seem to break any other browser, but might as
            // well remove them anyway.
            $s_data = str_replace("\00", '', $s_data);

            if ($ct == 'json') {
                header('Content-Type: application/json');
                // Add prototype security delimiters to returned JSON.
                echo '/*-secure-' . String::convertCharset($s_data, $charset, 'UTF-8') . '*/';
            } else {
                header('Content-Type: text/html; charset=' . $charset);
                echo htmlspecialchars($s_data);
            }
            break;

        case 'html':
        case 'plain':
        case 'xml':
            header('Content-Type: text/' . $ct . '; charset=' . $charset);
            echo $data;
            break;

        default:
            echo $data;
        }

        exit;
    }

    /**
     * Outputs the necessary script tags, honoring local configuration
     * choices as to script caching.
     */
    static public function includeScriptFiles()
    {
        global $conf;

        $cache_type = @$conf['server']['cachejs'];

        if (empty($cache_type) ||
            $cache_type == 'none' ||
            (($cache_type == 'horde_cache') &&
             !($cache = &self::getCacheOb()))) {
            Horde::includeScriptFiles();
            return;
        }

        $js_tocache = $js_force = array();
        $mtime = array(0);

        $s_list = Horde::listScriptFiles();
        foreach ($s_list as $app => $files) {
            foreach ($files as $file) {
                if ($file['d'] && ($file['f'][0] != '/')) {
                    $js_tocache[$file['p'] . $file['f']] = false;
                    $mtime[] = filemtime($file['p'] . $file['f']);
                } else {
                    if (!$file['d'] &&
                        ($app == 'dimp') &&
                        ($file['f'] == 'mailbox.js')) {
                        // Special dimp case: we keep mailbox.js in templates
                        // not for purposes of running PHP scripts in the
                        // file, but for ease of templating.  Thus, this file
                        // is OK to include inline.
                        $js_tocache[$file['p'] . $file['f']] = true;
                        $mtime[] = filemtime($file['p'] . $file['f']);
                    } else {
                        $js_force[] = $file['u'];
                    }
                }
            }
        }

        require_once IMP_BASE . '/lib/version.php';
        $sig = md5(serialize($s_list) . max($mtime) . IMP_VERSION);

        switch ($cache_type) {
        case 'filesystem':
            $js_filename = '/' . $sig . '.js';
            $js_path = $conf['server']['cachejsparams']['file_location'] . $js_filename;
            $js_url = $conf['server']['cachejsparams']['file_url'] . $js_filename;
            $exists = file_exists($js_path);
            break;

        case 'horde_cache':
            $exists = $cache->exists($sig, empty($conf['server']['cachejsparams']['lifetime']) ? 0 : $conf['server']['cachejsparams']['lifetime']);
            $js_url = self::getCacheURL('js', $sig);
            break;
        }

        if (!$exists) {
            $out = '';
            foreach ($js_tocache as $key => $val) {
                // Separate JS files with a newline since some compressors may
                // strip trailing terminators.
                if ($val) {
                    // Minify these files a bit by removing newlines and
                    // comments.
                    $out .= preg_replace(array('/\n+/', '/\/\*.*?\*\//'), array('', ''), file_get_contents($key)) . "\n";
                } else {
                    $out .= file_get_contents($key) . "\n";
                }
            }

            switch ($cache_type) {
            case 'filesystem':
                register_shutdown_function(array('IMP', 'filesystemGC'), 'js');
                file_put_contents($js_path, $out);
                break;

            case 'horde_cache':
                $cache->set($sig, $out);
                break;
            }
        }

        foreach (array_merge(array($js_url), $js_force) as $val) {
            echo '<script type="text/javascript" src="' . $val . '"></script>' . "\n";
        }
    }

    /**
     * Creates a URL for cached IMP data.
     *
     * @param string $type  The cache type.
     * @param string $cid   The cache id.
     *
     * @return string  The URL to the cache page.
     */
    static public function getCacheURL($type, $cid)
    {
        $parts = array(
            $GLOBALS['registry']->get('webroot', 'imp'),
            'cache.php',
            $type,
            $cid
        );
        return Horde::url(implode('/', $parts));
    }

    /**
     * Outputs the necessary style tags, honoring local configuration
     * choices as to stylesheet caching.
     *
     * @param boolean $print  Include print CSS?
     * @param string $app     The application to load ('dimp' or 'imp').
     */
    static public function includeStylesheetFiles($print = false, $app = 'imp')
    {
        global $conf, $prefs, $registry;

        $theme = $prefs->getValue('theme');
        $themesfs = $registry->get('themesfs');
        $themesuri = $registry->get('themesuri');
        if ($app == 'imp') {
            $css = Horde::getStylesheets('imp', $theme);
        } else {
            $css = self::_getDIMPStylesheets($theme);
        }
        $css_out = array();

        // Add print specific stylesheets.
        if ($print) {
            // Add Horde print stylesheet
            $tmp = array('u' => $registry->get('themesuri', 'horde') . '/print/screen.css',
                         'f' => $registry->get('themesfs', 'horde') . '/print/screen.css');
            if ($app == 'dimp') {
                $tmp['m'] = 'print';
                $css_out[] = $tmp;
                $css_out[] = array('u' => $themesuri . '/print-dimp.css',
                                   'f' => $themesfs . '/print-dimp.css',
                                   'm' => 'print');
            } else {
                $css_out[] = $tmp;
            }
            if (file_exists($themesfs . '/' . $theme . '/print.css')) {
                $tmp = array('u' => $themesuri . '/' . $theme . '/print.css',
                             'f' => $themesfs . '/' . $theme . '/print.css');
                if ($app == 'dimp') {
                    $tmp['m'] = 'print';
                }
                $css_out[] = $tmp;
            }
        }

        if ($app == 'dimp') {
            // Load custom stylesheets.
            if (!empty($conf['css_files'])) {
                foreach ($conf['css_files'] as $css_file) {
                    $css[] = array('u' => $themesuri . '/' . $css_file,
                                   'f' => $themesfs .  '/' . $css_file);
                }
            }
        }

        $cache_type = @$conf['server']['cachecss'];

        if (empty($cache_type) ||
            $cache_type == 'none' ||
            (($cache_type == 'horde_cache') &&
             !($cache = &self::getCacheOb()))) {
            $css_out = array_merge($css, $css_out);
        } else {
            $mtime = array(0);
            $out = '';

            foreach ($css as $file) {
                $mtime[] = filemtime($file['f']);
            }

            require_once IMP_BASE . '/lib/version.php';
            $sig = md5(serialize($css) . max($mtime) . IMP_VERSION);

            switch ($cache_type) {
            case 'filesystem':
                $css_filename = '/' . $sig . '.css';
                $css_path = $conf['server']['cachecssparams']['file_location'] . $css_filename;
                $css_url = $conf['server']['cachecssparams']['file_url'] . $css_filename;
                $exists = file_exists($css_path);
                break;

            case 'horde_cache':
                $exists = $cache->exists($sig, empty($GLOBALS['conf']['server']['cachecssparams']['lifetime']) ? 0 : $GLOBALS['conf']['server']['cachecssparams']['lifetime']);
                $css_url = self::getCacheURL('css', $sig);
                break;
            }

            if (!$exists) {
                $flags = defined('FILE_IGNORE_NEW_LINES') ? (FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : 0;
                foreach ($css as $file) {
                    $path = substr($file['u'], 0, strrpos($file['u'], '/') + 1);
                    // Fix relative URLs, remove multiple whitespaces, and
                    // strip comments.
                    $out .= preg_replace(array('/(url\(["\']?)([^\/])/i', '/\s+/', '/\/\*.*?\*\//'), array('$1' . $path . '$2', ' ', ''), implode('', file($file['f'], $flags)));
                }

                switch ($cache_type) {
                case 'filesystem':
                    register_shutdown_function(array('IMP', 'filesystemGC'), 'css');
                    file_put_contents($css_path, $out);
                    break;

                case 'horde_cache':
                    $cache->set($sig, $out);
                    break;
                }
            }

            $css_out = array_merge(array(array('u' => $css_url)), $css_out);
        }

        foreach ($css_out as $file) {
            echo '<link href="' . $file['u'] . '" rel="stylesheet" type="text/css"' . (isset($file['m']) ? ' media="' . $file['m'] . '"' : '') . ' />' . "\n";
        }
    }

    /**
     * TODO - Temporary DIMP fix.
     */
    private function _getDIMPStylesheets($theme = '')
    {
        if ($theme === '' && isset($GLOBALS['prefs'])) {
            $theme = $GLOBALS['prefs']->getValue('theme');
        }

        $css = array();
        $rtl = isset($GLOBALS['nls']['rtl'][$GLOBALS['language']]);

        /* Collect browser specific stylesheets if needed. */
        $browser_css = array();
        if ($GLOBALS['browser']->isBrowser('msie')) {
            $ie_major = $GLOBALS['browser']->getMajor();
            if ($ie_major == 7) {
                $browser_css[] = 'ie7.css';
                $browser_css[] = 'ie7-dimp.css';
            } elseif ($ie_major < 7) {
                $browser_css[] = 'ie6_or_less.css';
                $browser_css[] = 'ie6_or_less-dimp.css';
                if ($GLOBALS['browser']->getPlatform() == 'mac') {
                    $browser_css[] = 'ie5mac.css';
                }
            }
        }
        if ($GLOBALS['browser']->isBrowser('opera')) {
            $browser_css[] = 'opera.css';
        }
        if ($GLOBALS['browser']->isBrowser('mozilla') &&
            $GLOBALS['browser']->getMajor() >= 5 &&
            preg_match('/rv:(.*)\)/', $GLOBALS['browser']->getAgentString(), $revision) &&
            $revision[1] <= 1.4) {
            $browser_css[] = 'moz14.css';
        }
        if (strpos(strtolower($GLOBALS['browser']->getAgentString()), 'safari') !== false) {
            $browser_css[] = 'safari.css';
        }

        foreach (array('horde', 'imp') as $app) {
            $themes_fs = $GLOBALS['registry']->get('themesfs', $app);
            $themes_uri = Horde::url($GLOBALS['registry']->get('themesuri', $app), false, -1);
            $css[] = array('u' => $themes_uri . '/screen.css', 'f' => $themes_fs . '/screen.css');
            if ($app == 'imp') {
                $css[] = array('u' => $themes_uri . '/screen-dimp.css', 'f' => $themes_fs . '/screen-dimp.css');
            }
            if (!empty($theme)) {
                if (file_exists($themes_fs . '/' . $theme . '/screen.css')) {
                    $css[] = array('u' => $themes_uri . '/' . $theme . '/screen.css', 'f' => $themes_fs . '/' . $theme . '/screen.css');
                }
                if (($app == 'imp') &&
                    file_exists($themes_fs . '/' . $theme . '/screen-dimp.css')) {
                    $css[] = array('u' => $themes_uri . '/' . $theme . '/screen-dimp.css', 'f' => $themes_fs . '/' . $theme . '/screen-dimp.css');
                }
            }

            if ($rtl) {
                $css[] = array('u' => $themes_uri . '/rtl.css', 'f' => $themes_fs . '/rtl.css');
                if (!empty($theme) &&
                    file_exists($themes_fs . '/' . $theme . '/rtl.css')) {
                    $css[] = array('u' => $themes_uri . '/' . $theme . '/rtl.css', 'f' => $themes_fs . '/' . $theme . '/rtl.css');
                }
            }
            foreach ($browser_css as $browser) {
                if (file_exists($themes_fs . '/' . $browser)) {
                    $css[] = array('u' => $themes_uri . '/' . $browser, 'f' => $themes_fs . '/' . $browser);
                }
                if (!empty($theme) &&
                    file_exists($themes_fs . '/' . $theme . '/' . $browser)) {
                    $css[] = array('u' => $themes_uri . '/' . $theme . '/' . $browser, 'f' => $themes_fs . '/' . $theme . '/' . $browser);
                }
            }
        }

        return $css;
    }

    /**
     * Do garbage collection in the statically served file directory.
     *
     * @param string $type  Either 'css' or 'js'.
     */
    static public function filesystemGC($type)
    {
        $dir_list = &self::$_dirlist;

        $ptr = $GLOBALS['conf']['server'][(($type == 'css') ? 'cachecssparams' : 'cachejsparams')];
        $dir = $ptr['file_location'];
        if (in_array($dir, $dir_list)) {
            return;
        }

        $c_time = time() - $ptr['lifetime'];
        $d = dir($dir);
        $dir_list[] = $dir;

        while (($entry = $d->read()) !== false) {
            $path = $dir . '/' . $entry;
            if (in_array($entry, array('.', '..'))) {
                continue;
            }

            if ($c_time > filemtime($path)) {
                $old_error = error_reporting(0);
                unlink($path);
                error_reporting($old_error);
            }
        }
        $d->close();
    }

    /**
     * Return the key used for [en|de]crypting auth credentials.
     *
     * @return string  The secret key.
     */
    static public function getAuthKey()
    {
        $key = &self::$_authkey;

        if (is_null($key)) {
            $key = Secret::getKey(Auth::getProvider() == 'imp' ? 'auth' : 'imp');
        }
        return $key;
    }

    /**
     * Create a message list string.
     * Format: {mbox_length}[mailbox]range_start:range_end,uid,uid2...
     *
     * @param array $in  An array with the full mailbox name as keys and an
     *                   array of message indices as the values (see output
     *                   from IMP::parseIndicesList()).
     *
     * @return string  The message list string. The string does not maintain
     *                 sorted information.
     */
    static public function toRangeString($in)
    {
        $str = '';

        foreach ($in as $mbox => $uids) {
            if (empty($uids)) {
                continue;
            }

            sort($uids, SORT_NUMERIC);
            $first = $last = array_shift($uids);
            $out = array();

            foreach ($uids as $val) {
                if ($last + 1 == $val) {
                    $last = $val;
                } else {
                    $out[] = $first . ($last == $first ? '' : (':' . $last));
                    $first = $last = $val;
                }
            }
            $out[] = $first . ($last == $first ? '' : (':' . $last));
            $str .= '{' . strlen($mbox) . '}' . $mbox . implode(',', $out);
        }

        return $str;
    }

    /**
     * Parse a message list string generated by IMP::toRangeString().
     * Format: ({mbox_length}[mailbox]range_start:range_end,uid,uid2)
     *
     * @param string $msgstr  The message list string.
     *
     * @return array  An array with the full mailbox name as keys and an
     *                array of message indices as the values (see output
     *                from IMP::parseIndicesList()).
     */
    static public function parseRangeString($msgstr)
    {
        $msglist = array();
        $msgstr = trim($msgstr);

        while ($msgstr) {
            if ($msgstr[0] != '{') {
                break;
            }
            $i = strpos($msgstr, '}');
            $count = intval(substr($msgstr, 1, $i - 1));
            $mbox = substr($msgstr, $i + 1, $count);
            $i += $count + 1;
            $end = strpos($msgstr, '{', $i);
            if ($end === false) {
                $uidstr = substr($msgstr, $i);
                $msgstr = '';
            } else {
                $uidstr = substr($msgstr, $i, $end - $i);
                $msgstr = substr($msgstr, $end);
            }

            $uids = array();
            $uidarray = explode(',', $uidstr);
            if (empty($uidarray)) {
                $uidarray = array($uidstr);
            }
            foreach ($uidarray as $val) {
                $range = explode(':', $val);
                if (count($range) == 1) {
                    $uids[] = intval($val);
                } else {
                    $uids = array_merge($uids, range(intval($range[0]), intval($range[1])));
                }
            }
            $msglist[$mbox] = $uids;
        }

        return $msglist;
    }

    /**
     * Returns a Horde_Cache object (if configured) and handles any errors
     * associated with creating the object.
     *
     * @return Horde_Cache  A pointer to a Horde_Cache object.
     */
    static public function &getCacheOb()
    {
        global $conf;

        if ($conf['cache']['driver'] == 'none') {
            return false;
        }

        $cache = &Horde_Cache::singleton($conf['cache']['driver'], Horde::getDriverConfig('cache', $conf['cache']['driver']));
        if (is_a($cache, 'PEAR_Error')) {
            Horde::fatal($cache, __FILE__, __LINE__);
        }

        return $cache;
    }

   /**
     * Generate the JS code necessary to open a passphrase dialog. Adds the
     * necessary JS files to open the dialog.
     *
     * @param string $type    The dialog type.
     * @param string $action  The JS code to run after success. Defaults to
     *                        reloading the current window.
     * @param array $params   Any additiona parameters to pass.
     *
     * @return string  The generated JS code.
     */
    static public function passphraseDialogJS($type, $action = null,
                                              $params = array())
    {
        Horde::addScriptFile('prototype.js', 'horde', true);
        Horde::addScriptFile('effects.js', 'horde', true);
        Horde::addScriptFile('encrypt.js', 'imp', true);
        Horde::addScriptFile('redbox.js', 'imp', true);

        switch ($type) {
        case 'PGPPersonal':
            $text = _("Enter your personal PGP passphrase.");
            break;

        case 'PGPSymmetric':
            $text = _("Enter the passphrase used to encrypt this message.");
            break;
        }

        $js_params = array(
            'action' => $action ? 'function() {' . $action . '}' : '',
            'uri' => Horde::applicationUrl('imp-dimp.php', true, -1) . '/' . $type,
            'params' => $params,
            'text' => $text,
            'ok_text' => _("OK"),
            'cancel_text' => _("Cancel")
        );

        return 'IMPEncrypt.display(\'' . IMP::escapeJSON($js_params) . '\')';
    }
}
