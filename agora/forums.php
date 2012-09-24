<?php
/**
 * The Agora script to display a list of forums.
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
$scope = Horde_Util::getGet('scope', 'agora');
$forums = $injector->getInstance('Agora_Factory_Driver')->create($scope);

/* Set up actions */
if ($registry->isAdmin()) {
    $url = Horde::url('forums.php');
    foreach ($registry->listApps(array('hidden', 'notoolbar', 'active')) as $app) {
        if ($registry->hasMethod('hasComments', $app) &&
            $registry->callByPackage($app, 'hasComments') === true) {
            $app_name = $registry->get('name', $app);
            $actions[] = Horde::link($url->add('scope', $app), $app_name) . $app_name . '</a>';
        }
    }
}

/* Get the sorting. */
$sort_by = Agora::getSortBy('forums');
$sort_dir = Agora::getSortDir('forums');

/* Which forums page are we on?  Default to page 0. */
$forum_page = Horde_Util::getFormData('forum_page', 0);
$forums_per_page = $prefs->getValue('forums_per_page');
$forum_start = $forum_page * $forums_per_page;

/* Get the list of forums. */

try {
    $forums_list = $forums->getForums(0, true, $sort_by, $sort_dir, true, $forum_start, $forums_per_page);
    $forums_count = $forums->countForums();
} catch (Horde_Exception_NotFound $e) {
    $forums_count = 0;
}

/* Set up the column headers. */
$col_headers = array('forum_name' => _("Forum"), 'forum_description' => _("Description"), 'message_count' => _("Posts"), 'thread_count' => _("Threads"), 'message_timestamp' => _("Last Post"), 'message_author' => _("Posted by"), 'message_date' => _("Date"));
$col_headers = Agora::formatColumnHeaders($col_headers, $sort_by, $sort_dir, 'forums');

/* Set up the template tags. */
$view = new Agora_View();
$view->col_headers = $col_headers;
$view->forums_list = $forums_list;

Horde::startBuffer();
$notification->notify(array('listeners' => 'status'));
$view->notify = Horde::endBuffer();

$view->actions = empty($actions) ? null : $actions;

/* Set up pager. */
$vars = Horde_Variables::getDefaultVariables();
$pager_ob = new Horde_Core_Ui_Pager('forum_page', $vars, array('num' => $forums_count, 'url' => 'forums.php', 'perpage' => $forums_per_page));
$pager_ob->preserve('scope', $scope);
$view->pager_link = $pager_ob->render();

$page_output->addLinkTag(array(
    'href' => Horde::url('rss/index.php', true, -1)->add('scope', $scope),
    'title' => _("Forums")
));
$page_output->header(array(
    'title' => _("All Forums")
));
echo $view->render('forums');
$page_output->footer();
