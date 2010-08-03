<?php
/**
 * The Agora script to create or edit a forum.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Marko Djukic <marko@oblo.com>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('agora');

/* Set up the forums object. */
$forums = &Agora_Messages::singleton();

list($forum_id, , $scope) = Agora::getAgoraId();
$scope = Horde_Util::getGet('scope', 'agora');
$title = $forum_id ? _("Edit Forum") : _("New Forum");
$vars = Horde_Variables::getDefaultVariables();
$vars->set('forum_id', $forum_id);

/* Check permissions */
if ($forum_id && !$registry->isAdmin(array('permission' => 'agora:admin'))) {
    $notification->push(sprintf(_("You don't have permissions to edit forum %s"), $registry->get('name', $scope)), 'horde.warning');
    Horde::applicationUrl('forums.php', true)->redirect();
}
if (!$registry->isAdmin(array('permission' => 'agora:admin'))) {
    $notification->push(sprintf(_("You don't have permissions to create a new forum in %s"), $registry->get('name', $scope)), 'horde.warning');
    Horde::applicationUrl('forums.php', true)->redirect();
}

$form = new ForumForm($vars, $title);
if ($form->validate()) {
    $forum_id = $form->execute($vars);
    if ($forum_id instanceof PEAR_Error) {
        $notification->push(sprintf(_("Could not create the forum. %s"), $forum_id->message), 'horde.error');
        Horde::applicationUrl('forums.php', true)->redirect();
    }
    $notification->push($vars->get('forum_id') ? _("Forum Modified") : _("Forum created."), 'horde.success');
    header('Location: ' . Agora::setAgoraId($forum_id, null, Horde::applicationUrl('threads.php', true)));
    exit;
}

/* Check if a forum is being edited. */
if ($forum_id) {
    $forum = $forums->getForum($forum_id);
    if (is_a($forum, 'PEAR_Error')) {
        $notification->push($forum);
        unset($forum);
    } else {
        $vars = new Horde_Variables($forums->getForum($forum_id));
    }
}

/* Set up template variables. */
$view = new Agora_View();
$view->menu = Agora::getMenu('string');

Horde::startBuffer();
$form->renderActive(null, null, 'editforum.php', 'post');
$view->main = Horde::endBuffer();

Horde::startBuffer();
$notification->notify(array('listeners' => 'status'));
$view->notify = Horde::endBuffer();

require AGORA_TEMPLATES . '/common-header.inc';
echo $view->render('main.html.php');
require $registry->get('templates', 'horde') . '/common-footer.inc';
