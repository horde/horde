<?php
/**
 * Jonah_View_StoryEdit:: to add/edit stories.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Marko Djukic <marko@oblo.com>
 * @package Jonah
 *
 * @TODO: This will be rewritten to NOT use Horde_Form since we want more
 *        control over the form and, especially, the RTE.
 */
class Jonah_View_StoryEdit extends Jonah_View_Base
{
    /**
     * $notification
     * $registry
     * $vars
     *
     */
    public function run()
    {
        extract($this->_params, EXTR_REFS);

        $driver = $GLOBALS['injector']->getInstance('Jonah_Driver');

        /* Set up the form variables. */
        $channel_id = $vars->get('channel_id');

        /* Fetch the channel details, needed for later and to check if valid
         * channel has been requested. */
        try {
            $channel = $driver->getChannel($channel_id);
        } catch (Exception $e) {
            $notification->push(sprintf(_("Story editing failed: %s"), $e->getMessage()), 'horde.error');
            Horde::url('channels/index.php', true)->redirect();
            exit;
        }

        /* Check permissions. */
        if (!Jonah::checkPermissions(Jonah::typeToPermName($channel['channel_type']), Horde_Perms::EDIT, $channel_id)) {
            $notification->push(_("You are not authorised for this action."), 'horde.warning');
            $registry->authenticateFailure();
        }

        /* Check if a story is being edited. */
        $story_id = $vars->get('id');
        if ($story_id && !$vars->get('formname')) {
            $story = $driver->getStory($channel_id, $story_id);
            $story['tags'] = implode(',', array_values($story['tags']));
            $vars = new Horde_Variables($story);
        }

        /* Set up the form. */
        $form = new Jonah_Form_Story($vars);
        if ($form->validate($vars)) {
            $form->getInfo($vars, $info);
            try {
                $result = $driver->saveStory($info);
                $notification->push(sprintf(_("The story \"%s\" has been saved."), $info['title']), 'horde.success');
                Horde::url('stories/index.php')->add('channel_id', $channel_id)->redirect();
                exit;
            } catch (Exception $e) {
                $notification->push(sprintf(_("There was an error saving the story: %s"), $e->getMessage()), 'horde.error');
            }
        }

        /* Needed javascript. */
        Horde::addScriptFile('open_calendar.js', 'horde');
        $title = $form->getTitle();
        require JONAH_TEMPLATES . '/common-header.inc';
        require JONAH_TEMPLATES . '/menu.inc';
        $form->renderActive($form->getRenderer(), $vars, 'edit.php', 'post');
        require $registry->get('templates', 'horde') . '/common-footer.inc';
    }

}