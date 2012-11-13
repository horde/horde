<?php
/**
 * Rules script.
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Mike Cochrane <mike@graftonhall.co.nz>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('ingo');

/* Check rule permissions. */
$perms = $injector->getInstance('Horde_Core_Perms');
if (!$perms->hasAppPermission('allow_rules')) {
    Horde::permissionDeniedError(
        'ingo',
        'allow_rules',
        _("You are not allowed to create or edit custom rules.")
    );
    Horde::url('filters.php', true)->redirect();
}

/* Load the Ingo_Script:: driver. */
$ingo_script = $injector->getInstance('Ingo_Script');

/* Redirect if no rules are available. */
$availActions = $ingo_script->availableActions();
if (empty($availActions)) {
    $notification->push(_("Individual rules are not supported in the current filtering driver."), 'horde.error');
    Horde::url('filters.php', true)->redirect();
}

/* This provides the $ingo_fields array. */
$ingo_fields = Horde::loadConfiguration('fields.php', 'ingo_fields', 'ingo');

/* Get the current rules. */
$ingo_storage = $injector->getInstance('Ingo_Factory_Storage')->create();
$filters = $ingo_storage->retrieve(Ingo_Storage::ACTION_FILTERS);

/* Run through action handlers. */
$vars = $injector->getInstance('Horde_Variables');
switch ($vars->actionID) {
case 'rule_save':
case 'rule_update':
case 'rule_delete':
    $rule = array(
        'id' => $vars->id,
        'name' => $vars->name,
        'combine' => $vars->combine,
        'conditions' => array()
    );

    if ($ingo_script->caseSensitive()) {
        $casesensitive = $vars->case;
    }

    $valid = true;
    foreach (array_filter($vars->field) as $key => $val) {
        $condition = array();
        $f_label = null;

        if ($val == Ingo::USER_HEADER) {
            $condition['field'] = empty($vars->userheader[$key])
                ? ''
                : $vars->userheader[$key];
            $condition['type'] = Ingo_Storage::TYPE_HEADER;
        } elseif (!isset($ingo_fields[$val])) {
            $condition['field'] = $val;
            $condition['type'] = Ingo_Storage::TYPE_HEADER;
        } else {
            $condition['field'] = $val;
            $f_label = $ingo_fields[$val]['label'];
            $condition['type'] = $ingo_fields[$val]['type'];
        }
        $condition['match'] = isset($vars->match[$key])
            ? $vars->match[$key]
            : '';

        if (($vars->actionID == 'rule_save') &&
            empty($vars->value[$key]) &&
            !in_array($condition['match'], array('exists', 'not exist'))) {
            $notification->push(sprintf(_("You cannot create empty conditions. Please fill in a value for \"%s\"."), is_null($f_label) ? $condition['field'] : $f_label), 'horde.error');
            $valid = false;
        }

        $condition['value'] = isset($vars->value[$key])
            ? $vars->value[$key]
            : '';

        if (isset($casesensitive)) {
            $condition['case'] = isset($casesensitive[$key])
                ? $casesensitive[$key]
                : '';
        }
        $rule['conditions'][] = $condition;
    }

    $rule['action'] = $vars->action;

    switch ($ingo_storage->getActionInfo($vars->action)->type) {
    case 'folder':
        if ($vars->actionID == 'rule_save') {
            try {
                $rule['action-value'] = Ingo::validateFolder($vars, 'actionvalue');
            } catch (Ingo_Exception $e) {
                $notification->push($e, 'horde.error');
                $valid = false;
            }
        } else {
            $rule['action-value'] = $vars->actionvalue;
            if (!$vars->actionvalue && isset($vars->actionvalue_new)) {
                $page_output->addInlineScript(array(
                    'IngoNewFolder.setNewFolder("actionvalue", ' . Horde_Serialize::serialize($vars->actionvalue_new, Horde_Serialize::JSON) . ')'
                ), true);
            }
        }
        break;

    default:
        $rule['action-value'] = $vars->actionvalue;
        break;
    }

    $rule['stop'] = $vars->stop;

    $rule['flags'] = 0;
    $flags = empty($vars->flags) ? array() : $vars->flags;
    foreach ($flags as $val) {
        $rule['flags'] |= $val;
    }

    /* Save the rule. */
    switch ($vars->actionID) {
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

        if (!isset($vars->edit)) {
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
            $filters->updateRule($rule, $vars->edit);
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

        Horde::url('filters.php', true)->redirect();

    case 'rule_delete':
        if (isset($vars->conditionnumber)) {
            unset($rule['conditions'][intval($vars->conditionnumber)]);
            $rule['conditions'] = array_values($rule['conditions']);
        }
        break;
    }
    break;
}

if (!isset($rule)) {
    if (!isset($vars->edit)) {
        if ($perms->hasAppPermission('max_rules') !== true &&
            $perms->hasAppPermission('max_rules') <= count($filters->getFilterList())) {
            Horde::permissionDeniedError(
                'ingo',
                'max_rules',
                sprintf(_("You are not allowed to create more than %d rules."), $perms->hasAppPermission('max_rules'))
            );
            Horde::url('filters.php', true)->redirect();
        }
        $rule = $filters->getDefaultRule();
    } else {
        $rule = $filters->getRule($vars->edit);
    }

    if (!$rule) {
        $notification->push(_("Filter not found."), 'horde.error');
        Horde::url('filters.php', true)->redirect();
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

$view->avail_type = $ingo_script->availableTypes();
$view->edit = $vars->edit;
$view->fields = $ingo_fields;
$view->formurl = Horde::url('rule.php');
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
        ($condition['type'] == Ingo_Storage::TYPE_HEADER) &&
        !isset($ingo_fields[$tmp['field']])) {
        $tmp['userheader'] = empty($vars->userheader)
            ? $tmp['field']
            : $vars->userheader[$cond_num];
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
        if ($ingo_script->caseSensitive()) {
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

$view->flags = ($current_action->flags && $ingo_script->imapFlags());
$view->stop = $ingo_script->stopScript();

$page_output->addScriptFile('rule.js');
$page_output->addInlineJsVars(array(
    'IngoRule.filtersurl' => strval(Horde::url('filters.php', true)->setRaw(true))
));

$menu = Ingo::menu();
$page_output->header(array(
    'title' => $rule['name']
));
echo $menu;
Ingo::status();
echo $view->render('rule');
$page_output->footer();
