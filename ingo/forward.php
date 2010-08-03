<?php
/**
 * Forwards script.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author Todd Merritt <tmerritt@email.arizona.edu>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('ingo');

/* Redirect if forward is not available. */
if (!in_array(Ingo_Storage::ACTION_FORWARD, $_SESSION['ingo']['script_categories'])) {
    $notification->push(_("Forward is not supported in the current filtering driver."), 'horde.error');
    Horde::applicationUrl('filters.php', true)->redirect();
}

/* Get the forward object and rule. */
$forward = $ingo_storage->retrieve(Ingo_Storage::ACTION_FORWARD);
$filters = $ingo_storage->retrieve(Ingo_Storage::ACTION_FILTERS);
$fwd_id = $filters->findRuleId(Ingo_Storage::ACTION_FORWARD);
$fwd_rule = $filters->getRule($fwd_id);

/* Load libraries. */
$vars = Horde_Variables::getDefaultVariables();
if ($vars->submitbutton == _("Return to Rules List")) {
    Horde::applicationUrl('filters.php', true)->redirect();
}

/* Build form. */
$form = new Horde_Form($vars);
$v = $form->addVariable(_("Keep a copy of messages in this account?"), 'keep_copy', 'boolean', false);
$v->setHelp('forward-keepcopy');
$v = $form->addVariable(_("Address(es) to forward to:"), 'addresses', 'longtext', false, false, null, array(5, 40));
$v->setHelp('forward-addresses');
$form->setButtons(_("Save"));

/* Perform requested actions. */
if ($form->validate($vars)) {
    $forward->setForwardAddresses($vars->addresses);
    $forward->setForwardKeep($vars->keep_copy == 'on');
    $success = true;
    try {
        $ingo_storage->store($forward);
        $notification->push(_("Changes saved."), 'horde.success');
        if ($vars->submitbutton == _("Save and Enable")) {
            $filters->ruleEnable($fwd_id);
            $ingo_storage->store($filters);
            $notification->push(_("Rule Enabled"), 'horde.success');
            $fwd_rule['disable'] = false;
        } elseif ($vars->submitbutton == _("Save and Disable")) {
            $filters->ruleDisable($fwd_id);
            $ingo_storage->store($filters);
            $notification->push(_("Rule Disabled"), 'horde.success');
            $fwd_rule['disable'] = true;
        }
    } catch (Ingo_Exception $e) {
        $notification->push($e);
        $success = false;
    }

    if ($success && $prefs->getValue('auto_update')) {
        Ingo::updateScript();
    }
}

/* Add buttons depending on the above actions. */
if (empty($fwd_rule['disable'])) {
    $form->appendButtons(_("Save and Disable"));
} else {
    $form->appendButtons(_("Save and Enable"));
}
$form->appendButtons(_("Return to Rules List"));

/* Set default values. */
if (!$form->isSubmitted()) {
    $vars->keep_copy = $forward->getForwardKeep();
    $vars->addresses = implode("\n", $forward->getForwardAddresses());
}

/* Set form title. */
$form_title = _("Forward");
if (!empty($fwd_rule['disable'])) {
    $form_title .= ' [<span class="form-error">' . _("Disabled") . '</span>]';
}
$form_title .= ' ' . Horde_Help::link('ingo', 'forward');
$form->setTitle($form_title);

$title = _("Forwards Edit");
Ingo::prepareMenu();
require INGO_TEMPLATES . '/common-header.inc';
Ingo::menu();
Ingo::status();
$form->renderActive(new Horde_Form_Renderer(array('encode_title' => false)), $vars, 'forward.php', 'post');
require $registry->get('templates', 'horde') . '/common-footer.inc';
