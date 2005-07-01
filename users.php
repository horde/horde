<?php
if (!defined(SHOUT_BASE)) {
    define(SHOUT_BASE, dirname(__FILE__));
}

# Check that we are properly initialized
if (is_a($contexts, 'PEAR_Error')) {
    $notification->push(_("Internal error viewing requested page"),
                        'horde.error');
    $notification->notify();
    require $registry->get('templates', 'horde') . '/common-footer.inc';
    exit();
}

if (($context != "") && !in_array($context, $contexts)) {
    $notification->push("You do not have permission to access this system.",
        'horde.error');
    $notification->notify();
    require $registry->get('templates', 'horde') . '/common-footer.inc';
    exit();
}

require_once SHOUT_BASE . "/lib/Users.php";

$RENDERER = &new Horde_Form_Renderer();
$empty = '';
$vars = &Variables::getDefaultVariables($empty);
$formname = $vars->get('formname');

$title = "Users";

$contexts = $shout->getContexts("customer");

if (count($contexts) < 1) {
    # We should never get here except by malformed request
    # (intentional or otherwise)
    $notification->push(_("Internal error viewing requested page"),
        'horde.error');
    $notification->notify();
    require $registry->get('templates', 'horde') . '/common-footer.inc';
    exit();
}

if (count($contexts) > 1) {
    # User is allowed to view more than one context.  Prompt him
    # for the context to view

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
} else {
    # Based on the logic above, count($contexts) must == 1
    # Force the user to veiw that context
    $context = $contexts[0];
}

print_r($shout->getUsers($context));