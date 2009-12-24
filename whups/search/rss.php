<?php
/**
 * $Horde: whups/search/rss.php,v 1.5 2009/07/09 08:18:48 slusarz Exp $
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Duck <duck@obala.net>
 */

require_once dirname(__FILE__) . '/../lib/base.php';
require_once WHUPS_BASE . '/lib/Forms/Search.php';
require_once 'Horde/Template.php';

$vars = Horde_Variables::getDefaultVariables();
$limit = (int)$vars->get('limit');
$form = new SearchForm($vars);

if ($form->validate($vars, true)) {
    $form->getInfo($vars, $info);
    $tickets = $whups_driver->getTicketsByProperties($info);
    if (is_a($tickets, 'PEAR_Error')) {
        Horde::fatal($tickets, __FILE__, __LINE__);
    }
    Whups::sortTickets($tickets, 'date_updated', 'desc');
} else {
    Horde::fatal(_("Invalid search"), __FILE__, __LINE__);
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

$template = new Horde_Template();
$template->set('charset', Horde_Nls::getCharset());
$template->set('xsl', $registry->get('themesuri') . '/feed-rss.xsl');
$template->set('pubDate', htmlspecialchars(date('r')));
$template->set('title', _("Search Results"));
$template->set('items', $items, true);
$template->set('url', Horde::applicationUrl('search.php'));
$template->set('rss_url', Horde::selfUrl());
$template->set('description', _("Search Results"));

$browser->downloadHeaders('search.rss', 'text/xml', true);
echo $template->fetch(WHUPS_TEMPLATES . '/rss/items.rss');
