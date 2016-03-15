<?php
/**
 * Copyright 2001-2002 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2001-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 */

require_once __DIR__ . '/../lib/Application.php';
Horde_Registry::appInit('whups');

$ticket = Whups::getCurrentTicket();
$page_output->addLinkTag($ticket->feedLink());

$vars = Horde_Variables::getDefaultVariables();
$vars->set('id', $id = $ticket->getId());
foreach ($ticket->getDetails() as $varname => $value) {
    $vars->add($varname, $value);
}

Whups::addTopbarSearch();

$addform = new Whups_Form_AddListener($vars, _("Add Watcher"));
$delform = new Whups_Form_DeleteListener($vars, _("Remove Watcher"));

if ($vars->get('formname') == 'whups_form_addlistener' &&
    $addform->validate($vars)) {
    $addform->getInfo($vars, $info);
    try {
        $whups_driver->addListener($id, '**' . $info['add_listener']);
        $ticket->notify(
            $info['add_listener'],
            false,
            array('**' . $info['add_listener'] => 'listener')
        );
        $notification->push(
            sprintf(
                _("%s will be notified when this ticket is updated."),
                $info['add_listener']
            ),
            'horde.success');
        $ticket->show();
    } catch (Whups_Exception $e) {
        $notification->push($e, 'horde.error');
    }
} elseif ($listener = $vars->get('del_listener')) {
    try {
        $whups_driver->deleteListener($id, '**' . $listener);
        $notification->push(
            sprintf(
                _("%s will no longer receive updates for this ticket."),
                $listener
            ),
            'horde.success');
        $ticket->show();
    } catch (Whups_Exception $e) {
        $notification->push($e, 'horde.error');
    }
}

$form = new Whups_Form_TicketDetails(
    $vars, $ticket, '[#' . $id . '] ' . $ticket->get('summary')
);
$ticket->setDetails($vars);

$listeners = array_keys($whups_driver->getListeners($id, false, false, false));
array_walk(
    $listeners,
    function(&$listener)
    {
        $listener = preg_replace('/^\*\*/', '', $listener);
    }
);
$owners = $whups_driver->getOwners($id);
if ($owners) {
    $owners = reset($owners);
} else {
    $owners = array();
}
$delurl = Horde::url('ticket/watch.php')->add('id', $id);
$delimg = Horde::img('delete.png');

$r = new Horde_Form_Renderer();

// Output content.
$page_output->header(array(
    'title' => sprintf(
        _("Watchers for %s"),
        '[#' . $id . '] ' . $ticket->get('summary')
    )
));
$notification->notify(array('listeners' => 'status'));
require WHUPS_TEMPLATES . '/prevnext.inc';
Whups::getTicketTabs($vars, $id)->render('watch');
require WHUPS_TEMPLATES . '/ticket/watchers.inc';
$addform->renderActive($r, $vars, Horde::url('ticket/watch.php'), 'post');
echo '<br class="spacer" />';
if (!$registry->getAuth()) {
    $delform->renderActive($r, $vars, Horde::url('ticket/watch.php'), 'post');
    echo '<br class="spacer" />';
}
$form->renderInactive($form->getRenderer(), $vars);
$page_output->footer();
