<?php
/**
 * Copyright 2012-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2012-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Special prefs handling for the 'searchesmanagement' preference.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2012-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
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
        global $injector, $page_output, $prefs;

        $page_output->addScriptFile('hordecore.js', 'horde');
        $page_output->addScriptFile('prefs/searches.js');

        $p_css = new Horde_Themes_Element('prefs.css');
        $page_output->addStylesheet($p_css->fs, $p_css->uri);

        $imp_search = $injector->getInstance('IMP_Search');
        $fout = $vout = array();

        $view = new Horde_View(array(
            'templatePath' => IMP_TEMPLATES . '/prefs'
        ));
        $view->addHelper('FormTag');
        $view->addHelper('Tag');
        $view->addHelper('Text');

        $vfolder_locked = $prefs->isLocked('vfolder');
        $iterator = IMP_Search_IteratorFilter::create(
            IMP_Search_IteratorFilter::DISABLED |
            IMP_Search_IteratorFilter::VFOLDER
        );

        foreach ($iterator as $val) {
            if (!$val->prefDisplay) {
                continue;
            }

            $editable = !$vfolder_locked && $imp_search->isVFolder($val, true);
            $m_url = $val->enabled
                ? $val->mbox_ob->url('mailbox')->link()
                : null;

            $vout[] = array(
                'description' => Horde_String::truncate($val->querytext, 200),
                'edit' => ($editable ? $imp_search->editUrl($val) : null),
                'enabled' => $val->enabled,
                'enabled_locked' => $vfolder_locked,
                'key' => $val->id,
                'label' => $val->label,
                'm_url' => $m_url
            );
        }
        $view->vfolders = $vout;

        $filter_locked = $prefs->isLocked('filter');
        $iterator = IMP_Search_IteratorFilter::create(
            IMP_Search_IteratorFilter::DISABLED |
            IMP_Search_IteratorFilter::FILTER
        );

        foreach ($iterator as $val) {
            if (!$val->prefDisplay) {
                continue;
            }

            $editable = !$filter_locked && $imp_search->isFilter($val, true);

            $fout[] = array(
                'description' => Horde_String::truncate($val->querytext, 200),
                'edit' => ($editable ? $imp_search->editUrl($val) : null),
                'enabled' => $val->enabled,
                'enabled_locked' => $filter_locked,
                'key' => $val->id,
                'label' => $val->label
            );
        }
        $view->filters = $fout;

        if (empty($fout) && empty($vout)) {
            $view->nosearches = true;
        } else {
            $GLOBALS['page_output']->addInlineJsVars(array(
                'ImpSearchesPrefs.confirm_delete_filter' => _("Are you sure you want to delete this filter?"),
                'ImpSearchesPrefs.confirm_delete_vfolder' => _("Are you sure you want to delete this virtual folder?")
            ));
        }

        return $view->render('searches');
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
            $iterator = IMP_Search_IteratorFilter::create(
                IMP_Search_IteratorFilter::DISABLED |
                IMP_Search_IteratorFilter::VFOLDER
            );
            $vfolders = array();

            foreach ($iterator as $val) {
                $form_key = 'enable_' . $val->id;

                /* Only change enabled status for virtual folders displayed
                 * on the preferences screen. */
                if ($val->prefDisplay) {
                    $val->enabled = !empty($ui->vars->$form_key);
                    $vfolders[$val->id] = $val;
                }
            }
            $imp_search->setVFolders($vfolders);

            /* Update enabled status for Filters. */
            $iterator = IMP_Search_IteratorFilter::create(
                IMP_Search_IteratorFilter::DISABLED |
                IMP_Search_IteratorFilter::FILTER
            );
            $filters = array();

            foreach ($iterator as $val) {
                $form_key = 'enable_' . $val->id;
                $val->enabled = !empty($ui->vars->$form_key);
                $filters[$val->id] = $val;
            }
            $imp_search->setFilters($filters);
            break;
        }

        return false;
    }

}
