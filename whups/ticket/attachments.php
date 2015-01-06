<?php
/**
 * Copyright 2013-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Jan Schneider <jan@horde.org>
 */

require_once __DIR__ . '/../lib/Application.php';
Horde_Registry::appInit('whups');

$vars = Horde_Variables::getDefaultVariables();
$ticket = Whups::getCurrentTicket();

$view = $injector->createInstance('Horde_View');
try {
    $files = $ticket->listAllAttachments();
} catch (Whups_Exception $e) {
    $notification->push($e);
}
if ($files) {
    $format = array(
        $prefs->getValue('date_format'),
        $prefs->getValue('time_format')
    );
    $attachments = Whups::getAttachments($ticket->getId());
    $view->attachments = array();
    foreach ($files as $file) {
        $view->attachments[] = array_merge(
            array(
                'timestamp' => $file['timestamp'],
                'date' => strftime($format[0], $file['timestamp'])
                    . ' ' . strftime($format[1], $file['timestamp']),
                'user' => Whups::formatUser(
                    Whups::getUserAttributes($file['user_id']),
                    true,
                    true,
                    true
                ),
            ),
            Whups::attachmentUrl(
                $ticket->getId(),
                $attachments[$file['value']],
                $ticket->get('queue')
            )
        );
    }
}

Whups::addTopbarSearch();
Whups::addFeedLink();
$page_output->addLinkTag($ticket->feedLink());
$page_output->addScriptFile('tables.js', 'horde');
$page_output->header(array(
    'title' => sprintf(_("Attachments for %s"), '[#' . $id . '] ' . $ticket->get('summary'))
));
$notification->notify(array('listeners' => 'status'));
echo Whups::getTicketTabs($vars, $ticket->getId())->render('attachments');
echo $view->render('ticket/attachments');
$page_output->footer();
