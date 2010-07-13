<?php
/**
 * Whups RSS feed.
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @author Jan Schneider <jan@horde.org>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('whups');

require_once WHUPS_BASE . '/lib/Query.php';

$qManager = new Whups_QueryManager();
$vars = new Horde_Variables();

// See if we were passed a slug or id. Slug is tried first.
$whups_query = null;
$slug = Horde_Util::getFormData('slug');
if ($slug) {
    $whups_query = $qManager->getQueryBySlug($slug);
} else {
    $whups_query = $qManager->getQuery(Horde_Util::getFormData('query'));
}

if (!isset($whups_query) ||
    is_a($whups_query, 'PEAR_Error') ||
    $whups_query->parameters ||
    !$whups_query->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::READ)) {
    exit;
}

$tickets = $whups_driver->executeQuery($whups_query, $vars);
if (is_a($tickets, 'PEAR_Error') || !count($tickets)) {
    exit;
}

Whups::sortTickets($tickets, 'date_updated', 'desc');
$cnt = 0;
foreach (array_keys($tickets) as $i) {
    $description = 'Type: ' . $tickets[$i]['type_name'] . '; State: '
        . $tickets[$i]['state_name'];

    $items[$i]['title'] = htmlspecialchars(sprintf('[%s] %s',
                                                   $tickets[$i]['id'],
                                                   $tickets[$i]['summary']));
    $items[$i]['description'] = htmlspecialchars($description);
    $items[$i]['url'] = Whups::urlFor('ticket', $tickets[$i]['id'], true, -1);
    $items[$i]['pubDate'] = htmlspecialchars(date('r', $tickets[$i]['timestamp']));
}

$template = $injector->createInstance('Horde_Template');
$template->set('charset', $GLOBALS['registry']->getCharset());
$template->set('xsl', $registry->get('themesuri') . '/feed-rss.xsl');
$template->set('pubDate', htmlspecialchars(date('r')));
$template->set('title', htmlspecialchars($whups_query->name ? $whups_query->name : _("Query Results")));
$template->set('items', $items, true);
$url_param = isset($slug)
    ? array('slug' => $slug)
    : array('id' => Horde_Util::getFormData('query'));
$template->set('url', Whups::urlFor('query', $url_param, true, -1));
$template->set('rss_url', Whups::urlFor('query_rss', $url_param, true, -1));
$template->set('description', htmlspecialchars(sprintf(_("Tickets matching the query \"%s\"."), $whups_query->name)));

$browser->downloadHeaders((isset($slug) ? $slug : 'query') . '.rss', 'text/xml', true);
echo $template->fetch(WHUPS_TEMPLATES . '/rss/items.rss');
