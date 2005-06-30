<?php
if (!defined(SHOUT_BASE)) {
    define(SHOUT_BASE, dirname(__FILE__));
}

# Check that we are properly initialized
if (!isset($contexts) || is_a($contexts, 'PEAR_Error')) {
    $notification->push(_("Internal error viewing requested page"),
                        'horde.error');
}

if (!in_array($context, $contexts)) {
    $notification->push("You do not have permission to access this system.",
        'horde.error');
}
$notification->notify();
require_once SHOUT_BASE . "/lib/Users.php";

$RENDERER = &new Horde_Form_Renderer();
$empty = '';
$vars = &Variables::getDefaultVariables($empty);
$formname = $vars->get('formname');

$title = "Users";

$form = &Horde_Form::singleton('SelectContextForm', $vars);
$valid = $form->validate($vars, true);
/*
if ($valid) {
} else {*/
    if ($formname != 'selectcontext') {
        $form->clearValidation();
    }
    $form->open($RENDERER, $vars, 'users.php', 'post');
    $RENDERER->beginActive($form->getTitle());
    $RENDERER->renderFormActive($form, $vars);
    $RENDERER->submit();
    $RENDERER->end();
    $form->close($RENDERER);
// }

print_r($shout->getUsers($context));