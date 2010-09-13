<?php
/**
 * View for handling deletion of channels.
 *
 * Copyright 2003 - 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Marko Djukic <marko@oblo.com>
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Jonah
 */
class Jonah_View_ChannelDelete extends Jonah_View_Base
{
    /**
     * Expects:
     *   $vars
     *   $registry
     *   $notification
     */
    public function run()
    {
        extract($this->_params, EXTR_REFS);

        /* Set up the form variables and the form. */
        $form_submit = $vars->get('submitbutton');
        $channel_id = $vars->get('channel_id');

        try {
            $channel = $GLOBALS['injector']->getInstance('Jonah_Driver')->getChannel($channel_id);
        } catch (Exception $e) {
            Horde::logMessage($e, 'ERR');
            $notification->push(_("Invalid channel specified for deletion."), 'horde.message');
            Horde::url('channels')->redirect();
            exit;
        }

        /* If not yet submitted set up the form vars from the fetched channel. */
        if (empty($form_submit)) {
            $vars = new Horde_Variables($channel);
        }

        /* Check permissions and deny if not allowed. */
        if (!Jonah::checkPermissions(Jonah::typeToPermName($channel['channel_type']), Horde_Perms::DELETE, $channel_id)) {
            $notification->push(_("You are not authorised for this action."), 'horde.warning');
            $registry->authenticationFailure();
        }

        $title = sprintf(_("Delete News Channel \"%s\"?"), $vars->get('channel_name'));
        $form = new Horde_Form($vars, $title);
        $form->setButtons(array(_("Delete"), _("Do not delete")));
        $form->addHidden('', 'channel_id', 'int', true, true);
        $msg = _("Really delete this News Channel? All stories created in this channel will be lost!");
        $form->addVariable($msg, 'confirm', 'description', false);
        if ($form_submit == _("Delete")) {
            if ($form->validate($vars)) {
                $form->getInfo($vars, $info);
                try {
                    $delete = $GLOBALS['injector']->getInstance('Jonah_Driver')->deleteChannel($info);
                    $notification->push(_("The channel has been deleted."), 'horde.success');
                    Horde::url('channels')->redirect();
                    exit;
                } catch (Exception $e) {
                    $notification->push(sprintf(_("There was an error deleting the channel: %s"), $e->getMessage()), 'horde.error');
                }
            }
        } elseif (!empty($form_submit)) {
            $notification->push(_("Channel has not been deleted."), 'horde.message');
            Horde::url('channels')->redirect();
            exit;
        }

        require JONAH_TEMPLATES . '/common-header.inc';
        require JONAH_TEMPLATES . '/menu.inc';
        $form->renderActive(null, $vars, Horde::selfUrl(), 'post');
        require $registry->get('templates', 'horde') . '/common-footer.inc';
    }

}
