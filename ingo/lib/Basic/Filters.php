<?php
/**
 * Copyright 2002-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category  Horde
 * @copyright 2002-2013 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */

/**
 * Filters page.
 *
 * @author    Mike Cochrane <mike@graftonhall.co.nz>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2002-2013 Horde LLC
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

        /* Perform requested actions. */
        switch ($this->vars->actionID) {
        case 'rule_down':
        case 'rule_up':
        case 'rule_copy':
        case 'rule_delete':
        case 'rule_disable':
        case 'rule_enable':
            if (!$edit_allowed) {
                $notification->push(_("You do not have permission to edit filter rules."), 'horde.error');
                self::url()->redirect();
            }

            switch ($this->vars->actionID) {
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

            case 'rule_up':
                $filters->ruleUp($this->vars->rulenumber, $this->vars->get('steps', 1));
                break;

            case 'rule_down':
                $filters->ruleDown($this->vars->rulenumber, $this->vars->get('steps', 1));
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
            if ($prefs->getValue('auto_update')) {
                try {
                    Ingo::updateScript();
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
            if (!$edit_allowed) {
                $notification->push(_("You do not have permission to edit filter rules."), 'horde.error');
                self::url()->redirect();
            }
            $factory->perform($session->get('ingo', 'change'));
            break;
        }

        /* Get the list of rules now. */
        $filter_list = $filters->getFilterList();

        /* Common URLs. */
        $filters_url = self::url();
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
            $i = $rule_count = 0;
            $s_categories = $session->get('ingo', 'script_categories');

            foreach ($filter_list as $val) {
                if (in_array($val['action'], $s_categories)) {
                    ++$rule_count;
                }
            }

            foreach ($filter_list as $rule_number => $filter) {
                /* Skip non-display categories. */
                if (!in_array($filter['action'], $s_categories)) {
                    continue;
                }

                $entry = array(
                    'number' => ++$i
                );
                $url = $filters_url->copy()->add('rulenumber', $rule_number);
                $copyurl = $delurl = $editurl = $name = null;

                switch ($filter['action']) {
                case Ingo_Storage::ACTION_BLACKLIST:
                    $editurl = Ingo_Basic_Blacklist::url();
                    $entry['filterimg'] = Horde::img('blacklist.png');
                    $name = _("Blacklist");
                    break;

                case Ingo_Storage::ACTION_WHITELIST:
                    $editurl = Ingo_Basic_Whitelist::url();
                    $entry['filterimg'] = Horde::img('whitelist.png');
                    $name = _("Whitelist");
                    break;

                case Ingo_Storage::ACTION_VACATION:
                    $editurl = Ingo_Basic_Vacation::url();
                    $entry['filterimg'] = Horde::img('vacation.png');
                    $name = _("Vacation");
                    break;

                case Ingo_Storage::ACTION_FORWARD:
                    $editurl = Ingo_Basic_Forward::url();
                    $entry['filterimg'] = Horde::img('forward.png');
                    $name = _("Forward");
                    break;

                case Ingo_Storage::ACTION_SPAM:
                    $editurl = Ingo_Basic_Spam::url();
                    $entry['filterimg'] = Horde::img('spam.png');
                    $name = _("Spam Filter");
                    break;

                default:
                    $editurl = $rule_url->copy()->add(array(
                        'actionID' => 'rule_edit',
                        'edit' => $rule_number
                    ));
                    $delurl = $url->copy()->add('actionID', 'rule_delete');
                    $copyurl = $url->copy()->add('actionID', 'rule_copy');
                    $entry['filterimg'] = '';
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

                /* Create edit link. */
                $entry['editlink'] = Horde::link($editurl, sprintf(_("Edit %s"), $name));

                /* Create delete link. */
                if (!is_null($delurl)) {
                    $entry['dellink'] = Horde::link($delurl, sprintf(_("Delete %s"), $name), null, null, "return window.confirm('" . addslashes(_("Are you sure you want to delete this rule?")) . "');");
                    $entry['delimg'] = Horde::img('delete.png', sprintf(_("Delete %s"), $name));
                } else {
                    $entry['dellink'] = false;
                }

                /* Create copy link. */
                if (!is_null($copyurl) &&
                    (($perms->hasAppPermission('max_rules') === true) ||
                     $perms->hasAppPermission('max_rules') > count($filter_list))) {
                    $entry['copylink'] = Horde::link($copyurl, sprintf(_("Copy %s"), $name));
                    $entry['copyimg'] = Horde::img('copy.png', sprintf(_("Copy %s"), $name));
                } else {
                    $entry['copylink'] = false;
                }

                /* Create up/down arrow links. */
                $entry['upurl'] = $url->copy()->add('actionID', 'rule_up');
                $entry['downurl'] = $url->copy()->add('actionID', 'rule_down');
                $entry['uplink'] = ($i > 1)
                    ? Horde::link($entry['upurl'], _("Move Rule Up"))
                    : false;
                $entry['downlink'] = ($i < $rule_count)
                    ? Horde::link($entry['downurl'], _("Move Rule Down"))
                    : false;

                if (empty($filter['disable'])) {
                    if ($edit_allowed) {
                        $entry['disablelink'] = Horde::link($url->copy()->add('actionID', 'rule_disable'), sprintf(_("Disable %s"), $name)) .
                            Horde::img('enable.png', sprintf(_("Disable %s"), $name)) .
                            '</a>';
                    } else {
                        $entry['disablelink'] = Horde::img('enable.png');
                    }
                    $entry['enablelink'] = false;
                } else {
                    if ($edit_allowed) {
                        $entry['enablelink'] = Horde::link($url->copy()->add('actionID', 'rule_enable'), sprintf(_("Enable %s"), $name)) .
                            Horde::img('disable.png', sprintf(_("Enable %s"), $name)) .
                            '</a>';
                    } else {
                        $entry['enablelink'] = Horde::img('disable.png');
                    }
                    $entry['disablelink'] = false;
                }

                $display[] = $entry;
            }

            // TODO: This can probably be better abstracted into the view file.
            $view->filter = $display;
        }

        if ($factory->hasFeature('on_demand') && $edit_allowed) {
            $view->settings = true;
            $view->flags = $prefs->getValue('filter_seen');
            $view->show_filter_msg = $prefs->getValue('show_filter_msg');
        }

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
