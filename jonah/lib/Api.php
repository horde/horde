<?php
/**
 * Jonah external API interface.
 *
 * This file defines Jonah's external API interface. Other
 * applications can interact with Jonah through this API.
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Jonah
 */
class Jonah_Api extends Horde_Registry_Api
{
    /**
     * Get a list of stored channels.
     *
     * @return array An array of channels
     */
    public function listFeeds()
    {
        return $GLOBALS['injector']->getInstance('Jonah_Driver')->getChannels();
    }

    /**
     * Return the requested stories
     *
     * @param integer $channel_id   The channel to get the stories from.
     * @param array   $filter       Additional, optional filters.
     *   <pre>
     *     max_stories  The maximum number of stories to get.
     *     start_at     The story number to start retrieving.
     *     order        How to order the results.
     *   </pre>
     *
     * @return array An array of story information
     */
    public function stories($channel_id, $filter = array())
    {
        $filter = new Horde_Support_Array($filter);

        $stories = $GLOBALS['injector']
            ->getInstance('Jonah_Driver')
            ->getStories(
                array(
                    'channel_id' => $channel_id,
                    'limit' => $filter->get('max_stories', 10),
                    'startnumber' => $filter->get('start_at', 0),
                    'published' => true,
                ),
                $order
        );

        foreach (array_keys($stories) as $s) {
            if (empty($stories[$s]['body_type']) || $stories[$s]['body_type'] == 'text') {
                $stories[$s]['body_html'] = $GLOBALS['injector']->getInstance('Horde_Text_Filter')->filter($stories[$s]['story_body'], 'text2html', array('parselevel' => Horde_Text_Filter_Text2html::MICRO));
            } else {
                $stories[$s]['body_html'] = $stories[$s]['body'];
            }
        }

        return $stories;
    }

    /**
     * Fetches a story from a requested channel.
     *
     * @param integer $channel_id  The channel id to fetch.
     * @param integer $story_id    The story id to fetch.
     * @param boolean $read        Whether to update the read count.
     *
     * @return array  An array of story data
     */
    public function story($channel_id, $story_id, $read = true)
    {
        $story = $GLOBALS['injector']->getInstance('Jonah_Driver')->getStory($channel_id, $story_id, $read);
        if (empty($story['body_type']) || $story['body_type'] == 'text') {
            $story['body_html'] = $GLOBALS['injector']->getInstance('Horde_Text_Filter')->filter($story['body'], 'text2html', array('parselevel' => Horde_Text_Filter_Text2html::MICRO));
        } else {
            $story['body_html'] = $story['body'];
        }

        return $story;
    }

    /**
     * Callback for comment API
     *
     * @param integer $id  Internal data identifier
     *
     * @return mixed  Name of object on success | false on failure
     */
    public function commentCallback($story_id)
    {
        if (!$GLOBALS['conf']['comments']['allow']) {
            return false;
        }
        $story = $GLOBALS['injector']->getInstance('Jonah_Driver')->getStory(null, $story_id);

        return $story['title'];
    }

    /**
     * Check if comments are allowed.
     *
     * @return boolean
     */
    public function hasComments()
    {
        return $GLOBALS['conf']['comments']['allow'];
    }

    /**
     * Retrieve the list of used tag_names, tag_ids and the total number
     * of resources that are linked to that tag.
     *
     * @param array $tags  An optional array of tag_ids. If omitted, all tags
     *                     will be included.
     *
     * @param array $channel_id  An optional array of channel_ids.
     *
     * @return array  An array containing tag_name, and total
     */
    public function listTagInfo($tags = array(), $channel_id = null)
    {
        return $GLOBALS['injector']->getInstance('Jonah_Driver')->listTagInfo($tags, $channel_id);
    }

    /**
     * Return a set of tag_ids, given the tag name
     *
     * @param array $names  An array of names to search for
     *
     * @return Array An array of tag_name => tag_ids
     */
    public function getTagIds($names)
    {
        return $GLOBALS['injector']->getInstance('Jonah_Driver')->getTagIds($names);
    }

    /**
     * Searches internal channels for stories tagged with all requested tags.
     * Returns an application-agnostic array (useful for when doing a tag search
     * across multiple applications).
     *
     *
     * The 'raw' story array can be returned instead by setting $raw = true.
     *
     * @param array $names       An array of tag_names to search for
     *                           (AND'd together).
     * @param array $filter      An array of optional filter parameters.
     *   <pre>
     *     max       The maximum number of stories to return.
     *     from      The number of the story to start with.
     *     channel_id  An array of channel_ids to limit the search to.
     *     order     How to order the results (a Jonah::ORDER_* constant)
     *  </pre>
     * @param boolean $raw       Return the raw story data?
     *
     * @return  An array of results with the following structure:
     *    <pre>
     *      'title'    - The title for this resource.
     *      'desc'     - A terse description of this resource.
     *      'view_url' - The URL to view this resource.
     *      'app'      - The Horde application this resource belongs to.
     *    </pre>
     */
    public function searchTags($names, $filter = array(), $raw = false)
    {
        global $registry;

        // @TODO: Refactor when moving tag to content_tagger
        $filter = new Horde_Support_Array($filter);
        $results = $GLOBALS['injector']
            ->getInstance('Jonah_Driver')
            ->searchTags($names, $filter->max, $filter->from, $filter->channel_id, $filter->order);

        $return = array();
        if ($raw) {
            // Requesting the raw story information as returned from searchTags,
            // but add some additional information that external apps might
            // find useful.
            $comments = $GLOBALS['conf']['comments']['allow'] && $registry->hasMethod('forums/numMessages');
            foreach ($results as $story) {
                if (empty($story['body_type']) || $story['body_type'] == 'text') {
                    $story['body_html'] = $GLOBALS['injector']->getInstance('Horde_Text_Filter')->filter($story['body'], 'text2html', array('parselevel' => Horde_Text_Filter_Text2html::MICRO));
                } else {
                    $story['body_html'] = $story['body'];
                }

                if ($comments) {
                    $story['num_comments'] = $registry->call('forums/numMessages',
                                                             array($story['id'],
                                                                   $registry->getApp()));
                }

                $return[$story['id']] = $story;
            }
        } else {
            foreach($results as $story) {
                if (!empty($story)) {
                    $return[] = array('title' => $story['title'],
                                                        'desc' => $story['desc'],
                                                        'view_url' => $story['link'],
                                                        'app' => 'jonah');
                }
            }
        }

        return $return;
    }

    /**
     * Get the count of stories in the specified channel
     *
     * @param int $channel_id
     * @return mixed  The story count
     */
    public function storyCount($channel_id)
    {
        return $GLOBALS['injector']->getInstance('Jonah_Driver')->getStoryCount($channel_id);
    }

}
