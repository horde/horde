<?php
/**
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

if (!Whups::hasPermission($ticket->get('queue'), 'queue', 'update')) {
    $notification->push(_("Permission Denied"), 'horde.error');
    Horde::url($prefs->getValue('whups_default_view') . '.php', true)
        ->redirect();
}

$vars = Horde_Variables::getDefaultVariables();
$vars->set('id', $id = $ticket->getId());
foreach ($ticket->getDetails() as $varname => $value) {
    if ($varname == 'owners') {
        $owners = $gowners = array();
        foreach ($value as $owner) {
            if (strpos($owner, 'user:') !== false) {
                $owners[] = $owner;
            } else {
                $gowners[] = $owner;
            }
        }
        $vars->add('owners', $owners);
        $vars->add('group_owners', $gowners);
    }
    $vars->add($varname, $value);
}
if ($tid = $vars->get('transaction')) {
    $history = Whups::permissionsFilter($whups_driver->getHistory($ticket->getId()),
                                        'comment', Horde_Perms::READ);
    if (!empty($history[$tid]['comment'])) {
        // If this was a restricted comment, load the group_id it was
        // restricted to and default to keeping that restriction on
        // the reply.
        foreach ($history[$tid]['changes'] as $change) {
            if (!empty($change['private'])) {
                $permission = $GLOBALS['injector']
                    ->getInstance('Horde_Perms')
                    ->getPermission('whups:comments:' . $change['value']);
                $group_id = array_shift(array_keys($permission->getGroupPermissions()));
                $vars->set('group', $group_id);
                break;
            }
        }

        $flowed = new Horde_Text_Flowed(preg_replace("/\s*\n/U", "\n", $history[$tid]['comment']), 'UTF-8');
        $vars->set('newcomment', $flowed->toFlowed(true));
    }
}

// Edit action.
$title = '[#' . $id . '] ' . $ticket->get('summary');
$editform = new Whups_Form_Ticket_Edit($vars, $ticket, sprintf(_("Update %s"), $title));
if ($vars->get('formname') == 'whups_form_ticket_edit') {
    if ($editform->validate($vars)) {
        $editform->getInfo($vars, $info);

        $ticket->change('summary', $info['summary']);
        $ticket->change('state', $info['state']);
        $ticket->change('priority', $info['priority']);
        $ticket->change('due', $info['due']);
        if (!empty($info['version'])) {
            $ticket->change('version', $info['version']);
        }
        if (!empty($info['newcomment'])) {
            $ticket->change('comment', $info['newcomment']);
        }

        // Update user and group assignments.
        if (Whups::hasPermission($vars->get('queue'), 'queue', 'assign')) {
            $ticket->change('owners', array_merge(isset($info['owners']) ? $info['owners'] : array(),
                                                  isset($info['group_owners']) ? $info['group_owners'] : array()));
        }

        // Update attributes.
        $whups_driver->setAttributes($info, $ticket);

        // Add attachment if one was uploaded.
        if (!empty($info['newattachment']['name'])) {
            $ticket->change('attachment', array('name' => $info['newattachment']['name'],
                                                'tmp_name' => $info['newattachment']['tmp_name']));
        }

        // If there was a new comment and permissions were specified
        // on it, set them.
        if (!empty($info['group'])) {
            $ticket->change('comment-perms', $info['group']);
        }
        try {
            $ticket->commit();
            $notification->push(_("Ticket Updated"), 'horde.success');
            $ticket->show();
        } catch (Whups_Exception $e) {
            $notification->push($e, 'horde.error');
        }
    }
}

$page_output->header(array(
    'title' => $title
));
require WHUPS_TEMPLATES . '/menu.inc';
require WHUPS_TEMPLATES . '/prevnext.inc';

$tabs = Whups::getTicketTabs($vars, $id);
echo $tabs->render('update');

$editform->renderActive($editform->getRenderer(), $vars, Horde::url('ticket/update.php'), 'post');
echo '<br class="spacer" />';

$form = new Whups_Form_TicketDetails($vars, $ticket, $title);
$ticket->setDetails($vars);
$form->renderInactive($form->getRenderer(), $vars);

$page_output->footer();
