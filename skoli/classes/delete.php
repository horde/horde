<?php
/**
 * Copyright 2002-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

@define('SKOLI_BASE', dirname(dirname(__FILE__)));
require_once SKOLI_BASE . '/lib/base.php';
require_once SKOLI_BASE . '/lib/Forms/DeleteClass.php';

// Exit if this isn't an authenticated user.
if (!$GLOBALS['registry']->getAuth()) {
    Horde::url('list.php', true)->redirect();
}

$vars = Horde_Variables::getDefaultVariables();
$class_id = $vars->get('c');

$class = $skoli_shares->getShare($class_id);
if (is_a($class, 'PEAR_Error')) {
    $notification->push($class, 'horde.error');
    Horde::url('classes/', true)->redirect();
} elseif (!$class->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::DELETE)) {
    $notification->push(_("You are not allowed to delete this class."), 'horde.error');
    Horde::url('classes/', true)->redirect();
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

    Horde::url('classes/', true)->redirect();
}

$title = $form->getTitle();
require SKOLI_TEMPLATES . '/common-header.inc';
require SKOLI_TEMPLATES . '/menu.inc';
echo $form->renderActive($form->getRenderer(), $vars, 'delete.php', 'post');
require $registry->get('templates', 'horde') . '/common-footer.inc';
