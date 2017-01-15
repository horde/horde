<?php
/**
 * Handles the form to delete multiple tickets.
 *
 * Copyright 2016-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 */

require_once __DIR__ . '/../lib/Application.php';
Horde_Registry::appInit('whups');

Whups::addTopbarSearch();

$vars = Horde_Variables::getDefaultVariables();
$deleteform = new Whups_Form_Ticket_DeleteMultiple($vars);
$title = sprintf(_("Delete %d tickets?"), count($deleteform->getTickets()));
$deleteform->setTitle($title);

if ($vars->get('formname') == 'whups_form_ticket_deletemultiple' &&
    $deleteform->validate($vars)) {
    if ($vars->get('submitbutton') == _("Delete")) {
        $deleteform->getInfo($vars, $info);
        $tickets = @unserialize($info['tickets']);
        foreach ($tickets as $id) {
            try {
                Whups_Ticket::makeTicket($id)->delete();
                $notification->push(sprintf(_("Ticket %d has been deleted."), $id), 'horde.success');
            } catch (Whups_Exception $e) {
                $notification->push(_("There was an error deleting the ticket:") . ' ' . $e->getMessage(), 'horde.error');
            } catch (Horde_Exception_NotFound $e) {
                $notification->push(sprintf(_("Ticket %d not found."), $id));
            }
        }
    } else {
        $notification->push(_("The tickets were not deleted."), 'horde.message');
    }
    Horde::url($vars->get('url', $prefs->getValue('whups_default_view') . '.php'), true)
        ->redirect();
}

$vars->set('tickets', serialize($deleteform->getTickets()));
$page_output->header(array(
    'title' => $title
));
$notification->notify(array('listeners' => 'status'));
$deleteform->renderActive(
    $deleteform->getRenderer(),
    $vars,
    Horde::url('ticket/delete_multiple.php'),
    'post'
);
$page_output->footer();
