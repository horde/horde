<?php
/**
 * Copyright 2002-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

@define('SKOLI_BASE', dirname(dirname(__FILE__)));
require_once SKOLI_BASE . '/lib/base.php';
require_once SKOLI_BASE . '/lib/Forms/CreateClass.php';

// Exit if this isn't an authenticated user.
if (!$GLOBALS['registry']->getAuth()) {
    Horde::applicationUrl('list.php', true)->redirect();
}

// Exit if we don't have access to addressbooks.
require_once SKOLI_BASE . '/lib/School.php';
if (!count(Skoli_School::listAddressBooks())) {
    $notification->push(_("You don't have access to any valid addressbook."), 'horde.error');
    Horde::applicationUrl('classes/', true)->redirect();
}

$vars = Horde_Variables::getDefaultVariables();
$form = new Skoli_CreateClassForm($vars);

// Execute if the form is valid.
if ($form->validate($vars)) {
    $result = $form->execute();
    if (is_a($result, 'PEAR_Error')) {
        $notification->push($result, 'horde.error');
    } else {
        $notification->push(sprintf(_("The class \"%s\" has been created."), $vars->get('name')), 'horde.success');
        $GLOBALS['display_classes'][] = $form->shareid;
        $prefs->setValue('display_classes', serialize($GLOBALS['display_classes']));
    }

    Horde::applicationUrl('classes/', true)->redirect();
}

$title = $form->getTitle();
require SKOLI_TEMPLATES . '/common-header.inc';
require SKOLI_TEMPLATES . '/menu.inc';
echo $form->renderActive($form->getRenderer(), $vars, 'create.php', 'post');
require $registry->get('templates', 'horde') . '/common-footer.inc';
