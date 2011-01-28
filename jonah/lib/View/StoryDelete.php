<?php
/**
 * Jonah_View_StoryDelete:: handle story deletion
 *
 * Copyright 2003-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Marko Djukic <marko@oblo.com>
 * @package Jonah
 */
class Jonah_View_StoryDelete extends Jonah_View_Base
{
    public function run()
    {
        extract($this->_params, EXTR_REFS);

        $form_submit = $vars->get('submitbutton');
        $channel_id = $vars->get('channel_id');
        $story_id = $vars->get('id');

        /* Driver */
        $driver = $GLOBALS['injector']->getInstance('Jonah_Driver');

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
        if (!Jonah::checkPermissions(Jonah::typeToPermName($channel['channel_type']), Horde_Perms::DELETE, $channel_id)) {
            $notification->push(_("You are not authorised for this action."), 'horde.warning');
            $registry->authenticateFailure();
        }

        try {
            $story = $driver->getStory($channel_id, $story_id);
        } catch (Exception $e) {
            $notification->push(_("No valid story requested for deletion."), 'horde.message');
            Horde::url('channels/index.php', true)->redirect();
            exit;
        }

        /* If not yet submitted set up the form vars from the fetched story. */
        if (empty($form_submit)) {
            $vars = new Horde_Variables($story);
        }

        $title = sprintf(_("Delete News Story \"%s\"?"), $vars->get('title'));

        $form = new Horde_Form($vars, $title);
        $form->setButtons(array(_("Delete"), _("Do not delete")));
        $form->addHidden('', 'channel_id', 'int', true, true);
        $form->addHidden('', 'id', 'int', true, true);
        $form->addVariable(_("Really delete this News Story?"), 'confirm', 'description', false);

        if ($form_submit == _("Delete")) {
            if ($form->validate($vars)) {
                $form->getInfo($vars, $info);
                try {
                    $delete = $driver->deleteStory($info['channel_id'], $info['id']);
                    $notification->push(_("The story has been deleted."), 'horde.success');
                    Horde::url('stories/index.php', true)->add('channel_id', $channel_id)->setRaw(true)->redirect();
                    exit;
                } catch (Exception $e) {
                    $notification->push(sprintf(_("There was an error deleting the story: %s"), $e->getMessage()), 'horde.error');
                }
            }
        } elseif (!empty($form_submit)) {
            $notification->push(_("Story has not been deleted."), 'horde.message');
            $url = Horde::url('stories/index.php', true)->add('channel_id', $channel_id)->setRaw(true);
            Horde::url('stories/index.php', true)->add('channel_id', $channel_id)->setRaw(true)->redirect();
            exit;
        }
        require $registry->get('templates', 'horde') . '/common-header.inc';
        require JONAH_TEMPLATES . '/menu.inc';
        $form->renderActive(null, $vars, 'delete.php', 'post');
        require $registry->get('templates', 'horde') . '/common-footer.inc';
    }

}