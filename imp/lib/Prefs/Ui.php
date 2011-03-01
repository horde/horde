<?php
/**
 * IMP-specific prefs handling.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
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

    /**
     * Cached folder list.
     *
     * @var array
     */
    protected $_cache = null;

    /**
     * Run once on init when viewing prefs for an application.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     */
    public function prefsInit($ui)
    {
        global $conf, $injector, $registry, $session;

        /* Hide appropriate prefGroups. */
        if ($session->get('imp', 'protocol') == 'pop') {
            $ui->suppressGroups[] = 'flags';
            $ui->suppressGroups[] = 'searches';
            $ui->suppressGroups[] = 'server';
        }

        try {
            $injector->getInstance('IMP_Imap_Acl');
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

        if (!isset($conf['gnupg']['path'])) {
            $ui->suppressGroups[] = 'pgp';
        }

        if (!Horde_Util::extensionExists('openssl') ||
            !isset($conf['openssl']['path'])) {
            $ui->suppressGroups[] = 'smime';
        }

        if (!$injector->getInstance('IMP_Factory_Imap')->create()->allowFolders()) {
            $ui->suppressGroups[] = 'searches';
        }

        // TODO: For now, disable this group since accounts code has not
        // yet been fully written.
        $ui->suppressGroups[] = 'accounts';
    }

    /**
     * Determine active prefs when displaying a group.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     */
    public function prefsGroup($ui)
    {
        global $conf, $injector, $prefs, $registry, $session;

        switch ($ui->group) {
        case 'identities':
            if ($prefs->isLocked('sent_mail_folder')) {
                $ui->suppress[] = 'sentmailselect';
            } else {
                Horde::addScriptFile('folderprefs.js', 'imp');
            }

            if ($prefs->isLocked('signature_html') ||
                !$session->get('imp', 'rteavail')) {
                $ui->suppress[] = 'signature_html_select';
            } else {
                Horde::addScriptFile('signaturehtml.js', 'imp');
                IMP_Ui_Editor::init(false, 'signature_html');
            }
            break;
        }

        $folder_code = array();

        foreach ($ui->getChangeablePrefs() as $val) {
            switch ($val) {
            case 'accountsmanagement':
                if (empty($conf['user']['allow_accounts'])) {
                    $ui->suppress[] = 'accountsmanagement';
                } else {
                    Horde::addScriptFile('accountsprefs.js', 'imp');
                }
                break;

            case 'aclmanagement':
                Horde::addScriptFile('acl.js', 'imp');
                break;

            case 'add_source':
                try {
                    $ui->override['add_source'] = $registry->call('contacts/sources', array(true));
                } catch (Horde_Exception $e) {
                    $ui->suppress[] = 'add_source';
                }
                break;

            case 'alternative_display':
                $mock_part = new Horde_Mime_Part();
                $mock_part->setType('text/html');
                $v = $injector->getInstance('IMP_Factory_MimeViewer')->create($mock_part);

                if (!$v->canRender('inline')) {
                    $ui->suppress[] = 'alternative_display';
                }
                break;

            case 'compose_html_font_family':
            case 'compose_html_font_size':
                if (!$prefs->getValue('compose_html')) {
                    $ui->suppress[] = $val;
                }
                break;

            case 'delete_sentmail_monthly':
            case 'delete_sentmail_monthly_keep':
            case 'empty_spam_menu':
            case 'initialpageselect':
            case 'move_ham_after_report':
            case 'nav_expanded':
            case 'nav_poll_all':
            case 'purge_sentmail':
            case 'purge_sentmail_interval':
            case 'purge_sentmail_keep':
            case 'purge_spam':
            case 'purge_spam_interval':
            case 'purge_spam_keep':
            case 'purge_trash':
            case 'purge_trash_interval':
            case 'purge_trash_keep':
            case 'rename_sentmail_monthly':
            case 'tree_view':
            case 'use_trash':
                if ($session->get('imp', 'protocol') == 'pop') {
                    $ui->suppress[] = $val;
                }
                break;

            case 'delete_spam_after_report':
                if ($session->get('imp', 'protocol') == 'pop') {
                    $tmp = $ui->prefs['delete_spam_after_report']['enum'];
                    unset($tmp[2]);
                    $ui->override['delete_spam_after_report'] = $tmp;
                }
                break;

            case 'drafts_folder':
                $folder_code['drafts'] = _("Enter the name for your new drafts folder.");
                break;

            case 'draftsselect':
                if ($prefs->isLocked('drafts_folder')) {
                    $ui->suppress[] = 'draftsselect';
                }
                break;

            case 'dynamic_view':
                if (!empty($conf['user']['force_view'])) {
                    $ui->suppress[] = 'dynamic_view';
                }
                break;

            case 'empty_trash_menu':
            case 'trashselect':
                if (($session->get('imp', 'protocol') == 'pop') ||
                    $prefs->isLocked('use_trash') ||
                    !$prefs->getValue('use_trash')) {
                    $ui->suppress[] = $val;
                }
                break;

            case 'encryptselect':
                if ($prefs->isLocked('default_encrypt')) {
                    $ui->suppress[] = 'encryptselect';
                }
                break;

            case 'filter_any_mailbox':
            case 'filter_on_display':
            case 'filter_on_login':
                if (!$session->get('imp', 'filteravail')) {
                    $ui->suppress[] = $val;
                }
                break;

            case 'filters_blacklist_link':
                if (!$registry->hasMethod('mail/showBlacklist')) {
                    $ui->suppress[] = 'filters_blacklist_link';
                }
                break;

            case 'filters_link':
                if (!$registry->hasMethod('mail/showFilters')) {
                    $ui->suppress[] = 'filters_link';
                }
                break;

            case 'filters_whitelist_link':
                if (!$registry->hasMethod('mail/showWhitelist')) {
                    $ui->suppress[] = 'filters_whitelist_link';
                }
                break;

            case 'flagmanagement':
                if ($prefs->isLocked('msgflags') &&
                    $prefs->isLocked('msgflags_user')) {
                    $ui->nobuttons = true;
                } else {
                    Horde::addScriptFile('colorpicker.js', 'horde');
                    Horde::addScriptFile('flagprefs.js', 'imp');
                }
                break;

            case 'initialpageselect':
                if ($prefs->isLocked('initial_page')) {
                    $ui->suppress[] = 'initialpageselect';
                }
                break;

            case 'pgp_attach_pubkey':
            case 'use_pgp_text':
            case 'pgp_reply_pubkey':
            case 'pgp_scan_body':
            case 'pgp_verify':
            case 'pgpprivatekey':
            case 'pgppublickey':
                if (!$prefs->getValue('use_pgp')) {
                    $ui->suppress[] = $val;
                }
                break;

            case 'pgpmanagement':
            case 'smimemanagement':
                Horde::addScriptFile('imp.js', 'imp');
                break;

            case 'preview_maxlen':
            case 'preview_show_unread':
            case 'preview_show_tooltip':
            case 'preview_strip_nl':
                if (!$prefs->getValue('preview_enabled')) {
                    $ui->suppress[] = $val;
                }
                break;

            case 'searchesmanagement':
                Horde::addScriptFile('searchesprefs.js', 'imp');
                break;

            case 'send_mdn':
                if (empty($conf['maillog']['use_maillog'])) {
                    $ui->suppress[] = 'send_mdn';
                }
                break;

            case 'smime_verify':
            case 'smimeprivatekey':
            case 'smimepublickey':
            case 'use_smime_text':
                if (!$prefs->getValue('use_smime')) {
                    $ui->suppress[] = $val;
                }
                break;

            case 'soundselect':
                if ($prefs->isLocked('nav_audio')) {
                    $ui->suppress[] = 'soundselect';
                }
                break;

            case 'sourceselect':
                Horde_Core_Prefs_Ui_Widgets::addressbooksInit();
                break;

            case 'spam_folder':
                $folder_code['spam'] = _("Enter the name for your new spam folder.");
                break;

            case 'spamselect':
                if ($prefs->isLocked('spam_folder')) {
                    $ui->suppress[] = 'spamselect';
                }
                break;

            case 'stationery':
                $ui->nobuttons = true;
                break;

            case 'stationerymanagement':
                if ($prefs->isLocked('stationery')) {
                    $ui->suppress[]  = 'stationerymanagement';
                }
                break;

            case 'time_format':
                /* Set the timezone on this page so the output uses the
                 * configured time zone's time, not the system's time zone. */
                $registry->setTimeZone();
                break;

            case 'trash_folder':
                $folder_code['trash'] = _("Enter the name for your new trash folder.");
                break;

            case 'trashselect':
                if ($prefs->isLocked('trash_folder')) {
                    $ui->suppress[] = 'trashselect';
                }
                break;
            }
        }

        if (!empty($folder_code)) {
            Horde::addScriptFile('folderprefs.js', 'imp');
            Horde::addInlineJsVars(array(
                'ImpFolderPrefs.folders' => $folder_code
            ));
        }
    }

    /**
     * Generate code used to display a special preference.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     * @param string $item             The preference name.
     *
     * @return string  The HTML code to display on the prefs page.
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

        case 'mailto_handler':
            return $this->_mailtoHandler();

        case 'pgpprivatekey':
            return $this->_pgpPrivateKey($ui);

        case 'pgppublickey':
            return $this->_pgpPublicKey($ui);

        case 'searchesmanagement':
            return $this->_searchesManagement();

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
            return $this->_updateSpecialFolders('drafts_folder', IMP_Mailbox::formFrom($ui->vars->drafts), $ui->vars->drafts_folder_new, 'drafts', $ui);

        case 'encryptselect':
            return $prefs->setValue('default_encrypt', $ui->vars->default_encrypt);

        case 'flagmanagement':
            return $this->_updateFlagManagement($ui);

        case 'initialpageselect':
            return $prefs->setValue('initial_page', IMP_Mailbox::formFrom($ui->vars->initial_page));

        case 'pgpprivatekey':
            $this->_updatePgpPrivateKey($ui);
            return false;

        case 'pgppublickey':
            $this->_updatePgpPublicKey($ui);
            return false;

        case 'searchesmanagement':
            $this->_updateSearchesManagement($ui);
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
            return $this->_updateSpecialFolders('spam_folder', IMP_Mailbox::formFrom($ui->vars->spam), $ui->vars->spam_new, 'spam', $ui);

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
        global $notification, $prefs, $registry, $session;

        /* Always check to make sure we have a valid trash folder if delete to
         * trash is active. */
        if (($prefs->isDirty('use_trash') || $prefs->isDirty('trash_folder')) &&
            $prefs->getValue('use_trash') &&
            !$prefs->getValue('trash_folder')) {
            $notification->push(_("You have activated move to Trash but no Trash folder is defined. You will be unable to delete messages until you set a Trash folder in the preferences."), 'horde.warning');
        }

        switch ($ui->group) {
        case 'compose':
            if ($prefs->isDirty('mail_domain')) {
                $maildomain = preg_replace('/[^-\.a-z0-9]/i', '', $prefs->getValue('mail_domain'));
                $prefs->setValue('maildomain', $maildomain);
                if (!empty($maildomain)) {
                    $session->set('imp', 'maildomain', $maildomain);
                }
            }
            break;

        case 'dimp':
            if ($prefs->isDirty('dynamic_view')) {
                $session->set(
                    'imp',
                    'view',
                    $prefs->getValue('dynamic_view')
                        ? 'dimp'
                        : ($GLOBALS['browser']->isMobile() ? 'mimp' : 'imp')
                );
            }
            break;

        case 'display':
            if ($prefs->isDirty('tree_view')) {
                $registry->getApiInstance('imp', 'application')->mailboxesChanged();
            }
            break;

        case 'server':
            if ($prefs->isDirty('subscribe')) {
                $registry->getApiInstance('imp', 'application')->mailboxesChanged();
            }
            break;
        }
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

        Horde::addInlineJsVars(array(
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

        $folder = isset($ui->vars->folder)
            ? IMP_Mailbox::formFrom($ui->vars->folder)
            : IMP_Mailbox::get('INBOX');

        try {
            $curr_acl = $acl->getACL($folder);
        } catch (IMP_Exception $e) {
            $GLOBALS['notification']->push($e);
            $curr_acl = array();
        }

        $canEdit = $acl->canEdit($folder, $GLOBALS['registry']->getAuth());

        if (!$canEdit) {
            $GLOBALS['notification']->push(_("You do not have permission to change access to this folder."), 'horde.warning');
        }

        if (!count($curr_acl)) {
            $GLOBALS['notification']->push(_("The current list of users with access to this folder could not be retrieved."), 'horde.warning');
        }

        $t = $GLOBALS['injector']->createInstance('Horde_Template');
        $t->setOption('gettext', true);

        $t->set('options', IMP::flistSelect(array('selected' => $folder)));
        $t->set('current', sprintf(_("Current access to %s"), $folder->display));
        $t->set('folder', $folder->form_to);
        $t->set('hasacl', count($curr_acl));

        if (!$t->get('hasacl')) {
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
        }

        $t->set('canedit', $canEdit);

        if ($GLOBALS['session']->get('imp', 'imap_admin')) {
            $current_users = array_keys($curr_acl);
            $new_user = array();

            foreach (array('anyone') + $GLOBALS['registry']->callAppMethod('imp', 'authUserList') as $user) {
                if (in_array($user, $current_users)) {
                    continue;
                }
                $new_user[] = htmlspecialchars($user);
            }
            $t->set('new_user', $new_user);
        } else {
            $t->set('noadmin', true);
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
        $folder = IMP_Mailbox::formFrom($ui->vars->folder);
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
                        $GLOBALS['notification']->push(sprintf(_("User \"%s\" successfully given the specified rights for the folder \"%s\"."), $new_user, $folder->label), 'horde.success');
                    } else {
                        $GLOBALS['notification']->push(sprintf(_("All rights on folder \"%s\" successfully removed for user \"%s\"."), $folder->label, $new_user), 'horde.success');
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
                        $GLOBALS['notification']->push(sprintf(_("All rights on folder \"%s\" successfully removed for user \"%s\"."), $folder->label, $user), 'horde.success');
                    }
                } else {
                    $GLOBALS['notification']->push(sprintf(_("User \"%s\" successfully given the specified rights for the folder \"%s\"."), $user, $folder->label), 'horde.success');
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
        $t->set('nofolder', IMP_Mailbox::formTo(self::PREF_NO_FOLDER));
        $t->set('flist', IMP::flistSelect(array(
            'filter' => array('INBOX'),
            'new_folder' => true,
            'selected' => IMP_Mailbox::getPref('drafts_folder')
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

        $t = $GLOBALS['injector']->createInstance('Horde_Template');
        $t->setOption('gettext', true);
        $t->set('locked', $GLOBALS['prefs']->isLocked('msgflags'));

        $out = array();
        $flaglist = $GLOBALS['injector']->getInstance('IMP_Flags')->getList();
        foreach ($flaglist as $val) {
            $hash = hash('md5', $val->id);
            $bgid = 'bg_' . $hash;
            $color = htmlspecialchars($val->bgcolor);
            $label = htmlspecialchars($val->label);
            $bgstyle = 'background-color:' . $color;
            $tmp = array();

            if ($val instanceof IMP_Flag_User) {
                $tmp['label'] = $label;
                $tmp['user'] = true;
                $tmp['label_name'] = 'label_' . $hash;
            } else {
                $tmp['label'] = Horde::label($bgid, $label);
                $tmp['icon'] = $val->div;
            }

            $tmp['colorstyle'] = $bgstyle . ';color:' . htmlspecialchars($val->fgcolor);
            $tmp['colorid'] = $bgid;
            $tmp['color'] = $color;

            $out[] = $tmp;
        }
        $t->set('flags', $out);

        $t->set('picker_img', Horde::img('colorpicker.png', _("Color Picker")));

        return $t->fetch(IMP_TEMPLATES . '/prefs/flags.html');
    }

    /**
     * Update IMAP flag related preferences.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     *
     * @return boolean  True if preferences were updated.
     */
    protected function _updateFlagManagement($ui)
    {
        $imp_flags = $GLOBALS['injector']->getInstance('IMP_Flags');

        if ($ui->vars->flag_action == 'add') {
            $GLOBALS['notification']->push(sprintf(_("Added flag \"%s\"."), $ui->vars->flag_data), 'horde.success');
            $imp_flags->addFlag($ui->vars->flag_data);
            return;
        }

        // Don't set updated on these actions. User may want to do more
        // actions.
        $update = false;
        foreach ($imp_flags->getList() as $val) {
            $md5 = hash('md5', $val->id);

            switch ($ui->vars->flag_action) {
            case 'delete':
                if ($ui->vars->flag_data == ('bg_' . $md5)) {
                    unset($imp_flags[$val->id]);
                    $GLOBALS['notification']->push(sprintf(_("Deleted flag \"%s\"."), $val->label), 'horde.success');
                }
                break;

            default:
                /* Change labels for user-defined flags. */
                if ($val instanceof IMP_Flag_User) {
                    $label = $ui->vars->get('label_' . $md5);
                    if (strlen($label) && ($label != $val->label)) {
                        $imp_flags->updateFlag($val->id, 'label', $label);
                        $update = true;
                    }
                }

                /* Change background for all flags. */
                $bg = strtolower($ui->vars->get('bg_' . $md5));
                if ($bg != $val->bgcolor) {
                    $imp_flags->updateFlag($val->id, 'bgcolor', $bg);
                    $update = true;
                }
                break;
            }
        }

        return $update;
    }

    /* Initial page selection. */

    /**
     * Create code for initial page selection.
     *
     * @return string  HTML UI code.
     */
    protected function _initialPage()
    {
        global $injector, $prefs;

        $t = $injector->createInstance('Horde_Template');
        $t->setOption('gettext', true);

        if (!$injector->getInstance('IMP_Factory_Imap')->create()->allowFolders()) {
            $t->set('nofolder', true);
        } else {
            $mailbox_selected = $prefs->getValue('initial_page');
            $t->set('folder_page', IMP_Mailbox::formTo(self::PREF_FOLDER_PAGE));
            $t->set('folder_sel', $mailbox_selected == self::PREF_FOLDER_PAGE);
            $t->set('flist', IMP::flistSelect(array(
                'inc_vfolder' => true,
                'selected' => $mailbox_selected
            )));
        }

        $t->set('label', Horde::label('initial_page', _("View or mailbox to display after login:")));

        return $t->fetch(IMP_TEMPLATES . '/prefs/initialpage.html');
    }

    /* Mailto: handler. */

    /**
     * Create code for the mailto handler link.
     *
     * @return string  HTML UI code.
     */
    protected function _mailtoHandler()
    {
        Horde::addInlineScript(array(
            'if (!Object.isUndefined(navigator.registerProtocolHandler))' .
            '$("mailto_handler").show().down("A").observe("click", function() {' .
                'navigator.registerProtocolHandler("mailto","' .
                Horde::url('compose.php', true)->setRaw(true)->add(array(
                    'actionID' => 'mailto_link',
                    'to' => ''
                )) .
                '%s","' . $GLOBALS['registry']->get('name') . '");' .
            '})'
        ), 'dom');

        $t = $GLOBALS['injector']->createInstance('Horde_Template');
        $t->setOption('gettext', true);

        $t->set('desc', sprintf(_("Click here to open all mailto: links using %s."), $GLOBALS['registry']->get('name')));
        $t->set('img', Horde::img('compose.png'));

        return $t->fetch(IMP_TEMPLATES . '/prefs/mailto.html');
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

                $imple = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Imple')->create(array('imp', 'PassphraseDialog'), array(
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
                    '$("delete_pgp_privkey").observe("click", function(e) { if (!window.confirm(' . Horde_Serialize::serialize(_("Are you sure you want to delete your keypair? (This is NOT recommended!)"), Horde_Serialize::JSON, 'UTF-8') . ')) { e.stop(); } })'
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
                    '$("create_pgp_key").observe("click", function(e) { if (!window.confirm(' . Horde_Serialize::serialize(_("Key generation may take a long time to complete.  Continue with key generation?"), Horde_Serialize::JSON, 'UTF-8') . ')) { e.stop(); } })'
                ), 'dom');

                if ($GLOBALS['session']->get('imp', 'file_upload')) {
                    Horde::addInlineScript(array(
                        '$("import_pgp_personal").observe("click", function(e) { ' . Horde::popupJs($pgp_url, array('params' => array('actionID' => 'import_personal_public_key', 'reload' => $GLOBALS['session']->store($ui->selfUrl()->setRaw(true), false)), 'height' => 275, 'width' => 750, 'urlencode' => true)) . '; e.stop(); })'
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

        if ($GLOBALS['session']->get('imp', 'file_upload')) {
            $t->set('can_import', true);
            $t->set('no_source', !$GLOBALS['prefs']->getValue('add_source'));
            if (!$t->get('no_source')) {
                $t->set('import_pubkey-help', Horde_Help::link('imp', 'pgp-import-pubkey'));

                Horde::addInlineScript(array(
                    '$("import_pgp_public").observe("click", function(e) { ' . Horde::popupJs($pgp_url, array('params' => array('actionID' => 'import_public_key', 'reload' => $GLOBALS['session']->store($ui->selfUrl()->setRaw(true), false)), 'height' => 275, 'width' => 750, 'urlencode' => true)) . '; e.stop(); })'
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

    /* Saved Searches management. */

    /**
     * Create code for saved searches management.
     *
     * @return string  HTML UI code.
     */
    protected function _searchesManagement()
    {
        global $injector, $prefs;

        $t = $injector->createInstance('Horde_Template');
        $t->setOption('gettext', true);

        $imp_search = $injector->getInstance('IMP_Search');
        $fout = $mailboxids = $vout = array();
        $view_mode = IMP::getViewMode();

        $imp_search->setIteratorFilter(IMP_Search::LIST_VFOLDER | IMP_Search::LIST_DISABLED);
        $vfolder_locked = $prefs->isLocked('vfolder');

        foreach ($imp_search as $key => $val) {
            if (!$val->prefDisplay) {
                continue;
            }

            $editable = !$vfolder_locked && $imp_search->isVFolder($val, true);
            $m_url = ($val->enabled && ($view_mode == 'imp'))
                ? IMP::generateIMPUrl('mailbox.php', strval($val))->link(array('class' => 'vfolderenabled'))
                : null;

            if ($view_mode == 'dimp') {
                $mailboxids['enable_' . $key] = strval($val);
            }

            $vout[] = array(
                'description' => Horde_String::truncate($val->querytext, 200),
                'edit' => ($editable ? $imp_search->editUrl($val) : null),
                'enabled' => $val->enabled,
                'enabled_locked' => $vfolder_locked,
                'key' => $key,
                'label' => htmlspecialchars($val->label),
                'm_url' => $m_url
            );
        }
        $t->set('vfolders', $vout);

        $imp_search->setIteratorFilter(IMP_Search::LIST_FILTER | IMP_Search::LIST_DISABLED);
        $filter_locked = $prefs->isLocked('filter');

        foreach ($imp_search as $key => $val) {
            if (!$val->prefDisplay) {
                continue;
            }

            $editable = !$filter_locked && $imp_search->isFilter($val, true);

            if ($view_mode == 'dimp') {
                $mailboxids['enable_' . $key] = strval($val);
            }

            $fout[] = array(
                'description' => Horde_String::truncate($val->querytext, 200),
                'edit' => ($editable ? $imp_search->editUrl($val) : null),
                'enabled' => $val->enabled,
                'enabled_locked' => $filter_locked,
                'key' => $key,
                'label' => htmlspecialchars($val->label)
            );
        }
        $t->set('filters', $fout);

        if (empty($fout) && empty($vout)) {
            $t->set('nosearches', true);
        } else {
            Horde::addInlineJsVars(array(
                'ImpSearchesPrefs.confirm_delete_filter' => _("Are you sure you want to delete this filter?"),
                'ImpSearchesPrefs.confirm_delete_vfolder' => _("Are you sure you want to delete this virtual folder?"),
                'ImpSearchesPrefs.mailboxids' => $mailboxids
            ));
        }

        return $t->fetch(IMP_TEMPLATES . '/prefs/searches.html');
    }

    /**
     * Update Saved Searches related preferences.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     */
    protected function _updateSearchesManagement($ui)
    {
        $imp_search = $GLOBALS['injector']->getInstance('IMP_Search');

        switch ($ui->vars->searches_action) {
        case 'delete':
            /* Remove 'enable_' prefix. */
            $key = substr($ui->vars->searches_data, 7);
            if ($ob = $imp_search[$key]) {
                if ($imp_search->isVFolder($ob)) {
                    $GLOBALS['notification']->push(sprintf(_("Virtual Folder \"%s\" deleted."), $ob->label), 'horde.success');
                } elseif ($imp_search->isFilter($ob)) {
                    $GLOBALS['notification']->push(sprintf(_("Filter \"%s\" deleted."), $ob->label), 'horde.success');
                }
                unset($imp_search[$key]);
            }
            break;

        default:
            /* Update enabled status for Virtual Folders. */
            $imp_search->setIteratorFilter(IMP_Search::LIST_VFOLDER | IMP_Search::LIST_DISABLED);
            $vfolders = array();

            foreach ($imp_search as $key => $val) {
                $form_key = 'enable_' . $key;

                /* Only change enabled status for virtual folders displayed
                 * on the preferences screen. */
                if (isset($ui->vars->$form_key)) {
                    $val->enabled = !empty($ui->vars->$form_key);
                    $vfolders[$key] = $val;
                }
            }
            $imp_search->setVFolders($vfolders);

            /* Update enabled status for Filters. */
            $imp_search->setIteratorFilter(IMP_Search::LIST_FILTER | IMP_Search::LIST_DISABLED);
            $filters = array();

            foreach ($imp_search as $key => $val) {
                $form_key = 'enable_' . $key;
                $val->enabled = !empty($ui->vars->$form_key);
                $filters[$key] = $val;
            }
            $imp_search->setFilters($filters);
            break;
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

        $t->set('default', IMP_Mailbox::formTo(self::PREF_DEFAULT));
        $t->set('label', Horde::label('sent_mail_folder', _("Sent mail folder:")));
        $t->set('flist', IMP::flistSelect(array(
            'filter' => array('INBOX'),
            'new_folder' => true
        )));
        $t->set('special_use', $this->_getSpecialUse(IMP_Folder::$specialUse['sent']));

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
        global $injector, $prefs;

        $imp_imap = $injector->getInstance('IMP_Factory_Imap')->create();

        if (!$imp_imap->allowFolders() ||
            $prefs->isLocked('sent_mail_folder')) {
            return false;
        }

        $sent_mail_folder = IMP_Mailbox::formFrom($ui->vars->sent_mail_folder);
        if (empty($sent_mail_folder) && $ui->vars->sent_mail_folder_new) {
            $sent_mail_folder = Horde_String::convertCharset($ui->vars->sent_mail_folder_new, 'UTF-8', 'UTF7-IMAP');
        } elseif (strpos($sent_mail_folder, self::PREF_SPECIALUSE) === 0) {
            $sent_mail_folder = substr($folder, strlen(self::PREF_SPECIALUSE));
        } elseif (($sent_mail_folder == self::PREF_DEFAULT) &&
                  ($sm_default = $prefs->getDefault('sent_mail_folder'))) {
            $sent_mail_folder = $sm_default;
        }

        if (($sent_mail_folder = IMP_Mailbox::get($sent_mail_folder)->namespace_append) &&
            !$sent_mail_folder->create()) {
            return false;
        }

        return $injector->getInstance('IMP_Identity')->setValue('sent_mail_folder', $sent_mail_folder);
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

                $imple = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Imple')->create(array('imp', 'PassphraseDialog'), array(
                    'type' => 'smimePersonal'
                ));

                $imp_smime = $GLOBALS['injector']->getInstance('IMP_Crypt_Smime');
                $passphrase = $imp_smime->getPassphrase();
                $t->set('passphrase', empty($passphrase) ? Horde::link('#', _("Enter Passphrase"), null, null, null, null, null, array('id' => $imple->getPassphraseId())) . _("Enter Passphrase") : Horde::link($ui->selfUrl(array('special' => true))->add('unset_smime_passphrase', 1), _("Unload Passphrase")) . _("Unload Passphrase"));

                $t->set('viewprivate', Horde::link($smime_url->copy()->add('actionID', 'view_personal_private_key'), _("View Personal Private Key"), null, 'view_key'));
                $t->set('personalkey-delete-help', Horde_Help::link('imp', 'smime-delete-personal-certs'));

                Horde::addInlineScript(array(
                    '$("delete_smime_personal").observe("click", function(e) { if (!window.confirm(' . Horde_Serialize::serialize(_("Are you sure you want to delete your keypair? (This is NOT recommended!)"), Horde_Serialize::JSON, 'UTF-8') . ')) { e.stop(); } })'
                ), 'dom');
            } elseif ($GLOBALS['session']->get('imp', 'file_upload')) {
                $t->set('import-cert-help', Horde_Help::link('imp', 'smime-import-personal-certs'));

                Horde::addInlineScript(array(
                    '$("import_smime_personal").observe("click", function(e) { ' . Horde::popupJs($smime_url, array('params' => array('actionID' => 'import_personal_certs', 'reload' => $GLOBALS['session']->store($ui->selfUrl()->setRaw(true), false)), 'height' => 275, 'width' => 750, 'urlencode' => true)) . '; e.stop(); })'
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
            $self_url = $ui->selfUrl(array('special' => true, 'token' => true));

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

        if ($GLOBALS['session']->get('imp', 'file_upload')) {
            $t->set('can_import', true);
            $t->set('no_source', !$GLOBALS['prefs']->getValue('add_source'));
            if (!$t->get('no_source')) {
                $t->set('import_pubkey-help', Horde_Help::link('imp', 'smime-import-pubkey'));

                Horde::addInlineScript(array(
                    '$("import_smime_public").observe("click", function(e) { ' . Horde::popupJs($smime_url, array('params' => array('actionID' => 'import_public_key', 'reload' => $GLOBALS['session']->store($ui->selfUrl()->setRaw(true), false)), 'height' => 275, 'width' => 750, 'urlencode' => true)) . '; e.stop(); })'
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
            $GLOBALS['session']->remove('imp', 'ac_ajax');
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
        $t->set('nofolder', IMP_Mailbox::formTo(self::PREF_NO_FOLDER));
        $t->set('flist', IMP::flistSelect(array(
            'filter' => array('INBOX'),
            'new_folder' => true,
            'selected' => IMP_Mailbox::getPref('spam_folder')
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
        $stationery = $GLOBALS['injector']->getInstance('IMP_Compose_Stationery');

        if ($ob->type == 'html') {
            IMP_Ui_Editor::init(false, 'content');
        }

        $t = $GLOBALS['injector']->createInstance('Horde_Template');
        $t->setOption('gettext', true);

        $slist = array();
        foreach ($stationery as $key => $choice) {
            $slist[] = array(
                'selected' => ($ob->selected === $key),
                'text' => $choice['n'] . ' ' . ($choice['t'] == 'html' ? _("(HTML)") : _("(Plain Text)")),
                'val' => $key
            );
        }
        $t->set('slist', $slist);

        $t->set('selected', $ob->selected);
        $t->set('show_delete', ($ob->selected != -1));
        $t->set('last_type', $ob->type);
        $t->set('name_label', Horde::label('name', _("Stationery name:")));
        $t->set('name', $ob->name);
        $t->set('type_label', Horde::label('name', _("Stationery type:")));
        $t->set('plain', $ob->type == 'plain');
        $t->set('html', $ob->type == 'html');
        $t->set('content_label', Horde::label('content', _("Stationery:")));
        $t->set('content', $ob->content);

        return $t->fetch(IMP_TEMPLATES . '/prefs/stationery.html');
    }

    /**
     * Update stationery related preferences.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     */
    protected function _updateStationeryManagement($ui)
    {
        $ob = $this->_parseStationeryVars($ui);
        $stationery = $GLOBALS['injector']->getInstance('IMP_Compose_Stationery');
        $updated = false;

        if ($ui->vars->delete) {
            /* Delete stationery. */
            if (isset($stationery[$ob->selected])) {
                $updated = sprintf(_("The stationery \"%s\" has been deleted."), $stationery[$ob->selected]['n']);
                unset($stationery[$ob->selected]);
            }
        } elseif ($ui->vars->save) {
            /* Saving stationery. */
            $entry = array(
                'c' => $ob->content,
                'n' => $ob->name,
                't' => $ob->type
            );

            if ($ob->selected == -1) {
                $stationery[] = $entry;
                $updated = sprintf(_("The stationery \"%s\" has been added."), $ob->name);
            } else {
                $stationery[$ob->selected] = $entry;
                $updated = sprintf(_("The stationery \"%s\" has been updated."), $ob->name);
            }
        }

        if ($updated) {
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
     * 'content' - (string) Content.
     * 'name' - (string) Name.
     * 'selected' - (integer) The currently selected value.
     * 'type' - (string) Type.
     * </pre>
     */
    protected function _parseStationeryVars($ui)
    {
        $selected = strlen($ui->vars->stationery)
            ? intval($ui->vars->stationery)
            : -1;
        $stationery = $GLOBALS['injector']->getInstance('IMP_Compose_Stationery');

        if ($ui->vars->last_selected == $selected) {
            $content = strval($ui->vars->content);
            $name = strval($ui->vars->name);
            $type = isset($ui->vars->type)
                ? $ui->vars->type
                : 'plain';

            if ($content && ($ui->vars->last_type != $type)) {
                $content = ($type == 'plain')
                    ? $GLOBALS['injector']->getInstance('Horde_Core_Factory_TextFilter')->filter($content, 'Html2text')
                    : IMP_Compose::text2html($content);
            }
        } elseif ($selected == -1) {
            $content = $name = '';
            $type = 'plain';
        } else {
            $entry = $stationery[$selected];
            $content = $entry['c'];
            $name = $entry['n'];
            $type = $entry['t'];
        }


        $ob = new stdClass;
        $ob->content = $content;
        $ob->name = $name;
        $ob->selected = $selected;
        $ob->type = $type;

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
        global $injector, $prefs;

        $imp_search = $injector->getInstance('IMP_Search');
        $trash_folder = IMP_Mailbox::getPref('trash_folder');

        $t = $injector->createInstance('Horde_Template');
        $t->setOption('gettext', true);

        $t->set('label', Horde::label('trash', _("Trash folder:")));
        $t->set('nofolder', IMP_Mailbox::formTo(self::PREF_NO_FOLDER));
        $t->set('flist', IMP::flistSelect(array(
            'filter' => array('INBOX'),
            'new_folder' => true,
            'selected' => $trash_folder
        )));
        $t->set('special_use', $this->_getSpecialUse(IMP_Folder::$specialUse['trash']));

        if (!$prefs->isLocked('vfolder') || $imp_search['vtrash']->enabled) {
            $t->set('vtrash', IMP_Mailbox::formTo($imp_search->createSearchId('vtrash')));
            $t->set('vtrash_select', $trash_folder->vtrash);
        }

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
        $imp_search = $GLOBALS['injector']->getInstance('IMP_Search');
        $trash = IMP_Mailbox::formFrom($ui->vars->trash);

        if (!$GLOBALS['prefs']->isLocked('vfolder')) {
            $vtrash = $imp_search['vtrash'];
            $vtrash->enabled = $trash->vtrash;
            $imp_search['vtrash'] = $vtrash;
        }

        return $this->_updateSpecialFolders('trash_folder', $trash, $ui->vars->trash_new, 'trash', $ui);
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
        global $injector, $prefs;

        $imp_imap = $injector->getInstance('IMP_Factory_Imap')->create();

        if (!$imp_imap->allowFolders() || $prefs->isLocked($pref)) {
            return false;
        }

        if ($folder == self::PREF_NO_FOLDER) {
            return $prefs->setValue($pref, '');
        }

        if (strpos($folder, self::PREF_SPECIALUSE) === 0) {
            $folder = IMP_Mailbox::get(substr($folder, strlen(self::PREF_SPECIALUSE)));
        } elseif (!empty($new)) {
            $new = Horde_String::convertCharset($new, 'UTF-8', 'UTF7-IMAP');
            $folder = IMP_Mailbox::get($new)->namespace_append;
            if (!$folder->create(array($type => true))) {
                $folder = null;
            }
        }

        return $folder
            ? $prefs->setValue($pref, $folder->pref_to)
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
        global $injector;

        if (is_null($this->_cache)) {
            $this->_cache = $injector->getInstance('IMP_Factory_Imap')->create()->listMailboxes('*', Horde_Imap_Client::MBOX_ALL, array(
                'attributes' => true,
                'special_use' => true,
                'sort' => true
            ));
        }

        $special_use = array();
        foreach ($this->_cache as $val) {
            if (in_array($use, $val['attributes'])) {
                $mbox_ob = IMP_Mailbox::get($val['mailbox']);
                $special_use[] = array(
                    'l' => htmlspecialchars($mbox_ob->label),
                    'v' => IMP_Mailbox::formTo(PREF_SPECIALUSE . $mbox_ob)
                );
            }
        }

        if (empty($special_use)) {
            return '';
        }

        $t = $injector->createInstance('Horde_Template');
        $t->setOption('gettext', true);
        $t->set('special_use', $special_use);

        return $t->fetch(IMP_TEMPLATES . '/prefs/specialuse.html');
    }

}
