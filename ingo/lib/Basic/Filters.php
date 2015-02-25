<?php
/**
 * Copyright 2002-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category  Horde
 * @copyright 2002-2015 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */

/**
 * Filters page.
 *
 * @author    Mike Cochrane <mike@graftonhall.co.nz>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2002-2015 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */
class Ingo_Basic_Filters extends Ingo_Basic_Base
{
    /**
     */
    protected function _init()
    {
        global $injector, $notification, $page_output, $prefs, $session;

        /* Get the list of filter rules. */
        $ingo_storage = $injector->getInstance('Ingo_Factory_Storage')->create();

        /* Load the Ingo_Script factory. */
        $factory = $injector->getInstance('Ingo_Factory_Script');

        /* Get permissions. */
        $edit_allowed = Ingo::hasSharePermission(Horde_Perms::EDIT);
        $delete_allowed = Ingo::hasSharePermission(Horde_Perms::DELETE);

        /* Token checking. */
        $actionID = $this->_checkToken(array(
            'rule_copy',
            'rule_delete',
            'rule_disable',
            'rule_enable'
        ));

        /* Default to no mailbox filtering. */
        $mbox_search = null;

        /* Perform requested actions. */
        switch ($actionID) {
        case 'mbox_search':
            if (isset($this->vars->searchfield)) {
                $mbox_search = array(
                    'exact' => $this->vars->get('searchexact', 1),
                    'query' => $this->vars->searchfield
                );
            }
            break;

        case 'rule_copy':
        case 'rule_delete':
        case 'rule_disable':
        case 'rule_enable':
            if (!$edit_allowed) {
                $notification->push(_("You do not have permission to edit filter rules."), 'horde.error');
                self::url()->redirect();
            }

            $success = false;

            switch ($actionID) {
            case 'rule_delete':
                if (!$delete_allowed) {
                    $notification->push(_("You do not have permission to delete filter rules."), 'horde.error');
                    self::url()->redirect();
                }

                if (($tmp = $ingo_storage->getRuleByUid($this->vars->uid)) &&
                    $ingo_storage->deleteRule($tmp)) {
                    $notification->push(
                        sprintf(_("Rule \"%s\" deleted."), $tmp->name),
                        'horde.success'
                    );
                    $success = true;
                }
                break;

            case 'rule_copy':
                switch ($ingo_storage->maxRules()) {
                case Ingo_Storage::MAX_NONE:
                    Horde::permissionDeniedError(
                        'ingo',
                        'max_rules',
                        _("You are not allowed to create or edit custom rules.")
                    );
                    break 2;

                case Ingo_Storage::MAX_OVER:
                    Horde::permissionDeniedError(
                        'ingo',
                        'max_rules',
                        sprintf(_("You are not allowed to create more than %d rules."), $ingo_storage->max_rules)
                    );
                    break 2;
                }

                if (($tmp = $ingo_storage->getRuleByUid($this->vars->uid)) &&
                    $ingo_storage->copyRule($tmp)) {
                    $notification->push(
                        sprintf(_("Rule \"%s\" copied."), $tmp->name),
                        'horde.success'
                    );
                    $success = true;
                }
                break;

            case 'rule_disable':
            case 'rule_enable':
                if ($tmp = $ingo_storage->getRuleByUid($this->vars->uid)) {
                    $tmp->disable = ($actionID === 'rule_disable');
                    $ingo_storage->updateRule($tmp);
                    $notification->push(
                        sprintf(
                            ($actionID === 'rule_disable')
                                ? _("Rule \"%s\" disabled.")
                                : _("Rule \"%s\" enabled."),
                            $tmp->name
                        ),
                        'horde.success'
                    );
                    $success = true;
                }
                break;
            }

            /* Save changes */
            if ($success) {
                try {
                    Ingo_Script_Util::update();
                } catch (Ingo_Exception $e) {
                    $notification->push($e->getMessage(), 'horde.error');
                }
            }
            break;

        case 'settings_save':
            if (!$edit_allowed) {
                $notification->push(_("You do not have permission to edit filter rules."), 'horde.error');
                self::url()->redirect();
            }
            $prefs->setValue('show_filter_msg', $this->vars->show_filter_msg);
            $prefs->setValue('filter_seen', $this->vars->filter_seen);
            $notification->push(_("Settings successfully updated."), 'horde.success');
            break;

        case 'apply_filters':
            $factory->perform();
            break;
        }

        /* Common URLs. */
        $filters_url = $this->_addToken(self::url());
        $rule_url = Ingo_Basic_Rule::url();

        $view = new Horde_View(array(
            'templatePath' => INGO_TEMPLATES . '/basic/filters'
        ));
        $view->addHelper('Horde_Core_View_Helper_Help');
        $view->addHelper('Horde_Core_View_Helper_Image');
        $view->addHelper('Horde_Core_View_Helper_Label');
        $view->addHelper('FormTag');
        $view->addHelper('Tag');

        $view->canapply = $factory->canPerform();
        $view->deleteallowed = $delete_allowed;
        $view->editallowed = $edit_allowed;
        $view->formurl = $filters_url;

        $view->can_copy = $edit_allowed && !$ingo_storage->maxRules();

        $display = array();
        $filters = Ingo_Storage_FilterIterator_Match::create(
            $ingo_storage,
            $session->get('ingo', 'script_categories')
        );

        foreach ($filters as $rule) {
            $copyurl = $delurl = $editurl = null;
            $entry = array();
            $url = $filters_url->copy()->add('uid', $rule->uid);

            switch (get_class($rule)) {
            case 'Ingo_Rule_System_Blacklist':
                if (!is_null($mbox_search)) {
                    continue 2;
                }
                $editurl = Ingo_Basic_Blacklist::url();
                $entry['filterimg'] = 'blacklist.png';
                break;

            case 'Ingo_Rule_System_Whitelist':
                if (!is_null($mbox_search)) {
                    continue 2;
                }
                $editurl = Ingo_Basic_Whitelist::url();
                $entry['filterimg'] = 'whitelist.png';
                break;

            case 'Ingo_Rule_System_Vacation':
                if (!is_null($mbox_search)) {
                    continue 2;
                }
                $editurl = Ingo_Basic_Vacation::url();
                $entry['filterimg'] = 'vacation.png';
                break;

            case 'Ingo_Rule_System_Forward':
                if (!is_null($mbox_search)) {
                    continue 2;
                }
                $editurl = Ingo_Basic_Forward::url();
                $entry['filterimg'] = 'forward.png';
                break;

            case 'Ingo_Rule_System_Spam':
                if (!is_null($mbox_search)) {
                    continue 2;
                }
                $editurl = Ingo_Basic_Spam::url();
                $entry['filterimg'] = 'spam.png';
                break;

            default:
                if (!is_null($mbox_search)) {
                    if ($mbox_search['exact']) {
                        if (strcasecmp($filter['action-value'], $mbox_search['query']) !== 0) {
                            continue 2;
                        }
                    } elseif (stripos($filter['action-value'], $mbox_search['query']) === false) {
                        continue 2;
                    }
                }

                $editurl = $rule_url->copy()->add(array(
                    'edit' => $rule->uid
                ));
                $delurl = $url->copy()->add('actionID', 'rule_delete');
                $copyurl = $url->copy()->add('actionID', 'rule_copy');
                break;
            }

            /* Create description. */
            if (!$edit_allowed) {
                $entry['descriplink'] = htmlspecialchars($rule->name);
            } elseif (!empty($rule->conditions)) {
                $entry['descriplink'] = Horde::linkTooltip(
                    $editurl,
                    sprintf(_("Edit %s"), $rule->name),
                    null,
                    null,
                    null,
                    $rule->description
                ) . htmlspecialchars($rule->name) . '</a>';
            } else {
                $entry['descriplink'] = Horde::link(
                    $editurl,
                    sprintf(_("Edit %s"), $rule->name)
                ) . htmlspecialchars($rule->name) . '</a>';
            }

            /* Create delete link. */
            if ($delete_allowed && !is_null($delurl)) {
                $entry['dellink'] = Horde::link(
                    $delurl,
                    sprintf(_("Delete %s"), $rule->name),
                    null,
                    null,
                    "return window.confirm('" . addslashes(_("Are you sure you want to delete this rule?")) . "');"
                );
            }

            /* Create copy link. */
            if ($view->can_copy && !is_null($copyurl)) {
                $entry['copylink'] = Horde::link(
                    $copyurl,
                    sprintf(_("Copy %s"), $rule->name)
                );
            }

            /* Create disable/enable link. */
            if (!$rule->disable) {
                $entry['disabled'] = true;
                if ($edit_allowed) {
                    $entry['disablelink'] = Horde::link(
                        $url->copy()->add('actionID', 'rule_disable'),
                        sprintf(_("Disable %s"), $rule->name)
                    );
                }
            } elseif ($edit_allowed) {
                $entry['enablelink'] = Horde::link(
                    $url->copy()->add('actionID', 'rule_enable'),
                    sprintf(_("Enable %s"), $rule->name)
                );
            }

            $display[$rule->uid] = $entry;
        }

        $view->filter = $display;
        $view->mbox_search = $mbox_search;

        if ($edit_allowed && is_null($mbox_search)) {
            if ($factory->hasFeature('on_demand')) {
                $view->settings = true;
                $view->flags = $prefs->getValue('filter_seen');
                $view->show_filter_msg = $prefs->getValue('show_filter_msg');
            }

            $page_output->addScriptFile('hordecore.js', 'horde');
            $page_output->addScriptPackage('Horde_Core_Script_Package_Sortable');
        }

        $page_output->addScriptFile('stripe.js', 'horde');
        $page_output->addScriptFile('filters.js');

        $page_output->ajax = true;

        $topbar = $injector->getInstance('Horde_View_Topbar');
        $topbar->search = true;
        $topbar->searchAction = self::url();
        $topbar->searchLabel = _("Mailbox Search");
        $topbar->searchParameters = array(
            'actionID' => 'mbox_search',
            'searchexact' => 0,
            'page' => 'filters'
        );

        $this->header = _("Filter Rules");
        $this->output = $view->render('filters');
    }

    /**
     * @param array $opts  Additional options:
     * <pre>
     *   - mbox_search: (string) Filter results by this mailbox.
     *   - mbox_search_substr: (boolean) If true, do substring search instead
     *                         of exact match.
     * </pre>
     */
    public static function url(array $opts = array())
    {
        $url = Horde::url('basic.php')->add('page', 'filters');

        if (isset($opts['mbox_search'])) {
            $url->add(array(
                'actionID' => 'mbox_search',
                'searchexact' => intval(empty($opts['mbox_search_substr'])),
                'searchfield' => strval($opts['mbox_search'])
            ));
        }

        return $url;
    }

}
