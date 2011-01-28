<?php
/**
 * Copyright 2001-2002 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2001-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('whups');

$ticket = Horde_Util::getFormData('id');
$ticket = preg_replace('|\D|', '', $ticket);
if (!$ticket) {
    exit;
}

// Get the ticket details first.
$details = $whups_driver->getTicketDetails($ticket);
if (is_a($details, 'PEAR_Error')) {
    exit;
}

// Check permissions on this ticket.
if (!count(Whups::permissionsFilter(array($details['queue'] => ''), 'queue', Horde_Perms::READ))) {
    exit;
}

$history = Whups::permissionsFilter($whups_driver->getHistory($ticket),
                                    'comment', Horde_Perms::READ);
$items = array();
$self = Whups::urlFor('ticket', $ticket, true, -1);
foreach (array_keys($history) as $i) {
    if (!isset($history[$i]['comment_text'])) {
        continue;
    }
    $items[$i]['title'] = htmlspecialchars(substr($history[$i]['comment_text'], 0, 60));
    $items[$i]['description'] = htmlspecialchars($history[$i]['comment_text']);
    $items[$i]['pubDate'] = htmlspecialchars(date('r', $history[$i]['timestamp']));
    $items[$i]['url'] = $self . '#t' . $i;
}

$template = $injector->createInstance('Horde_Template');
$template->set('xsl', Horde_Themes::getFeedXsl());
$template->set('pubDate', htmlspecialchars(date('r')));
$template->set('title', htmlspecialchars($details['summary']));
$template->set('items', $items, true);
$template->set('url', Whups::urlFor('ticket', $ticket, true));
$template->set('rss_url', Whups::urlFor('ticket_rss', $ticket, true));
$template->set('description', htmlspecialchars($details['summary']));

$browser->downloadHeaders($details['summary'] . '.rss',
                          'text/xml', true);
echo $template->fetch(WHUPS_TEMPLATES . '/rss/items.rss');
