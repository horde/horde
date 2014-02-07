<?php
/**
 * Copyright 2002-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category  Horde
 * @copyright 2002-2014 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */

/**
 * Filters page.
 *
 * @author    Mike Cochrane <mike@graftonhall.co.nz>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2002-2014 Horde LLC
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
        $filters = $ingo_storage->retrieve(Ingo_Storage::ACTION_FILTERS);

        /* Load the Ingo_Script factory. */
        $factory = $injector->getInstance('Ingo_Factory_Script');

        /* Get permissions. */
        $edit_allowed = Ingo::hasSharePermission(Horde_Perms::EDIT);
        $delete_allowed = Ingo::hasSharePermission(Horde_Perms::DELETE);

        /* Permissions. */
        $perms = $injector->getInstance('Horde_Core_Perms');

        /* Token checking. */
        $actionID = $this->_checkToken(array(
            'rule_copy',
            'rule_delete',
            'rule_disable',
            'rule_enable',
            'update_sort'
        ));

        /* Perform requested actions. */
        switch ($actionID) {
        case 'rule_copy':
        case 'rule_delete':
        case 'rule_disable':
        case 'rule_enable':
            if (!$edit_allowed) {
                $notification->push(_("You do not have permission to edit filter rules."), 'horde.error');
                self::url()->redirect();
            }

            switch ($actionID) {
            case 'rule_delete':
                if (!$delete_allowed) {
                    $notification->push(_("You do not have permission to delete filter rules."), 'horde.error');
                    self::url()->redirect();
                }

                $tmp = $filters->getFilter($this->vars->rulenumber);
                if ($filters->deleteRule($this->vars->rulenumber)) {
                    $notification->push(sprintf(_("Rule \"%s\" deleted."), $tmp['name']), 'horde.success');
                }
                break;

            case 'rule_copy':
                if (!$perms->hasAppPermission('allow_rules')) {
                    Horde::permissionDeniedError(
                        'ingo',
                        'allow_rules',
                        _("You are not allowed to create or edit custom rules.")
                    );
                    break 2;
                } elseif (($perms->hasAppPermission('max_rules') !== true) &&
                          $perms->hasAppPermission('max_rules') <= count($filters->getFilterList())) {
                    Horde::permissionDeniedError(
                        'ingo',
                        'max_rules',
                        sprintf(_("You are not allowed to create more than %d rules."), $perms->hasAppPermission('max_rules'))
                    );
                    break 2;
                } else {
                    $tmp = $filters->getFilter($this->vars->rulenumber);
                    if ($filters->copyRule($this->vars->rulenumber)) {
                        $notification->push(sprintf(_("Rule \"%s\" copied."), $tmp['name']), 'horde.success');
                    }
                }
                break;

            case 'rule_disable':
                $tmp = $filters->getFilter($this->vars->rulenumber);
                $filters->ruleDisable($this->vars->rulenumber);
                $notification->push(sprintf(_("Rule \"%s\" disabled."), $tmp['name']), 'horde.success');
                break;

            case 'rule_enable':
                $tmp = $filters->getFilter($this->vars->rulenumber);
                $filters->ruleEnable($this->vars->rulenumber);
                $notification->push(sprintf(_("Rule \"%s\" enabled."), $tmp['name']), 'horde.success');
                break;
            }

            /* Save changes */
            $ingo_storage->store($filters);
            try {
                Ingo_Script_Util::update();
            } catch (Ingo_Exception $e) {
                $notification->push($e->getMessage(), 'horde.error');
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

        case 'update_sort':
            if (!$edit_allowed) {
                $notification->push(_("You do not have permission to edit filter rules."), 'horde.error');
                self::url()->redirect();
            }
            try {
                $filters->sort(json_decode($this->vars->sort_order));
                $notification->push(
                    _("Rule sort saved successfully."),
                    'horde.success'
                );
            } catch (Ingo_Exception $e) {
                $notification->push(
                    _("Rule sort not saved."),
                    'horde.error'
                );
            }
            break;
        }

        /* Get the list of rules now. */
        $filter_list = $filters->getFilterList();

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

        if (count($filter_list)) {
            $display = array();
            $s_categories = $session->get('ingo', 'script_categories');

            $view->can_copy =
                $edit_allowed &&
                ((($max_rules = $perms->hasAppPermission('max_rules')) === true) ||
                ($max_rules > count($filter_list)));

            foreach ($filter_list as $rule_number => $filter) {
                /* Non-display categories. */
                if (!in_array($filter['action'], $s_categories)) {
                    $display[$rule_number] = false;
                    continue;
                }

                $copyurl = $delurl = $editurl = $name = null;
                $entry = array();
                $url = $filters_url->copy()->add('rulenumber', $rule_number);

                switch ($filter['action']) {
                case Ingo_Storage::ACTION_BLACKLIST:
                    $editurl = Ingo_Basic_Blacklist::url();
                    $entry['filterimg'] = 'blacklist.png';
                    $name = _("Blacklist");
                    break;

                case Ingo_Storage::ACTION_WHITELIST:
                    $editurl = Ingo_Basic_Whitelist::url();
                    $entry['filterimg'] = 'whitelist.png';
                    $name = _("Whitelist");
                    break;

                case Ingo_Storage::ACTION_VACATION:
                    $editurl = Ingo_Basic_Vacation::url();
                    $entry['filterimg'] = 'vacation.png';
                    $name = _("Vacation");
                    break;

                case Ingo_Storage::ACTION_FORWARD:
                    $editurl = Ingo_Basic_Forward::url();
                    $entry['filterimg'] = 'forward.png';
                    $name = _("Forward");
                    break;

                case Ingo_Storage::ACTION_SPAM:
                    $editurl = Ingo_Basic_Spam::url();
                    $entry['filterimg'] = 'spam.png';
                    $name = _("Spam Filter");
                    break;

                default:
                    $editurl = $rule_url->copy()->add(array(
                        'edit' => $rule_number
                    ));
                    $delurl = $url->copy()->add('actionID', 'rule_delete');
                    $copyurl = $url->copy()->add('actionID', 'rule_copy');
                    $name = $filter['name'];
                    break;
                }

                /* Create description. */
                if (!$edit_allowed) {
                    $entry['descriplink'] = htmlspecialchars($name);
                } elseif (!empty($filter['conditions'])) {
                    $entry['descriplink'] = Horde::linkTooltip($editurl, sprintf(_("Edit %s"), $name), null, null, null, Ingo::ruleDescription($filter)) . htmlspecialchars($name) . '</a>';
                } else {
                    $entry['descriplink'] = Horde::link($editurl, sprintf(_("Edit %s"), $name)) . htmlspecialchars($name) . '</a>';
                }

                /* Create delete link. */
                if ($delete_allowed && !is_null($delurl)) {
                    $entry['dellink'] = Horde::link($delurl, sprintf(_("Delete %s"), $name), null, null, "return window.confirm('" . addslashes(_("Are you sure you want to delete this rule?")) . "');");
                }

                /* Create copy link. */
                if ($view->can_copy && !is_null($copyurl)) {
                    $entry['copylink'] = Horde::link($copyurl, sprintf(_("Copy %s"), $name));
                }

                /* Create disable/enable link. */
                if (empty($filter['disable'])) {
                    $entry['disabled'] = true;
                    if ($edit_allowed) {
                        $entry['disablelink'] = Horde::link($url->copy()->add('actionID', 'rule_disable'), sprintf(_("Disable %s"), $name));
                    }
                } elseif ($edit_allowed) {
                    $entry['enablelink'] = Horde::link($url->copy()->add('actionID', 'rule_enable'), sprintf(_("Enable %s"), $name));
                }

                $display[$rule_number] = $entry;
            }

            $view->filter = $display;
        }

        if ($factory->hasFeature('on_demand') && $edit_allowed) {
            $view->settings = true;
            $view->flags = $prefs->getValue('filter_seen');
            $view->show_filter_msg = $prefs->getValue('show_filter_msg');
        }

        $page_output->addScriptPackage('Horde_Core_Script_Package_Sortable');
        $page_output->addScriptFile('stripe.js', 'horde');
        $page_output->addScriptFile('filters.js');

        $this->header = _("Filter Rules");
        $this->output = $view->render('filters');
    }

    /**
     */
    static public function url(array $opts = array())
    {
        return Horde::url('basic.php')->add('page', 'filters');
    }

}
