<?php
/**
 * Turba_View_StoryList:: A view to handle displaying a list of stories in a
 * channel.
 *
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
class Jonah_View_StoryList extends Jonah_View_Base
{
    /**
     * expects
     *   $registry
     *   $notification
     *   $prefs
     *   $conf
     *   $channel_id
     */
    public function run()
    {
        extract($this->_params, EXTR_REFS);

        $channel = $GLOBALS['injector']->getInstance('Jonah_Driver')->getChannel($channel_id);
        if (!Jonah::checkPermissions(Jonah::typeToPermName($channel['channel_type']), Horde_Perms::EDIT, $channel_id)) {
            $notification->push(_("You are not authorised for this action."), 'horde.warning');
            $registry->authenticateFailure();
        }

        /* Check if a URL has been passed. */
        $url = Horde_Util::getFormData('url');
        if ($url) {
            $url = new Horde_Url($url);
        }

        try {
            $stories = $GLOBALS['injector']->getInstance('Jonah_Driver')->getStories(array('channel_id' => $channel_id));
        } catch (Exception $e) {
            $notification->push(sprintf(_("Invalid channel requested. %s"), $e->getMessage()), 'horde.error');
            Horde::url('channels/index.php', true)->redirect();
            exit;
        }

        /* Do some state tests. */
        if (empty($stories)) {
            $notification->push(_("No available stories."), 'horde.warning');
        }
        if (!empty($refresh)) {
            $notification->push(_("Channel refreshed."), 'horde.success');
        }
        if (!empty($url)) {
            $url->redirect();
            exit;
        }

        /* Get channel details, for title, etc. */
        $allow_delete = Jonah::checkPermissions(Jonah::typeToPermName($channel['channel_type']), Horde_Perms::DELETE, $channel_id);

        /* Build story specific fields. */
        foreach ($stories as $key => $story) {
            /* published is the publication/release date, updated is the last change date. */
            if (!empty($stories[$key]['published'])) {
                $stories[$key]['published_date'] = strftime($prefs->getValue('date_format') . ', ' . ($prefs->getValue('twentyFour') ? '%H:%M' : '%I:%M%p'), $stories[$key]['published']);
            } else {
                $stories[$key]['published_date'] = '';
            }

            /* Default to no links. */
            $stories[$key]['pdf_link'] = '';
            $stories[$key]['edit_link'] = '';
            $stories[$key]['delete_link'] = '';
            $stories[$key]['view_link'] = Horde::link(Horde::url($story['permalink']), $story['description']) . htmlspecialchars($story['title']) . '</a>';

            /* PDF link. */
            $url = Horde::url('stories/pdf.php')->add(array('id' => $story['id'], 'channel_id' => $channel_id));
            $stories[$key]['pdf_link'] = $url->link(array('title' => _("PDF version"))) . Horde::img('mime/pdf.png') . '</a>';

            /* Edit story link. */
            $url = Horde::url('stories/edit.php')->add(array('id' => $story['id'], 'channel_id' => $channel_id));
            $stories[$key]['edit_link'] = $url->link(array('title' => _("Edit story"))) . Horde::img('edit.png') . '</a>';

            /* Delete story link. */
            if ($allow_delete) {
                $url = Horde::url('stories/delete.php')->add(array('id' => $story['id'], 'channel_id' => $channel_id));
                $stories[$key]['delete_link'] = $url->link(array('title' => _("Delete story"))) . Horde::img('delete.png') . '</a>';
            }

            /* Comment counter. */
            if ($conf['comments']['allow'] &&
                $registry->hasMethod('forums/numMessages')) {
                $comments = $registry->call('forums/numMessages', array($stories[$key]['id'], 'jonah'));
                if (!is_a($comments, 'PEAR_Error')) {
                    $stories[$key]['comments'] = $comments;
                }
            }

        }

        /* Render page */
        $title = $channel['channel_name'];
        $view = new Horde_View(array('templatePath' => JONAH_TEMPLATES . '/stories'));
        $view->stories = $stories;
        $view->read = true;
        $view->comments = $conf['comments']['allow'] && $registry->hasMethod('forums/numMessages') && $channel['channel_type'] == Jonah::INTERNAL_CHANNEL;
        require JONAH_TEMPLATES . '/common-header.inc';
        require JONAH_TEMPLATES . '/menu.inc';
        echo $view->render('index');
        require $registry->get('templates', 'horde') . '/common-footer.inc';
    }

}