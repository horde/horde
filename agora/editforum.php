<?php
/**
 * The Agora script to create or edit a forum.
 *
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Marko Djukic <marko@oblo.com>
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('agora');

/* Set up the forums object. */
$forums = $injector->getInstance('Agora_Factory_Driver')->create();

list($forum_id, , $scope) = Agora::getAgoraId();
$scope = Horde_Util::getGet('scope', 'agora');
$vars = Horde_Variables::getDefaultVariables();

/* Check if a forum is being edited. */
if (isset($forum_id) && !$vars->get('forum_name')) {
    try {
        $vars = new Horde_Variables($forums->getForum($forum_id));
        $vars->set('forum_id', $forum_id);
    } catch (Horde_Exception $e) {
        $notification->push($e->getMessage());
        unset($forum_id);
    }
}

$title = isset($forum_id) ? _("Edit Forum") : _("New Forum");

/* Check permissions */
if (isset($forum_id) && !$registry->isAdmin(array('permission' => 'agora:admin'))) {
    $notification->push(sprintf(_("You don't have permissions to edit forum %s"), $registry->get('name', $scope)), 'horde.warning');
    Horde::url('forums.php', true)->redirect();
}
if (!$registry->isAdmin(array('permission' => 'agora:admin'))) {
    $notification->push(sprintf(_("You don't have permissions to create a new forum in %s"), $registry->get('name', $scope)), 'horde.warning');
    Horde::url('forums.php', true)->redirect();
}

$form = new Agora_Form_Forum($vars, $title);
if ($form->validate()) {
    $forum_id = $form->execute($vars);
    if ($forum_id instanceof PEAR_Error) {
        $notification->push(sprintf(_("Could not create the forum. %s"), $forum_id->message), 'horde.error');
        Horde::url('forums.php', true)->redirect();
    }
    $notification->push($vars->get('forum_id') ? _("Forum Modified") : _("Forum created."), 'horde.success');
    header('Location: ' . Agora::setAgoraId($forum_id, null, Horde::url('threads.php', true)));
    exit;
}

/* Set up template variables. */
$view = new Agora_View();
$view->menu = Horde::menu();

Horde::startBuffer();
$form->renderActive(null, null, Horde::url('editforum.php'), 'post');
$view->main = Horde::endBuffer();

Horde::startBuffer();
$notification->notify(array('listeners' => 'status'));
$view->notify = Horde::endBuffer();

$page_output->header(array(
    'title' => $title
));
echo $view->render('main');
$page_output->footer();
