<?php
/**
 * Filters script.
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author Mike Cochrane <mike@graftonhall.co.nz>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('ingo');

/* Get the list of filter rules. */
$filters = &$ingo_storage->retrieve(Ingo_Storage::ACTION_FILTERS);
if (is_a($filters, 'PEAR_Error')) {
    throw new Horde_Exception($filters);
}

/* Load the Ingo_Script:: driver. */
$ingo_script = Ingo::loadIngoScript();

/* Determine if we need to show the on-demand settings. */
$on_demand = $ingo_script->performAvailable();

/* Get web parameter data. */
$actionID = Horde_Util::getFormData('actionID');
$id = Horde_Util::getFormData('rulenumber');

/* Get permissions. */
$edit_allowed = Ingo::hasSharePermission(Horde_Perms::EDIT);
$delete_allowed = Ingo::hasSharePermission(Horde_Perms::DELETE);

/* Perform requested actions. */
switch ($actionID) {
case 'rule_down':
case 'rule_up':
case 'rule_copy':
case 'rule_delete':
case 'rule_disable':
case 'rule_enable':
    if (!$edit_allowed) {
        $notification->push(_("You do not have permission to edit filter rules."), 'horde.error');
        header('Location: ' . Horde::applicationUrl('filters.php', true));
        exit;
    }
    switch ($actionID) {
    case 'rule_delete':
        if (!$delete_allowed) {
            $notification->push(_("You do not have permission to delete filter rules."), 'horde.error');
            header('Location: ' . Horde::applicationUrl('filters.php', true));
            exit;
        }

        $tmp = $filters->getFilter($id);
        if ($filters->deleteRule($id)) {
            $notification->push(sprintf(_("Rule \"%s\" deleted."), $tmp['name']), 'horde.success');
        }
        break;

    case 'rule_copy':
        if (!$GLOBALS['perms']->hasAppPermission('allow_rules')) {
            try {
                $message = Horde::callHook('perms_denied', array('ingo:allow_rules'));
            } catch (Horde_Exception_HookNotSet $e) {
                $message = @htmlspecialchars(_("You are not allowed to create or edit custom rules."), ENT_COMPAT, Horde_Nls::getCharset());
            }
            $notification->push($message, 'horde.error', array('content.raw'));
            break 2;
        } elseif ($GLOBALS['perms']->hasAppPermission('max_rules') !== true &&
                  $GLOBALS['perms']->hasAppPermission('max_rules') <= count($filters->getFilterList())) {
            try {
                $message = Horde::callHook('perms_denied', array('ingo:max_rules'));
            } catch (Horde_Exception_HookNotSet $e) {
                $message = @htmlspecialchars(sprintf(_("You are not allowed to create more than %d rules."), $GLOBALS['perms']->hasAppPermission('max_rules')), ENT_COMPAT, Horde_Nls::getCharset());
            }
            $notification->push($message, 'horde.error', array('content.raw'));
            break 2;
        } else {
            $tmp = $filters->getFilter($id);
            if ($filters->copyRule($id)) {
                $notification->push(sprintf(_("Rule \"%s\" copied."), $tmp['name']), 'horde.success');
            }
        }
        break;

    case 'rule_up':
        $steps = Horde_Util::getFormData('steps', 1);
        $filters->ruleUp($id, $steps);
        break;

    case 'rule_down':
        $steps = Horde_Util::getFormData('steps', 1);
        $filters->ruleDown($id, $steps);
        break;

    case 'rule_disable':
        $tmp = $filters->getFilter($id);
        $filters->ruleDisable($id);
        $notification->push(sprintf(_("Rule \"%s\" disabled."), $tmp['name']), 'horde.success');
        break;

    case 'rule_enable':
        $tmp = $filters->getFilter($id);
        $filters->ruleEnable($id);
        $notification->push(sprintf(_("Rule \"%s\" enabled."), $tmp['name']), 'horde.success');
        break;
    }

    /* Save changes */
    $ingo_storage->store($filters);
    if ($prefs->getValue('auto_update')) {
        Ingo::updateScript();
    }
    break;

case 'settings_save':
    if (!$edit_allowed) {
        $notification->push(_("You do not have permission to edit filter rules."), 'horde.error');
        header('Location: ' . Horde::applicationUrl('filters.php', true));
        exit;
    }
    $prefs->setValue('show_filter_msg', Horde_Util::getFormData('show_filter_msg'));
    $prefs->setValue('filter_seen', Horde_Util::getFormData('filter_seen'));
    $notification->push(_("Settings successfully updated."), 'horde.success');
    break;

case 'apply_filters':
    if (!$edit_allowed) {
        $notification->push(_("You do not have permission to edit filter rules."), 'horde.error');
        header('Location: ' . Horde::applicationUrl('filters.php', true));
        exit;
    }
    $ingo_script->apply();
    break;
}

