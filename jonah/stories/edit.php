<?php
/**
 * Script to add/edit stories.
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * $Horde: jonah/stories/edit.php,v 1.48 2009/11/24 04:15:38 chuck Exp $
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Marko Djukic <marko@oblo.com>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
$jonah = Horde_Registry::appInit('jonah');
require_once JONAH_BASE . '/lib/Forms/Story.php';
require_once 'Horde/Form/Action.php';
require_once 'Horde/Form/Renderer.php';

$news = Jonah_News::factory();

/* Set up the form variables. */
$vars = Horde_Variables::getDefaultVariables();
$channel_id = $vars->get('channel_id');

/* Fetch the channel details, needed for later and to check if valid
 * channel has been requested. */
$channel = $news->isChannelEditable($channel_id);
if (is_a($channel, 'PEAR_Error')) {
    $notification->push(sprintf(_("Story editing failed: %s"), $channel->getMessage()), 'horde.error');
    $url = Horde::applicationUrl('channels/index.php', true);
    header('Location: ' . $url);
    exit;
}

/* Check permissions. */
if (!Jonah::checkPermissions(Jonah::typeToPermName($channel['channel_type']), Horde_Perms::EDIT, $channel_id)) {
    $notification->push(_("You are not authorised for this action."), 'horde.warning');
    Horde::authenticationFailureRedirect();
}

/* Check if a story is being edited. */
$story_id = $vars->get('story_id');
if ($story_id && !$vars->get('formname')) {
    $story = $news->getStory($channel_id, $story_id);
    $story['story_tags'] = implode(',', array_values($story['story_tags']));
    $vars = new Horde_Variables($story);
}

/* Set up the form. */
$form = new StoryForm($vars);
if ($form->validate($vars)) {
    $form->getInfo($vars, $info);
    $result = $news->saveStory($info);
    if (is_a($result, 'PEAR_Error')) {
        $notification->push(sprintf(_("There was an error saving the story: %s"), $result->getMessage()), 'horde.error');
    } else {
        $notification->push(sprintf(_("The story \"%s\" has been saved."), $info['story_title']), 'horde.success');
        $url = Horde_Util::addParameter('stories/index.php', 'channel_id', $channel_id);
        header('Location: ' . Horde::applicationUrl($url, true));
        exit;
    }
}

/* Render the form. */
$template = new Horde_Template();

Horde::startBuffer();
$form->renderActive($form->getRenderer(), $vars, 'edit.php', 'post');
$template->set('main', Horde::endBuffer());

$template->set('menu', Jonah::getMenu('string'));

// Buffer the notifications and send to the template
Horde::startBuffer();
$GLOBALS['notification']->notify(array('listeners' => 'status'));
$template->set('notify', Horde::endBuffer());

$title = $form->getTitle();
require JONAH_TEMPLATES . '/common-header.inc';
echo $template->fetch(JONAH_TEMPLATES . '/main/main.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
