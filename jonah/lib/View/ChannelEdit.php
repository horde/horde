<?php
/**
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Marko Djukic <marko@oblo.com>
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Jonah
 */
class Jonah_View_ChannelEdit extends Jonah_View_Base
{
    /**
     * expects
     *   $notification
     *   $registry
     *   $vars
     */
    public function run()
    {
        extract($this->_params, EXTR_REFS);

        $form = new Jonah_Form_Feed($vars);

        /* Set up some variables. */
        $formname = $vars->get('formname');
        $channel_id = $vars->get('channel_id');

        /* Form not yet submitted and is being edited. */
        if (!$formname && $channel_id) {
            $vars = new Horde_Variables(Jonah::getFeed());
        }

        /* Get the vars for channel type. */
        $channel_type = $vars->get('channel_type');

        /* Output the extra fields required for this channel type. */
        $form->setExtraFields($channel_id);
        if ($formname && empty($changed_type)) {
            if ($form->validate($vars)) {
                $form->getInfo($vars, $info);
                if (empty($channel_id)) {
                    try {
                        $save = Jonah::addFeed($info);
                        $notification->push(sprintf(_("The feed \"%s\" has been added."), $info['channel_name']), 'horde.success');
                        Horde::url('channels')->redirect();
                        exit;
                    } catch (Exception $e) {
                        $notification->push(sprintf(_("There was an error saving the feed: %s"), $e->getMessage()), 'horde.error');
                    }
                } else {
                    try {
                        $save = Jonah::updateFeed();
                        $notification->push(sprintf(_("The feed \"%s\" has been updated."), $info['channel_name']), 'horde.success');
                        Horde::url('channels')->redirect();
                        exit;
                    } catch (Exception $e) {
                        $notification->push(sprintf(_("There was an error saving the feed: %s"), $e->getMessage()), 'horde.error');
                    }
                }
            }
        }

        $renderer = new Horde_Form_Renderer();
        $title = $form->getTitle();
        require $registry->get('templates', 'horde') . '/common-header.inc';
        require JONAH_TEMPLATES . '/menu.inc';
        $form->renderActive($renderer, $vars, 'edit.php', 'post');
        require $registry->get('templates', 'horde') . '/common-footer.inc';
    }

}
