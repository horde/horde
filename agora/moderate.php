<?php
/**
 * The Agora script to moderate any outstanding messages requiring moderation.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('agora');

/* Set up the messages object. */
$scope = Horde_Util::getGet('scope', 'agora');
$messages = &Agora_Messages::singleton($scope);

/* Which page are we on? Default to page 0. */
$messages_page = Horde_Util::getFormData('page', 0);
$messages_per_page = $prefs->getValue('threads_per_page');
$messages_start = $messages_page * $messages_per_page;

/* Get the sorting. */
$sort_by = Agora::getSortBy('moderate');
$sort_dir = Agora::getSortDir('moderate');

/* Check for any actions. */
switch (Horde_Util::getFormData('action')) {
case _("Approve"):
    $message_ids = Horde_Util::getFormData('message_ids');
    $messages->moderate('approve', $message_ids);
    $notification->push(sprintf(_("%d messages was approved."), count($message_ids)), 'horde.success');
    break;

case _("Delete"):
    $message_ids = Horde_Util::getFormData('message_ids');
    $messages->moderate('delete', $message_ids);
    $notification->push(sprintf(_("%d messages was deleted."), count($message_ids)), 'horde.success');
    break;
}

/* Get a list of messages still to moderate. Error will occur if you don't have the right permissions */
$messages_list = $messages->getModerateList($sort_by, $sort_dir);
if ($messages_list instanceof PEAR_Error) {
    $notification->push($messages_list->getMessage(), 'horde.error');
    Horde::url('forums.php', true)->redirect();
} elseif (empty($messages_list)) {
    $messages_count = 0;
    $notification->push(_("No messages are waiting for moderation."), 'horde.message');
} else {
    $messages_count = count($messages_list);
    $messages_list = array_slice($messages_list, $messages_start, $messages_per_page);
}


/* Set up the column headers. */
$col_headers = array('forum_id' => _("Forum"), 'message_subject' => _("Subject"), 'message_author' => _("Posted by"), 'message_body' => _("Body"), 'message_timestamp' => _("Date"));
$col_headers = Agora::formatColumnHeaders($col_headers, $sort_by, $sort_dir, 'moderate');

/* Set up the template tags. */
$view = new Agora_View();
$view->col_headers = $col_headers;
$view->messages = $messages_list;
$view->buttons = array(_("Approve"), _("Delete"));
$view->session_tag = Horde_Util::formInput();

Horde::startBuffer();
$notification->notify(array('listeners' => 'status'));
$view->notify = Horde::endBuffer();

/* Set up pager. */
$vars = Horde_Variables::getDefaultVariables();
$pager_ob = new Horde_Core_Ui_Pager('moderate_page', $vars, array('num' => $messages_count, 'url' => Horde::selfUrl(true), 'perpage' => $messages_per_page));
$pager_ob->preserve('agora', Horde_Util::getFormData('agora'));
$view->pager = $pager_ob->render();

if (isset($api_call)) {
    return $view->render('moderate.html.php');
} else {
    $title = _("Messages Awaiting Moderation");
    $view->menu = Agora::getMenu('string');
    Horde::addScriptFile('stripe.js', 'horde', true);
    require AGORA_TEMPLATES . '/common-header.inc';
    echo $view->render('moderate.html.php');
    require $registry->get('templates', 'horde') . '/common-footer.inc';
}
