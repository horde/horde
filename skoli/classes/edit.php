<?php
/**
 * Copyright 2002-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

@define('SKOLI_BASE', dirname(dirname(__FILE__)));
require_once SKOLI_BASE . '/lib/base.php';
require_once SKOLI_BASE . '/lib/Forms/EditClass.php';

// Exit if this isn't an authenticated user.
if (!$GLOBALS['registry']->getAuth()) {
    Horde::applicationUrl('list.php', true)->redirect();
}

$vars = Horde_Variables::getDefaultVariables();
$class = $skoli_shares->getShare($vars->get('c'));
if (is_a($class, 'PEAR_Error')) {
    $notification->push($class, 'horde.error');
    Horde::applicationUrl('classes/', true)->redirect();
} elseif (!$class->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
    $notification->push(_("You are not allowed to change this class."), 'horde.error');
    Horde::applicationUrl('classes/', true)->redirect();
}
$vars->set('school', $class->get('school'));
if (!$vars->exists('marks')) {
    $vars->set('marks', $class->get('marks'));
}
if (!$vars->exists('address_book')) {
    $vars->set('address_book', $class->get('address_book'));
}

$form = new Skoli_EditClassForm($vars, $class);

// Execute if the form is valid.
if ($form->validate($vars)) {
    $original_name = $class->get('name');
    $result = $form->execute();
    if (is_a($result, 'PEAR_Error')) {
        $notification->push($result, 'horde.error');
    } else {
        if ($class->get('name') != $original_name) {
            $notification->push(sprintf(_("The class \"%s\" has been renamed to \"%s\"."), $original_name, $class->get('name')), 'horde.success');
        } else {
            $notification->push(sprintf(_("The class \"%s\" has been saved."), $original_name), 'horde.success');
        }
    }

    Horde::applicationUrl('classes/', true)->redirect();
}

if (!$vars->exists('name')) {
    $vars->set('name', $class->get('name'));
    $vars->set('description', $class->get('desc'));
    $vars->set('category', $class->get('category'));
    foreach ($form->_schoolproperties as $name) {
        if ($name != 'marks') {
            $vars->set($name, $class->get($name));
        }
    }
    $studentslist = current(Skoli::listStudents($vars->get('c')));
    $studentsvars = array();
    foreach ($studentslist['_students'] as $student) {
        $studentsvars[] = $student['__key'];
    }
    $vars->set('students', $studentsvars);
}

$title = $form->getTitle();
require SKOLI_TEMPLATES . '/common-header.inc';
require SKOLI_TEMPLATES . '/menu.inc';
echo $form->renderActive($form->getRenderer(), $vars, 'edit.php', 'post');
require $registry->get('templates', 'horde') . '/common-footer.inc';
