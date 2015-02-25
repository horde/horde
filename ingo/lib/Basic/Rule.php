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
 * Rule page.
 *
 * @author    Mike Cochrane <mike@graftonhall.co.nz>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2002-2015 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */
class Ingo_Basic_Rule extends Ingo_Basic_Base
{
    /**
     */
    protected function _init()
    {
        global $conf, $injector, $notification, $page_output;

        $ingo_storage = $injector->getInstance('Ingo_Factory_Storage')->create();

        switch ($ingo_storage->maxRules()) {
        case Ingo_Storage::MAX_NONE:
            Horde::permissionDeniedError(
                'ingo',
                'allow_rules',
                _("You are not allowed to create or edit custom rules.")
            );
            Ingo_Basic_Filters::url()->redirect();

        case Ingo_Storage::MAX_OVER:
            Horde::permissionDeniedError(
                'ingo',
                'max_rules',
                sprintf(_("You are not allowed to create more than %d rules."), $ingo_storage->max_rules)
            );
            Ingo_Basic_Filters::url()->redirect();
        }

        if (!Ingo::hasSharePermission(Horde_Perms::EDIT)) {
            $notification->push(
                _("You do not have permission to edit filter rules."),
                'horde.error'
            );
            Ingo_Basic_Filters::url()->redirect();
        }

        /* Load the Ingo_Script:: driver. */
        $ingo_script = $injector->getInstance('Ingo_Factory_Script')
            ->create(Ingo::RULE_FILTER);

        /* Redirect if no rules are available. */
        $availActions = $ingo_script->availableActions();
        if (empty($availActions)) {
            $notification->push(
                _("Individual rules are not supported in the current filtering driver."),
                'horde.error'
            );
            Ingo_Basic_Filters::url()->redirect();
        }

        /* This provides the $ingo_fields array. */
        $config = new Horde_Registry_LoadConfig(
            'ingo',
            'fields.php',
            'ingo_fields'
        );
        $ingo_fields = $config->config['ingo_fields'];

        /* Token checking. */
        $actionID = $this->_checkToken(array(
            'rule_save',
            'rule_delete'
        ));

        /* Update the current rules before performing any action. */
        switch ($this->vars->action) {
        case 'Ingo_Rule_User_Discard':
        case 'Ingo_Rule_User_FlagOnly':
        case 'Ingo_Rule_User_Keep':
        case 'Ingo_Rule_User_Move':
        case 'Ingo_Rule_User_MoveKeep':
        case 'Ingo_Rule_User_Notify':
        case 'Ingo_Rule_User_Redirect':
        case 'Ingo_Rule_User_RedirectKeep':
        case 'Ingo_Rule_User_Reject':
            $rule = new $this->vars->action();
            $rule->combine = $this->vars->combine;
            $rule->name = $this->vars->name;
            $rule->stop = $this->vars->stop;
            $rule->uid = $this->vars->edit;
            break;

        default:
            $rule = isset($this->vars->edit)
                ? $ingo_storage->getRuleByUid($this->vars->edit)
                : new Ingo_Rule_User();
            break;
        }

        if (!$rule) {
            $notification->push(_("Filter not found."), 'horde.error');
            Ingo_Basic_Filters::url()->redirect();
        }

        if ($ingo_script->hasFeature('case_sensitive')) {
            $casesensitive = $this->vars->case;
        }

        foreach (array_filter(isset($this->vars->field) ? $this->vars->field : array()) as $key => $val) {
            $condition = array();
            $f_label = null;

            if ($val == Ingo::USER_HEADER) {
                $condition['field'] = empty($this->vars->userheader[$key])
                    ? ''
                    : $this->vars->userheader[$key];
                $condition['type'] = Ingo_Rule_User::TEST_HEADER;
            } elseif (!isset($ingo_fields[$val])) {
                $condition['field'] = $val;
                $condition['type'] = Ingo_Rule_User::TEST_HEADER;
            } else {
                $condition['field'] = $val;
                $f_label = $ingo_fields[$val]['label'];
                $condition['type'] = $ingo_fields[$val]['type'];
            }

            $condition['match'] = isset($this->vars->match[$key])
                ? $this->vars->match[$key]
                : '';

            if (($actionID == 'rule_save') &&
                empty($this->vars->value[$key]) &&
                !in_array($condition['match'], array('exists', 'not exist'))) {
                $notification->push(
                    sprintf(
                        _("You cannot create empty conditions. Please fill in a value for \"%s\"."),
                        is_null($f_label) ? $condition['field'] : $f_label
                    ),
                    'horde.error'
                );
                $actionID = null;
            }

            $condition['value'] = isset($this->vars->value[$key])
                ? $this->vars->value[$key]
                : '';

            if (isset($casesensitive)) {
                $condition['case'] = isset($casesensitive[$key])
                    ? $casesensitive[$key]
                    : '';
            }

            $tmp = $rule->conditions;
            $tmp[] = $condition;
            $rule->conditions = $tmp;
        }

        if ($this->vars->action) {
            switch ($rule->type) {
            case Ingo_Rule_User::TYPE_MAILBOX:
                switch ($actionID) {
                case 'rule_save':
                    try {
                        $rule->value = $this->validateMbox('actionvalue');
                    } catch (Ingo_Exception $e) {
                        $notification->push($e, 'horde.error');
                        $actionID = null;
                    }
                    break;

                default:
                    $rule->value = $this->vars->actionvalue;
                    if (!$this->vars->actionvalue &&
                        isset($this->vars->actionvalue_new)) {
                        $page_output->addInlineScript(array(
                            'IngoNewFolder.setNewFolder("actionvalue", ' . Horde_Serialize::serialize($this->vars->actionvalue_new, Horde_Serialize::JSON) . ')'
                        ), true);
                    }
                    break;
                }
                break;

            default:
                $rule->value = $this->vars->actionvalue;
                break;
            }
        }

        $flags = empty($this->vars->flags)
            ? array()
            : $this->vars->flags;
        $tmp = $rule->flags;
        foreach ($flags as $val) {
            $tmp |= $val;
        }
        $tmp->flags = $tmp;

        /* Run through action handlers. */
        switch ($actionID) {
        case 'rule_save':
            if (empty($rule->conditions)) {
                $notification->push(
                    _("You need to select at least one field to match."),
                    'horde.error'
                );
                break;
            }

            $ingo_storage->updateRule($rule);
            $notification->push(_("Changes saved."), 'horde.success');

            try {
                Ingo_Script_Util::update();
            } catch (Ingo_Exception $e) {
                $notification->push($e, 'horde.error');
            }

            Ingo_Basic_Filters::url()->redirect();

        case 'rule_delete':
            if (isset($this->vars->conditionnumber)) {
                $tmp = $rule->conditions;
                unset($tmp[intval($this->vars->conditionnumber)]);
                $rule->conditions = array_values($tmp);
            }
            break;
        }

        /* Add new, blank condition. */
        $rule->conditions[] = array();

        /* Prepare the view. */
        $view = new Horde_View(array(
            'templatePath' => INGO_TEMPLATES . '/basic/rule'
        ));
        $view->addHelper('Horde_Core_View_Helper_Help');
        $view->addHelper('Horde_Core_View_Helper_Image');
        $view->addHelper('Horde_Core_View_Helper_Label');
        $view->addHelper('FormTag');
        $view->addHelper('Tag');
        $view->addHelper('Text');

        $view->avail_types = $ingo_script->availableTypes();
        $view->edit = $this->vars->edit;
        $view->fields = $ingo_fields;
        $view->formurl = $this->_addToken(self::url());
        $view->rule = $rule;
        $view->special = $ingo_script->specialTypes();
        $view->userheader = !empty($conf['rules']['userheader']);

        $filter = array();
        $lastcond = count($rule->conditions) - 1;

        /* Display the conditions. */
        foreach ($rule->conditions as $cond_num => $condition) {
            $tmp = array(
                'cond_num' => intval($cond_num),
                'field' => isset($condition['field']) ? $condition['field'] : '',
                'lastfield' => ($lastcond == $cond_num)
            );

            if ($view->userheader &&
                isset($condition['type']) &&
                ($condition['type'] == Ingo_Rule_User::TEST_HEADER) &&
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
                    'label' => $rule->getTestInfo($test)->label,
                    'selected' => (isset($condition['match']) && ($test == $condition['match'])),
                    'value' => $test
                );
            }

            if (!in_array($selected_test, array('exists', 'not exist'))) {
                $tmp['match_value'] = isset($condition['value'])
                    ? $condition['value']
                    : '';
            }

            $testOb = $rule->getTestInfo(!empty($condition['match']) ? $condition['match'] : 'contains');
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
            $ob = new $val();

            $actions[] = array(
                'label' => $ob->label,
                'selected' => ($ob instanceof $rule),
                'value' => $val
            );
        }
        $view->actions = $actions;

        /* Get the action value output. */
        switch ($rule->type) {
        case Ingo_Rule_User::TYPE_MAILBOX:
            $view->actionvaluelabel = _("Select target folder");
            $view->actionvalue = Ingo_Flist::select($rule->value);
            break;

        case Ingo_Rule_User::TYPE_TEXT:
            $view->actionvaluelabel = _("Value");
            $view->actionvalue = '<input id="actionvalue" name="actionvalue" size="40" value="' . htmlspecialchars($rule->value) . '" />';
            break;
        }

        $view->flags = (($rule->flags && Ingo_Rule_User::FLAG_AVAILABLE) &&
                        $ingo_script->hasFeature('imap_flags'));
        $view->stop = $ingo_script->hasFeature('stop_script');

        $page_output->addScriptFile('rule.js');
        $page_output->addInlineJsVars(array(
            'IngoRule.filtersurl' => strval(Ingo_Basic_Filters::url()->setRaw(true))
        ));

        $this->header = $rule->name;
        $this->output = $view->render('rule');
    }

    /**
     */
    public static function url(array $opts = array())
    {
        return Horde::url('basic.php')->add('page', 'rule');
    }

}
