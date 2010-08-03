<?php
/**
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * $Horde: jonah/channels/delete.php,v 1.36 2009/11/24 04:15:37 chuck Exp $
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Marko Djukic <marko@oblo.com>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
$jonah = Horde_Registry::appInit('jonah');
require_once 'Horde/Form.php';
require_once 'Horde/Form/Renderer.php';

$news = Jonah_News::factory();

/* Set up the form variables and the form. */
$vars = Horde_Variables::getDefaultVariables();
$form_submit = $vars->get('submitbutton');
$channel_id = $vars->get('channel_id');

$channel = $news->getChannel($channel_id);
if (is_a($channel, 'PEAR_Error')) {
    $notification->push(_("Invalid channel specified for deletion."), 'horde.message');
    Horde::applicationUrl('channels/index.php', true)->redirect();
}

/* Check permissions and deny if not allowed. */
if (!Jonah::checkPermissions(Jonah::typeToPermName($channel['channel_type']), Horde_Perms::DELETE, $channel_id)) {
    $notification->push(_("You are not authorised for this action."), 'horde.warning');
    Horde::authenticationFailureRedirect();
}

/* If not yet submitted set up the form vars from the fetched
 * channel. */
if (empty($form_submit)) {
    $vars = new Horde_Variables($channel);
}

$title = sprintf(_("Delete News Channel \"%s\"?"), $vars->get('channel_name'));
$form = new Horde_Form($vars, $title);

$form->setButtons(array(_("Delete"), _("Do not delete")));
$form->addHidden('', 'channel_id', 'int', true, true);

$msg = _("Really delete this News Channel?");
if ($vars->get('channel_type') == Jonah::INTERNAL_CHANNEL) {
    $msg .= ' ' . _("All stories created in this channel will be lost!");
} else {
    $msg .= ' ' . _("Any cached stories for this channel will be lost!");
}
$form->addVariable($msg, 'confirm', 'description', false);

if ($form_submit == _("Delete")) {
    if ($form->validate($vars)) {
        $form->getInfo($vars, $info);
        $delete = $news->deleteChannel($info);
        if (is_a($delete, 'PEAR_Error')) {
            $notification->push(sprintf(_("There was an error deleting the channel: %s"), $delete->getMessage()), 'horde.error');
        } else {
            $notification->push(_("The channel has been deleted."), 'horde.success');
            Horde::applicationUrl('channels/index.php', true)->redirect();
        }
    }
} elseif (!empty($form_submit)) {
    $notification->push(_("Channel has not been deleted."), 'horde.message');
    Horde::applicationUrl('channels/index.php', true)->redirect();
}

$template = new Horde_Template();

// Buffer the main form and send to the template
Horde::startBuffer();
$form->renderActive(null, $vars, 'delete.php', 'post');
$template->set('main', Horde::endBuffer());

$template->set('menu', Jonah::getMenu('string'));

// Buffer the notifications and send to the template
Horde::startBuffer();
$GLOBALS['notification']->notify(array('listeners' => 'status'));
$template->set('notify', Horde::endBuffer());

require JONAH_TEMPLATES . '/common-header.inc';
echo $template->fetch(JONAH_TEMPLATES . '/main/main.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
