<?php
/**
 * The Agora script to display a list of forums.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Jan Schneider <jan@horde.org>
 * @author Marko Djukic <marko@oblo.com>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('agora');

if (!$registry->isAdmin()) {
    Horde::url('forums.php', true)->redirect();
}

/* Set up the messages object. */
$scope = Horde_Util::getFormData('scope', 'agora');
$messages = &Agora_Messages::singleton($scope);
if ($messages instanceof PEAR_Error) {
    $notification->push($messages->getMessage(), 'horde.warning');
    Horde::url('forums.php', true)->redirect();
}

/* Moderator action */
$action = Horde_Util::getFormData('action');
if ($action) {
    $forum_id = Horde_Util::getFormData('forum_id');
    $moderator = Horde_Util::getFormData('moderator');
    $result = $messages->updateModerator($moderator, $forum_id, $action);
    if ($result instanceof PEAR_Error) {
        $notification->push($result->getMessage(), 'horde.error');
    }

    Horde::url('moderators.php', true)->redirect();
}

/* Get the list of forums. */
$forums_list = $messages->getForums(0, true, 'forum_name');
if ($forums_list instanceof PEAR_Error) {
    $notification->push($forums_list->getMessage(), 'horde.error');
    Horde::url('forums.php', true)->redirect();
}

/* Add delete links to moderators */
$url = Horde_Util::addParameter(Horde::url('moderators.php'), 'action', 'delete');
foreach ($forums_list as $forum_id => $forum) {
    if (!isset($forum['moderators'])) {
        unset($forums_list[$forum_id]);
        continue;
    }
    foreach ($forum['moderators'] as $id => $moderator) {
        $delete = Horde_Util::addParameter($url, array('moderator' => $moderator, 'forum_id' => $forum_id));
        $forums_list[$forum_id]['moderators'][$id] = Horde::link($delete, _("Delete")) . $moderator . '</a>';
    }
}

$title = _("Moderators");
$vars = Horde_Variables::getDefaultVariables();
$form = new Horde_Form($vars, $title);
$form->addHidden('', 'scope', 'text', false);
$form->addHidden('', 'action', 'text', false);
$vars->set('action', 'add');
$form->addVariable(_("Moderator"), 'moderator', 'text', true);
if ($messages->countForums() > 50) {
    $form->addVariable(_("Forum"), 'forum_id', 'int', true);
} else {
    $forums_enum = $messages->getForums(0, false, 'forum_name', 0, !$registry->isAdmin());
    $form->addVariable(_("Forum"), 'forum_id', 'enum', true, false, false, array($forums_enum));
}

/* Set up template data. */
$view = new Agora_View();
$view->menu = Agora::getMenu('string');

Horde::startBuffer();
$form->renderActive(null, null, 'moderators.php', 'post');
$view->formbox = Horde::endBuffer();

Horde::startBuffer();
$notification->notify(array('listeners' => 'status'));
$view->notify = Horde::endBuffer();

$view->forums = $forums_list;

Horde::addScriptFile('stripe.js', 'horde', true);
require AGORA_TEMPLATES . '/common-header.inc';
echo $view->render('moderators.html.php');
require $registry->get('templates', 'horde') . '/common-footer.inc';
