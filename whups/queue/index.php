<?php
/**
 * Allows direct access to open tickets in specified queue.
 *
 * Copyright 2007-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Michael J. Rubinsk <mrubinsk@horde.org>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('whups');

// See if we were passed a slug or id. Slug is tried first.
$slug = Horde_Util::getFormData('slug');
if ($slug) {
    $queue = $whups_driver->getQueueBySlugInternal($slug);
    $id = $queue['id'];
} else {
    $id = Horde_Util::getFormData('id');
    $queue = $whups_driver->getQueue($id);
}

if (!$id) {
    $notification->push(_("Invalid queue"), 'horde.error');
    Horde::url($prefs->getValue('whups_default_view') . '.php', true)
        ->redirect();
}

// Update sorting preferences.
if (Horde_Util::getFormData('sortby') !== null) {
    $prefs->setValue('sortby', Horde_Util::getFormData('sortby'));
}
if (Horde_Util::getFormData('sortdir') !== null) {
    $prefs->setValue('sortdir', Horde_Util::getFormData('sortdir'));
}
if (Horde_Util::getFormData('isajax') !== null) {
    exit;
}

$title = sprintf(_("Open tickets in %s"), $queue['name']);
require $registry->get('templates', 'horde') . '/common-header.inc';
require WHUPS_TEMPLATES . '/menu.inc';

$criteria = array('queue' => $id,
                  'category' => array('unconfirmed', 'new', 'assigned'));

try {
    $tickets = $whups_driver->getTicketsByProperties($criteria);
    Whups::sortTickets($tickets);
    $values = Whups::getSearchResultColumns();
    $self = Whups::urlFor('queue', $queue);
    $results = new Whups_View_Results(array('title' => sprintf(_("Open tickets in %s"), $queue['name']),
                                            'results' => $tickets,
                                            'values' => $values,
                                            'url' => $self));
    $session->set('whups', 'last_search', $self);
    $results->html();
} catch (Whups_Exception $e) {
    $notification->push(
        sprintf(_("There was an error locating tickets in this queue: %s"), $e->getMessage()),
        'horde.error');
}

require $registry->get('templates', 'horde') . '/common-footer.inc';
