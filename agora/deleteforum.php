<?php
/**
 * The Agora script to delete a forum.
 *
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Jan Schneider <jan@horde.org>
 * @author Marko Djukic <marko@oblo.com>
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('agora');

/* Set up the forums object. */
$scope = Horde_Util::getFormData('scope', 'agora');
$forums = $injector->getInstance('Agora_Factory_Driver')->create($scope);

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

// TODO Cancel button doesn't work currently, because it has no condition set
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
        try {
            // TODO also delete child forums as we state in the GUI
            $forums->deleteForum($vars->get('forum_id'));
            $notification->push(_("Forum deleted."), 'horde.success');
        } catch (Agora_Exception $e) {
            $notification->push(sprintf(_("Could not delete the forum. %s"), $e->getMessage()), 'horde.error');
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
$form->renderActive(null, $vars, Horde::url('deleteforum.php'), 'post');
$view->main = Horde::endBuffer();

Horde::startBuffer();
$notification->notify(array('listeners' => 'status'));
$view->notify = Horde::endBuffer();

$page_output->header();
echo $view->render('main');
$page_output->footer();
