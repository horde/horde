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
 * Rule page.
 *
 * @author    Mike Cochrane <mike@graftonhall.co.nz>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2002-2013 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */
class Ingo_Basic_Rule extends Ingo_Basic_Base
{
    /**
     */
    protected function _init()
    {
        global $conf, $injector, $notification, $page_output, $prefs, $session;

        /* Check rule permissions. */
        $perms = $injector->getInstance('Horde_Core_Perms');
        if (!$perms->hasAppPermission('allow_rules')) {
            Horde::permissionDeniedError(
                'ingo',
                'allow_rules',
                _("You are not allowed to create or edit custom rules.")
            );
            Ingo_Basic_Filters::url()->redirect();
        }

        /* Load the Ingo_Script:: driver. */
        $ingo_script = $injector->getInstance('Ingo_Factory_Script')->create(Ingo::RULE_FILTER);

        /* Redirect if no rules are available. */
        $availActions = $ingo_script->availableActions();
        if (empty($availActions)) {
            $notification->push(_("Individual rules are not supported in the current filtering driver."), 'horde.error');
            Ingo_Basic_Filters::url()->redirect();
        }

        /* This provides the $ingo_fields array. */
        $ingo_fields = Horde::loadConfiguration('fields.php', 'ingo_fields', 'ingo');

        /* Get the current rules. */
        $ingo_storage = $injector->getInstance('Ingo_Factory_Storage')->create();
        $filters = $ingo_storage->retrieve(Ingo_Storage::ACTION_FILTERS);

        /* Run through action handlers. */
        switch ($this->vars->actionID) {
        case 'rule_save':
        case 'rule_update':
        case 'rule_delete':
            $rule = array(
                'id' => $this->vars->id,
                'name' => $this->vars->name,
                'combine' => $this->vars->combine,
                'conditions' => array()
            );

            if ($ingo_script->hasFeature('case_sensitive')) {
                $casesensitive = $this->vars->case;
            }

            $valid = true;
            foreach (array_filter($this->vars->field) as $key => $val) {
                $condition = array();
                $f_label = null;

                if ($val == Ingo::USER_HEADER) {
                    $condition['field'] = empty($this->vars->userheader[$key])
                        ? ''
                        : $this->vars->userheader[$key];
                    $condition['type'] = Ingo_Storage::TYPE_HEADER;
                } elseif (!isset($ingo_fields[$val])) {
                    $condition['field'] = $val;
                    $condition['type'] = Ingo_Storage::TYPE_HEADER;
                } else {
                    $condition['field'] = $val;
                    $f_label = $ingo_fields[$val]['label'];
                    $condition['type'] = $ingo_fields[$val]['type'];
                }
                $condition['match'] = isset($this->vars->match[$key])
                    ? $this->vars->match[$key]
                    : '';

                if (($this->vars->actionID == 'rule_save') &&
                    empty($this->vars->value[$key]) &&
                    !in_array($condition['match'], array('exists', 'not exist'))) {
                    $notification->push(sprintf(_("You cannot create empty conditions. Please fill in a value for \"%s\"."), is_null($f_label) ? $condition['field'] : $f_label), 'horde.error');
                    $valid = false;
                }

                $condition['value'] = isset($this->vars->value[$key])
                    ? $this->vars->value[$key]
                    : '';

                if (isset($casesensitive)) {
                    $condition['case'] = isset($casesensitive[$key])
                        ? $casesensitive[$key]
                        : '';
                }
                $rule['conditions'][] = $condition;
            }

            $rule['action'] = $this->vars->action;

            switch ($ingo_storage->getActionInfo($this->vars->action)->type) {
            case 'folder':
                if ($this->vars->actionID == 'rule_save') {
                    try {
                        $rule['action-value'] = Ingo::validateFolder($this->vars, 'actionvalue');
                    } catch (Ingo_Exception $e) {
                        $notification->push($e, 'horde.error');
                        $valid = false;
                    }
                } else {
                    $rule['action-value'] = $this->vars->actionvalue;
                    if (!$this->vars->actionvalue &&
                        isset($this->vars->actionvalue_new)) {
                        $page_output->addInlineScript(array(
                            'IngoNewFolder.setNewFolder("actionvalue", ' . Horde_Serialize::serialize($this->vars->actionvalue_new, Horde_Serialize::JSON) . ')'
                        ), true);
                    }
                }
                break;

            default:
                $rule['action-value'] = $this->vars->actionvalue;
                break;
            }

            $rule['stop'] = $this->vars->stop;

            $rule['flags'] = 0;
            $flags = empty($this->vars->flags)
                ? array()
                : $this->vars->flags;
            foreach ($flags as $val) {
                $rule['flags'] |= $val;
            }

            /* Save the rule. */
            switch ($this->vars->actionID) {
            case 'rule_save':
                if (!$valid) {
                    break;
                }

                if (!Ingo::hasSharePermission(Horde_Perms::EDIT)) {
                    $notification->push(_("You do not have permission to edit filter rules."), 'horde.error');
                    break;
                }

                if (empty($rule['conditions'])) {
                    $notification->push(_("You need to select at least one field to match."), 'horde.error');
                    break;
                }

                if (!isset($this->vars->edit)) {
                    if (($perms->hasAppPermission('max_rules') !== true) &&
                        ($perms->hasAppPermission('max_rules') <= count($filters->getFilterList()))) {
                        Horde::permissionDeniedError(
                            'ingo',
                            'max_rules',
                            sprintf(_("You are not allowed to create more than %d rules."), $perms->hasAppPermission('max_rules'))
                        );
                        break;
                    }
                    $filters->addRule($rule);
                } else {
                    $filters->updateRule($rule, $this->vars->edit);
                }

                $session->set('ingo', 'change', time());

                $ingo_storage->store($filters);
                $notification->push(_("Changes saved."), 'horde.success');

                if ($prefs->getValue('auto_update')) {
                    try {
                        Ingo::updateScript();
                    } catch (Ingo_Exception $e) {
                        $notification->push($e, 'horde.error');
                    }
                }

                Ingo_Basic_Filters::url()->redirect();

            case 'rule_delete':
                if (isset($this->vars->conditionnumber)) {
                    unset($rule['conditions'][intval($this->vars->conditionnumber)]);
                    $rule['conditions'] = array_values($rule['conditions']);
                }
                break;
            }
            break;
        }

        if (!isset($rule)) {
            if (!isset($this->vars->edit)) {
                if (($perms->hasAppPermission('max_rules') !== true) &&
                    $perms->hasAppPermission('max_rules') <= count($filters->getFilterList())) {
                    Horde::permissionDeniedError(
                        'ingo',
                        'max_rules',
                        sprintf(_("You are not allowed to create more than %d rules."), $perms->hasAppPermission('max_rules'))
                    );
                    Ingo_Basic_Filters::url()->redirect();
                }
                $rule = $filters->getDefaultRule();
            } else {
                $rule = $filters->getRule($this->vars->edit);
            }

            if (!$rule) {
                $notification->push(_("Filter not found."), 'horde.error');
                Ingo_Basic_Filters::url()->redirect();
            }
        }

        /* Add new, blank condition. */
        $rule['conditions'][] = array();

        /* Prepare the view. */
        $view = new Horde_View(array(
            'templatePath' => INGO_TEMPLATES . '/basic/rule'
        ));
        $view->addHelper('Horde_Core_View_Helper_Help');
        $view->addHelper('Horde_Core_View_Helper_Label');
        $view->addHelper('FormTag');
        $view->addHelper('Tag');
        $view->addHelper('Text');

        $view->avail_types = $ingo_script->availableTypes();
        $view->edit = $this->vars->edit;
        $view->fields = $ingo_fields;
        $view->formurl = self::url();
        $view->rule = $rule;
        $view->special = $ingo_script->specialTypes();
        $view->userheader = !empty($conf['rules']['userheader']);

        $filter = array();
        $lastcond = count($rule['conditions']) - 1;

        /* Display the conditions. */
        foreach ($rule['conditions'] as $cond_num => $condition) {
            $tmp = array(
                'cond_num' => intval($cond_num),
                'field' => isset($condition['field']) ? $condition['field'] : '',
                'lastfield' => ($lastcond == $cond_num)
            );

            if ($view->userheader &&
                isset($condition['type']) &&
                ($condition['type'] == Ingo_Storage::TYPE_HEADER) &&
                !isset($ingo_fields[$tmp['field']])) {
                $tmp['userheader'] = $tmp['field'];
            }

            if ($tmp['lastfield']) {
                $filter[] = $tmp;
                continue;
            }

            /* Create the match listing. */
            if (!isset($condition['field']) ||
                ($condition['field'] == Ingo::USER_HEADER) ||
                !isset($ingo_fields[$condition['field']]['tests'])) {
                $avail_tests = $ingo_script->availableTests();
            } else {
                $avail_tests = $ingo_fields[$condition['field']]['tests'];
            }

            $tmp['matchtest'] = array();
            $selected_test = empty($condition['match'])
                ? null
                : $condition['match'];
            foreach ($avail_tests as $test) {
                if (is_null($selected_test)) {
                    $selected_test = $test;
                }
                $tmp['matchtest'][] = array(
                    'label' => $ingo_storage->getTestInfo($test)->label,
                    'selected' => (isset($condition['match']) && ($test == $condition['match'])),
                    'value' => $test
                );
            }

            if (!in_array($selected_test, array('exists', 'not exist'))) {
                $tmp['match_value'] = isset($condition['value'])
                    ? $condition['value']
                    : '';
            }

            $testOb = $ingo_storage->getTestInfo(!empty($condition['match']) ? $condition['match'] : 'contains');
            switch ($testOb->type) {
            case 'text':
                if ($ingo_script->hasFeature('case_sensitive')) {
                    $tmp['case_sensitive'] = !empty($condition['case']);
                }
                break;
            }

            $filter[] = $tmp;
        }

        $view->filter = $filter;

        /* Get the action select output. */
        $actions = array();
        foreach ($availActions as $val) {
            $action = $ingo_storage->getActionInfo($val);
            $actions[] = array(
                'label' => $action->label,
                'selected' => ($val == $rule['action']),
                'value' => $val
            );
            if ($val == $rule['action']) {
                $current_action = $action;
            }
        }
        $view->actions = $actions;

        /* Get the action value output. */
        switch ($current_action->type) {
        case 'folder':
            $view->actionvaluelabel = _("Select target folder");
            $view->actionvalue = Ingo::flistSelect($rule['action-value']);
            break;

        case 'text':
        case 'int':
            $view->actionvaluelabel = _("Value");
            $view->actionvalue = '<input id="actionvalue" name="actionvalue" size="40" value="' . htmlspecialchars($rule['action-value']) . '" />';
            break;
        }

        $view->flags = $current_action->flags && $ingo_script->hasFeature('imap_flags');
        $view->stop = $ingo_script->hasFeature('stop_script');

        $page_output->addScriptFile('rule.js');
        $page_output->addInlineJsVars(array(
            'IngoRule.filtersurl' => strval(Ingo_Basic_Filters::url()->setRaw(true))
        ));

        $this->header = $rule['name'];
        $this->output = $view->render('rule');
    }

    /**
     */
    static public function url(array $opts = array())
    {
        return Horde::url('basic.php')->add('page', 'rule');
    }

}
