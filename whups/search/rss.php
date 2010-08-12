<?php
/**
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Duck <duck@obala.net>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('whups');

require_once WHUPS_BASE . '/lib/Forms/Search.php';

$vars = Horde_Variables::getDefaultVariables();
$limit = (int)$vars->get('limit');
$form = new SearchForm($vars);

if ($form->validate($vars, true)) {
    $form->getInfo($vars, $info);
    $tickets = $whups_driver->getTicketsByProperties($info);
    if ($tickets instanceof PEAR_Error) {
        throw new Horde_Exception($tickets);
    }
    Whups::sortTickets($tickets, 'date_updated', 'desc');
} else {
    throw new Horde_Exception(_("Invalid search"));
}

$count = 0;
$items = array();
foreach (array_keys($tickets) as $i) {
    if ($limit > 0 && $count++ == $limit) {
        break;
    }
    $description = sprintf(_("Type: %s; State: %s"),
                           $tickets[$i]['type_name'],
                           $tickets[$i]['state_name']);

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
$template->set('title', _("Search Results"));
$template->set('items', $items, true);
$template->set('url', Horde::applicationUrl('search.php'));
$template->set('rss_url', Horde::selfUrl());
$template->set('description', _("Search Results"));

$browser->downloadHeaders('search.rss', 'text/xml', true);
echo $template->fetch(WHUPS_TEMPLATES . '/rss/items.rss');
