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

$vars = Horde_Variables::getDefaultVariables();
$vars->set('id', $id = $ticket->getId());
foreach ($ticket->getDetails() as $varname => $value) {
    $vars->add($varname, $value);
}
if ($tid = $vars->get('transaction')) {
    $history = Whups::permissionsFilter($whups_driver->getHistory($ticket->getId()),
                                        'comment', Horde_Perms::READ);
    if (!empty($history[$tid]['comment'])) {
        $private = false;
        foreach ($history[$tid]['changes'] as $change) {
            if (!empty($change['private'])) {
                if (!$GLOBALS['injector']->getInstance('Horde_Perms')->hasPermission('whups:comments:' . $change['value'], $GLOBALS['registry']->getAuth(), Horde_Perms::READ)) {
                    $private = true;
                    break;
                }
            }
        }

        if (!$private) {
            $flowed = new Horde_Text_Flowed(preg_replace("/\s*\n/U", "\n", $history[$tid]['comment']), 'UTF-8');
            $vars->set('newcomment', $flowed->toFlowed(true));
        }
    }
}

$title = sprintf(_("Comment on %s"), '[#' . $id . '] ' . $ticket->get('summary'));
$commentForm = new Whups_Form_AddComment($vars, $title);
if ($vars->get('formname') == 'whups_form_addcomment' &&
    $commentForm->validate($vars)) {
    $commentForm->getInfo($vars, $info);

    // Add comment.
    if (!empty($info['newcomment'])) {
        $ticket->change('comment', $info['newcomment']);
    }

    if (!empty($info['user_email'])) {
        $ticket->change('comment-email', $info['user_email']);
    }

    // Add attachment if one was uploaded.
    if (!empty($info['newattachment']['name'])) {
        $ticket->change('attachment', array('name' => $info['newattachment']['name'],
                                            'tmp_name' => $info['newattachment']['tmp_name']));
    }

    // Add watch
    if (!empty($info['add_watch'])) {
        $whups_driver->addListener($ticket->getId(), '**' . $info['user_email']);
    }

    // If there was a new comment and permissions were specified on
    // it, set them.
    if (!empty($info['group'])) {
        $ticket->change('comment-perms', $info['group']);
    }

    try {
        $ticket->commit();
        $notification->push(_("Comment added"), 'horde.success');
        $ticket->show();
    } catch (Whups_Exception $e) {
        $notification->push($e->getMessage(), 'horde.error');
    }
}

$page_output->header(array(
    'title' => $title
));
require WHUPS_TEMPLATES . '/menu.inc';
require WHUPS_TEMPLATES . '/prevnext.inc';

$tabs = Whups::getTicketTabs($vars, $id);
echo $tabs->render('comment');

$commentForm->renderActive(new Horde_Form_Renderer(), $vars, Horde::url('ticket/comment.php'), 'post');

$page_output->footer();
