<?php
/**
 * Displays and handles the form to delete a ticket.
 *
 * Copyright 2001-2002 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 */

require_once __DIR__ . '/../lib/Application.php';
Horde_Registry::appInit('whups');

$ticket = Whups::getCurrentTicket();
$page_output->addLinkTag($ticket->feedLink());
$details = $ticket->getDetails();
if (!Whups::hasPermission($details['queue'], 'queue', Horde_Perms::DELETE)) {
    $notification->push(_("Permission Denied"), 'horde.error');
    Horde::url($prefs->getValue('whups_default_view') . '.php', true)
        ->redirect();
}

$vars = Horde_Variables::getDefaultVariables();
$vars->set('id', $id = $ticket->getId());
foreach ($details as $varname => $value) {
    $vars->add($varname, $value);
}
$title = sprintf(_("Delete %s?"), '[#' . $id . '] ' . $ticket->get('summary'));
$deleteform = new Whups_Form_Ticket_Delete($vars, $title);

if ($vars->get('formname') == 'whups_form_ticket_delete') {
    if ($deleteform->validate($vars)) {
        if ($vars->get('yesno') == 1) {
            $deleteform->getInfo($vars, $info);
            try {
                $ticket->delete();
                $notification->push(sprintf(_("Ticket %d has been deleted."), $info['id']), 'horde.success');
                Horde::url($prefs->getValue('whups_default_view') . '.php', true)
                    ->redirect();
            } catch (Whups_Exception $e) {
                $notification->push(_("There was an error deleting the ticket:") . ' ' . $e->getMessage(), 'horde.error');
            } catch (Horde_Exception_NotFound $e) {
                $notification->push(sprintf(_("Ticket %d not found."), $info['id']));
            }
        } else {
            $notification->push(_("The ticket was not deleted."), 'horde.message');
        }
    }
}

$page_output->header(array(
    'title' => $title
));
require WHUPS_TEMPLATES . '/menu.inc';
require WHUPS_TEMPLATES . '/prevnext.inc';

$tabs = Whups::getTicketTabs($vars, $id);
echo $tabs->render('delete');

$deleteform->renderActive($deleteform->getRenderer(), $vars, Horde::url('ticket/delete.php'), 'post');
echo '<br />';

$form = new Whups_Form_TicketDetails($vars, $ticket);
$ticket->setDetails($vars);

$r = $form->getRenderer();
$r->beginInactive($title);
$r->renderFormInactive($form, $vars);
$r->end();

$page_output->footer();
