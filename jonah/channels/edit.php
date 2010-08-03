<?php
/**
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * $Horde: jonah/channels/edit.php,v 1.37 2009/11/24 04:15:37 chuck Exp $
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Marko Djukic <marko@oblo.com>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
$jonah = Horde_Registry::appInit('jonah');
require_once JONAH_BASE . '/lib/Forms/Feed.php';
require_once 'Horde/Form/Renderer.php';

$news = Jonah_News::factory();

/* Set up the form variables and the form. */
$vars = Horde_Variables::getDefaultVariables();
$form = new FeedForm($vars);

/* Set up some variables. */
$formname = $vars->get('formname');
$channel_id = $vars->get('channel_id');

/* Form not yet submitted and is being edited. */
if (!$formname && $channel_id) {
    $vars = new Horde_Variables($news->getChannel($channel_id));
}

/* Get the vars for channel type. */
$channel_type = $vars->get('channel_type');
$old_channel_type = $vars->get('old_channel_type');
$changed_type = false;

/* Check permissions and deny if not allowed. */
if (!Jonah::checkPermissions(Jonah::typeToPermName($channel_type), Horde_Perms::EDIT, $channel_id)) {
    $notification->push(_("You are not authorised for this action."), 'horde.warning');
    Horde::authenticationFailureRedirect();
}

/* If this is null then new form, so set both to default. */
if (is_null($channel_type)) {
    $channel_type = Jonah_News::getDefaultType();
    $old_channel_type = $channel_type;
}

/* Check if channel type has been changed and notify. */
if ($channel_type != $old_channel_type && $formname) {
    $changed_type = true;
    $notification->push(_("Feed type changed."), 'horde.message');
}
$vars->set('old_channel_type', $channel_type);

/* Output the extra fields required for this channel type. */
$form->setExtraFields($channel_type, $channel_id);

if ($formname && !$changed_type) {
    if ($form->validate($vars)) {
        $form->getInfo($vars, $info);
        $save = $news->saveChannel($info);
        if (is_a($save, 'PEAR_Error')) {
            $notification->push(sprintf(_("There was an error saving the feed: %s"), $save->getMessage()), 'horde.error');
        } else {
            $notification->push(sprintf(_("The feed \"%s\" has been saved."), $info['channel_name']), 'horde.success');
            if ($channel_type == Jonah::AGGREGATED_CHANNEL) {
                $notification->push(_("You can now edit the sub-feeds."), 'horde.message');
            } else {
                Horde::applicationUrl('channels/index.php', true)->redirect();
            }
        }
    }
}

$renderer = new Horde_Form_Renderer();
Horde::startBuffer();
$form->renderActive($renderer, $vars, 'edit.php', 'post');
$main = Horde::endBuffer();

$template = new Horde_Template();
$template->set('main', $main);
$template->set('menu', Jonah::getMenu('string'));

// Buffer the notifications and send to the template
Horde::startBuffer();
$GLOBALS['notification']->notify(array('listeners' => 'status'));
$template->set('notify', Horde::endBuffer());

$title = $form->getTitle();
require JONAH_TEMPLATES . '/common-header.inc';
echo $template->fetch(JONAH_TEMPLATES . '/main/main.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
