<?php
/**
 * $Horde: shout/system.php,v 1.0 2005/07/07 11:36:01 ben Exp $
 *
 * Copyright 2005 Ben Klang <ben@alkaloid.net>
 *
 * See the enclosed file LICENSE for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 */
@define('SHOUT_BASE', dirname(__FILE__) . '/..');
require_once SHOUT_BASE . '/lib/System.php';
require_once 'Horde/Variables.php';

$RENDERER = &new Horde_Form_Renderer();

$empty = '';
$beendone = 0;
$wereerrors = 0;

$vars = &Variables::getDefaultVariables($empty);
$syscontext = Util::getFormData("syscontext");
$formname = $vars->get('formname');

$title = _("System Settings");

$ContextForm = &Horde_Form::singleton('ContextForm', $vars);
$ContextFormValid = $ContextForm->validate($vars, true);

// print_r($vars);
if ($ContextFormValid) {
    $SettingsForm = &Horde_Form::singleton('SettingsForm', $vars);

    $SettingsForm->open($RENDERER, $vars, 'index.php', 'post');
    $SettingsFormValid = $SettingsForm->validate($vars, true);
    // Render the original form readonly.
    $ContextForm->preserve($vars);
    $RENDERER->beginInactive($ContextForm->getTitle());
    $RENDERER->renderFormInactive($ContextForm, $vars);
    $RENDERER->end();
    echo '<br />';

    // Render the second stage form.
    if ($formname != 'SettingsForm') {
        $SettingsForm->clearValidation();
    }
    $RENDERER->beginActive($SettingsForm->getTitle());
    $ContextForm->preserveVarByPost($vars, "section");
    $SettingsForm->preserve($vars);
    $RENDERER->renderFormActive($SettingsForm, $vars);
    $RENDERER->submit();
    $RENDERER->end();

    $SettingsForm->close($RENDERER);

    $beendone = 1;
} else {
    if ($formname != 'ContextForm') {
        $ContextForm->clearValidation();
    }
    $ContextForm->open($RENDERER, $vars, 'index.php', 'post');
    $ContextForm->preserveVarByPost($vars, "section");
    $ContextForm->preserve($vars);
    $RENDERER->beginActive($ContextForm->getTitle());
    $RENDERER->renderFormActive($ContextForm, $vars);
    $RENDERER->submit();
    $RENDERER->end();
    $ContextForm->close($RENDERER);
}
