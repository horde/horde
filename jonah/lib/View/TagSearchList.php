<?php
/**
 * Turba_View_TagSearchList:: A view to handle displaying a list of stories
 * matching a requested tag filter.
 *
 * Copyright 2003-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Jonah
 */
class Jonah_View_TagSearchList extends Jonah_View_Base
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
        $driver = $GLOBALS['injector']->getInstance('Jonah_Driver');

        // Require a channel to be selected.
        if (is_null($channel_id)) {
            $channels = $driver->getChannels();
        } else {
            $channels = array($channel_id);
        }

        $stories = array();
        foreach ($channels as $channel) {
            if (!Jonah::checkPermissions('channels', Horde_Perms::SHOW, array($channel['channel_id']))) {
                $notification->push(_("You are not authorised for this action."), 'horde.warning');
                throw new Horde_Exception_AuthenticationFailure();
            }
            try {
                $criteria = array(
                    'tags' => array($tag),
                    'limit' => 10,
                    'channel_id' => $channel['channel_id']
                );
                $cstories = $driver->getStories($criteria);
            } catch (Exception $e) {
                $notification->push(sprintf(_("Invalid channel requested. %s"), $e->getMessage()), 'horde.error');
                Horde::url('channels/index.php', true)->redirect();
                exit;
            }
            $stories = array_merge($stories, $cstories);
        }

        if (empty($stories)) {
            $notification->push(_("No available stories."), 'horde.warning');
        }

        foreach ($stories as $key => $story) {
            /* Use the channel_id from the story hash since we might be dealing
            with more than one channel. */
            $channel_id = $story['channel_id'];

            if (!empty($stories[$key]['published'])) {
                $stories[$key]['published_date'] = strftime($prefs->getValue('date_format') . ', ' . ($prefs->getValue('twentyFour') ? '%H:%M' : '%I:%M%p'), $stories[$key]['published']);
            } else {
                $stories[$key]['published_date'] = '';
            }

            /* Default to no links. */
            $stories[$key]['pdf_link'] = '';
            $stories[$key]['edit_link'] = '';
            $stories[$key]['delete_link'] = '';
            $stories[$key]['view_link'] = Horde::url($story['link'])->link(array('title' => $story['description'])) . htmlspecialchars($story['title']) . '</a>';

            /* PDF link. */
            $url = Horde::url('stories/pdf.php')->add(array('id' => $story['id'], 'channel_id' => $channel_id));
            $stories[$key]['pdf_link'] = $url->link(array('title' => _("PDF version"))) . Horde::img('mime/pdf.png') . '</a>';

            /* Edit story link. */
            if (Jonah::checkPermissions('channels', Horde_Perms::EDIT, array($channel_id))) {
                $url = Horde::url('stories/edit.php')->add(array('id' => $story['id'], 'channel_id' => $channel_id));
                $stories[$key]['edit_link'] = $url->link(array('title' => _("Edit story"))) . Horde::img('edit.png') . '</a>';
            }

            /* Delete story link. */
            if (Jonah::checkPermissions('channels', Horde_Perms::DELETE, array($channel_id))) {
                $url = Horde::url('stories/delete.php')->add(array('id' => $story['id'], 'channel_id' => $channel_id));
                $stories[$key]['delete_link'] = $url->link(array('title' => _("Delete story"))) . Horde::img('delete.png') . '</a>';
            }

            /* Comment counter. */
            if ($conf['comments']['allow'] &&
                $registry->hasMethod('forums/numMessages')) {
                try {
                    $comments = $registry->call('forums/numMessages', array($stories[$key]['id'], 'jonah'));
                } catch (Exception $e) {}
                $stories[$key]['comments'] = $comments;
            }
        }

        /* Render page */
        //$title = $channel['channel_name'];
        $view = new Horde_View(array('templatePath' => JONAH_TEMPLATES . '/stories'));
        $view->stories = $stories;
        $view->read = true;
        $view->comments = $conf['comments']['allow'] && $registry->hasMethod('forums/numMessages');

        $GLOBALS['page_output']->header(array(
            'title' => $title
        ));
        $notification->notify(array('listeners' => 'status'));
        echo $view->render('index');
        $GLOBALS['page_output']->footer();
    }

}

