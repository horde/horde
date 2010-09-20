<?php
/**
 * Login system task for automated upgrade tasks.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_LoginTasks_SystemTask_UpgradeFromImp4 extends Horde_LoginTasks_SystemTask
{
    /**
     * The interval at which to run the task.
     *
     * @var integer
     */
    public $interval = Horde_LoginTasks::ONCE;

    /**
     * Perform all functions for this task.
     */
    public function execute()
    {
        $this->_upgradeAbookPrefs();
        $this->_upgradeExpireImapCache();
        $this->_upgradeForwardPrefs();
        $this->_upgradeLoginTasksPrefs();
        $this->_upgradeSortPrefs();
        $this->_upgradeVirtualFolders();
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
                $prefs->setValue('search_fields', $fields);
            }
        }
    }

    /**
     * Expire existing IMAP cache.
     */
    protected function _upgradeExpireImapCache()
    {
        try {
            $ob = $GLOBALS['injector']->getInstance('IMP_Imap')->getOb()->ob;
            $ob->login();

            $mboxes = $ob->listMailboxes('*', Horde_Imap_Client::MBOX_ALL, array('flat' => true));

            foreach ($mboxes as $val) {
                $ob->cache->deleteMailbox($val);
            }
        } catch (Exception $e) {}
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
            $prefs->setDefault('forward_default', true);
            break;
        }
    }

    /**
     * Upgrade to the new login tasks preferences.
     */
    protected function _upgradeLoginTasksPrefs()
    {
        global $prefs;

        if (!$prefs->isDefault('initial_page') &&
            ($prefs->getValue('initial_page') == 'folders.php')) {
            $prefs->setValue('initial_page', IMP_Prefs_Ui::PREF_FOLDER_PAGE);
        }
    }

    /**
     * Check for old, non-existent sort values. See Bug #7296.
     */
    protected function _upgradeSortPrefs()
    {
        global $prefs;

        if (!$prefs->isDefault('sortpref')) {
            $update = false;
            $sortpref = @unserialize($prefs->getValue('sortpref'));
            foreach ($sortpref as $key => $val) {
                $sb = $this->_newSortbyValue($val['b']);
                if (!is_null($sb)) {
                    $sortpref[$key]['b'] = $sb;
                    $update = true;
                }
            }

            if ($update) {
                $prefs->setValue('sortpref', serialize($sortpref));
            }
        }

        if (!$prefs->isDefault('sortby')) {
            $sb = $this->_newSortbyValue($prefs->getValue('sortby'));
            if (!is_null($sb)) {
                $prefs->setValue('sortby', $sb);
            }
        }
    }

    /**
     * Get the new sortby pref value.
     *
     * @param integer $sortby  The old value.
     *
     * @return integer  Null if no change or else the converted sort value.
     */
    protected function _newSortbyValue($sortby)
    {
        switch ($sortby) {
        case 1: // SORTARRIVAL
            /* Sortarrival was the same thing as sequence sort in IMP 4. */
            return Horde_Imap_Client::SORT_SEQUENCE;

        case 2: // SORTDATE
            return IMP::IMAP_SORT_DATE;

        case 161: // SORTTHREAD
            return Horde_Imap_Client::SORT_THREAD;
        }

        return null;
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
                    $prefs->setValue('trash_folder', strval($val));
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

        foreach ($vfolders as $id => $vfolder) {
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
                case 'to':
                case 'cc':
                case 'bcc':
                case 'subject':
                    $ob->add(new IMP_Search_Element_Header(
                        $ui['text'][$key],
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

}
