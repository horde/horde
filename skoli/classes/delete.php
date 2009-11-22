<?php
/**
 * $Horde: skoli/classes/delete.php,v 0.1 $
 *
 * Copyright 2002-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

@define('SKOLI_BASE', dirname(dirname(__FILE__)));
require_once SKOLI_BASE . '/lib/base.php';
require_once SKOLI_BASE . '/lib/Forms/DeleteClass.php';

// Exit if this isn't an authenticated user.
if (!Horde_Auth::getAuth()) {
    header('Location: ' . Horde::applicationUrl('list.php', true));
    exit;
}

$vars = Horde_Variables::getDefaultVariables();
$class_id = $vars->get('c');

$class = $skoli_shares->getShare($class_id);
if (is_a($class, 'PEAR_Error')) {
    $notification->push($class, 'horde.error');
    header('Location: ' . Horde::applicationUrl('classes/', true));
    exit;
} elseif (!$class->hasPermission(Horde_Auth::getAuth(), Horde_Perms::DELETE)) {
    $notification->push(_("You are not allowed to delete this class."), 'horde.error');
    header('Location: ' . Horde::applicationUrl('classes/', true));
    exit;
}

$form = new Skoli_DeleteClassForm($vars, $class);

// Execute if the form is valid (must pass with POST variables only).
if ($form->validate(new Horde_Variables($_POST))) {
    $result = $form->execute();
    if (is_a($result, 'PEAR_Error')) {
        $notification->push($result, 'horde.error');
    } elseif ($result) {
        $notification->push(sprintf(_("The class \"%s\" has been deleted."), $class->get('name')), 'horde.success');
    }

    header('Location: ' . Horde::applicationUrl('classes/', true));
    exit;
}

$title = $form->getTitle();
require SKOLI_TEMPLATES . '/common-header.inc';
require SKOLI_TEMPLATES . '/menu.inc';
echo $form->renderActive($form->getRenderer(), $vars, 'delete.php', 'post');
require $registry->get('templates', 'horde') . '/common-footer.inc';
