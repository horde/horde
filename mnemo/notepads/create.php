<?php
/**
 *
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @package @Mnemo
 */

@define('MNEMO_BASE', dirname(__DIR__));
require_once MNEMO_BASE . '/lib/Application.php';
Horde_Registry::appInit('mnemo');

// Exit if this isn't an authenticated user or if the user can't
// create new notepads (default share is locked).
if (!$GLOBALS['registry']->getAuth() || $prefs->isLocked('default_notepad')) {
    Horde::url('', true)->redirect();
}

$vars = Horde_Variables::getDefaultVariables();
$form = new Mnemo_Form_CreateNotepad($vars);

// Execute if the form is valid.
if ($form->validate($vars)) {
    try {
        $notepad = $form->execute();
        $notification->push(sprintf(_("The notepad \"%s\" has been created."), $vars->get('name')), 'horde.success');
        Horde::url('notepads/edit.php')
            ->add('n', $notepad->getName())
            ->redirect();
    } catch (Exception $e) {
        $notification->push($e);
    }
}

$page_output->header(array(
    'title' => $form->getTitle()
));
$notification->notify();
echo $form->renderActive($form->getRenderer(), $vars, Horde::url('notepads/create.php'), 'post');
$page_output->footer();
