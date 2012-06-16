<?php
/**
 * Special prefs handling for the 'searchesmanagement' preference.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Prefs_Special_Searches implements Horde_Core_Prefs_Ui_Special
{
    /**
     */
    public function init(Horde_Core_Prefs_Ui $ui)
    {
    }

    /**
     */
    public function display(Horde_Core_Prefs_Ui $ui)
    {
        global $injector, $page_output, $prefs, $registry;

        $page_output->addScriptFile('searchesprefs.js');

        $t = $injector->createInstance('Horde_Template');
        $t->setOption('gettext', true);

        $imp_search = $injector->getInstance('IMP_Search');
        $fout = $mailboxids = $vout = array();
        $view_mode = $registry->getView();

        $imp_search->setIteratorFilter(IMP_Search::LIST_VFOLDER | IMP_Search::LIST_DISABLED);
        $vfolder_locked = $prefs->isLocked('vfolder');

        foreach ($imp_search as $key => $val) {
            if (!$val->prefDisplay) {
                continue;
            }

            $editable = !$vfolder_locked && $imp_search->isVFolder($val, true);
            $m_url = ($val->enabled && ($view_mode == Horde_Registry::VIEW_BASIC))
                ? $val->mbox_ob->url('mailbox.php')->link(array('class' => 'vfolderenabled'))
                : null;

            if ($view_mode == Horde_Registry::VIEW_DYNAMIC) {
                $mailboxids['enable_' . $key] = $val->formid;
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

            if ($editable && ($view_mode == Horde_Registry::VIEW_DYNAMIC)) {
                $mailboxids['enable_' . $key] = $val->formid;
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
            $GLOBALS['page_output']->addInlineJsVars(array(
                'ImpSearchesPrefs.confirm_delete_filter' => _("Are you sure you want to delete this filter?"),
                'ImpSearchesPrefs.confirm_delete_vfolder' => _("Are you sure you want to delete this virtual folder?"),
                'ImpSearchesPrefs.mailboxids' => $mailboxids
            ));
        }

        return $t->fetch(IMP_TEMPLATES . '/prefs/searches.html');
    }

    /**
     */
    public function update(Horde_Core_Prefs_Ui $ui)
    {
        global $injector, $notification;

        $imp_search = $injector->getInstance('IMP_Search');

        switch ($ui->vars->searches_action) {
        case 'delete':
            /* Remove 'enable_' prefix. */
            $key = substr($ui->vars->searches_data, 7);
            if ($ob = $imp_search[$key]) {
                if ($imp_search->isVFolder($ob)) {
                    $notification->push(sprintf(_("Virtual Folder \"%s\" deleted."), $ob->label), 'horde.success');
                } elseif ($imp_search->isFilter($ob)) {
                    $notification->push(sprintf(_("Filter \"%s\" deleted."), $ob->label), 'horde.success');
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

        return false;
    }

}
