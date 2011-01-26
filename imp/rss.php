<?php
/**
 * IMP mailbox RSS feed.
 *
 * Copyright 2007-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Eric Garrido <ekg2002@columbia.edu>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */

require_once dirname(__FILE__) . '/lib/Application.php';
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
$mailbox = 'INBOX';
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

$mailbox = IMP_Mailbox::get($mailbox);
$imp_mailbox = $mailbox->getListOb();

/* Obtain some information describing the mailbox state. */
$total_num = count($imp_mailbox);
$unseen_num = $mailbox->vinbox
    ? $total_num
    : $imp_mailbox->unseenMessages(Horde_Imap_Client::SORT_RESULTS_COUNT);

$query = new Horde_Imap_Client_Search_Query();
if ($new_mail) {
    $query->flag('\\seen', false);
}
$ids = $injector->getInstance('IMP_Search')->runQuery($query, $mailbox, Horde_Imap_Client::SORT_ARRIVAL, 1);

if (count($ids)) {
    $imp_ui = new IMP_Ui_Mailbox($mailbox);

    $overview = $imp_mailbox->getMailboxArray(array_slice($ids[$mailbox], 0, 20), array('preview' => $prefs->getValue('preview_enabled')));

    foreach ($overview['overview'] as $ob) {
        $from_addr = $imp_ui->getFrom($ob['envelope'], array('fullfrom' => true));
        $items[] = array_map('htmlspecialchars', array(
            'title' => $imp_ui->getSubject($ob['envelope']->subject),
            'pubDate' => $ob['envelope']->date->format('r'),
            'description' => isset($ob['preview']) ? $ob['preview'] : '',
            'url' => Horde::url(IMP::generateIMPUrl('message.php', $mailbox, $ob['uid'], $mailbox), true, array('append_session' => -1)),
            'fromAddr' => $from_addr['fullfrom'],
            'toAddr' => Horde_Mime_Address::addrArray2String($ob['envelope']->to, array('charset' => 'UTF-8'))
        ));
    }
}

$description = ($total_num == 0)
    ? _("No Messages")
    : sprintf(_("%u of %u messages in %s unread."), $unseen_num, $total_num, $mailbox->label);

$t = $injector->createInstance('Horde_Template');
$t->set('charset', 'UTF-8');
$t->set('xsl', Horde_Themes::getFeedXsl());
$t->set('pubDate', htmlspecialchars(date('r')));
$t->set('desc', htmlspecialchars($description));
$t->set('title', htmlspecialchars($registry->get('name') . ' - ' . $mailbox->label));
$t->set('items', $items, true);
$t->set('url', htmlspecialchars(Horde::url(IMP::generateIMPUrl('message.php', $mailbox), true, array('append_session' => -1))));
$t->set('rss_url', htmlspecialchars(Horde::url('rss.php', true, array('append_session' => -1))));
$browser->downloadHeaders('mailbox.rss', 'text/xml', true);
echo $t->fetch(IMP_TEMPLATES . '/rss/mailbox.rss');
