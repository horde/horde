<?php
/**
 * Vacation script.
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author Mike Cochrane <mike@graftonhall.co.nz>
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('ingo');

/* Redirect if vacation is not available. */
if (!in_array(Ingo_Storage::ACTION_VACATION, $session->get('ingo', 'script_categories'))) {
    $notification->push(_("Vacation is not supported in the current filtering driver."), 'horde.error');
    Horde::url('filters.php', true)->redirect();
}

/* Get vacation object and rules. */
$ingo_storage = $injector->getInstance('Ingo_Factory_Storage')->create();
$vacation = $ingo_storage->retrieve(Ingo_Storage::ACTION_VACATION);
$filters = $ingo_storage->retrieve(Ingo_Storage::ACTION_FILTERS);
$vac_id = $filters->findRuleId(Ingo_Storage::ACTION_VACATION);
$vac_rule = $filters->getRule($vac_id);

/* Load libraries. */
$vars = Horde_Variables::getDefaultVariables();
if ($vars->submitbutton == _("Return to Rules List")) {
    Horde::url('filters.php', true)->redirect();
}

/* Build form. */
$form = new Ingo_Form_Vacation($vars);

/* Perform requested actions. */
if ($form->validate($vars)) {
    $form->getInfo($vars, $info);
    $vacation->setVacationAddresses(isset($info['addresses']) ? $info['addresses'] : '');
    $vacation->setVacationDays($info['days']);
    $vacation->setVacationExcludes($info['excludes']);
    $vacation->setVacationIgnorelist(($info['ignorelist'] == 'on'));
    $vacation->setVacationReason($info['reason']);
    $vacation->setVacationSubject($info['subject']);
    $vacation->setVacationStart($info['start']);
    $vacation->setVacationEnd($info['end']);

    try {
        $ingo_storage->store($vacation);
        $notification->push(_("Changes saved."), 'horde.success');
        if ($vars->submitbutton == _("Save and Enable")) {
            $filters->ruleEnable($vac_id);
            $ingo_storage->store($filters);
            $notification->push(_("Rule Enabled"), 'horde.success');
            $vac_rule['disable'] = false;
        } elseif ($vars->get('submitbutton') == _("Save and Disable")) {
            $filters->ruleDisable($vac_id);
            $ingo_storage->store($filters);
            $notification->push(_("Rule Disabled"), 'horde.success');
            $vac_rule['disable'] = true;
        }
        if ($prefs->getValue('auto_update')) {
            Ingo::updateScript();
        }
    } catch (Ingo_Exception $e) {
        $notification->push($result);
    }

    /* Update the timestamp for the rules. */
    $session->set('ingo', 'change', time());
}

/* Add buttons depending on the above actions. */
$form->setCustomButtons($vac_rule['disable']);

/* Make sure we have at least one address. */
if (!$vacation->getVacationAddresses()) {
    $identity = $injector->getInstance('Horde_Core_Factory_Identity')->create();
    $addresses = implode("\n", $identity->getAll('from_addr'));
    /* Remove empty lines. */
    $addresses = preg_replace('/\n+/', "\n", $addresses);
    if (empty($addresses)) {
        $addresses = $GLOBALS['registry']->getAuth();
    }
    $vacation->setVacationAddresses($addresses);
}

/* Set default values. */
if (!$form->isSubmitted()) {
    $vars->set('addresses', implode("\n", $vacation->getVacationAddresses()));
    $vars->set('excludes', implode("\n", $vacation->getVacationExcludes()));
    $vars->set('ignorelist', $vacation->getVacationIgnorelist());
    $vars->set('days', $vacation->getVacationDays());
    $vars->set('subject', $vacation->getVacationSubject());
    $vars->set('reason', $vacation->getVacationReason());
    $vars->set('start', $vacation->getVacationStart());
    $vars->set('end', $vacation->getVacationEnd());
    $vars->set('start_year', $vacation->getVacationStartYear());
    $vars->set('start_month', $vacation->getVacationStartMonth() - 1);
    $vars->set('start_day', $vacation->getVacationStartDay() - 1);
    $vars->set('end_year', $vacation->getVacationEndYear());
    $vars->set('end_month', $vacation->getVacationEndMonth() - 1);
    $vars->set('end_day', $vacation->getVacationEndDay() - 1);
}

/* Set form title. */
$form_title = _("Vacation");
if (!empty($vac_rule['disable'])) {
    $form_title .= ' [<span class="form-error">' . _("Disabled") . '</span>]';
}
$form_title .= ' ' . Horde_Help::link('ingo', 'vacation');
$form->setTitle($form_title);

Horde::startBuffer();
$form->renderActive(new Horde_Form_Renderer(array('encode_title' => false)), $vars, Horde::url('vacation.php'), 'post');
$form_output = Horde::endBuffer();

$menu = Ingo::menu();
$page_output->header(array(
    'title' => _("Vacation Edit")
));
echo $menu;
Ingo::status();
echo $form_output;
$page_output->footer();
