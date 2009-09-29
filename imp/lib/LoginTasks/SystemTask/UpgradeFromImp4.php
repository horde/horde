<?php
/**
 * Login system task for automated upgrade tasks.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_LoginTasks
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
        IMP::initialize();

        $this->_upgradeSortPrefs();
        $this->_upgradeVirtualFolders();
    }

    /**
     * Check for old, non-existent sort values. See Bug #7296.
     */
    protected function _upgradeSortPrefs()
    {
        $sortby = $GLOBALS['prefs']->getValue('sortby');
        if ($sortby > 10) {
            $GLOBALS['prefs']->setValue('sortby', Horde_Imap_Client::SORT_ARRIVAL);
        }

        $update = false;
        $sortpref = @unserialize($GLOBALS['prefs']->getValue('sortpref'));
        foreach ($sortpref as $key => $val) {
            if ($val['b'] > 10) {
                $sortpref[$key]['b'] = Horde_Imap_Client::SORT_ARRIVAL;
                $update = true;
            }
        }
        if ($update) {
            $GLOBALS['prefs']->setValue('sortpref', serialize($sortpref));
        }
    }

    /**
     * Upgrade IMP 4 style virtual folders.
     */
    protected function _upgradeVirtualFolders()
    {
        $vfolders = $GLOBALS['prefs']->getValue('vfolder');
        if (!empty($vfolders)) {
            $vfolders = @unserialize($vfolders);
        }

        if (empty($vfolders) || !is_array($vfolders)) {
            return;
        }

        $imp_ui_search = new IMP_UI_Search();

        foreach ($vfolders as $id => $vfolder) {
            /* If this is already a stdClass object, we have already
             * upgraded. */
            if (is_object($vfolder)) {
                return;
            }

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

            $rules = array();

            foreach ($ui['field'] as $key => $val) {
                $tmp = new stdClass;

                switch ($val) {
                case 'from':
                case 'to':
                case 'cc':
                case 'bcc':
                case 'subject':
                case 'body':
                case 'text':
                    $tmp->t = $val;
                    $tmp->v = $ob['text'][$key];
                    $tmp->n = !empty($ob['text_not'][$key]);
                    break;

                case 'date_on':
                case 'date_until':
                case 'date_since':
                    $tmp->t = $val;
                    $tmp->v = new stdClass;
                    $tmp->v->y = $ob['date'][$key]['year'];
                    $tmp->v->m = $ob['date'][$key]['month'] - 1;
                    $tmp->v->d = $ob['date'][$key]['day'];
                    break;

                case 'size_smaller':
                case 'size_larger':
                    $tmp->t = $val;
                    $tmp->v = $ob['text'][$key];
                    break;

                case 'seen':
                case 'unseen':
                case 'answered':
                case 'unanswered':
                case 'flagged':
                case 'unflagged':
                case 'deleted':
                case 'undeleted':
                    $tmp->t = 'flag';
                    $tmp->v = (strpos($val, 'un') === false)
                        ? '\\' . $val
                        : '0\\\\' . substr($val, 2);
                    break;
                }

                $rules[] = $tmp;

                if ($or_match) {
                    $tmp = new stdClass;
                    $tmp->t = 'or';
                    $rules[] = $tmp;
                }
            }

            /* This will overwrite the existing entry. */
            $query = $imp_ui_search->createQuery($rules);
            $GLOBALS['imp_search']->addVFolder($query, $ui['folders'], $rules, $ui['vfolder_label'], $id);
        }
    }

}
