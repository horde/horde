<?php
/**
 * Displays and handles the form to delete a ticket.
 *
 * Copyright 2001-2002 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2001-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('whups');

class DeleteTicketForm extends Horde_Form {

    var $_queue;

    function DeleteTicketForm(&$vars, $title = '')
    {
        parent::Horde_Form($vars, $title, 'deleteticketform');

        $info = $GLOBALS['whups_driver']->getTicketDetails($vars->get('id'));
        $this->_queue = $info['queue'];
        $this->addHidden('', 'id', 'int', true, true);
        $summary = &$this->addVariable(_("Summary"), 'summary', 'text', false,
                                       true);
        $summary->setDefault($info['summary']);
        $yesno = array(0 => _("No"), 1 => _("Yes"));
        $this->addVariable(_("Really delete this ticket? It will NOT be archived, and will be gone forever."), 'yesno', 'enum', true, false, null, array($yesno));
    }

    function validate(&$vars)
    {
        if (!Whups::hasPermission($this->_queue, 'queue', Horde_Perms::DELETE)) {
            $this->setError('yesno', _("Permission Denied."));
        }

        return parent::validate($vars);
    }

}

$ticket = Whups::getCurrentTicket();
$linkTags[] = $ticket->feedLink();
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
$deleteform = new DeleteTicketForm($vars, $title);

if ($vars->get('formname') == 'deleteticketform') {
    if ($deleteform->validate($vars)) {
        if ($vars->get('yesno') == 1) {
            $deleteform->getInfo($vars, $info);
            $result = $whups_driver->deleteTicket($info);

            if (!is_a($result, 'PEAR_Error')) {
                $notification->push(sprintf(_("Ticket %d has been deleted."), $info['id']), 'horde.success');
                Horde::url($prefs->getValue('whups_default_view') . '.php', true)
                    ->redirect();
            }
            $notification->push(_("There was an error deleting the ticket:") . ' ' . $result->getMessage(), 'horde.error');
        } else {
            $notification->push(_("The ticket was not deleted."), 'horde.message');
        }
    }
}

require $registry->get('templates', 'horde') . '/common-header.inc';
require WHUPS_TEMPLATES . '/menu.inc';
require WHUPS_TEMPLATES . '/prevnext.inc';

$tabs = Whups::getTicketTabs($vars, $id);
echo $tabs->render('delete');

$deleteform->renderActive($deleteform->getRenderer(), $vars, 'delete.php', 'post');
echo '<br />';

$form = new TicketDetailsForm($vars, $ticket);
$ticket->setDetails($vars);

$r = $form->getRenderer();
$r->beginInactive($title);
$r->renderFormInactive($form, $vars);
$r->end();

require $registry->get('templates', 'horde') . '/common-footer.inc';
