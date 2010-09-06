<?php
/**
 * IMP-specific prefs handling.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Prefs_Ui
{
    const PREF_DEFAULT = "default\0";
    const PREF_FOLDER_PAGE = 'folders.php';
    const PREF_NO_FOLDER = "nofolder\0";
    const PREF_SPECIALUSE = "specialuse\0";
    const PREF_VTRASH = "vtrash\0";

    /**
     * Cached folder list.
     *
     * @var array
     */
    protected $_cache = null;

    /**
     * Populate dynamically-generated preference values.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     */
    public function prefsEnum($ui)
    {
        global $prefs, $registry;

        switch ($ui->group) {
        case 'addressbooks':
            if (!$prefs->isLocked('add_source')) {
                try {
                    $sources = array();
                    foreach ($registry->call('contacts/sources', array(true)) as $source => $name) {
                        $sources[$source] = $name;
                    }
                    $ui->override['add_source'] = $sources;
                } catch (Horde_Exception $e) {
                    $ui->suppress[] = 'add_source';
                }
            }
            break;

        case 'delmove':
            if (isset($_SESSION['imp']['protocol']) &&
                ($_SESSION['imp']['protocol'] == 'pop')) {
                $tmp = $ui->prefs['delete_spam_after_report']['enum'];
                unset($tmp[2]);
                $ui->override['delete_spam_after_report'] = $tmp;
            }
            break;
        }
    }

    /**
     * Code to run on init when viewing prefs for this application.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     */
    public function prefsInit($ui)
    {
        global $conf, $prefs, $registry;

        $pop3 = (isset($_SESSION['imp']['protocol']) && ($_SESSION['imp']['protocol'] == 'pop'));

        switch ($ui->group) {
        case 'accounts':
            if (empty($conf['user']['allow_accounts'])) {
                $ui->suppress[] = 'accountsmanagement';
            } else {
                Horde::addScriptFile('accountsprefs.js', 'imp');
            }
            break;

        case 'acl':
            Horde::addScriptFile('acl.js', 'imp');
            break;

        case 'addressbooks':
            if (!$prefs->isLocked('sourceselect')) {
                Horde_Core_Prefs_Ui_Widgets::addressbooksInit();
            }
            break;

        case 'compose':
            if ($prefs->isLocked('default_encrypt')) {
                $ui->suppress[] = 'encryptselect';
            }
            if (empty($conf['compose']['allow_receipts'])) {
                $ui->suppress[] = 'disposition_request_read';
            }

            if (!$prefs->getValue('compose_html')) {
                $ui->suppress[] = 'compose_html_font_family';
                $ui->suppress[] = 'compose_html_font_size';
            }

            /* Sort encodings. */
            if (!$prefs->isLocked('sending_charset')) {
                asort($registry->nlsconfig['encodings']);
            }
            break;

        case 'delmove':
            if ($pop3) {
                $ui->suppress[] = 'move_ham_after_report';
                $ui->suppress[] = 'move_spam_report';
                $ui->suppress[] = 'empty_spam_menu';
                $ui->suppress[] = 'use_trash';
                $ui->suppress[] = 'trashselect';
                $ui->suppress[] = 'empty_trash_menu';
            } else {
                if ($prefs->isLocked('use_trash') ||
                    !$prefs->getValue('use_trash')) {
                    $ui->suppress[] = 'trashselect';
                    $ui->suppress[] = 'empty_trash_menu';
                }

                if (!$prefs->getValue('spam_folder')) {
                    $ui->suppress[] = 'move_spam_report';
                }
            }
            break;

        case 'dimp':
            if (!empty($conf['user']['force_view'])) {
                $ui->suppress[] = 'dynamic_view';
            }
            break;

        case 'display':
            /* Set the timezone on this page so the 'time_format' output uses
             * the configured time zone's time, not the system's time zone. */
            $registry->setTimeZone();
            if ($pop3) {
                $ui->suppress[] = 'nav_expanded';
                $ui->suppress[] = 'tree_view';
            }
            break;

        case 'filters':
            if (empty($_SESSION['imp']['filteravail'])) {
                $ui->suppress[] = 'filter_on_login';
                $ui->suppress[] = 'filter_on_display';
                $ui->suppress[] = 'filter_any_mailbox';
            }
            if (!$registry->hasMethod('mail/showFilters')) {
                $ui->suppress[] = 'filters_link';
            }
            if (!$registry->hasMethod('mail/showBlacklist')) {
                $ui->suppress[] = 'filters_blacklist_link';
            }
            if (!$registry->hasMethod('mail/showWhitelist')) {
                $ui->suppress[] = 'filters_whitelist_link';
            }
            break;

        case 'flags':
            if ($prefs->isLocked('msgflags') &&
                $prefs->isLocked('msgflags_user')) {
                $ui->nobuttons = true;
            } else {
                Horde::addScriptFile('colorpicker.js', 'horde');
                Horde::addScriptFile('flagprefs.js', 'imp');
            }
            break;

        case 'identities':
            if ($prefs->isLocked('sent_mail_folder')) {
                $ui->suppress[] = 'sentmailselect';
            } else {
                Horde::addScriptFile('folderprefs.js', 'imp');
            }

            if ($prefs->isLocked('signature_html') ||
                empty($_SESSION['imp']['rteavail'])) {
                $ui->suppress[] = 'signature_html_select';
            } else {
                Horde::addScriptFile('signaturehtml.js', 'imp');
                $GLOBALS['injector']->getInstance('Horde_Editor')->getEditor('Ckeditor', array('id' => 'signature_html'));
            }
            break;

        case 'logintasks':
            if ($prefs->isLocked('initial_page')) {
                $ui->suppress[] = 'initialpageselect';
            }
            if ($pop3) {
                $ui->suppress[] = 'initialpageselect';
                $ui->suppress[] = 'rename_sentmail_monthly';
                $ui->suppress[] = 'delete_sentmail_monthly';
                $ui->suppress[] = 'delete_sentmail_monthly_keep';
                $ui->suppress[] = 'purge_sentmail';
                $ui->suppress[] = 'purge_sentmail_interval';
                $ui->suppress[] = 'purge_sentmail_keep';
                $ui->suppress[] = 'purge_trash';
                $ui->suppress[] = 'purge_trash_interval';
                $ui->suppress[] = 'purge_trash_keep';
                $ui->suppress[] = 'purge_spam';
                $ui->suppress[] = 'purge_spam_interval';
                $ui->suppress[] = 'purge_spam_keep';
            }
            break;

        case 'newmail':
            if ($prefs->isLocked('nav_audio')) {
                $ui->suppress[] = 'soundselect';
            }
            if ($pop3) {
                $ui->suppress[] = 'nav_poll_all';
            }
            break;

        case 'pgp':
            if ($prefs->getValue('use_pgp')) {
                Horde::addScriptFile('imp.js', 'imp');
            } else {
                $ui->suppress[] = 'use_pgp_text';
                $ui->suppress[] = 'pgp_attach_pubkey';
                $ui->suppress[] = 'pgp_scan_body';
                $ui->suppress[] = 'pgp_verify';
                $ui->suppress[] = 'pgp_reply_pubkey';
                $ui->suppress[] = 'pgppublickey';
                $ui->suppress[] = 'pgpprivatekey';
            }
            break;

        case 'server':
            $code = array();

            if ($prefs->isLocked('drafts_folder')) {
                $ui->suppress[] = 'draftsselect';
            } else {
                $code['drafts'] = _("Enter the name for your new drafts folder.");
            }

            if ($prefs->isLocked('spam_folder')) {
                $ui->suppress[] = 'spamselect';
            } else {
                $code['spam'] = _("Enter the name for your new spam folder.");
            }

            if (!$prefs->isLocked('trash_folder') &&
                !$prefs->isLocked('use_vtrash')) {
                $code['trash'] = _("Enter the name for your new trash folder.");
            } else {
                $ui->suppress[] = 'trashselect';
            }

            if (!empty($code)) {
                Horde::addScriptFile('folderprefs.js', 'imp');
                Horde::addInlineJsVars(array(
                    'ImpFolderPrefs.folders' => $code
                ));
            }
            break;

        case 'smime':
            $use_smime = false;
            if ($prefs->getValue('use_smime')) {
                try {
                    $GLOBALS['injector']->getInstance('IMP_Crypt_Smime')->checkForOpenSSL();
                    $use_smime = true;
                } catch (Horde_Exception $e) {}
            }

            if ($use_smime) {
                Horde::addScriptFile('imp.js', 'imp');
            } else {
                $ui->suppress[] = 'use_smime_text';
                $ui->suppress[] = 'smime_verify';
                $ui->suppress[] = 'smimepublickey';
                $ui->suppress[] = 'smimeprivatekey';
            }
            break;

        case 'stationery':
            if ($prefs->isLocked('stationery')) {
                $ui->suppress[]  = 'stationerymanagement';
            } else {
                $ui->nobuttons = true;
            }
            break;

        case 'standard':
            if (!$prefs->getValue('preview_enabled')) {
                $ui->suppress[] = 'preview_maxlen';
                $ui->suppress[] = 'preview_strip_nl';
                $ui->suppress[] = 'preview_show_unread';
                $ui->suppress[] = 'preview_show_tooltip';
            }
            break;

        case 'viewing':
            if (empty($conf['maillog']['use_maillog'])) {
                $ui->suppress[] = 'disposition_send_mdn';
            }

            $mock_part = new Horde_Mime_Part();
            $mock_part->setType('text/html');
            $v = $GLOBALS['injector']->getInstance('IMP_Mime_Viewer')->getViewer($mock_part);

            if (!$v->canRender('inline')) {
                $ui->suppress[] = 'html_image_replacement';
                $ui->suppress[] = 'html_image_addrbook';
            }

            /* Sort encodings. */
            if (!$prefs->isLocked('default_msg_charset')) {
                asort($registry->nlsconfig['encodings']);
            }
            break;
        }

        /* Hide appropriate prefGroups. */
        if ($pop3) {
            $ui->suppressGroups[] = 'server';
            $ui->suppressGroups[] = 'flags';
        }

        try {
            $GLOBALS['injector']->getInstance('IMP_Imap_Acl');
        } catch (IMP_Exception $e) {
            $ui->suppressGroups[] = 'acl';
        }

        if (empty($conf['user']['allow_accounts'])) {
            $ui->suppressGroups[] = 'accounts';
        }

        $contacts_app = $registry->hasInterface('contacts');
        if (!$contacts_app || !$registry->hasPermission($contacts_app)) {
            $ui->suppressGroups[] = 'addressbooks';
        }

        if (!isset($GLOBALS['conf']['gnupg']['path'])) {
            $ui->suppressGroups[] = 'pgp';
        }

        if (!Horde_Util::extensionExists('openssl') ||
            !isset($conf['openssl']['path'])) {
            $ui->suppressGroups[] = 'smime';
        }

        // TODO: For now, disable this group since accounts code has not
        // yet been fully written.
        $ui->suppressGroups[] = 'accounts';
    }

    /**
     * Generate code used to display a special preference.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     * @param string $item             The preference name.
     *
     * @return string  The HTML code to display on the options page.
     */
    public function prefsSpecial($ui, $item)
    {
        switch ($item) {
        case 'accountsmanagement':
            return $this->_accountsManagement($ui);

        case 'aclmanagement':
            return $this->_aclManagement($ui);

        case 'draftsselect':
            return $this->_drafts();

        case 'encryptselect':
            return $this->_encrypt();

        case 'flagmanagement':
            return $this->_flagManagement();

        case 'initialpageselect':
            return $this->_initialPage();

        case 'pgpprivatekey':
            return $this->_pgpPrivateKey($ui);

        case 'pgppublickey':
            return $this->_pgpPublicKey($ui);

        case 'sentmailselect':
            return $this->_sentmail();

        case 'smimeprivatekey':
            return $this->_smimePrivateKey($ui);

        case 'smimepublickey':
            return $this->_smimePublicKey($ui);

        case 'signature_html_select':
            return $this->_signatureHtml();

        case 'soundselect':
            return $this->_sound();

        case 'sourceselect':
            $search = IMP::getAddressbookSearchParams();
            return Horde_Core_Prefs_Ui_Widgets::addressbooks(array(
                'fields' => $search['fields'],
                'sources' => $search['sources']
            ));

        case 'spamselect':
            return $this->_spam();

        case 'stationerymanagement':
            return $this->_stationeryManagement($ui);

        case 'trashselect':
            return $this->_trash();
        }

        return '';
    }

    /**
     * Special preferences handling on update.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     * @param string $item             The preference name.
     *
     * @return boolean  True if preference was updated.
     */
    public function prefsSpecialUpdate($ui, $item)
    {
        global $prefs;

        switch ($item) {
        case 'accountsmanagement':
            $this->_updateAccountsManagement($ui);
            return false;

        case 'aclmanagement':
            $this->_updateAclManagement($ui);
            return false;

        case 'draftsselect':
            return $this->_updateSpecialFolders('drafts_folder', IMP::formMbox($ui->vars->drafts, false), $ui->vars->drafts_folder_new, 'drafts', $ui);

        case 'encryptselect':
            return $prefs->setValue('default_encrypt', $ui->vars->default_encrypt);

        case 'flagmanagement':
            $this->_updateFlagManagement($ui);
            return false;

        case 'initialpageselect':
            return $prefs->setValue('initial_page', IMP::formMbox($ui->vars->initial_page, false));

        case 'pgpprivatekey':
            $this->_updatePgpPrivateKey($ui);
            return false;

        case 'pgppublickey':
            $this->_updatePgpPublicKey($ui);
            return false;

        case 'sentmailselect':
            return $this->_updateSentmail($ui);

        case 'smimeprivatekey':
            $this->_updateSmimePrivateKey($ui);
            return false;

        case 'smimepublickey':
            $this->_updateSmimePublicKey($ui);
            return false;

        case 'signature_html_select':
            return $GLOBALS['injector']->getInstance('IMP_Identity')->setValue('signature_html', $ui->vars->signature_html);

        case 'soundselect':
            return $prefs->setValue('nav_audio', $ui->vars->nav_audio);

        case 'sourceselect':
            return $this->_updateSource($ui);

        case 'spamselect':
            return $this->_updateSpecialFolders('spam_folder', IMP::formMbox($ui->vars->spam, false), $ui->vars->spam_new, 'spam', $ui);

        case 'stationerymanagement':
            return $this->_updateStationeryManagement($ui);

        case 'trashselect':
            return $this->_updateTrash($ui);
        }

        return false;
    }

    /**
     * Called when preferences are changed.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     */
    public function prefsCallback($ui)
    {
        global $prefs;

        /* Always check to make sure we have a valid trash folder if delete to
         * trash is active. */
        if (($prefs->isDirty('use_trash') || $prefs->isDirty('trash_folder')) &&
            $prefs->getValue('use_trash') &&
            !$prefs->getValue('trash_folder') &&
            !$prefs->getValue('use_vtrash')) {
            $GLOBALS['notification']->push(_("You have activated move to Trash but no Trash folder is defined. You will be unable to delete messages until you set a Trash folder in the preferences."), 'horde.warning');
        }

        switch ($ui->group) {
        case 'compose':
            if ($prefs->isDirty('mail_domain')) {
                $maildomain = preg_replace('/[^-\.a-z0-9]/i', '', $prefs->getValue('mail_domain'));
                $prefs->setValue('maildomain', $maildomain);
                if (!empty($maildomain)) {
                    $_SESSION['imp']['maildomain'] = $maildomain;
                }
            }
            break;

        case 'delmove':
            if ($prefs->isDirty('use_trash')) {
                $ui->suppress = array_diff($ui->suppress, array('trashselect', 'empty_trash_menu'));
            }
            if ($prefs->isDirty('use_vtrash')) {
                $GLOBALS['injector']->getInstance('IMP_Search')->init(true);
            }
            break;

        case 'dimp':
            if ($prefs->isDirty('dynamic_view')) {
                $_SESSION['imp']['view'] = $prefs->getValue('dynamic_view')
                    ? 'dimp'
                    : ($GLOBALS['browser']->isMobile() ? 'mimp' : 'imp');
            }
            break;

        case 'display':
            if ($prefs->isDirty('tree_view')) {
                $GLOBALS['registry']->getApiInstance('imp', 'application')->mailboxesChanged();
            }
            break;

        case 'server':
            if ($prefs->isDirty('use_vinbox')) {
                $GLOBALS['injector']->getInstance('IMP_Search')->init(true);
            }

            if ($prefs->isDirty('subscribe')) {
                $GLOBALS['registry']->getApiInstance('imp', 'application')->mailboxesChanged();
            }
            break;
        }
    }

    /**
     * Generate the menu to use on the prefs page.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     *
     * @return Horde_Menu  A Horde_Menu object.
     */
    public function prefsMenu($ui)
    {
        return IMP::getMenu();
    }

    /* Accounts management handling. */

    /**
     * Create code for accounts management.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     *
     * @return string  HTML UI code.
     */
    protected function _accountsManagement($ui)
    {
        $ui->nobuttons = true;

        Horde::addInlineHsVars(array(
            'ImpAccountsPrefs.confirm_delete' => _("Are you sure you want to delete this account?")
        ));

        $t = $GLOBALS['injector']->createInstance('Horde_Template');
        $t->setOption('gettext', true);

        if ($ui->vars->accounts_action == 'new') {
            $t->set('new', true);
        } else {
            $accounts_list = $GLOBALS['injector']->getInstance('IMP_Accounts')->getList();
            if (!empty($accounts_list)) {
                $t->set('delete_img', Horde::img('delete.png'));

                $out = array();
                foreach ($accounts_list as $key => $val) {
                    $out[] = array(
                        'id' => $key,
                        'label' => htmlspecialchars($val['label']),
                        'port' => htmlspecialchars($val['port']),
                        'secure' => ($val['secure'] == 'yes'),
                        'secure_auto' => ($val['secure'] == 'auto'),
                        'server' => htmlspecialchars($val['server']),
                        'type' => htmlspecialchars($val['type']),
                    );
                }
                $t->set('accounts', $out);
            }
        }

        return $t->fetch(IMP_TEMPLATES . '/prefs/accounts.html');
    }

    /**
     * Update accounts related preferences.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     */
    protected function _updateAccountsManagement($ui)
    {
        $success = false;

        switch ($ui->vars->accounts_action) {
        case 'add':
            if (!$ui->vars->accounts_server ||
                !$ui->vars->accounts_username) {
                    $GLOBALS['notification']->push(_("Missing required values."), 'horde.error');
                } else {
                    /* Port is not required. */
                    $port = $ui->vars->accounts_port;
                    if (!$port) {
                        $port = ($ui->vars->accounts_type == 'imap') ? 143 : 110;
                    }

                    /* Label is not required. */
                    $label = $ui->vars->accounts_label;
                    if (!strlen($label)) {
                        $label = $ui->vars->accounts_server . ':' . $port . ' [' . $ui->vars->accounts_type . ']';
                    }

                    $imp_accounts = $GLOBALS['injector']->getInstance('IMP_Accounts');
                    $imp_accounts->addAccount(array(
                        'label' => $label,
                        'port' => $port,
                        'secure' => $ui->vars->accounts_secure,
                        'server' => $ui->vars->accounts_server,
                        'type' => $ui->vars->accounts_type,
                        'username' => $ui->vars->accounts_username
                    ));
                    $GLOBALS['notification']->push(sprintf(_("Account \"%s\" added."), $ui->vars->accounts_server), 'horde.success');

                    $success = true;
                }
            break;

        case 'delete':
            $imp_accounts = $GLOBALS['injector']->getInstance('IMP_Accounts');
            $tmp = $imp_accounts->getAccount($ui->vars->accounts_data);
            if ($imp_accounts->deleteAccount($ui->vars->accounts_data)) {
                $GLOBALS['notification']->push(sprintf(_("Account \"%s\" deleted."), $tmp['server']), 'horde.success');
                $success = true;
            }
            break;
        }

        if ($success) {
            $GLOBALS['registry']->getApiInstance('imp', 'application')->mailboxesChanged();
        }
    }

    /* ACL management. */

    /**
     * Create code for ACL management.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     *
     * @return string  HTML UI code.
     */
    protected function _aclManagement($ui)
    {
        $acl = $GLOBALS['injector']->getInstance('IMP_Imap_Acl');
        $imp_folder = $GLOBALS['injector']->getInstance('IMP_Folder');
        $rights = $acl->getRights();

        $folder = empty($ui->vars->folder)
            ? 'INBOX'
            : IMP::formMbox($ui->vars->folder, false);

        try {
            $curr_acl = $acl->getACL($folder);
        } catch (IMP_Exception $e) {
            $GLOBALS['notification']->push($e);
            return '';
        }
        $canEdit = $acl->canEdit($folder, $GLOBALS['registry']->getAuth());

        $t = $GLOBALS['injector']->createInstance('Horde_Template');
        $t->setOption('gettext', true);

        $t->set('options', IMP::flistSelect(array('selected' => $folder)));
        $t->set('current', sprintf(_("Current access to %s"), IMP::displayFolder($folder)));
        $t->set('folder', IMP::formMbox($folder, true));
        $t->set('noacl', !count($curr_acl));
        $t->set('maxrule', 1);

        if (!$t->get('noacl')) {
            $i = 0;
            $cval = array();
            $protected = $acl->getProtected();

            foreach ($curr_acl as $index => $rule) {
                $entry = array(
                    'i' => ++$i,
                    'num_val' => ($i - 1),
                    'disabled' => in_array($index, $protected) || !$canEdit,
                    'index' => $index
                );

                /* Create table of each ACL option for each user granted
                 * permissions; enabled indicates the right has been given to
                 * the user */
                foreach (array_keys($rights) as $val) {
                    $entry['rule'][] = array(
                        'enabled' => in_array($val, $rule),
                        'val' => $val
                    );
                 }
                 $cval[] = $entry;
             }

             $t->set('curr_acl', $cval);
             $t->set('maxval', count($curr_acl));

             /* Number of individual ACL options, for table rendering. */
             $t->set('maxrule', count($rights));
        }

        $t->set('canedit', $canEdit);

        if (empty($_SESSION['imp']['imap']['admin'])) {
            $t->set('noadmin', true);
        } else {
            $current_users = array_keys($curr_acl);
            $new_user = array();

            foreach (array('anyone') + $GLOBALS['registry']->callAppMethod('imp', 'authUserList') as $user) {
                if (in_array($user, $current_users)) {
                    continue;
                }
                $new_user[] = htmlspecialchars($user);
            }
            $t->set('new_user', $new_user);
        }

        $rightsval = array();
        foreach ($rights as $right => $val) {
            $rightsval[] = array(
                'right' => $right,
                'desc' => $val['desc'],
                'title' => $val['title']
            );
        }

        $t->set('rights', $rightsval);
        $t->set('width', round(100 / (count($rightsval) + 1)) . '%');

        return $t->fetch(IMP_TEMPLATES . '/prefs/acl.html');
    }

    /**
     * Update ACL related preferences.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     */
    protected function _updateAclManagement($ui)
    {
        if ($ui->vars->change_acl_folder) {
            return;
        }

        $acl = $GLOBALS['injector']->getInstance('IMP_Imap_Acl');
        $folder = IMP::formMbox($ui->vars->folder, false);
        $rights = array_keys($acl->getRights());

        $acl_list = $ui->vars->acl;
        if (!$acl_list) {
            $acl_list = array();
        }
        $new_user = $ui->vars->new_user;

        /* Check to see if $new_user already has an acl on the folder. */
        if (strlen($new_user) && $ui->vars->new_acl) {
            if (isset($acl_list[$new_user])) {
                $acl_list[$new_user] = $ui->vars->new_acl;
            } else {
                try {
                    $acl->editACL($folder, $new_user, $ui->vars->new_acl);
                    if (count($ui->vars->new_acl)) {
                        $GLOBALS['notification']->push(sprintf(_("User \"%s\" successfully given the specified rights for the folder \"%s\"."), $new_user, IMP::getLabel($folder)), 'horde.success');
                    } else {
                        $GLOBALS['notification']->push(sprintf(_("All rights on folder \"%s\" successfully removed for user \"%s\"."), IMP::getLabel($folder), $new_user), 'horde.success');
                    }
                } catch (IMP_Exception $e) {
                    $GLOBALS['notification']->push($e);
                    return;
                }
            }
        }

        try {
            $curr_acl = $acl->getACL($folder);
        } catch (IMP_Exception $e) {
            $GLOBALS['notification']->notify($e);
            return;
        }
        $protected = $acl->getProtected();

        foreach ($acl_list as $user => $val) {
            if ($val) {
                /* We had to have an empty value submitted to make sure all
                 * users with acls were sent back, so we can remove those
                 * without checkmarks. */
                unset($val[0]);
            } else {
                $val = array();
            }

            if (!$user) {
                $GLOBALS['notification']->push(_("No user specified."), 'horde.error');
                continue;
            }

            if (in_array($user, $protected)) {
                continue;
            }

            /* Check to see if ACL changed, but only compare rights we
             * understand. */
            if (isset($curr_acl[$user])) {
                $knownRights = array_intersect($curr_acl[$user], $rights);
                sort($knownRights);
                sort($val);
                if ($knownRights == $val) {
                    continue;
                }
            }

            try {
                /* Only set or delete rights that we know (RFC 4314 [5.1.2])
                 * but ignore c and d rights that are sent for BC reasons (RFC
                 * 4314 [2.1.1]. */
                $val = array_merge($val, array_diff($curr_acl[$user], array_merge($rights, array('c', 'd'))));
                $acl->editACL($folder, $user, $val);
                if (!count($val)) {
                    if (isset($curr_acl[$user])) {
                        $GLOBALS['notification']->push(sprintf(_("All rights on folder \"%s\" successfully removed for user \"%s\"."), IMP::getLabel($folder), $user), 'horde.success');
                    }
                } else {
                    $GLOBALS['notification']->push(sprintf(_("User \"%s\" successfully given the specified rights for the folder \"%s\"."), $user, IMP::getLabel($folder)), 'horde.success');
                }
            } catch (IMP_Exception $e) {
                $GLOBALS['notification']->push($e);
            }
        }
    }

    /* Drafts selection. */

    /**
     * Create code for drafts selection.
     *
     * @return string  HTML UI code.
     */
    protected function _drafts()
    {
        $t = $GLOBALS['injector']->createInstance('Horde_Template');
        $t->setOption('gettext', true);

        $t->set('label', Horde::label('drafts', _("Drafts folder:")));
        $t->set('nofolder', self::PREF_NO_FOLDER);
        $t->set('flist', IMP::flistSelect(array(
            'filter' => array('INBOX'),
            'new_folder' => true,
            'selected' => IMP::folderPref($GLOBALS['prefs']->getValue('drafts_folder'), true)
        )));
        $t->set('special_use', $this->_getSpecialUse(IMP_Folder::$specialUse['drafts']));

        return $t->fetch(IMP_TEMPLATES . '/prefs/drafts.html');
    }

    /* Message encryption selection. */

    /**
     * Create code for message encryption selection.
     *
     * @return string  HTML UI code.
     */
    protected function _encrypt()
    {
        $t = $GLOBALS['injector']->createInstance('Horde_Template');

        $t->set('label', Horde::label('default_encrypt', _("Your default encryption method for sending messages:")));
        $t->set('elist', IMP::encryptList());

        return $t->fetch(IMP_TEMPLATES . '/prefs/encrypt.html');
    }

    /* IMAP Flag (keyword) management. */

    /**
     * Create code for flag management.
     *
     * @return string  HTML UI code.
     */
    protected function _flagManagement()
    {
        Horde::addInlineJsVars(array(
            'ImpFlagPrefs.new_prompt' => _("Please enter the label for the new flag:"),
            'ImpFlagPrefs.confirm_delete' => _("Are you sure you want to delete this flag?")
        ));

        $msgflags_locked = $GLOBALS['prefs']->isLocked('msgflags');
        $userflags_locked = $GLOBALS['prefs']->isLocked('msgflags_user');

        $t = $GLOBALS['injector']->createInstance('Horde_Template');
        $t->setOption('gettext', true);

        $out = array();
        $flaglist = $GLOBALS['injector']->getInstance('IMP_Imap_Flags')->getList(array('div' => true, 'fgcolor' => true));
        foreach ($flaglist as $key => $val) {
            $hash = hash('md5', $key);
            $bgid = 'bg_' . $hash;
            $color = htmlspecialchars($val['b']);
            $label = htmlspecialchars($val['l']);
            $bgstyle = 'background-color:' . $color;
            $tmp = array();

            if ($val['t'] == 'imapp') {
                $tmp['label'] = $label;
                $tmp['imapp'] = true;
                $tmp['label_name'] = 'label_' . $hash;
                if ($userflags_locked) {
                    $tmp['locked'] = true;
                }
            } else {
                $tmp['label'] = Horde::label($bgid, $label);
                $tmp['icon'] = $val['div'];
                if ($msgflags_locked) {
                    $tmp['locked'] = true;
                }
            }

            $tmp['colorstyle'] = $bgstyle . ';color:' . htmlspecialchars($val['f']);
            $tmp['colorid'] = $bgid;
            $tmp['color'] = $color;

            $tmp['flag_del'] = !empty($val['d']);

            $out[] = $tmp;
        }
        $t->set('flags', $out);

        $t->set('picker_img', Horde::img('colorpicker.png', _("Color Picker")));
        $t->set('userflags_notlocked', !$userflags_locked);

        return $t->fetch(IMP_TEMPLATES . '/prefs/flags.html');
    }

    /**
     * Update IMAP flag related preferences.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     */
    protected function _updateFlagManagement($ui)
    {
        $imp_flags = $GLOBALS['injector']->getInstance('IMP_Imap_Flags');

        if ($ui->vars->flag_action == 'add') {
            $GLOBALS['notification']->push(sprintf(_("Added flag \"%s\"."), $ui->vars->flag_data), 'horde.success');
            $imp_flags->addFlag($ui->vars->flag_data);
            return;
        }

        $def_color = $GLOBALS['prefs']->getValue('msgflags_color');

        // Don't set updated on these actions. User may want to do more actions.
        foreach ($imp_flags->getList() as $key => $val) {
            $md5 = hash('md5', $key);

            switch ($ui->vars->flag_action) {
            case 'delete':
                if ($ui->vars->flag_data == ('bg_' . $md5)) {
                    $imp_flags->deleteFlag($key);
                    $GLOBALS['notification']->push(sprintf(_("Deleted flag \"%s\"."), $val['l']), 'horde.success');
                }
                break;

            default:
                /* Change labels for user-defined flags. */
                if ($val['t'] == 'imapp') {
                    $label = $ui->vars->get('label_' . $md5);
                    if (strlen($label) && ($label != $val['l'])) {
                        $imp_flags->updateFlag($key, array('l' => $label));
                    }
                }

                /* Change background for all flags. */
                $bg = strtolower($ui->vars->get('bg_' . $md5));
                if ((isset($val['b']) && ($bg != $val['b'])) ||
                    (!isset($val['b']) && ($bg != $def_color))) {
                    $imp_flags->updateFlag($key, array('b' => $bg));
                }
                break;
            }
        }
    }

    /* Initial page selection. */

    /**
     * Create code for initial page selection.
     *
     * @return string  HTML UI code.
     */
    protected function _initialPage()
    {
        $t = $GLOBALS['injector']->createInstance('Horde_Template');
        $t->setOption('gettext', true);

        if (empty($GLOBALS['conf']['user']['allow_folders'])) {
            $t->set('nofolder', true);
        } else {
            $mailbox_selected = $GLOBALS['prefs']->getValue('initial_page');
            $t->set('folder_page', IMP::formMbox(self::PREF_FOLDER_PAGE, true));
            $t->set('folder_sel', $mailbox_selected == self::PREF_FOLDER_PAGE);
            $t->set('flist', IMP::flistSelect(array(
                'inc_vfolder' => true,
                'selected' => $mailbox_selected
            )));
        }

        $t->set('label', Horde::label('initial_page', _("View or mailbox to display after login:")));

        return $t->fetch(IMP_TEMPLATES . '/prefs/initialpage.html');
    }

    /* PGP Private Key management. */

    /**
     * Create code for personal PGP key management.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     *
     * @return string  HTML UI code.
     */
    protected function _pgpPrivateKey($ui)
    {
        $t = $GLOBALS['injector']->createInstance('Horde_Template');
        $t->setOption('gettext', true);

        $t->set('personalkey-help', Horde_Help::link('imp', 'pgp-overview-personalkey'));

        if (!Horde::isConnectionSecure()) {
            $t->set('notsecure', true);
        } else {
            $pgp_url = Horde::url('pgp.php');

            $t->set('has_key', $GLOBALS['prefs']->getValue('pgp_public_key') && $GLOBALS['prefs']->getValue('pgp_private_key'));
            if ($t->get('has_key')) {
                $t->set('viewpublic', Horde::link($pgp_url->copy()->add('actionID', 'view_personal_public_key'), _("View Personal Public Key"), null, 'view_key'));
                $t->set('infopublic', Horde::link($pgp_url->copy()->add('actionID', 'info_personal_public_key'), _("Information on Personal Public Key"), null, 'info_key'));
                $t->set('sendkey', Horde::link($ui->selfUrl(array('special' => true))->add('send_pgp_key', 1), _("Send Key to Public Keyserver")));
                $t->set('personalkey-public-help', Horde_Help::link('imp', 'pgp-personalkey-public'));

                $imple = $GLOBALS['injector']->getInstance('Horde_Ajax_Imple')->getImple(array('imp', 'PassphraseDialog'), array(
                    'type' => 'pgpPersonal'
                ));

                $imp_pgp = $GLOBALS['injector']->getInstance('IMP_Crypt_Pgp');
                $passphrase = $imp_pgp->getPassphrase('personal');
                $t->set('passphrase', empty($passphrase) ? Horde::link('#', _("Enter Passphrase"), null, null, null, null, null, array('id' => $imple->getPassphraseId())) . _("Enter Passphrase") : Horde::link($ui->selfUrl(array('special' => true))->add('unset_pgp_passphrase', 1), _("Unload Passphrase")) . _("Unload Passphrase"));

                $t->set('viewprivate', Horde::link($pgp_url->copy()->add('actionID', 'view_personal_private_key'), _("View Personal Private Key"), null, 'view_key'));
                $t->set('infoprivate', Horde::link($pgp_url->copy()->add('actionID', 'info_personal_private_key'), _("Information on Personal Private Key"), null, 'info_key'));
                $t->set('personalkey-private-help', Horde_Help::link('imp', 'pgp-personalkey-private'));
                $t->set('personalkey-delete-help', Horde_Help::link('imp', 'pgp-personalkey-delete'));

                Horde::addInlineScript(array(
                    '$("delete_pgp_privkey").observe("click", function(e) { if (!window.confirm(' . Horde_Serialize::serialize(_("Are you sure you want to delete your keypair? (This is NOT recommended!)"), Horde_Serialize::JSON, $GLOBALS['registry']->getCharset()) . ')) { e.stop(); } })'
                ), 'dom');
            } else {
                $imp_identity = $GLOBALS['injector']->getInstance('IMP_Identity');
                $t->set('fullname', $imp_identity->getFullname());
                $t->set('personalkey-create-name-help', Horde_Help::link('imp', 'pgp-personalkey-create-name'));
                $t->set('personalkey-create-comment-help', Horde_Help::link('imp', 'pgp-personalkey-create-comment'));
                $t->set('fromaddr', $imp_identity->getFromAddress());
                $t->set('personalkey-create-email-help', Horde_Help::link('imp', 'pgp-personalkey-create-email'));
                $t->set('personalkey-create-keylength-help', Horde_Help::link('imp', 'pgp-personalkey-create-keylength'));
                $t->set('personalkey-create-passphrase-help', Horde_Help::link('imp', 'pgp-personalkey-create-passphrase'));

                Horde::addInlineScript(array(
                    '$("create_pgp_key").observe("click", function(e) { if (!window.confirm(' . Horde_Serialize::serialize(_("Key generation may take a long time to complete.  Continue with key generation?"), Horde_Serialize::JSON, $GLOBALS['registry']->getCharset()) . ')) { e.stop(); } })'
                ), 'dom');

                if ($_SESSION['imp']['file_upload']) {
                    $cacheSess = $GLOBALS['injector']->getInstance('Horde_SessionObjects');
                    Horde::addInlineScript(array(
                        '$("import_pgp_personal").observe("click", function(e) { ' . Horde::popupJs($pgp_url, array('params' => array('actionID' => 'import_personal_public_key', 'reload' => $cacheSess->storeOid($ui->selfUrl()->setRaw(true), false)), 'height' => 275, 'width' => 750, 'urlencode' => true)) . '; e.stop(); })'
                    ), 'dom');
                }

                $t->set('personalkey-create-actions-help', Horde_Help::link('imp', 'pgp-personalkey-create-actions'));
            }
        }

        return $t->fetch(IMP_TEMPLATES . '/prefs/pgpprivatekey.html');
    }

    /**
     * Update personal PGP related preferences.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     */
    protected function _updatePgpPrivateKey($ui)
    {
        if (isset($ui->vars->delete_pgp_privkey)) {
            $GLOBALS['injector']->getInstance('IMP_Crypt_Pgp')->deletePersonalKeys();
            $GLOBALS['notification']->push(_("Personal PGP keys deleted successfully."), 'horde.success');
        } elseif (isset($ui->vars->create_pgp_key)) {
            /* Check that fields are filled out (except for Comment) and that
             * the passphrases match. */
            if (empty($ui->vars->generate_realname) ||
                empty($ui->vars->generate_email)) {
                $GLOBALS['notification']->push(_("Name and/or email cannot be empty"), 'horde.error');
            } elseif (empty($ui->vars->generate_passphrase1) ||
                      empty($ui->vars->generate_passphrase2)) {
                $GLOBALS['notification']->push(_("Passphrases cannot be empty"), 'horde.error');
            } elseif ($ui->vars->generate_passphrase1 !== $ui->vars->generate_passphrase2) {
               $GLOBALS['notification']->push(_("Passphrases do not match"), 'horde.error');
            } else {
                try {
                    $GLOBALS['injector']->getInstance('IMP_Crypt_Pgp')->generatePersonalKeys($ui->vars->generate_realname, $ui->vars->generate_email, $ui->vars->generate_passphrase1, $ui->vars->_generate_comment, $ui->vars->generate_keylength);
                    $GLOBALS['notification']->push(_("Personal PGP keypair generated successfully."), 'horde.success');
                } catch (Exception $e) {
                    $GLOBALS['notification']->push($e);
                }
            }
        } elseif (isset($ui->vars->send_pgp_key)) {
            try {
                $imp_pgp = $GLOBALS['injector']->getInstance('IMP_Crypt_Pgp');
                $imp_pgp->sendToPublicKeyserver($imp_pgp->getPersonalPublicKey());
                $GLOBALS['notification']->push(_("Key successfully sent to the public keyserver."), 'horde.success');
            } catch (Exception $e) {
                $GLOBALS['notification']->push($e);
            }
        } elseif (isset($ui->vars->unset_pgp_passphrase)) {
            $GLOBALS['injector']->getInstance('IMP_Crypt_Pgp')->unsetPassphrase('personal');
            $GLOBALS['notification']->push(_("PGP passphrase successfully unloaded."), 'horde.success');
        }
    }

    /* PGP Public Key management. */

    /**
     * Create code for PGP public key management.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     *
     * @return string  HTML UI code.
     */
    protected function _pgpPublicKey($ui)
    {
        $imp_pgp = $GLOBALS['injector']->getInstance('IMP_Crypt_Pgp');

        /* Get list of Public Keys on keyring. */
        try {
            $pubkey_list = $imp_pgp->listPublicKeys();
        } catch (Horde_Exception $e) {
            $pubkey_list = array();
        }

        $pgp_url = Horde::url('pgp.php');

        $t = $GLOBALS['injector']->createInstance('Horde_Template');
        $t->setOption('gettext', true);

        $t->set('manage_pubkey-help', Horde_Help::link('imp', 'pgp-manage-pubkey'));

        if (!empty($pubkey_list)) {
            $plist = array();
            $self_url = $ui->selfUrl(array('special' => true));

            foreach ($pubkey_list as $val) {
                $plist[] = array(
                    'name' => $val['name'],
                    'email' => $val['email'],
                    'view' => Horde::link($pgp_url->copy()->add(array('actionID' => 'view_public_key', 'email' => $val['email'])), sprintf(_("View %s Public Key"), $val['name']), null, 'view_key'),
                    'info' => Horde::link($pgp_url->copy()->add(array('actionID' => 'info_public_key', 'email' => $val['email'])), sprintf(_("Information on %s Public Key"), $val['name']), null, 'info_key'),
                    'delete' => Horde::link($self_url->copy()->add(array('delete_pgp_pubkey' => 1, 'email' => $val['email'])), sprintf(_("Delete %s Public Key"), $val['name']), null, null, "window.confirm('" . addslashes(_("Are you sure you want to delete this public key?")) . "')")
                );
            }
            $t->set('pubkey_list', $plist);
        }

        if ($_SESSION['imp']['file_upload']) {
            $t->set('can_import', true);
            $t->set('no_source', !$GLOBALS['prefs']->getValue('add_source'));
            if (!$t->get('no_source')) {
                $cacheSess = $GLOBALS['injector']->getInstance('Horde_SessionObjects');
                $t->set('import_pubkey-help', Horde_Help::link('imp', 'pgp-import-pubkey'));

                Horde::addInlineScript(array(
                    '$("import_pgp_public").observe("click", function(e) { ' . Horde::popupJs($pgp_url, array('params' => array('actionID' => 'import_public_key', 'reload' => $cacheSess->storeOid($ui->selfUrl()->setRaw(true), false)), 'height' => 275, 'width' => 750, 'urlencode' => true)) . '; e.stop(); })'
                ), 'dom');
            }
        }

        return $t->fetch(IMP_TEMPLATES . '/prefs/pgppublickey.html');
    }

    /**
     * Update PGP public key related preferences.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     */
    protected function _updatePgpPublicKey($ui)
    {
        if (isset($ui->vars->delete_pgp_pubkey)) {
            try {
                $GLOBALS['injector']->getInstance('IMP_Crypt_Pgp')->deletePublicKey($ui->vars->email);
                $GLOBALS['notification']->push(sprintf(_("PGP Public Key for \"%s\" was successfully deleted."), $ui->vars->email), 'horde.success');
            } catch (Horde_Exception $e) {
                $GLOBALS['notification']->push($e);
            }
        }
    }

    /* Sentmail selection. */

    /**
     * Create code for sentmail selection.
     *
     * @return string  HTML UI code.
     */
    protected function _sentmail()
    {
        $identity = $GLOBALS['injector']->getInstance('IMP_Identity');

        $js = array();
        foreach (array_keys($identity->getAll('id')) as $key) {
            $js[$key] = $identity->getValue('sent_mail_folder', $key);
        };

        Horde::addInlineJsVars(array(
            'ImpFolderPrefs.folders' => array('sent_mail_folder' => _("Create a new sent-mail folder")),
            'ImpFolderPrefs.sentmail' => $js
        ));

        $t = $GLOBALS['injector']->createInstance('Horde_Template');
        $t->setOption('gettext', true);

        $t->set('default', IMP::formMbox(self::PREF_DEFAULT, true));
        $t->set('label', Horde::label('sent_mail_folder', _("Sent mail folder:")));
        $t->set('flist', IMP::flistSelect(array(
            'filter' => array('INBOX'),
            'new_folder' => true
        )));
        $t->set('special_use', $this->_getSpecialUse(IMP_Folder::$specialUse['sentmail']));

        return $t->fetch(IMP_TEMPLATES . '/prefs/sentmail.html');
    }

    /**
     * Update sentmail related preferences.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     *
     * @return boolean  True if preferences were updated.
     */
    protected function _updateSentmail($ui)
    {
        global $conf, $prefs;

        if (!$conf['user']['allow_folders'] ||
            $prefs->isLocked('sent_mail_folder')) {
            return false;
        }

        $sent_mail_folder = IMP::formMbox($ui->vars->sent_mail_folder, false);
        if (empty($sent_mail_folder) && $ui->vars->sent_mail_folder_new) {
            $sent_mail_folder = Horde_String::convertCharset($ui->vars->sent_mail_folder_new, $GLOBALS['registry']->getCharset(), 'UTF7-IMAP');
        } elseif (strpos($sent_mail_folder, self::PREF_SPECIALUSE) === 0) {
            $sent_mail_folder = substr($folder, strlen(self::PREF_SPECIALUSE));
        } elseif (($sent_mail_folder == self::PREF_DEFAULT) &&
                  ($sm_default = $prefs->getDefault('sent_mail_folder'))) {
            $sent_mail_folder = $sm_default;
        }

        $sent_mail_folder = $GLOBALS['injector']->getInstance('IMP_Imap')->getOb()->appendNamespace($sent_mail_folder);

        if ($sent_mail_folder) {
            $imp_folder = $GLOBALS['injector']->getInstance('IMP_Folder');
            if (!$imp_folder->exists($sent_mail_folder) &&
                !$imp_folder->create($sent_mail_folder, $prefs->getValue('subscribe'), array('sent' => true))) {
                return false;
            }
        }

        return $GLOBALS['injector']->getInstance('IMP_Identity')->setValue('sent_mail_folder', $sent_mail_folder);
    }

    /* Personal S/MIME certificate management. */

    /**
     * Create code for personal S/MIME certificate management.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     *
     * @return string  HTML UI code.
     */
    protected function _smimePrivateKey($ui)
    {
        $t = $GLOBALS['injector']->createInstance('Horde_Template');
        $t->setOption('gettext', true);

        $t->set('personalkey-help', Horde_Help::link('imp', 'smime-overview-personalkey'));

        if (!Horde::isConnectionSecure()) {
            $t->set('notsecure', true);
        } else {
            $smime_url = Horde::url('smime.php');

            $t->set('has_key', $GLOBALS['prefs']->getValue('smime_public_key') && $GLOBALS['prefs']->getValue('smime_private_key'));
            if ($t->get('has_key')) {
                $t->set('viewpublic', Horde::link($smime_url->copy()->add('actionID', 'view_personal_public_key'), _("View Personal Public Certificate"), null, 'view_key'));
                $t->set('infopublic', Horde::link($smime_url->copy()->add('actionID', 'info_personal_public_key'), _("Information on Personal Public Certificate"), null, 'info_key'));

                $imple = $GLOBALS['injector']->getInstance('Horde_Ajax_Imple')->getImple(array('imp', 'PassphraseDialog'), array(
                    'type' => 'smimePersonal'
                ));

                $imp_smime = $GLOBALS['injector']->getInstance('IMP_Crypt_Smime');
                $passphrase = $imp_smime->getPassphrase();
                $t->set('passphrase', empty($passphrase) ? Horde::link('#', _("Enter Passphrase"), null, null, null, null, null, array('id' => $imple->getPassphraseId())) . _("Enter Passphrase") : Horde::link($ui->selfUrl(array('special' => true))->add('unset_smime_passphrase', 1), _("Unload Passphrase")) . _("Unload Passphrase"));

                $t->set('viewprivate', Horde::link($smime_url->copy()->add('actionID', 'view_personal_private_key'), _("View Personal Private Key"), null, 'view_key'));
                $t->set('personalkey-delete-help', Horde_Help::link('imp', 'smime-delete-personal-certs'));

                Horde::addInlineScript(array(
                    '$("delete_smime_personal").observe("click", function(e) { if (!window.confirm(' . Horde_Serialize::serialize(_("Are you sure you want to delete your keypair? (This is NOT recommended!)"), Horde_Serialize::JSON, $GLOBALS['registry']->getCharset()) . ')) { e.stop(); } })'
                ), 'dom');
            } elseif ($_SESSION['imp']['file_upload']) {
                $cacheSess = $GLOBALS['injector']->getInstance('Horde_SessionObjects');
                $t->set('import-cert-help', Horde_Help::link('imp', 'smime-import-personal-certs'));

                Horde::addInlineScript(array(
                    '$("import_smime_personal").observe("click", function(e) { ' . Horde::popupJs($smime_url, array('params' => array('actionID' => 'import_personal_public_key', 'reload' => $cacheSess->storeOid($ui->selfUrl()->setRaw(true), false)), 'height' => 275, 'width' => 750, 'urlencode' => true)) . '; e.stop(); })'
                ), 'dom');
            }
        }

        return $t->fetch(IMP_TEMPLATES . '/prefs/smimeprivatekey.html');
    }

    /**
     * Update personal S/MIME certificate related preferences.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     */
    protected function _updateSmimePrivateKey($ui)
    {
        if (isset($ui->vars->delete_smime_personal)) {
            $GLOBALS['injector']->getInstance('IMP_Crypt_Smime')->deletePersonalKeys();
            $GLOBALS['notification']->push(_("Personal S/MIME keys deleted successfully."), 'horde.success');
        } elseif (isset($ui->vars->unset_smime_passphrase)) {
            $GLOBALS['injector']->getInstance('IMP_Crypt_Smime')->unsetPassphrase();
            $GLOBALS['notification']->push(_("S/MIME passphrase successfully unloaded."), 'horde.success');
        }
    }

    /* S/MIME public certificate management. */

    /**
     * Create code for S/MIME public certificate management.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     *
     * @return string  HTML UI code.
     */
    protected function _smimePublicKey($ui)
    {
        $imp_smime = $GLOBALS['injector']->getInstance('IMP_Crypt_Smime');

        /* Get list of Public Keys on keyring. */
        try {
            $pubkey_list = $imp_smime->listPublicKeys();
        } catch (Horde_Exception $e) {
            $pubkey_list = array();
        }

        $smime_url = Horde::url('smime.php');

        $t = $GLOBALS['injector']->createInstance('Horde_Template');
        $t->setOption('gettext', true);

        $t->set('manage_pubkey-help', Horde_Help::link('imp', 'smime-manage-pubkey'));

        if (!empty($pubkey_list)) {
            $plist = array();
            $self_url = $ui->selfUrl(array('special' => true));

            foreach ($pubkey_list as $val) {
                $plist[] = array(
                    'name' => $val['name'],
                    'email' => $val['email'],
                    'view' => Horde::link($smime_url->copy()->add(array('actionID' => 'view_public_key', 'email' => $val['email'])), sprintf(_("View %s Public Key"), $val['name']), null, 'view_key'),
                    'info' => Horde::link($smime_url->copy()->add(array('actionID' => 'info_public_key', 'email' => $val['email'])), sprintf(_("Information on %s Public Key"), $val['name']), null, 'info_key'),
                    'delete' => Horde::link($self_url->copy()->add(array('delete_smime_pubkey' => 1, 'email' => $val['email'])), sprintf(_("Delete %s Public Key"), $val['name']), null, null, "window.confirm('" . addslashes(_("Are you sure you want to delete this public key?")) . "')")
                );
            }
            $t->set('pubkey_list', $plist);
        }

        if ($_SESSION['imp']['file_upload']) {
            $t->set('can_import', true);
            $t->set('no_source', !$GLOBALS['prefs']->getValue('add_source'));
            if (!$t->get('no_source')) {
                $cacheSess = $GLOBALS['injector']->getInstance('Horde_SessionObjects');
                $t->set('import_pubkey-help', Horde_Help::link('imp', 'smime-import-pubkey'));

                Horde::addInlineScript(array(
                    '$("import_smime_public").observe("click", function(e) { ' . Horde::popupJs($smime_url, array('params' => array('actionID' => 'import_public_key', 'reload' => $cacheSess->storeOid($ui->selfUrl()->setRaw(true), false)), 'height' => 275, 'width' => 750, 'urlencode' => true)) . '; e.stop(); })'
                ), 'dom');
            }
        }

        return $t->fetch(IMP_TEMPLATES . '/prefs/smimepublickey.html');
    }

    /**
     * Update S/MIME public key related preferences.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     */
    protected function _updateSmimePublicKey($ui)
    {
        if (isset($ui->vars->delete_smime_pubkey)) {
            try {
                $GLOBALS['injector']->getInstance('IMP_Crypt_Smime')->deletePublicKey($ui->vars->email);
                $GLOBALS['notification']->push(sprintf(_("S/MIME Public Key for \"%s\" was successfully deleted."), $ui->vars->email), 'horde.success');
            } catch (Horde_Exception $e) {
                $GLOBALS['notification']->push($e);
            }
        }
    }

    /* HTML Signature editing. */

    /**
     * Create code for HTML Signature editing.
     *
     * @return string  HTML UI code.
     */
    protected function _signatureHtml()
    {
        $identity = $GLOBALS['injector']->getInstance('IMP_Identity');

        $js = array();
        foreach (array_keys($identity->getAll('id')) as $key) {
            $js[$key] = $identity->getValue('signature_html', $key);
        };

        Horde::addInlineJsVars(array(
            'ImpHtmlSignaturePrefs.sigs' => $js
        ));

        $t = $GLOBALS['injector']->createInstance('Horde_Template');
        $t->setOption('gettext', true);

        return $t->fetch(IMP_TEMPLATES . '/prefs/signaturehtml.html');
    }

    /* Sound selection. */

    /**
     * Create code for sound selection.
     *
     * @return string  HTML UI code.
     */
    protected function _sound()
    {
        $t = $GLOBALS['injector']->createInstance('Horde_Template');
        $t->setOption('gettext', true);

        $nav_audio = $GLOBALS['prefs']->getValue('nav_audio');

        $t->set('nav_audio', $nav_audio);

        $sounds = array();
        foreach (Horde_Themes::soundList() as $key => $val) {
            $sounds[] = array(
                'c' => ($nav_audio == $key),
                'l' => htmlspecialchars($key),
                's' => htmlspecialchars($val->uri),
                'v' => htmlspecialchars($key)
            );
        }
        $t->set('sounds', $sounds);

        return $t->fetch(IMP_TEMPLATES . '/prefs/sound.html');
    }

    /* Addressbook selection. */

    /**
     * Update address book related preferences.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     *
     * @return boolean  True if preferences were updated.
     */
    protected function _updateSource($ui)
    {
        global $prefs;

        $data = Horde_Core_Prefs_Ui_Widgets::addressbooksUpdate($ui);
        $updated = false;

        if (isset($data['sources'])) {
            $prefs->setValue('search_sources', $data['sources']);
            unset($_SESSION['imp']['cache']['ac_ajax']);
            $updated = true;
        }

        if (isset($data['fields'])) {
            $prefs->setValue('search_fields', $data['fields']);
            $updated = true;
        }

        return $updated;
    }

    /* Spam selection. */

    /**
     * Create code for spam selection.
     *
     * @return string  HTML UI code.
     */
    protected function _spam()
    {
        $t = $GLOBALS['injector']->createInstance('Horde_Template');
        $t->setOption('gettext', true);

        $t->set('label', Horde::label('spam', _("Spam folder:")));
        $t->set('nofolder', self::PREF_NO_FOLDER);
        $t->set('flist', IMP::flistSelect(array(
            'filter' => array('INBOX'),
            'new_folder' => true,
            'selected' => IMP::folderPref($GLOBALS['prefs']->getValue('spam_folder'), true)
        )));
        $t->set('special_use', $this->_getSpecialUse(IMP_Folder::$specialUse['spam']));

        return $t->fetch(IMP_TEMPLATES . '/prefs/spam.html');
    }

    /* Stationery management. */

    /**
     * Create code for stationery management.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     *
     * @return string  HTML UI code.
     */
    protected function _stationeryManagement($ui)
    {
        $ob = $this->_parseStationeryVars($ui);

        if ($ob->stationery['t'] == 'html') {
            $GLOBALS['injector']->getInstance('Horde_Editor')->getEditor('Ckeditor', array('id' => 'content'));
        }

        $t = $GLOBALS['injector']->createInstance('Horde_Template');
        $t->setOption('gettext', true);

        $slist = array();
        foreach ($ob->stationery_list as $key => $choice) {
            $slist[] = array(
                'selected' => ($ob->selected === $key),
                'text' => $choice['n'] . ' ' . ($choice['t'] == 'html' ? _("(HTML)") : _("(Plain Text)")),
                'val' => $key
            );
        }
        $t->set('slist', $slist);

        $t->set('selected', strlen($ob->selected));
        $t->set('last_type', $ob->stationery['t']);
        $t->set('name_label', Horde::label('name', _("Stationery name:")));
        $t->set('name', $ob->stationery['n']);
        $t->set('type_label', Horde::label('name', _("Stationery type:")));
        $t->set('plain', $ob->stationery['t'] == 'plain');
        $t->set('html', $ob->stationery['t'] == 'html');
        $t->set('content_label', Horde::label('content', _("Stationery:")));
        $t->set('content', $ob->stationery['c']);

        return $t->fetch(IMP_TEMPLATES . '/prefs/stationery.html');
    }

    /**
     * Update stationery related preferences.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     */
    protected function _updateStationeryManagement($ui)
    {
        $updated = false;

        if ($ui->vars->delete) {
            /* Delete stationery. */
            $ob = $this->_parseStationeryVars($ui);

            if (isset($ob->stationery_list[$ob->selected])) {
                $updated = sprintf(_("The stationery \"%s\" has been deleted."), $ob->stationery_list[$ob->selected]['n']);
                unset($ob->stationery_list[$ob->selected]);
            }
        } elseif ($ui->vars->save) {
            /* Saving stationery. */
            $ob = $this->_parseStationeryVars($ui);

            if (strlen($ob->selected)) {
                $ob->stationery_list[$ob->selected] = $ob->stationery;
                $updated = sprintf(_("The stationery \"%s\" has been updated."), $ob->stationery['n']);
            } else {
                $ob->stationery_list[] = $ob->stationery;
                $updated = sprintf(_("The stationery \"%s\" has been added."), $ob->stationery['n']);
            }
        }

        if ($updated) {
            $GLOBALS['prefs']->setValue('stationery', serialize(Horde_String::convertCharset(array_values($ob->stationery_list), $GLOBALS['registry']->getCharset(), $GLOBALS['prefs']->getCharset())), false);
            $GLOBALS['notification']->push($updated, 'horde.success');
        }
    }

    /**
     * Parse the variables for the stationery management screen.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     *
     * @return object  Object with the following properties:
     * <pre>
     * 'selected' - (integer) The currently selected value.
     * 'stationery' - (array) The current stationery entry.
     * 'stationery_list' - (array) The list of all stationery values.
     * </pre>
     */
    protected function _parseStationeryVars($ui)
    {
        $ob = new stdClass;

        $ob->selected = strlen($ui->vars->stationery)
            ? intval($ui->vars->stationery)
            : null;

        $stationery_list = @unserialize($GLOBALS['prefs']->getValue('stationery', false));
        $ob->stationery_list = is_array($stationery_list)
            ? Horde_String::convertCharset($stationery_list, $GLOBALS['prefs']->getCharset())
            : array();

        $content = strval($ui->vars->content);
        $type = isset($ui->vars->type)
            ? $ui->vars->type
            : 'plain';

        if ($content && ($ui->vars->last_type != $type)) {
            $content = ($type == 'plain')
                ? $GLOBALS['injector']->getInstance('Horde_Text_Filter')->filter($content, 'Html2text')
                : nl2br(htmlspecialchars(htmlspecialchars($content)));
        }

        $ob->stationery = array(
            'c' => $content,
            'n' => strval($ui->vars->name),
            't' => $type
        );

        return $ob;
    }

    /* Trash selection. */

    /**
     * Create code for trash selection.
     *
     * @return string  HTML UI code.
     */
    protected function _trash()
    {
        $t = $GLOBALS['injector']->createInstance('Horde_Template');
        $t->setOption('gettext', true);

        $use_vtrash = $GLOBALS['prefs']->getValue('use_vtrash');

        $t->set('label', Horde::label('trash', _("Trash folder:")));
        $t->set('nofolder', self::PREF_NO_FOLDER);
        $t->set('vtrash', self::PREF_VTRASH);
        $t->set('vtrash_select', $use_vtrash);
        $t->set('flist', IMP::flistSelect(array(
            'filter' => array('INBOX'),
            'new_folder' => true,
            'selected' => ($use_vtrash ? null : IMP::folderPref($GLOBALS['prefs']->getValue('trash_folder'), true))
        )));
        $t->set('special_use', $this->_getSpecialUse(IMP_Folder::$specialUse['trash']));

        return $t->fetch(IMP_TEMPLATES . '/prefs/trash.html');
    }

    /**
     * Update trash related preferences.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     *
     * @return boolean  True if preferences were updated.
     */
    protected function _updateTrash($ui)
    {
        global $prefs;

        $trash = IMP::formMbox($ui->vars->trash, false);

        if ($trash == self::PREF_VTRASH) {
            if (!$prefs->isLocked('use_vtrash')) {
                $prefs->setValue('use_vtrash', 1);
                $prefs->setValue('trash_folder', '');
                return true;
            }
        } elseif ($this->_updateSpecialFolders('trash_folder', $trash, $ui->vars->trash_new, 'trash', $ui)) {
            $prefs->setValue('use_vtrash', 0);
            $prefs->setDirty('trash_folder', true);
            return true;
        }

        return false;
    }

    /* Utility functions. */

    /**
     * Update special folder preferences.
     *
     * @param string $pref             The pref name to update.
     * @param string $folder           The old name.
     * @param string $new              The new name.
     * @param string $type             Folder type: 'drafts', 'spam', 'trash'.
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     *
     * @return boolean  True if preferences were updated.
     */
    protected function _updateSpecialFolders($pref, $folder, $new, $type, $ui)
    {
        global $prefs;

        if (!$GLOBALS['conf']['user']['allow_folders'] ||
            $prefs->isLocked($pref)) {
            return false;
        }

        if ($folder == self::PREF_NO_FOLDER) {
            return $prefs->setValue($pref, '');
        }

        if (strpos($folder, self::PREF_SPECIALUSE) === 0) {
            $folder = substr($folder, strlen(self::PREF_SPECIALUSE));
        } elseif (!empty($new)) {
            $new = Horde_String::convertCharset($new, $GLOBALS['registry']->getCharset(), 'UTF7-IMAP');
            $folder = $GLOBALS['injector']->getInstance('IMP_Imap')->getOb()->appendNamespace($new);
            if (!$GLOBALS['injector']->getInstance('IMP_Folder')->create($folder, $prefs->getValue('subscribe'), array($type => true))) {
                $folder = null;
            }
        }

        return strlen($folder)
            ? $prefs->setValue($pref, IMP::folderPref($folder, false))
            : false;
    }

    /**
     * Get the list of special use mailboxes of a certain type.
     *
     * @param string $use  The special-use flag.
     *
     * @return string  HTML code.
     */
    protected function _getSpecialUse($use)
    {
        if (is_null($this->_cache)) {
            $this->_cache = $GLOBALS['injector']->getInstance('IMP_Imap')->getOb()->listMailboxes('*', Horde_Imap_Client::MBOX_ALL, array(
                'attributes' => true,
                'special_use' => true,
                'sort' => true
            ));
        }

        $special_use = array();
        foreach ($this->_cache as $key => $val) {
            if (in_array($use, $val['attributes'])) {
                $special_use[] = array(
                    'l' => htmlspecialchars(IMP::getLabel($val)),
                    'v' => IMP::formMbox(PREF_SPECIALUSE . $key, true)
                );
            }
        }

        if (empty($special_use)) {
            return '';
        }

        $t = $GLOBALS['injector']->createInstance('Horde_Template');
        $t->setOption('gettext', true);
        $t->set('special_use', $special_use);

        return $t->fetch(IMP_TEMPLATES . '/prefs/specialuse.html');
    }

}
