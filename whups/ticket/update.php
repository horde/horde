<?php
/**
 * Copyright 2001-2002 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2001-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('whups');

require_once WHUPS_BASE . '/lib/Forms/EditTicket.php';

$ticket = Whups::getCurrentTicket();

if (!Whups::hasPermission($ticket->get('queue'), 'queue', 'update')) {
    $notification->push(_("Permission Denied"), 'horde.error');
    Horde::url($prefs->getValue('whups_default_view') . '.php', true)
        ->redirect();
}

$vars = Horde_Variables::getDefaultVariables();
$vars->set('id', $id = $ticket->getId());
foreach ($ticket->getDetails() as $varname => $value) {
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
                $permission = $GLOBALS['injector']->getInstance('Horde_Perms')->getPermission('whups:comments:' . $change['value']);
                if (!is_a($permission, 'PEAR_Error')) {
                    $group_id = array_shift(array_keys($permission->getGroupPermissions()));
                    $vars->set('group', $group_id);
                }
                break;
            }
        }

        $flowed = new Horde_Text_Flowed(preg_replace("/\s*\n/U", "\n", $history[$tid]['comment']));
        $vars->set('newcomment', $flowed->toFlowed(true));
    }
}

// Edit action.
if ($vars->get('formname') == 'editticketform') {
    $editform = new EditTicketForm($vars, $ticket);
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

        $result = $ticket->commit();
        if (is_a($result, 'PEAR_Error')) {
            $notification->push($result, 'horde.error');
        } else {
            $notification->push(_("Ticket Updated"), 'horde.success');
            $ticket->show();
        }
    }
}

$title = '[#' . $id . '] ' . $ticket->get('summary');
require WHUPS_TEMPLATES . '/common-header.inc';
require WHUPS_TEMPLATES . '/menu.inc';
require WHUPS_TEMPLATES . '/prevnext.inc';

$tabs = Whups::getTicketTabs($vars, $id);
echo $tabs->render('update');

$form = new EditTicketForm($vars, $ticket, sprintf(_("Update %s"), $title));
$form->renderActive($form->getRenderer(), $vars, 'update.php', 'post');
echo '<br class="spacer" />';

$form = new TicketDetailsForm($vars, $ticket, $title);
$ticket->setDetails($vars);
$form->renderInactive($form->getRenderer(), $vars);

require $registry->get('templates', 'horde') . '/common-footer.inc';
