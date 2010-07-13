<?php
/**
 * Whups RSS feed.
 *
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('whups');

// See if we were passed a slug or id. Slug is tried first.
$slug = Horde_Util::getFormData('slug');
if ($slug) {
    $queue = $whups_driver->getQueueBySlugInternal($slug);
    // Bad queue slug?
    if (!count($queue)) {
        exit;
    }
    $id = $queue['id'];
} else {
    $id = Horde_Util::getFormData('id');
    $queue = $whups_driver->getQueue($id);
}

// If a specific state_category is not specified, default to returning all
// open tickets.
$state_category = Horde_Util::getFormData('state');
if ($state_category) {
    $state_display = Horde_String::ucFirst($state_category);
    // When specifying an explicit state, limit the feed to 10.
    $limit = 10;
    $state_category = array($state_category);
} else {
    $state_category = array('unconfirmed', 'new', 'assigned');
    $state_display = _("Open");
    $limit = 0;
}

$criteria = array();

// See if we are requesting a specific type_id (for bug, feature etc...)
$typeId = Horde_Util::getFormData('type_id');
if (is_numeric($typeId)) {
    $type = $whups_driver->getType($typeId);
    if (!is_a($type, 'PEAR_Error')) {
        $criteria['type'] = array($typeId);
    } else {
        unset($type);
    }
}

if (!$id && !$state_category && !$typeId) {
    exit;
}

$criteria['category'] = $state_category;
if ($id) {
    $criteria['queue'] = $id;
}

$tickets = $whups_driver->getTicketsByProperties($criteria);
if (is_a($tickets, 'PEAR_Error') || !count($tickets)) {
    exit;
}

Whups::sortTickets($tickets, 'date_updated', 'desc');
$cnt = 0;
foreach (array_keys($tickets) as $i) {
    if ($limit > 0 && $cnt++ == $limit) {
        break;
    }
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
if (isset($type) && isset($queue['name'])) {
    $rss_title = sprintf(_("%s %s tickets in %s"),
                         $state_display,
                         $type['name'], $queue['name']);
} elseif (isset($type)) {
    $rss_title = sprintf(_("%s %s tickets in all queues"),
                         $state_display,
                         $type['name']);
} elseif (isset($queue['name'])) {
    $rss_title = sprintf(_("%s tickets in %s"),
                         $state_display,
                         $queue['name']);
} else {
     $rss_title = sprintf(_("%s tickets in all queues"), $state_display);
}
$template->set('title', htmlspecialchars($rss_title));
$template->set('items', $items, true);
$template->set('url',
               Horde_Util::addParameter(Horde::applicationUrl('queue/', true, -1),
                                  'id', $id));
$template->set('rss_url',
               Horde_Util::addParameter(Horde::applicationUrl('rss.php', true, -1),
                                  'id',  $id));
if (isset($queue['name'])) {
    $description = sprintf(_("Open tickets in %s"), $queue['name']);
} else {
    $description = _("Open tickets in all queues.");
}
$template->set('description', htmlspecialchars($description));

$browser->downloadHeaders((isset($queue['name']) ? $queue['name'] : 'horde') . '.rss', 'text/xml', true);
echo $template->fetch(WHUPS_TEMPLATES . '/rss/items.rss');