/* Get the list of rules now. */
$filter_list = $filters->getFilterList();

Horde::addScriptFile('tooltips.js', 'horde');
Horde::addScriptFile('stripe.js', 'horde');
Horde::addScriptFile('filters.js', 'ingo');
Ingo::prepareMenu();
$title = _("Filter Rules");
require INGO_TEMPLATES . '/common-header.inc';
require INGO_TEMPLATES . '/menu.inc';
require INGO_TEMPLATES . '/filters/header.inc';

/* Common URLs. */
$filters_url = Horde::applicationUrl('filters.php');
$rule_url = Horde::applicationUrl('rule.php');

if (count($filter_list) == 0) {
    require INGO_TEMPLATES . '/filters/filter-none.inc';
} else {
    $display = array();
    $i = 0;
    $rule_count = array_sum(array_map(create_function('$a', "return (in_array(\$a['action'], \$_SESSION['ingo']['script_categories'])) ? 1 : 0;"), $filter_list));

    /* Common graphics. */
    $down_img = Horde::img('nav/down.png', _("Move Rule Down"), '', $registry->getImageDir('horde'));
    $up_img = Horde::img('nav/up.png', _("Move Rule Up"), '', $registry->getImageDir('horde'));

    foreach ($filter_list as $rule_number => $filter) {
        /* Skip non-display categories. */
        if (!in_array($filter['action'], $_SESSION['ingo']['script_categories'])) {
            continue;
        }

        $entry = array();
        $entry['number'] = ++$i;
        $url = Horde_Util::addParameter($filters_url, 'rulenumber', $rule_number);
        $copyurl = $delurl = $editurl = $name = null;

        switch ($filter['action']) {
        case Ingo_Storage::ACTION_BLACKLIST:
            $editurl = Horde::applicationUrl('blacklist.php');
            $entry['filterimg'] = Horde::img('blacklist.png');
            $name = _("Blacklist");
            break;

        case Ingo_Storage::ACTION_WHITELIST:
            $editurl = Horde::applicationUrl('whitelist.php');
            $entry['filterimg'] = Horde::img('whitelist.png');
            $name = _("Whitelist");
            break;

        case Ingo_Storage::ACTION_VACATION:
            $editurl = Horde::applicationUrl('vacation.php');
            $entry['filterimg'] = Horde::img('vacation.png');
            $name = _("Vacation");
            break;

        case Ingo_Storage::ACTION_FORWARD:
            $editurl = Horde::applicationUrl('forward.php');
            $entry['filterimg'] = Horde::img('forward.png');
            $name = _("Forward");
            break;

        case Ingo_Storage::ACTION_SPAM:
            $editurl = Horde::applicationUrl('spam.php');
            $entry['filterimg'] = Horde::img('spam.png');
            $name = _("Spam Filter");
            break;

        default:
            $editurl = Horde_Util::addParameter($rule_url, array('edit' => $rule_number, 'actionID' => 'rule_edit'));
            $delurl  = Horde_Util::addParameter($url, 'actionID', 'rule_delete');
            $copyurl = Horde_Util::addParameter($url, 'actionID', 'rule_copy');
            $entry['filterimg'] = false;
            $name = $filter['name'];
            break;
        }

        /* Create description. */
        if (!$edit_allowed) {
            $entry['descriplink'] = @htmlspecialchars($name, ENT_COMPAT, Horde_Nls::getCharset());
        } elseif (!empty($filter['conditions'])) {
            $descrip = '';
            $condition_size = count($filter['conditions']) - 1;
            foreach ($filter['conditions'] as $condid => $cond) {

                $descrip .= sprintf("%s %s \"%s\"", _($cond['field']), _($cond['match']), $cond['value']);
                if (!empty($cond['case'])) {
                    $descrip .= ' [' . _("Case Sensitive") . ']';
                }
                if ($condid < $condition_size) {
                    $descrip .= ($filter['combine'] == Ingo_Storage::COMBINE_ALL) ? _(" and") : _(" or");
                    $descrip .= "\n  ";
                }
            }

            $descrip .= "\n";

            $ob = $ingo_storage->getActionInfo($filter['action']);
            $descrip .= $ob->label;

            if ($filter['action-value']) {
                $descrip .= ': ' . $filter['action-value'];
            }

            if ($filter['stop']) {
                $descrip .= "\n[stop]";
            }

            $entry['descriplink'] = Horde::linkTooltip($editurl, sprintf(_("Edit %s"), $name), null, null, null, $descrip) . @htmlspecialchars($name, ENT_COMPAT, Horde_Nls::getCharset()) . '</a>';
        } else {
            $entry['descriplink'] = Horde::link($editurl, sprintf(_("Edit %s"), $name)) . @htmlspecialchars($name, ENT_COMPAT, Horde_Nls::getCharset()) . '</a>';
        }

        /* Create edit link. */
        $entry['editlink'] = Horde::link($editurl, sprintf(_("Edit %s"), $name));

        /* Create delete link. */
        if (!is_null($delurl)) {
            $entry['dellink'] = Horde::link($delurl, sprintf(_("Delete %s"), $name), null, null, "return window.confirm('" . addslashes(_("Are you sure you want to delete this rule?")) . "');");
            $entry['delimg'] = Horde::img('delete.png', sprintf(_("Delete %s"), $name), '', $registry->getImageDir('horde'));
        } else {
            $entry['dellink'] = false;
        }

        /* Create copy link. */
        if (!is_null($copyurl) &&
            (!empty($conf['hooks']['permsdenied']) ||
             $GLOBALS['perms']->hasAppPermission('max_rules') === true ||
             $GLOBALS['perms']->hasAppPermission('max_rules') > count($filter_list))) {
            $entry['copylink'] = Horde::link($copyurl, sprintf(_("Copy %s"), $name));
            $entry['copyimg'] = Horde::img('copy.png', sprintf(_("Copy %s"), $name));
        } else {
            $entry['copylink'] = false;
        }

        /* Create up/down arrow links. */
        $entry['upurl'] = Horde_Util::addParameter($url, 'actionID', 'rule_up');
        $entry['downurl'] = Horde_Util::addParameter($url, 'actionID', 'rule_down');
        $entry['uplink'] = ($i > 1) ? Horde::link($entry['upurl'], _("Move Rule Up")) : false;
        $entry['downlink'] = ($i < $rule_count) ? Horde::link($entry['downurl'], _("Move Rule Down")) : false;

        if (empty($filter['disable'])) {
            if ($edit_allowed) {
                $entry['disablelink'] = Horde::link(Horde_Util::addParameter($url, 'actionID', 'rule_disable'), sprintf(_("Disable %s"), $name));
                $entry['disableimg'] = Horde::img('enable.png', sprintf(_("Disable %s"), $name));
            } else {
                $entry['disableimg'] = Horde::img('enable.png');
                $entry['disablelink'] = false;
            }
            $entry['enablelink'] = false;
            $entry['enableimg'] = false;
        } else {
            if ($edit_allowed) {
                $entry['enablelink'] = Horde::link(Horde_Util::addParameter($url, 'actionID', 'rule_enable'), sprintf(_("Enable %s"), $name));
                $entry['enableimg'] = Horde::img('disable.png', sprintf(_("Enable %s"), $name));
            } else {
                $entry['enableimg'] = Horde::img('disable.png');
                $entry['enablelink'] = false;
            }
            $entry['disablelink'] = false;
            $entry['disableimg'] = false;
        }

        $display[] = $entry;
    }

    /* Output the template. */
    $template = $injector->createInstance('Horde_Template');
    $template->set('down_img', $down_img);
    $template->set('up_img', $up_img);
    $template->set('filter', $display, true);
    $template->set('edit_allowed', $edit_allowed, true);
    $template->set('delete_allowed', $delete_allowed, true);
    $template->setOption('gettext', true);
    echo $template->fetch(INGO_TEMPLATES . '/filters/filter.html');
}

$actions = $ingo_script->availableActions();
$createrule = (!empty($actions) &&
               (!empty($conf['hooks']['permsdenied']) ||
                ($GLOBALS['perms']->hasAppPermission('allow_rules') &&
                 ($GLOBALS['perms']->hasAppPermission('max_rules') === true ||
                  $GLOBALS['perms']->hasAppPermission('max_rules') > count($filter_list)))));
$canapply = $ingo_script->canApply();
require INGO_TEMPLATES . '/filters/footer.inc';
if ($on_demand && $edit_allowed) {
    require INGO_TEMPLATES . '/filters/settings.inc';
}

require $registry->get('templates', 'horde') . '/common-footer.inc';
