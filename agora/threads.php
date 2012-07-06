<?php
/**
 * The Agora script to display a list of threads in a forum.
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

/* Make sure we have a forum id. */
list($forum_id, , $scope) = Agora::getAgoraId();
if (empty($forum_id)) {
    Horde::url('forums.php', true)->redirect();
}

/* Check if this is a valid thread, otherwise show the forum list. */
$threads = $injector->getInstance('Agora_Factory_Driver')->create($scope, $forum_id);
if ($threads instanceof PEAR_Error) {
    $notification->push(sprintf(_("Could not list threads. %s"), $threads->getMessage()), 'horde.warning');
    Horde::url('forums.php', true)->redirect();
}

/* Which thread page are we on?  Default to page 0. */
$thread_page = Horde_Util::getFormData('thread_page', 0);
$threads_per_page = $prefs->getValue('threads_per_page');
$thread_start = $thread_page * $threads_per_page;

/* Get the forum data. */
$forum_array = $threads->getForum();

/* Get the sorting. */
$sort_by = Agora::getSortBy('threads');
$sort_dir = Agora::getSortDir('threads');

/* Get a list of threads. */
$threads_list = $threads->getThreads(0, false, $sort_by, $sort_dir, false, '', null, $thread_start, $threads_per_page);
if ($threads_list instanceof PEAR_Error) {
    $notification->push($threads_list->getMessage(), 'horde.error');
    Horde::url('forums.php', true)->redirect();
}
if (empty($threads_list)) {
    $threads_count = 0;
} else {
    $threads_count = $threads->_forum['thread_count'];
}
/* Set up the column headers. */
$col_headers = array('message_subject' => _("Subject"), 'message_seq' => _("Posts"), 'view_count' => _("Views"), 'message_author' => _("Started"), 'message_modifystamp' => _("Last post"));
$col_headers = Agora::formatColumnHeaders($col_headers, $sort_by, $sort_dir, 'threads');

/* Set up the template tags. */
$view = new Agora_View();
$view->col_headers = $col_headers;
$view->threads = $threads_list;
$view->forum_name = sprintf(_("Threads in %s"), $forum_array['forum_name']);
$view->forum_description =  Agora_Driver::formatBody($forum_array['forum_description']);
$view->actions = $threads->getThreadActions();
$view->menu = Horde::menu();

Horde::startBuffer();
$notification->notify(array('listeners' => 'status'));
$view->notify = Horde::endBuffer();

$view->rss = Horde_Util::addParameter(Horde::url('rss/threads.php', true, -1), array('scope' => $scope, 'forum_id' => $forum_id));

/* Set up pager. */
$vars = Horde_Variables::getDefaultVariables();
$pager_ob = new Horde_Core_Ui_Pager('thread_page', $vars, array('num' => $threads_count, 'url' => 'threads.php', 'perpage' => $threads_per_page));
$pager_ob->preserve('agora', Horde_Util::getFormData('agora'));
$view->pager_link = $pager_ob->render();

$page_output->header(array(
    'title' => sprintf(_("Threads in %s"), $forum_array['forum_name'])
));
echo $view->render('threads');
$page_output->footer();
