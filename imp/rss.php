<?php
/**
 * IMP mailbox RSS feed.
 *
 * Copyright 2007-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Eric Garrido <ekg2002@columbia.edu>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */

require_once __DIR__ . '/lib/Application.php';
try {
    Horde_Registry::appInit('imp', array('authentication' => 'throw'));
} catch (Horde_Exception $e) {
    //!$auth->authenticate($_SERVER['PHP_AUTH_USER'], array('password' => isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : null)))) {
    header('WWW-Authenticate: Basic realm="IMP RSS Interface"');
    header('HTTP/1.0 401 Unauthorized');
    echo '401 Unauthorized';
    exit;
}

$items = array();
$mailbox = IMP_Mailbox::get('INBOX');
$new_mail = $request = $searchid = false;
$unseen_num = 0;

/* Determine the mailbox that was requested and if only new mail should be
 * displayed. Default to new mail in INBOX. */
$request = Horde_Util::getPathInfo();
if (!empty($request)) {
    $request_parts = explode('/-/', $request);
    if (!empty($request_parts[0])) {
        $ns_info = $injector->getInstance('IMP_Factory_Imap')->create()->getNamespace();
        $mailbox = IMP_Mailbox::get(preg_replace('/\//', $ns_info['delimiter'], trim($request_parts[0], '/')))->namespace_append;

        /* Make sure mailbox exists or else exit immediately. */
        if (!$mailbox->exists) {
            exit;
        }
    }
    $new_mail = (isset($request_parts[1]) && ($request_parts[1] === 'new'));
}

$imp_mailbox = $mailbox->getListOb();

/* Obtain some information describing the mailbox state. */
$total_num = count($imp_mailbox);
$unseen_num = $mailbox->vinbox
    ? $total_num
    : $imp_mailbox->unseenMessages(Horde_Imap_Client::SEARCH_RESULTS_COUNT);

$query = new Horde_Imap_Client_Search_Query();
if ($new_mail) {
    $query->flag(Horde_Imap_Client::FLAG_SEEN, false);
}
$ids = $mailbox->runSearchQuery($query, Horde_Imap_Client::SORT_ARRIVAL, 1);

if (count($ids)) {
    $imp_ui = new IMP_Ui_Mailbox($mailbox);

    $overview = $imp_mailbox->getMailboxArray(array_slice($ids[strval($mailbox)], 0, 20), array('preview' => $prefs->getValue('preview_enabled')));

    foreach ($overview['overview'] as $ob) {
        $from_addr = $imp_ui->getFrom($ob['envelope']);
        $items[] = array_map('htmlspecialchars', array(
            'title' => $imp_ui->getSubject($ob['envelope']->subject),
            'pubDate' => $ob['envelope']->date->format('r'),
            'description' => isset($ob['preview']) ? $ob['preview'] : '',
            'url' => Horde::url($mailbox->url('message.php', $ob['uid'], $mailbox), true, array('append_session' => -1)),
            'fromAddr' => strval($from_addr['from_list']),
            'toAddr' => strval($ob['envelope']->to)
        ));
    }
}

$description = ($total_num == 0)
    ? _("No Messages")
    : sprintf(_("%u of %u messages in %s unread."), $unseen_num, $total_num, $mailbox->label);

$view = new Horde_View(array(
    'templatePath' => IMP_TEMPLATES . '/rss'
));
$view->addHelper('Text');

$view->desc = $description;
$view->items = $items;
$view->pubDate = date('r');
$view->rss_url = Horde::url('rss.php', true, array('append_session' => -1));
$view->title = $registry->get('name') . ' - ' . $mailbox->label;
$view->url = Horde::url($mailbox->url('message.php'), true, array('append_session' => -1));
$view->xsl = Horde_Themes::getFeedXsl();

$browser->downloadHeaders('mailbox.rss', 'text/xml', true);
echo $view->render('mailbox.rss');
