<?php
/**
 * Jonah_View_StoryView:: class to display an individual story.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Jonah
 */
class Jonah_View_StoryView extends Jonah_View_Base
{
    /**
     * Expects
     *   $registry
     *   $notification
     *   $browser
     *   $story_id
     *   $channel_id
     *
     */
    public function run()
    {
        extract($this->_params, EXTR_REFS);

        $driver = $GLOBALS['injector']->getInstance('Jonah_Driver');
        try {
            $story = $driver->getStory($channel_id, $story_id, !$browser->isRobot());
        } catch (Exception $e) {
            $notification->push(sprintf(_("Error fetching story: %s"), $e->getMessage()), 'horde.warning');
            require JONAH_TEMPLATES . '/common-header.inc';
            require JONAH_TEMPLATES . '/menu.inc';
            require $registry->get('templates', 'horde') . '/common-footer.inc';
            exit;
        }

        /* Grab tag related content for entire channel */
        $cloud = new Horde_Core_Ui_TagCloud();
        $allTags = $driver->listTagInfo(array(), $channel_id);
        foreach ($allTags as $tag_id => $taginfo) {
            $cloud->addElement($taginfo['tag_name'], Horde::url('results.php')->add(array('tag_id' => $tag_id, 'channel_id' => $channel_id)), $taginfo['total']);
        }

        /* Prepare the story's tags for display */
        // FIXME - need to actually use these.
        $tag_html = array();
        $tag_link = Horde::url('stories/results.php')->add('channel_id', $channel_id);
        foreach ($story['tags'] as $id => $tag) {
            $link = $tag_link->copy()->add('tag_id', $id);
            $tag_html[] = $link->link() . $tag . '</a>';
        }

        /* Filter and prepare story content. */
        if (!empty($story['body_type']) && $story['body_type'] == 'text') {
            $story['body'] = $GLOBALS['injector']->getInstance('Horde_Text_Filter')->filter($story['body'], 'text2html', array('parselevel' => Horde_Text_Filter_Text2html::MICRO));
        }

        // @TODO: Where is this used and what for?
        if (!empty($story['url'])) {
            $story['body'] .= Horde::link(Horde::externalUrl($story['url'])) . htmlspecialchars($story['url']) . '</a></p>';
        }

        if (empty($story['published_date'])) {
            $story['published_date'] = false;
        }

        $view = new Horde_View(array('templatePath' => array(JONAH_TEMPLATES . '/stories',
                                                             JONAH_TEMPLATES . '/stories/partial',
                                                             JONAH_TEMPLATES . '/stories/layout')));
        $view->addHelper('Tag');
        $view->addBuiltinHelpers();
        $view->tagcloud = $cloud->buildHTML();
        $view->story = $story;

        /* Insert link for sharing. */
        if ($conf['sharing']['allow']) {
            $url = Horde::url('stories/share.php')->add(array('id' => $story['id'], 'channel_id' => $channel_id));
            $view->sharelink = $url->link() . _("Share this story") . '</a>';
        }

        /* Insert comments. */
        if ($conf['comments']['allow']) {
            if (!$registry->hasMethod('forums/doComments')) {
                $err = 'User comments are enabled but the forums API is not available.';
                Horde::logMessage($err, 'ERR');
            } else {
                try {
                    $comments = $registry->call('forums/doComments', array('jonah', $story_id, 'commentCallback'));
                } catch (Exception $e) {
                    Horde::logMessage($e, 'ERR');
                    $comments = array('threads' => '', 'comments' => '');
                }
                $view->comments = $comments;
            }
        }

        require JONAH_TEMPLATES . '/common-header.inc';
        require JONAH_TEMPLATES . '/menu.inc';
        echo $view->render('view');
        require $registry->get('templates', 'horde') . '/common-footer.inc';
    }

}
