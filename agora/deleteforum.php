<?php
/**
 * The Agora script to delete a forum.
 *
 * Copyright 2003-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Jan Schneider <jan@horde.org>
 * @author Marko Djukic <marko@oblo.com>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('agora');

/* Set up the forums object. */
$scope = Horde_Util::getFormData('scope', 'agora');
$forums = &Agora_Messages::singleton($scope);

/* Check permissions */
if (!$forums->hasPermission(Horde_Perms::DELETE)) {
    $notification->push(sprintf(_("You don't have permissions to delete forums in %s"), $registry->get('name', $scope)), 'horde.warning');
    Horde::url('forums.php', true)->redirect();
}

/* Get forum. */
list($forum_id) = Agora::getAgoraId();
$forum = $forums->getForum($forum_id);
if ($forum instanceof PEAR_Error) {
    $notification->push($forum->message, 'horde.error');
    Horde::url('forums.php', true)->redirect();
}

/* Prepare forum. */
$vars = Horde_Variables::getDefaultVariables();
$form = new Horde_Form($vars, _("Delete Forum"));

$form->setButtons(array(_("Delete"), _("Cancel")));
$form->addHidden('', 'forum_id', 'int', $forum_id);
$form->addHidden('', 'scope', 'text', $scope);
$form->addVariable(_("This will delete the forum, any subforums and all relative messages."), 'prompt', 'description', false);
$form->addVariable(_("Forum name"), 'forum_name', 'text', false, true);
$vars->set('forum_name', $forum['forum_name']);
$vars->set('forum_id', $forum_id);

/* Get a list of available forums. */
$forums_list = Agora::formatCategoryTree($forums->getForums($forum_id, false, null, null));
if (!empty($forums_list)) {
    $html = implode('<br />', $forums_list);
    $form->addVariable(_("Subforums"), 'subforums', 'html', false, true);
    $vars->set('subforums', $html);
}

/* Process delete. */
if ($form->validate()) {
    if ($vars->get('submitbutton') == _("Delete")) {
        $result = $forums->deleteForum($vars->get('forum_id'));
        if ($result instanceof PEAR_Error) {
            $notification->push(sprintf(_("Could not delete the forum. %s"), $result->message), 'horde.error');
        } else {
            $notification->push(_("Forum deleted."), 'horde.success');
        }
    } else {
        $notification->push(_("Forum not deleted."), 'horde.message');
    }
    Horde::url('forums.php', true)->redirect();
}

/* Set up template variables. */
$view = new Agora_View();
$view->menu = Horde::menu();

Horde::startBuffer();
$form->renderActive(null, $vars, 'deleteforum.php', 'post');
$view->main = Horde::endBuffer();

Horde::startBuffer();
$notification->notify(array('listeners' => 'status'));
$view->notify = Horde::endBuffer();

require $registry->get('templates', 'horde') . '/common-header.inc';
echo $view->render('main.html.php');
require $registry->get('templates', 'horde') . '/common-footer.inc';
