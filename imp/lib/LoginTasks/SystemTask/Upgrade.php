<?php
/**
 * Login system task for automated upgrade tasks.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_LoginTasks_SystemTask_Upgrade extends Horde_Core_LoginTasks_SystemTask_Upgrade
{
    /**
     */
    protected $_app = 'imp';

    /**
     */
    protected $_versions = array(
        '5.0',
        '6.0'
    );

    /**
     */
    protected function _upgrade($version)
    {
        switch ($version) {
        case '5.0':
            $this->_upgradeAbookPrefs();
            $this->_upgradeComposePrefs();
            $this->_upgradeDeleteAttachmentsMonthlyPrefs();
            $this->_upgradeDeleteSentmailMonthlyPrefs();
            $this->_upgradeForwardPrefs();
            $this->_upgradeLoginTasksPrefs();
            $this->_upgradeMsgDisplayPrefs();
            $this->_upgradeNewmailPrefs();
            $this->_upgradePurgeSentmailPrefs();
            $this->_upgradePurgeSpamPrefs();
            $this->_upgradePurgeTrashPrefs();
            $this->_upgradeSortPrefs();
            $this->_upgradeStationery();
            $this->_upgradeVirtualFolders();
            break;

        case '6.0':
            $this->_upgradeComposeCursor();
            $this->_upgradeInnocentPrefs();
            $this->_upgradeMailboxPrefs();
            $this->_upgradeStationeryToTemplates();
            $this->_upgradeVirtualFolders6();
            break;
        }
    }

    /**
     * Upgrade to the new addressbook preferences.
     */
    protected function _upgradeAbookPrefs()
    {
        global $prefs;

        if (!$prefs->isDefault('search_sources')) {
            $src = $prefs->getValue('search_sources');
            if (!is_array(json_decode($src))) {
                $prefs->setValue('search_sources', json_encode(explode("\t", $src)));
            }
        }

        if (!$prefs->isDefault('search_fields')) {
            $val = $prefs->getValue('search_fields');
            if (!is_array(json_decode($val, true))) {
                $fields = array();
                foreach (explode("\n", $val) as $field) {
                    $field = trim($field);
                    if (!empty($field)) {
                        $tmp = explode("\t", $field);
                        if (count($tmp) > 1) {
                            $source = array_splice($tmp, 0, 1);
                            $fields[$source[0]] = $tmp;
                        }
                    }
                }
                $prefs->setValue('search_fields', json_encode($fields));
            }
        }
    }

    /**
     * Upgrade to the new compose preferences.
     */
    protected function _upgradeComposePrefs()
    {
        global $prefs;

        if ($prefs->isDefault('request_mdn') &&
            ($val = $prefs->getValue('disposition_request_read'))) {
            $prefs->setValue('request_mdn', $val);
        }
    }

    /**
     * 'delete_attachments_monthly' no longer exists -> use
     * 'delete_attachments_monthly_keep' instead.
     */
    protected function _upgradeDeleteAttachmentsMonthlyPrefs()
    {
        global $prefs;

        if (!$prefs->getValue('delete_attachments_monthly') &&
            ($prefs->getDefault('delete_attachments_monthly') !== null)) {
            $prefs->setValue('delete_attachments_monthly_keep', 0);
        }

        // Need to remove old pref or else there can be no way of determining
        // whether upgrade has previously occurred.
        $prefs->remove('delete_attachments_monthly');
    }

    /**
     * 'delete_sentmail_monthly' no longer exists -> use
     * 'delete_sentmail_monthly_keep' instead.
     */
    protected function _upgradeDeleteSentmailMonthlyPrefs()
    {
        global $prefs;

        if (!$prefs->getValue('delete_sentmail_monthly') &&
            ($prefs->getDefault('delete_sentmail_monthly') !== null)) {
            $prefs->setValue('delete_sentmail_monthly_keep', 0);
        }

        // Need to remove old pref or else there can be no way of determining
        // whether upgrade has previously occurred.
        $prefs->remove('delete_sentmail_monthly');
    }

    /**
     * Upgrade to the new forward preferences.
     */
    protected function _upgradeForwardPrefs()
    {
        global $prefs;

        if ($prefs->isDefault('forward_default')) {
            return;
        }

        switch ($prefs->getValue('forward_default')) {
        case 'forward_attachments':
            $prefs->setValue('forward_default', 'both');
            break;

        case 'forward_all':
            $prefs->setValue('forward_default', 'attach');
            break;

        case 'forward_body':
            $prefs->setValue('forward_default', 'body');
            break;

        case 'attach':
        case 'body':
        case 'both':
            // Ignore - already converted.
            break;

        default:
            $prefs->setValue('forward_default', 'attach');
            break;
        }
    }

    /**
     * Upgrade to the new login tasks preferences.
     */
    protected function _upgradeLoginTasksPrefs()
    {
        global $prefs;

        $replace = $prefs->getValue('html_image_replacement');
        if (!is_null($replace) && !$replace) {
            $prefs->setValue('image_replacement', 0);
        }

        $addrbook = $prefs->getValue('html_image_addrbook');
        if (!is_null($addrbook) && !$addrbook) {
            $prefs->setValue('image_addrbook', 0);
        }
    }

    /**
     * Upgrade to the new message display preferences.
     */
    protected function _upgradeMsgDisplayPrefs()
    {
        global $prefs;

        if (!$prefs->isDefault('initial_page')) {
            if ($prefs->getValue('initial_page') == 'folders.php') {
                $prefs->setValue('initial_page', IMP::INITIAL_FOLDERS);
            } elseif (strpos($prefs->getValue('initial_page'), '**search_') === 0) {
                /* No easy way to convert old virtual folder label to new
                 * label, so revert to default. */
                $prefs->remove('initial_page');
            }
        }

        if ($prefs->isDefault('send_mdn') &&
            $prefs->getValue('disposition_send_mdn')) {
            $prefs->setValue('send_mdn', 1);
        }
    }

    /**
     * Upgrade to the new newmail preferences.
     */
    protected function _upgradeNewmailPrefs()
    {
        global $prefs;

        if ($prefs->isDefault('newmail_notify') &&
            $prefs->getValue('nav_popup')) {
            $prefs->setValue('newmail_notify', 1);
        }

        if ($prefs->getValue('newmail_notify') &&
            $prefs->isDefault('newmail_audio') &&
            ($nav_audio = $prefs->getValue('nav_audio'))) {
            $prefs->setValue('newmail_audio', $nav_audio);
        }
    }

    /**
     * 'purge_sentmail' no longer exists -> use 'purge_sentmail_interval'
     * instead.
     */
    protected function _upgradePurgeSentmailPrefs()
    {
        global $prefs;

        if (!$prefs->getValue('purge_sentmail') &&
            ($prefs->getDefault('purge_sentmail') !== null)) {
            $prefs->remove('purge_sentmail_interval');
        }

        // Need to remove old pref or else there can be no way of determining
        // whether upgrade has previously occurred.
        $prefs->remove('purge_sentmail');
    }

    /**
     * 'purge_spam' no longer exists -> use 'purge_spam_interval' instead.
     */
    protected function _upgradePurgeSpamPrefs()
    {
        global $prefs;

        if (!$prefs->getValue('purge_spam') &&
            ($prefs->getDefault('purge_spam') !== null)) {
            $prefs->remove('purge_spam_interval');
        }

        // Need to remove old pref or else there can be no way of determining
        // whether upgrade has previously occurred.
        $prefs->remove('purge_spam');
    }

    /**
     * 'purge_trash' no longer exists -> use 'purge_trash_interval' instead.
     */
    protected function _upgradePurgeTrashPrefs()
    {
        global $prefs;

        if (!$prefs->getValue('purge_trash') &&
            ($prefs->getDefault('purge_trash') !== null)) {
            $prefs->remove('purge_trash_interval');
        }

        // Need to remove old pref or else there can be no way of determining
        // whether upgrade has previously occurred.
        $prefs->remove('purge_trash');
    }

    /**
     * Check for old, non-existent sort values. See Bug #7296.
     */
    protected function _upgradeSortPrefs()
    {
        global $injector, $prefs;

        $sort_ob = $injector->getInstance('IMP_Prefs_Sort');
        $sort_ob->upgradePrefs();

        if (!$prefs->isDefault('sortby')) {
            $sb = $sort_ob->newSortbyValue($prefs->getValue('sortby'));
            if (!is_null($sb)) {
                $prefs->setValue('sortby', $sb);
            }
        }
    }

    /**
     * Upgrade stationery preference.
     */
    protected function _upgradeStationery()
    {
        $upgrade_prefs = array(
            'stationery'
        );

        $GLOBALS['injector']->getInstance('Horde_Core_Prefs_Storage_Upgrade')->upgradeSerialized($GLOBALS['prefs'], $upgrade_prefs);
    }

    /**
     * Upgrade IMP 4 style virtual folders.
     */
    protected function _upgradeVirtualFolders()
    {
        global $prefs;

        $use_vinbox = $prefs->getValue('use_vinbox');
        $use_vtrash = $prefs->getValue('use_vtrash');

        $vfolders = $prefs->getValue('vfolder');
        if (!empty($vfolders)) {
            $vfolders = @unserialize($vfolders);
        }

        if (empty($vfolders) || !is_array($vfolders)) {
            return;
        }

        if ($prefs->isDefault('vfolder') || is_object(reset($vfolders))) {
            foreach ($vfolders as $val) {
                if (!is_null($use_vinbox) &&
                    ($val instanceof IMP_Search_Vfolder_Vinbox)) {
                    $val->enabled = (bool)$use_vinbox;
                } elseif (!is_null($use_vtrash) &&
                          ($val instanceof IMP_Search_Vfolder_Vtrash)) {
                    $val->enabled = (bool)$use_vtrash;
                    if ($use_vtrash) {
                        $prefs->setValue('trash_folder', strval($val));
                    }
                }
            }
            $prefs->setValue('vfolder', serialize($vfolders));
            return;
        }

        $new_vfolders = array();
        if ($use_vinbox) {
            $new_vfolders[] = new IMP_Search_Vfolder_Vinbox();
        }
        if ($use_vtrash) {
            $vtrash = $new_vfolders[] = new IMP_Search_Vfolder_Vtrash();
            $prefs->setValue('trash_folder', strval($vtrash));
        }

        foreach ($vfolders as $vfolder) {
            $ui = $vfolder['uiinfo'];

            $or_match = ($ui['match'] == 'or');

            /* BC: Convert old (IMP < 4.2.1) style w/separate flag entry to
             * new style where flags are part of the fields to query. */
            if (!empty($ui['flag'])) {
                $lookup = array(
                    1 => 'seen',
                    2 => 'answered',
                    3 => 'flagged',
                    4 => 'deleted'
                );

                foreach ($ui['flag'] as $key => $val) {
                    if (($val == 0) || ($val == 1)) {
                        $ui['field'][] = (($val == 1) ? 'un' : '') . $lookup[$key];
                    }
                }
            }

            foreach ($ui['field'] as $key => $val) {
                $ob = new IMP_Search_Vfolder(array(
                    'enabled' => true,
                    'label' => $ui['vfolder_label'],
                    'mboxes' => $ui['folders']
                ));

                switch ($val) {
                case 'from':
                case 'cc':
                case 'bcc':
                case 'subject':
                    $ob->add(new IMP_Search_Element_Header(
                        $ui['text'][$key],
                        $val,
                        !empty($ui['text_not'][$key])
                    ));
                    break;

                case 'to':
                    $ob->add(new IMP_Search_Element_Recipient(
                        $val,
                        !empty($ui['text_not'][$key])
                    ));
                    break;

                case 'body':
                case 'text':
                    $ob->add(new IMP_Search_Element_Text(
                        $ui['text'][$key],
                        ($val == 'body'),
                        !empty($ui['text_not'][$key])
                    ));
                    break;

                case 'date_on':
                case 'date_until':
                case 'date_since':
                    if ($val == 'date_on') {
                        $type = IMP_Search_Element_Date::DATE_ON;
                    } elseif ($val == 'date_until') {
                        $type = IMP_Search_Element_Date::DATE_BEFORE;
                    } else {
                        $type = IMP_Search_Element_Date::DATE_SINCE;
                    }
                    $ob->add(new IMP_Search_Element_Date(
                        new DateTime($ui['date'][$key]['year'] . '-' . $ui['date'][$key]['month'] . '-' . $ui['date'][$key]['day']),
                        $type
                    ));
                    break;

                case 'size_smaller':
                case 'size_larger':
                    $ob->add(new IMP_Search_Element_Size(
                        $ui['text'][$key],
                        $val == 'size_larger'
                    ));
                    break;

                case 'seen':
                case 'unseen':
                case 'answered':
                case 'unanswered':
                case 'flagged':
                case 'unflagged':
                case 'deleted':
                case 'undeleted':
                    if (strpos($val, 'un') === false) {
                        $ob->add(new IMP_Search_Element_Flag(
                            $val,
                            true
                        ));
                    } else {
                        $ob->add(new IMP_Search_Element_Flag(
                            substr($val, 2),
                            false
                        ));
                    }
                    break;
                }

                if ($or_match) {
                    $ob->add(new IMP_Search_Element_Or());
                }
            }

            $new_vfolders[] = $ob;
        }

        $GLOBALS['injector']->getInstance('IMP_Search')->setVFolders($new_vfolders);
    }

    /**
     * Upgrades the 'compose_cursor' preference (IMP 6).
     */
    protected function _upgradeComposeCursor()
    {
        global $prefs;

        if ($prefs->getValue('compose_cursor') == 'sig') {
            $prefs->setValue('compose_cursor', 'bottom');
        }
    }

    /**
     * Upgrades the 'move_ham_after_report' preference (IMP 6).
     */
    protected function _upgradeInnocentPrefs()
    {
        global $prefs;

        if (!$prefs->isDefault('move_ham_after_report')) {
            $prefs->setValue('move_innocent_after_report', $prefs->getValue('move_ham_after_report'));
        }
    }

    /**
     * As of IMP 6, special mailboxes are stored in UTF-8, not UTF7-IMAP.
     */
    protected function _upgradeMailboxPrefs()
    {
        global $injector, $prefs;

        $special_mboxes = array(
            'drafts_folder',
            'spam_folder',
            'trash_folder'
        );

        foreach ($special_mboxes as $val) {
            if (!$prefs->isDefault($val)) {
                $old_pref = IMP_Mailbox::getPref($val);
                $mbox = IMP_Mailbox::get(Horde_String::convertCharset(strval($old_pref), 'UTF7-IMAP', 'UTF-8'));
                $prefs->setValue($val, $mbox->pref_to);
            }
        }

        $imp_identity = $injector->getInstance('IMP_Identity');

        foreach ($imp_identity->getAll('sent_mail_folder') as $key => $val) {
            if (!is_null($val)) {
                $mbox = IMP_Mailbox::get(Horde_String::convertCharset(strval($val), 'UTF7-IMAP', 'UTF-8'));
                $imp_identity->setValue('sent_mail_folder', $mbox, $key);
            }
        }
    }

    /**
     * For IMP 6, upgrade stationery preference -> templates mailbox.
     */
    protected function _upgradeStationeryToTemplates()
    {
        global $injector, $prefs;

        $slist = @unserialize($prefs->getValue('stationery'));
        if (is_array($slist)) {
            /* Old entry format:
             * 'c' => (string) Content
             * 'n' => (string) Name
             * 't' => (string) Type */
            foreach ($slist as $val) {
                $injector->getInstance('IMP_Factory_Compose')->create()->saveTemplate(
                    array('subject' => $val['n']),
                    $val['c']
                );
            }
        }
    }

    /**
     * Upgrade IMP 5.0.x virtual folders.
     */
    protected function _upgradeVirtualFolders6()
    {
        $imp_search = $GLOBALS['injector']->getInstance('IMP_Search');
        $imp_search->setIteratorFilter(IMP_Search::LIST_VFOLDER | IMP_Search::LIST_DISABLED);

        foreach ($imp_search as $key => $val) {
            $replace = false;
            $tmp = $val->query;

            foreach (array_keys($tmp) as $key2) {
                if ($tmp[$key2] instanceof IMP_Search_Element_Date) {
                    $criteria = $tmp[$key2]->getCriteria();

                    switch ($criteria->t) {
                    case IMP_Search_Element_Date::DATE_ON:
                        $ob = new IMP_Search_Element_Daterange(
                            new DateTime('@' . $criteria->d),
                            new DateTime('@' . $criteria->d)
                        );
                        break;

                    case IMP_Search_Element_Date::DATE_BEFORE:
                        $ob = new IMP_Search_Element_Daterange(
                            null,
                            new DateTime('@' . $criteria->d)
                        );
                        break;

                    case IMP_Search_Element_Date::DATE_SINCE:
                        $ob = new IMP_Search_Element_Daterange(
                            new DateTime('@' . $criteria->d),
                            null
                        );
                        break;
                    }

                    $tmp[$key2] = $ob;
                    $replace = true;
                }
            }

            if ($replace) {
                $val->replace($tmp);
                $imp_search[$key] = $val;
            }
        }
    }

}
