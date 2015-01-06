<?php
/**
 * Copyright 2002-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('whups');

Whups::addTopbarSearch();

/* Supported graph types. Unused at the moment. */
$graphs = array('open|queue_name' => array('chart', _("Open Tickets by Queue")),
                'open|state_name' => array('chart', _("Open Tickets by State")),
                'open|type_name' => array('chart', _("Open Tickets by Type")),
                'open|priority_name' => array('chart', _("Open Tickets by Priority")),
                'open|user_id_requester' => array('chart', _("Open Tickets by Requester")),
                'open|owner' => array('chart', _("Open Tickets by Owner")),
                '@closed:avg:open|owner' => array('plot', _("Average days to close by Owner")),
                '@closed:avg:open|user_id_requester' => array('plot', _("Average days to close by Requester")),
                '@closed:avg:open|queue_name' => array('plot', _("Average days to close by Queue")));

/* Supported statistic types. */
$stats = array('avg|open' => _("Average time a ticket is unresolved"),
               'max|open' => _("Maximum time a ticket is unresolved"),
               'min|open' => _("Minimum time a ticket is unresolved"));

$queues = Whups::permissionsFilter($whups_driver->getQueues(), 'queue', Horde_Perms::READ);

$reporter = new Whups_Reports($whups_driver);

$page_output->header(array(
    'title' => _("Reports")
));
$notification->notify(array('listeners' => 'status'));
require WHUPS_TEMPLATES . '/reports/stats.inc';
$page_output->footer();
