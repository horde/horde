<?php
/**
 * Forwards script.
 *
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author Todd Merritt <tmerritt@email.arizona.edu>
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('ingo');

/* Redirect if forward is not available. */
if (!in_array(Ingo_Storage::ACTION_FORWARD, $session->get('ingo', 'script_categories'))) {
    $notification->push(_("Forward is not supported in the current filtering driver."), 'horde.error');
    Horde::url('filters.php', true)->redirect();
}

/* Get the forward object and rule. */
$ingo_storage = $injector->getInstance('Ingo_Factory_Storage')->create();
$forward = $ingo_storage->retrieve(Ingo_Storage::ACTION_FORWARD);
$filters = $ingo_storage->retrieve(Ingo_Storage::ACTION_FILTERS);
$fwd_id = $filters->findRuleId(Ingo_Storage::ACTION_FORWARD);
$fwd_rule = $filters->getRule($fwd_id);

/* Load libraries. */
$vars = Horde_Variables::getDefaultVariables();
if ($vars->submitbutton == _("Return to Rules List")) {
    Horde::url('filters.php', true)->redirect();
}

/* Build form. */
$form = new Ingo_Form_Forward($vars);

/* Perform requested actions. */
if ($form->validate($vars)) {
    $forward->setForwardAddresses($vars->addresses);
    $forward->setForwardKeep($vars->keep_copy == 'on');
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
        if ($prefs->getValue('auto_update')) {
            Ingo::updateScript();
        }
    } catch (Ingo_Exception $e) {
        $notification->push($e);
    }
}

/* Add buttons depending on the above actions. */
$form->setCustomButtons($fwd_rule['disable']);

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

$menu = Ingo::menu();
$page_output->header(array(
    'title' => _("Forwards Edit")
));
echo $menu;
Ingo::status();
$form->renderActive(new Horde_Form_Renderer(array('encode_title' => false)), $vars, Horde::url('forward.php'), 'post');
$page_output->footer();
