<?php
/**
 * Login system task for automated upgrade tasks.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package IMP
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
        $this->_upgradeForwardPrefs();
        $this->_upgradeSortPrefs();
        $this->_upgradeVirtualFolders();
    }

    /**
     * Upgrade to the new forward preferences.
     */
    protected function upgradeForwardPrefs()
    {
        switch ($GLOBALS['prefs']->getValue('forward_default')) {
        case 'forward_attachments':
            $GLOBALS['prefs']->setValue('forward_default', 'both');
            break;

        case 'forward_all':
            $GLOBALS['prefs']->setValue('forward_default', 'attach');
            break;

        case 'forward_body':
            $GLOBALS['prefs']->setValue('forward_default', 'body');
            break;

        case 'attach':
        case 'body':
        case 'both':
            // Ignore - already converted.
            break;

        default:
            $GLOBALS['prefs']->setValue('forward_default', 'attach');
            $GLOBALS['prefs']->setDefault('forward_default', true);
            break;
        }
    }

    /**
     * Check for old, non-existent sort values. See Bug #7296.
     */
    protected function _upgradeSortPrefs()
    {
        $update = false;
        $sortpref = @unserialize($GLOBALS['prefs']->getValue('sortpref'));
        foreach ($sortpref as $key => $val) {
            $sb = $this->_newSortbyValue($val['b']);
            if (!is_null($sb)) {
                $sortpref[$key]['b'] = $sb;
                $update = true;
            }
        }

        if ($update) {
            $GLOBALS['prefs']->setValue('sortpref', serialize($sortpref));
        }

        $sb = $this->_newSortbyValue($GLOBALS['prefs']->getValue('sortby'));
        if (!is_null($sb)) {
            $GLOBALS['prefs']->setValue('sortby', $sb);
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
        $vfolders = $GLOBALS['prefs']->getValue('vfolder');
        if (!empty($vfolders)) {
            $vfolders = @unserialize($vfolders);
        }

        if (empty($vfolders) || !is_array($vfolders)) {
            return;
        }

        $imp_ui_search = new IMP_Ui_Search();

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
