<?php
/**
 * The Agora script ban users from a specific forum.
 *
 * Copyright 2006-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('agora');

/* Make sure we have a forum id. */
list($forum_id, , $scope) = Agora::getAgoraId();
$forums = &Agora_Messages::singleton($scope, $forum_id);
if ($forums instanceof PEAR_Error) {
    $notification->push($forums->message, 'horde.error');
    Horde::url('forums.php', true)->redirect();
}

/* Check permissions */
if (!$forums->hasPermission(Horde_Perms::DELETE)) {
    $notification->push(sprintf(_("You don't have permissions to ban users from forum %s."), $forum_id), 'horde.warning');
    Horde::url('forums.php', true)->redirect();
}

/* Ban action */
if (($action = Horde_Util::getFormData('action')) !== null) {
    $user = Horde_Util::getFormData('user');
    $result = $forums->updateBan($user, $forum_id, $action);
    if ($result instanceof PEAR_Error) {
        $notification->push($result->getMessage(), 'horde.error');
    }

    $url = Agora::setAgoraId($forum_id, null, Horde::url('ban.php'), $scope);
    header('Location: ' . $url);
    exit;
}

/* Get the list of banned users. */
$delete = Horde_Util::addParameter(Horde::url('ban.php'),
                            array('action' => 'delete',
                                  'scope' => $scope,
                                  'forum_id' => $forum_id));
$banned = $forums->getBanned();
foreach ($banned as $user => $level) {
    $banned[$user] = Horde::link(Horde_Util::addParameter($delete, 'user', $user), _("Delete")) . $user . '</a>';
}

$title = _("Ban");
$vars = Horde_Variables::getDefaultVariables();
$form = new Horde_Form($vars, $title);
$form->addHidden('', 'scope', 'text', false);
$form->addHidden('', 'agora', 'text', false);
$form->addHidden('', 'action', 'text', false);
$vars->set('action', 'add');
$form->addVariable(_("User"), 'user', 'text', true);

$view = new Agora_View();
$view->menu = Horde::menu();

Horde::startBuffer();
$form->renderActive(null, null, 'ban.php', 'post');
$view->formbox = Horde::endBuffer();

Horde::startBuffer();
$notification->notify(array('listeners' => 'status'));
$view->notify = Horde::endBuffer();

$view->banned = $banned;
$view->forum = $forums->getForum();

require $registry->get('templates', 'horde') . '/common-header.inc';
echo $view->render('ban.html.php');
require $registry->get('templates', 'horde') . '/common-footer.inc';
