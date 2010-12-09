<?php
/**
 * Turba_View_TagSearchList:: A view to handle displaying a list of stories
 * matching a requested tag filter.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
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

        /* Use the passed channel_id, or use all public channels */
        if (!is_null($channel_id)) {
            $channel = $driver->getChannel($channel_id);
            if (!Jonah::checkPermissions(Jonah::typeToPermName($channel['channel_type']), Horde_Perms::SHOW, $channel_id)) {
                $notification->push(_("You are not authorised for this action."), 'horde.warning');
                $registry->authenticateFailure();
            }
            $channel_ids = array($channel_id);
        } else {
            $channel_ids = array();
            $channels = $driver->getChannels();
            foreach ($channels as $ch) {
                if (Jonah::checkPermissions(Jonah::typeToPermName($ch['channel_type']), Horde_Perms::SHOW, $ch['channel_id'])) {
                    $channel_ids[] = $ch['channel_id'];
                }
            }
        }

        $tag_name = array_shift($driver->getTagNames(array($tag_id)));
        try {
            $stories = $driver->searchTagsById(array($tag_id), 10, 0, $channel_ids);
        } catch (Exception $e) {
            $notification->push(sprintf(_("Invalid channel requested. %s"), $e->getMessage()), 'horde.error');
            Horde::url('channels/index.php', true)->redirect();
            exit;
        }

        /* Do some state tests. */
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
            if (Jonah::checkPermissions(Jonah::typeToPermName(Jonah::INTERNAL_CHANNEL), Horde_Perms::EDIT, $channel_id)) {
                $url = Horde::url('stories/edit.php')->add(array('id' => $story['id'], 'channel_id' => $channel_id));
                $stories[$key]['edit_link'] = $url->link(array('title' => _("Edit story"))) . Horde::img('edit.png') . '</a>';
            }

            /* Delete story link. */
            if (Jonah::checkPermissions(Jonah::typeToPermName(Jonah::INTERNAL_CHANNEL), Horde_Perms::DELETE, $channel_id)) {
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
        $view->comments = $conf['comments']['allow'] && $registry->hasMethod('forums/numMessages') && $channel['channel_type'] == Jonah::INTERNAL_CHANNEL;
        require $registry->get('templates', 'horde') . '/common-header.inc';
        require JONAH_TEMPLATES . '/menu.inc';
        echo $view->render('index');
        require $registry->get('templates', 'horde') . '/common-footer.inc';
    }

}

