<?php
/**
 * IMP-specific prefs handling.
 *
 * Copyright 2010-2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Prefs_Ui
{
    const PREF_DEFAULT = "default\0";
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
        global $conf, $injector, $registry;

        $imp_imap = $injector->getInstance('IMP_Factory_Imap')->create();

        /* Hide appropriate prefGroups. */
        if (!$imp_imap->access(IMP_Imap::ACCESS_FLAGS)) {
            $ui->suppressGroups[] = 'flags';
        }
        if (!$imp_imap->access(IMP_Imap::ACCESS_SEARCH)) {
            $ui->suppressGroups[] = 'searches';
        }

        try {
            $injector->getInstance('IMP_Imap_Acl');
        } catch (IMP_Exception $e) {
            $ui->suppressGroups[] = 'acl';
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

        if (!$imp_imap->access(IMP_Imap::ACCESS_FOLDERS)) {
            $ui->suppressGroups[] = 'searches';
        }
    }

    /**
     * Determine active prefs when displaying a group.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     */
    public function prefsGroup($ui)
    {
        global $conf, $injector, $prefs, $registry, $session;

        $cprefs = $ui->getChangeablePrefs();
        $imp_imap = $injector->getInstance('IMP_Factory_Imap')->create();

        switch ($ui->group) {
        case 'identities':
            if ($prefs->isLocked('sent_mail_folder')) {
                $ui->suppress[] = 'sentmailselect';
            }

            if ($prefs->isLocked('signature_html') ||
                !$session->get('imp', 'rteavail')) {
                $ui->suppress[] = 'signature_html_select';
            }
            break;

        case 'traditional':
            if (!isset($cprefs['preview_enabled'])) {
                $ui->suppress[] = 'traditional_mailbox';
            }
            if (!isset($cprefs['compose_popup'])) {
                $ui->suppress[] = 'traditional_compose';
            }
            break;
        }

        foreach ($cprefs as $val) {
            switch ($val) {
            case 'add_source':
                try {
                    $ui->override['add_source'] = $registry->call('contacts/sources', array(true));
                } catch (Horde_Exception $e) {
                    $ui->suppress[] = $val;
                }
                break;

            case 'alternative_display':
                $mock_part = new Horde_Mime_Part();
                $mock_part->setType('text/html');
                $v = $injector->getInstance('IMP_Factory_MimeViewer')->create($mock_part);

                if (!$v->canRender('inline')) {
                    $ui->suppress[] = $val;
                }
                break;

            case 'compose_html_font_family':
            case 'compose_html_font_size':
                if (!$prefs->getValue('compose_html')) {
                    $ui->suppress[] = $val;
                }
                break;

            case 'compose_confirm':
                if (!$prefs->getValue('compose_popup')) {
                    $ui->suppress[] = $val;
                }
                break;

            case 'delete_attachments_monthly_keep':
                if (empty($conf['compose']['link_attachments'])) {
                    $ui->suppress[] = $val;
                }
                break;

            case 'delete_sentmail_monthly_keep':
            case 'empty_spam_menu':
            case 'initialpageselect':
            case 'move_ham_after_report':
            case 'nav_expanded':
            case 'nav_poll_all':
            case 'purge_sentmail_interval':
            case 'purge_sentmail_keep':
            case 'purge_spam_interval':
            case 'purge_spam_keep':
            case 'rename_sentmail_monthly':
            case 'tree_view':
                if (!$imp_imap->access(IMP_Imap::ACCESS_FOLDERS)) {
                    $ui->suppress[] = $val;
                }
                break;

            case 'delete_spam_after_report':
                if (!$imp_imap->access(IMP_Imap::ACCESS_FOLDERS)) {
                    $tmp = $ui->prefs['delete_spam_after_report']['enum'];
                    unset($tmp[2]);
                    $ui->override['delete_spam_after_report'] = $tmp;
                }
                break;

            case 'draftsselect':
                if ($prefs->isLocked('drafts_folder')) {
                    $ui->suppress[] = $val;
                }
                break;

            case 'dynamic_view':
                if (!empty($conf['user']['force_view'])) {
                    $ui->suppress[] = $val;
                }
                break;

            case 'empty_trash_menu':
            case 'purge_trash_interval':
            case 'purge_trash_keep':
            case 'trashselect':
                if (!$imp_imap->access(IMP_Imap::ACCESS_TRASH) ||
                    $prefs->isLocked('use_trash') ||
                    !$prefs->getValue('use_trash')) {
                    $ui->suppress[] = $val;
                }
                break;

            case 'encryptselect':
                if ($prefs->isLocked('default_encrypt')) {
                    $ui->suppress[] = $val;
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
                try {
                    $ui->prefs[$val]['url'] = $registry->link('mail/showBlacklist');
                } catch (Horde_Exception $e) {
                    $ui->suppress[] = $val;
                }
                break;

            case 'filters_link':
                try {
                    $ui->prefs[$val]['url'] = $registry->link('mail/showFilters');
                } catch (Horde_Exception $e) {
                    $ui->suppress[] = $val;
                }
                break;

            case 'filters_whitelist_link':
                try {
                    $ui->prefs[$val]['url'] = $registry->link('mail/showWhitelist');
                } catch (Horde_Exception $e) {
                    $ui->suppress[] = $val;
                }
                break;

            case 'flagmanagement':
                if ($prefs->isLocked('msgflags') &&
                    $prefs->isLocked('msgflags_user')) {
                    $ui->nobuttons = true;
                }
                break;

            case 'initialpageselect':
                if ($prefs->isLocked('initial_page')) {
                    $ui->suppress[] = $val;
                }
                break;

            case 'newmail_soundselect':
                if (!$prefs->getValue('newmail_notify') ||
                    $prefs->isLocked('newmail_audio')) {
                    $ui->suppress[] = $val;
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

            case 'preview_maxlen':
            case 'preview_show_unread':
            case 'preview_show_tooltip':
            case 'preview_strip_nl':
                if (!$prefs->getValue('preview_enabled')) {
                    $ui->suppress[] = $val;
                }
                break;

            case 'reply_lang':
                $langs = Horde_Nls::getLanguageISO();
                $ui->override['reply_lang'] = $langs;
                break;

            case 'send_mdn':
                if (empty($conf['maillog']['use_maillog'])) {
                    $ui->suppress[] = $val;
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

            case 'sourceselect':
                Horde_Core_Prefs_Ui_Widgets::addressbooksInit();
                break;

            case 'spamselect':
                if ($prefs->isLocked('spam_folder')) {
                    $ui->suppress[] = $val;
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

            case 'trashselect':
                if ($prefs->isLocked('trash_folder')) {
                    $ui->suppress[] = $val;
                }
                break;

            case 'use_trash':
                if (!$imp_imap->access(IMP_Imap::ACCESS_TRASH)) {
                    $ui->suppress[] = $val;
                }
                break;
            }
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
        case 'aclmanagement':
            Horde::addScriptFile('acl.js', 'imp');
            return $this->_aclManagement($ui);

        case 'draftsselect':
            Horde::addScriptFile('folderprefs.js', 'imp');
            Horde::addInlineJsVars(array(
                'ImpFolderPrefs.folders.drafts' => _("Enter the name for your new drafts folder.")
            ));
            return $this->_drafts();

        case 'encryptselect':
            return $this->_encrypt();

        case 'flagmanagement':
            if (!$ui->nobuttons) {
                Horde::addScriptFile('colorpicker.js', 'horde');
                Horde::addScriptFile('flagprefs.js', 'imp');
            }
            return $this->_flagManagement();

        case 'initialpageselect':
            return $this->_initialPage();

        case 'mailto_handler':
            return $this->_mailtoHandler();

        case 'newmail_soundselect':
            return $this->_newmailAudio();

        case 'pgpprivatekey':
            Horde::addScriptFile('imp.js', 'imp');
            return $this->_pgpPrivateKey($ui);

        case 'pgppublickey':
            Horde::addScriptFile('imp.js', 'imp');
            return $this->_pgpPublicKey($ui);

        case 'searchesmanagement':
            Horde::addScriptFile('searchesprefs.js', 'imp');
            return $this->_searchesManagement();

        case 'sentmailselect':
            Horde::addScriptFile('folderprefs.js', 'imp');
            return $this->_sentmail();

        case 'smimeprivatekey':
            Horde::addScriptFile('imp.js', 'imp');
            return $this->_smimePrivateKey($ui);

        case 'smimepublickey':
            Horde::addScriptFile('imp.js', 'imp');
            return $this->_smimePublicKey($ui);

        case 'signature_html_select':
            Horde::addScriptFile('signaturehtml.js', 'imp');
            IMP_Ui_Editor::init(false, 'signature_html');
            return $this->_signatureHtml();

        case 'sourceselect':
            $search = IMP::getAddressbookSearchParams();
            return Horde_Core_Prefs_Ui_Widgets::addressbooks(array(
                'fields' => $search['fields'],
                'sources' => $search['sources']
            ));

        case 'spamselect':
            Horde::addScriptFile('folderprefs.js', 'imp');
            Horde::addInlineJsVars(array(
                'ImpFolderPrefs.folders.spam' => _("Enter the name for your new spam folder.")
            ));
            return $this->_spam();

        case 'stationerymanagement':
            return $this->_stationeryManagement($ui);

        case 'trashselect':
            Horde::addScriptFile('folderprefs.js', 'imp');
            Horde::addInlineJsVars(array(
                'ImpFolderPrefs.folders.trash' => _("Enter the name for your new trash folder.")
            ));
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
        global $injector, $prefs;

        switch ($item) {
        case 'aclmanagement':
            $this->_updateAclManagement($ui);
            return false;

        case 'draftsselect':
            return $this->_updateSpecialFolders('drafts_folder', IMP_Mailbox::formFrom($ui->vars->drafts), $ui->vars->drafts_folder_new, Horde_Imap_Client::SPECIALUSE_DRAFTS, $ui);

        case 'encryptselect':
            return $prefs->setValue('default_encrypt', $ui->vars->default_encrypt);

        case 'flagmanagement':
            return $this->_updateFlagManagement($ui);

        case 'initialpageselect':
            return $prefs->setValue('initial_page', IMP_Mailbox::formFrom($ui->vars->initial_page));

        case 'newmail_soundselect':
            return $prefs->setValue('newmail_audio', $ui->vars->newmail_audio);

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
            return $injector->getInstance('IMP_Identity')->setValue('signature_html', $ui->vars->signature_html);

        case 'sourceselect':
            return $this->_updateSource($ui);

        case 'spamselect':
            if ($this->_updateSpecialFolders('spam_folder', IMP_Mailbox::formFrom($ui->vars->spam), $ui->vars->spam_new, Horde_Imap_Client::SPECIALUSE_JUNK, $ui)) {
                $injector->getInstance('IMP_Factory_Imap')->create()->updateFetchIgnore();
                return true;
            }

            return false;

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
        global $browser, $notification, $prefs, $registry, $session;

        if ($prefs->isDirty('use_trash')) {
            IMP_Mailbox::getPref('trash_folder')->expire(IMP_Mailbox::CACHE_SPECIALMBOXES);
        }

        /* Always check to make sure we have a valid trash folder if delete to
         * trash is active. */
        if (($prefs->isDirty('use_trash') || $prefs->isDirty('trash_folder')) &&
            $prefs->getValue('use_trash') &&
            !$prefs->getValue('trash_folder')) {
            $notification->push(_("You have activated move to Trash but no Trash folder is defined. You will be unable to delete messages until you set a Trash folder in the preferences."), 'horde.warning');
        }

        if ($prefs->isDirty('mail_domain')) {
            $maildomain = preg_replace('/[^-\.a-z0-9]/i', '', $prefs->getValue('mail_domain'));
            $prefs->setValue('maildomain', $maildomain);
            if (!empty($maildomain)) {
                $session->set('imp', 'maildomain', $maildomain);
            }
        }

        if ($prefs->isDirty('dynamic_view')) {
            $session->set(
                'imp',
                'view',
                ($prefs->getValue('dynamic_view') && $session->get('horde', 'mode') != 'traditional')
                    ? 'dimp'
                    : ($browser->isMobile() ? 'mimp' : 'imp')
            );
        }

        if ($prefs->isDirty('subscribe') || $prefs->isDirty('tree_view')) {
            $registry->getApiInstance('imp', 'application')->mailboxesChanged();
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

        $folder = isset($ui->vars->folder)
            ? IMP_Mailbox::formFrom($ui->vars->folder)
            : IMP_Mailbox::get('INBOX');

        try {
            $curr_acl = $acl->getACL($folder);
        } catch (IMP_Exception $e) {
            $GLOBALS['notification']->push($e);
            $curr_acl = array();
        }

        if (!($canEdit = $acl->canEdit($folder))) {
            $GLOBALS['notification']->push(_("You do not have permission to change access to this folder."), 'horde.warning');
        }

        $rightslist = $acl->getRights();

        $t = $GLOBALS['injector']->createInstance('Horde_Template');
        $t->setOption('gettext', true);

        $t->set('options', IMP::flistSelect(array(
            'basename' => true,
            'selected' => $folder
        )));
        $t->set('current', sprintf(_("Current access to %s"), $folder->display));
        $t->set('folder', $folder->form_to);
        $t->set('hasacl', count($curr_acl));

        if ($t->get('hasacl')) {
            $cval = array();

            foreach ($curr_acl as $index => $rule) {
                $entry = array(
                    'index' => htmlspecialchars($index),
                    'rule' => array()
                );

                if ($rule instanceof Horde_Imap_Client_Data_AclNegative) {
                    $entry['negative'] = htmlspecialchars(substr($index, 1));
                }

                /* Create table of each ACL option for each user granted
                 * permissions; enabled indicates the right has been given to
                 * the user. */
                $rightsmbox = $acl->getRightsMbox($folder, $index);
                foreach (array_keys($rightslist) as $val) {
                    $entry['rule'][] = array(
                        'disable' => !$canEdit || !$rightsmbox[$val],
                        'on' => $rule[$val],
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

            try {
                foreach (array('anyone') + $GLOBALS['registry']->callAppMethod('imp', 'authUserList') as $user) {
                    if (!in_array($user, $current_users)) {
                        $new_user[] = htmlspecialchars($user);
                    }
                }
            } catch (Horde_Exception $e) {
                $GLOBALS['notification']->push($e);
                return;
            }
            $t->set('new_user', $new_user);
        } else {
            $t->set('noadmin', true);
        }

        $rights = array();
        foreach ($rightslist as $key => $val) {
            $val['val'] = $key;
            $rights[] = $val;
        }
        $t->set('rights', $rights);

        $t->set('width', round(100 / (count($rights) + 1)) . '%');

        return $t->fetch(IMP_TEMPLATES . '/prefs/acl.html');
    }

    /**
     * Update ACL related preferences.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     */
    protected function _updateAclManagement($ui)
    {
        global $injector, $notification;

        if ($ui->vars->change_acl_folder) {
            return;
        }

        $acl = $injector->getInstance('IMP_Imap_Acl');
        $folder = IMP_Mailbox::formFrom($ui->vars->folder);

        try {
            $curr_acl = $acl->getACL($folder);
        } catch (IMP_Exception $e) {
            $notification->push($e);
            return;
        }

        if (!($acl_list = $ui->vars->acl)) {
            $acl_list = array();
        }
        $new_user = $ui->vars->new_user;

        if (strlen($new_user) && $ui->vars->new_acl) {
            if (isset($acl_list[$new_user])) {
                $acl_list[$new_user] = $ui->vars->new_acl;
            } else {
                try {
                    $acl->addRights($folder, $new_user, implode('', $ui->vars->new_acl));
                    $notification->push(sprintf(_("ACL for \"%s\" successfully created for the mailbox \"%s\"."), $new_user, $folder->label), 'horde.success');
                } catch (IMP_Exception $e) {
                    $notification->push($e);
                }
            }
        }

        foreach ($curr_acl as $index => $rule) {
            if (isset($acl_list[$index])) {
                /* Check to see if ACL changed, but only compare rights we
                 * understand. */
                $acldiff = $rule->diff(implode('', $acl_list[$index]));
                $update = false;

                try {
                    if ($acldiff['added']) {
                        $acl->addRights($folder, $index, $acldiff['added']);
                        $update = true;
                    }
                    if ($acldiff['removed']) {
                        $acl->removeRights($folder, $index, $acldiff['removed']);
                        $update = true;
                    }

                    if ($update) {
                        $notification->push(sprintf(_("ACL rights for \"%s\" updated for the mailbox \"%s\"."), $index, $folder->label), 'horde.success');
                    }
                } catch (IMP_Exception $e) {
                    $notification->push($e);
                }
            } else {
                /* If we dont see ANY form params, the user deleted all
                 * rights. */
                try {
                    $acl->removeRights($folder, $index, null);
                    $notification->push(sprintf(_("All rights on mailbox \"%s\" successfully removed for \"%s\"."), $folder->label, $index), 'horde.success');
                } catch (IMP_Exception $e) {
                    $notification->push($e);
                }
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
            'basename' => true,
            'filter' => array('INBOX'),
            'new_folder' => true,
            'selected' => IMP_Mailbox::getPref('drafts_folder')
        )));
        $t->set('special_use', $this->_getSpecialUse(Horde_Imap_Client::SPECIALUSE_DRAFTS));

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
                $tmp['icon'] = $val->span;
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

        if (!$injector->getInstance('IMP_Factory_Imap')->create()->access(IMP_Imap::ACCESS_FOLDERS)) {
            $t->set('nofolder', true);
        } else {
            if (!($initial_page = $prefs->getValue('initial_page'))) {
                $initial_page = 'INBOX';
            }
            $t->set('folder_page', IMP_Mailbox::formTo(IMP::INITIAL_FOLDERS));
            $t->set('folder_sel', $initial_page == IMP::INITIAL_FOLDERS);
            $t->set('flist', IMP::flistSelect(array(
                'basename' => true,
                'inc_vfolder' => true,
                'selected' => $initial_page
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
                '=%s","' . $GLOBALS['registry']->get('name') . '");' .
            '})'
        ), 'dom');

        $t = $GLOBALS['injector']->createInstance('Horde_Template');
        $t->setOption('gettext', true);

        $t->set('desc', sprintf(_("Click here to open all mailto: links using %s."), $GLOBALS['registry']->get('name')));
        $t->set('img', Horde::img('compose.png'));

        return $t->fetch(IMP_TEMPLATES . '/prefs/mailto.html');
    }

    /* Newmail audio selection. */

    /**
     * Create code for newmail audio selection.
     *
     * @return string  HTML UI code.
     */
    protected function _newmailAudio()
    {
        $t = $GLOBALS['injector']->createInstance('Horde_Template');
        $t->setOption('gettext', true);

        $newmail_audio = $GLOBALS['prefs']->getValue('newmail_audio');

        $t->set('newmail_audio', $newmail_audio);

        $sounds = array();
        foreach (Horde_Themes::soundList() as $key => $val) {
            $sounds[] = array(
                'c' => ($newmail_audio == $key),
                'l' => htmlspecialchars($key),
                's' => htmlspecialchars($val->uri),
                'v' => htmlspecialchars($key)
            );
        }
        $t->set('sounds', $sounds);

        return $t->fetch(IMP_TEMPLATES . '/prefs/newmailaudio.html');
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
                $t->set('sendkey', Horde::link($ui->selfUrl(array('special' => true, 'token' => true))->add('send_pgp_key', 1), _("Send Key to Public Keyserver")));
                $t->set('personalkey-public-help', Horde_Help::link('imp', 'pgp-personalkey-public'));

                if ($passphrase = $GLOBALS['injector']->getInstance('IMP_Crypt_Pgp')->getPassphrase('personal')) {
                    $t->set('passphrase', Horde::link($ui->selfUrl(array('special' => true, 'token' => true))->add('unset_pgp_passphrase', 1), _("Unload Passphrase")) . _("Unload Passphrase"));
                } else {
                    $imple = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Imple')->create(array('imp', 'PassphraseDialog'), array(
                        'reloadurl' => $ui->selfUrl()->setRaw(true),
                        'type' => 'pgpPersonal'
                    ));
                    $t->set('passphrase', Horde::link('#', _("Enter Passphrase"), null, null, null, null, null, array('id' => $imple->getPassphraseId())) . _("Enter Passphrase"));
                }

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
                    $t->set('import_pgp_private', true);
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
                ? $val->mbox_ob->url('mailbox.php')->link(array('class' => 'vfolderenabled'))
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
                if ($val->prefDisplay) {
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
            $js[$key] = $identity->getValue('sent_mail_folder', $key)->form_to;
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
            'basename' => true,
            'filter' => array('INBOX'),
            'new_folder' => true
        )));
        $t->set('special_use', $this->_getSpecialUse(Horde_Imap_Client::SPECIALUSE_SENT));

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

        if (!$imp_imap->access(IMP_Imap::ACCESS_FOLDERS) ||
            $prefs->isLocked('sent_mail_folder')) {
            return false;
        }

        if (!$ui->vars->sent_mail_folder && $ui->vars->sent_mail_folder_new) {
            $sent_mail_folder = IMP_Mailbox::get(Horde_String::convertCharset($ui->vars->sent_mail_folder_new, 'UTF-8', 'UTF7-IMAP'))->namespace_append;
        } else {
            $sent_mail_folder = IMP_Mailbox::formFrom($ui->vars->sent_mail_folder);
            if (strpos($sent_mail_folder, self::PREF_SPECIALUSE) === 0) {
                $sent_mail_folder = IMP_Mailbox::get(substr($folder, strlen(self::PREF_SPECIALUSE)));
            } elseif (($sent_mail_folder == self::PREF_DEFAULT) &&
                      ($sm_default = $prefs->getDefault('sent_mail_folder'))) {
                $sent_mail_folder = IMP_Mailbox::get($sm_default)->namespace_append;
            }
        }

        if ($sent_mail_folder && !$sent_mail_folder->create()) {
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

                if ($passphrase = $GLOBALS['injector']->getInstance('IMP_Crypt_Smime')->getPassphrase()) {
                    $t->set('passphrase', Horde::link($ui->selfUrl(array('special' => true))->add('unset_smime_passphrase', 1), _("Unload Passphrase")) . _("Unload Passphrase"));
                } else {
                    $imple = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Imple')->create(array('imp', 'PassphraseDialog'), array(
                        'reloadurl' => $ui->selfUrl()->setRaw(true),
                        'type' => 'smimePersonal'
                    ));
                    $t->set('passphrase', Horde::link('#', _("Enter Passphrase"), null, null, null, null, null, array('id' => $imple->getPassphraseId())) . _("Enter Passphrase"));
                }

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
            'basename' => true,
            'filter' => array('INBOX'),
            'new_folder' => true,
            'selected' => IMP_Mailbox::getPref('spam_folder')
        )));
        $t->set('special_use', $this->_getSpecialUse(Horde_Imap_Client::SPECIALUSE_JUNK));

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
            'basename' => true,
            'filter' => array('INBOX'),
            'new_folder' => true,
            'selected' => $trash_folder
        )));
        $t->set('special_use', $this->_getSpecialUse(Horde_Imap_Client::SPECIALUSE_TRASH));

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

        if ($this->_updateSpecialFolders('trash_folder', $trash, $ui->vars->trash_new, Horde_Imap_Client::SPECIALUSE_TRASH, $ui)) {
            $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->updateFetchIgnore();
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
     * @param string $type             Special use attribute (RFC 6154).
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     *
     * @return boolean  True if preferences were updated.
     */
    protected function _updateSpecialFolders($pref, $folder, $new, $type, $ui)
    {
        global $injector, $prefs;

        $imp_imap = $injector->getInstance('IMP_Factory_Imap')->create();

        if (!$imp_imap->access(IMP_Imap::ACCESS_FOLDERS) ||
            $prefs->isLocked($pref)) {
            return false;
        }

        if ($mbox_ob = IMP_Mailbox::getPref($pref)) {
            $mbox_ob->expire(IMP_Mailbox::CACHE_SPECIALMBOXES);
        }

        if ($folder == self::PREF_NO_FOLDER) {
            return $prefs->setValue($pref, '');
        }

        if (strpos($folder, self::PREF_SPECIALUSE) === 0) {
            $folder = IMP_Mailbox::get(substr($folder, strlen(self::PREF_SPECIALUSE)));
        } elseif (!empty($new)) {
            $new = Horde_String::convertCharset($new, 'UTF-8', 'UTF7-IMAP');
            $folder = IMP_Mailbox::get($new)->namespace_append;
            if (!$folder->create(array('special_use' => array($type)))) {
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
                    'v' => IMP_Mailbox::formTo(self::PREF_SPECIALUSE . $mbox_ob)
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
