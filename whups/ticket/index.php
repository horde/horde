<?php
/**
 * Copyright 2001-2002 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once __DIR__ . '/../lib/Application.php';
Horde_Registry::appInit('whups');

$ticket = Whups::getCurrentTicket();
$vars = Horde_Variables::getDefaultVariables();
$ticket->setDetails($vars);
$page_output->addLinkTag($ticket->feedLink());

$page_output->header(array(
    'title' => '[#' . $ticket->getId() . '] ' . $ticket->get('summary')
));
require WHUPS_TEMPLATES . '/menu.inc';
require WHUPS_TEMPLATES . '/prevnext.inc';

$tabs = Whups::getTicketTabs($vars, $ticket->getId());
echo $tabs->render('history');

$form = new Whups_Form_TicketDetails($vars, $ticket);

$renderer = $form->getRenderer();
$renderer->_name = $form->getName();
$renderer->beginInactive($title);
$renderer->renderFormInactive($form, $vars);
$renderer->end();

echo '<br class="spacer" />';

$comment = new Whups_Form_Renderer_Comment();
$comment->begin(_("History"));
$history = Whups::permissionsFilter(
    $whups_driver->getHistory($ticket->getId(), $form),
    'comment',
    Horde_Perms::READ);
$chtml = array();
foreach ($history as $transaction => $comment_values) {
    $chtml[] = $comment->render($transaction, new Horde_Variables($comment_values));
}
if ($prefs->getValue('comment_sort_dir')) {
    $chtml = array_reverse($chtml);
}
echo implode('', $chtml);
$comment->end();

$page_output->footer();
