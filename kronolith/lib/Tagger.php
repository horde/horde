<?php
/**
 * Kronolith interface to the Horde_Content tagger
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 *
 * @package Kronolith
 */
class Kronolith_Tagger
{
    /**
     * Local cache of the type name => ids from Content, so we don't have to
     * query for them each time.
     *
     * @var array
     */
    protected $_type_ids = array();

    /**
     * @var Content_Tagger
     */
    protected $_tagger;

    /**
     * Constructor.
     *
     * @return Kronolith_Tagger
     */
    public function __construct()
    {
        // Remember the types to avoid having Content query them again.
        $type_mgr = $GLOBALS['injector']->getInstance('Content_Types_Manager');
        $types = $type_mgr->ensureTypes(array('calendar', 'event'));
        $this->_type_ids = array('calendar' => (int)$types[0],
                                 'event' => (int)$types[1]);

        // Cache the tagger statically
        $this->_tagger = $GLOBALS['injector']->getInstance('Content_Tagger');
    }

    /**
     * Tags a kronolith object with any number of tags.
     *
     * @param string $localId       The identifier of the kronolith object.
     * @param mixed $tags           Either a single tag string or an array of
     *                              tags.
     * @param string $owner         The tag owner (should normally be the owner
     *                              of the resource).
     * @param string $content_type  The type of object we are tagging
     *                              (event/calendar).
     *
     * @return void
     */
    public function tag($localId, $tags, $owner, $content_type = 'event')
    {
        // If we don't have an array - split the string.
        if (!is_array($tags)) {
            $tags = $this->_tagger->splitTags($tags);
        }

        $this->_tagger->tag($owner,
                            array('object' => $localId,
                                  'type' => $this->_type_ids[$content_type]),
                            $tags);
    }

    /**
     * Retrieves the tags on given object(s).
     *
     * @param string $localId  The identifier of the kronolith object.
     * @param string $type     The type of object $localId represents.
     *
     * @return a tag_id => tag_name hash.
     */
    public function getTags($localId, $type = 'event')
    {
        if (!is_array($localId)) {
            $localId = array($localId);
        }
        $tags = array();
        foreach ($localId as $id) {
            $tags = $tags + $this->_tagger->getTags(array('objectId' => array('object' => $id, 'type' => $type)));
        }

        return $tags;
    }

    /**
     * Removes a tag from a kronolith object.
     *
     * Removes *all* tags - regardless of the user that added the tag.
     *
     * @param string $localId       The kronolith object identifier.
     * @param mixed $tags           Either a tag_id, tag_name or an array of
     *                              ids or names to remove.
     * @param string $content_type  The type of object that $localId represents.
     */
    public function untag($localId, $tags, $content_type = 'event')
    {
        $this->_tagger->removeTagFromObject(
            array('object' => $localId, 'type' => $this->_type_ids[$content_type]), $tags);
    }

    /**
     * Tags the given resource with *only* the tags provided, removing any
     * tags that are already present but not in the list.
     *
     * @param string $localId  The identifier for the kronolith object.
     * @param mixed $tags      Either a tag_id, tag_name, or array of tag_ids.
     * @param string $owner    The tag owner - should normally be the resource
     *                         owner.
     * @param $content_type    The type of object that $localId represents.
     */
    public function replaceTags($localId, $tags, $owner, $content_type = 'event')
    {
        // First get a list of existing tags.
        $existing_tags = $this->getTags($localId, $content_type);

        // If we don't have an array - split the string.
        if (!is_array($tags)) {
            $tags = $this->_tagger->splitTags($tags);
        }
        $remove = array();
        foreach ($existing_tags as $tag_id => $existing_tag) {
            $found = false;
            foreach ($tags as $tag_text) {
                if ($existing_tag == $tag_text) {
                    $found = true;
                    break;
                }
            }
            // Remove any tags that were not found in the passed in list.
            if (!$found) {
                $remove[] = $tag_id;
            }
        }

        $this->untag($localId, $remove, $content_type);
        $add = array();
        foreach ($tags as $tag_text) {
            $found = false;
            foreach ($existing_tags as $existing_tag) {
                if ($tag_text == $existing_tag) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $add[] = $tag_text;
            }
        }

        $this->tag($localId, $add, $owner, $content_type);
    }

    /**
     * Searches for resources that are tagged with all of the requested tags.
     *
     * @param array $tags    Either a tag_id, tag_name or an array.
     * @param array $filter  Array of filter parameters.
     *                       - type (string) - only return either events or
     *                         calendars, not both.
     *                       - user (array) - only include objects owned by
     *                         these users.
     *                       - calendar (array) - restrict to events contained
     *                         in these calendars.
     *
     * @return  A hash of 'calendars' and 'events' that each contain an array
     *          of calendar_ids and event_uids respectively.
     */
    public function search($tags, $filter = array())
    {
        $args = array();

        /* These filters are mutually exclusive */
        if (array_key_exists('user', $filter)) {
            /* semi-hack to see if we are querying for a system-owned share -
             * will need to get the list of all system owned shares and query
             * using a calendar filter instead of a user filter. */
            if (empty($filter['user'])) {
                // @TODO: No way to get only the system shares the current
                // user can see?
                $calendars = $GLOBALS['kronolith_shares']->listSystemShares();
                $args['calendarId'] = array();
                foreach ($calendars as $name => $share) {
                    if ($share->hasPermission(Horde_Auth::getAuth(), Horde_Perms::READ)) {
                        $args['calendarId'][] = $name;
                    }
                }
            } else {
                // Items owned by specific user(s)
                $args['userId'] = $filter['user'];
            }
        } elseif (!empty($filter['calendar'])) {
            // Only events located in specific calendar(s)
            if (!is_array($filter['calendar'])) {
                $filter['calendar'] = array($filter['calendar']);
            }
            $args['calendarId'] = $filter['calendar'];
        }

        /* Add the tags to the search */
        $args['tagId'] = $this->_tagger->getTagIds($tags);

        /* Restrict to events or calendars? */
        $cal_results = $event_results = array();
        if (empty($filter['type']) || $filter['type'] == 'calendar') {
            $args['typeId'] = $this->_type_ids['calendar'];
            $cal_results = $this->_tagger->getObjects($args);
        }

        if (empty($filter['type']) || $filter['type'] == 'event') {
            $args['typeId'] = $this->_type_ids['event'];
            $event_results = $this->_tagger->getObjects($args);
        }
        
        $results = array('calendars' => array_values($cal_results),
                         'events' => (!empty($args['calendarId']) && count($event_results))
                                     ? Kronolith::getDriver()->filterEventsByCalendar(array_values($event_results), $args['calendarId'])
                                     : array_values($event_results));

        return $results;
    }

    /**
     * Returns tags belonging to the current user beginning with $token.
     *
     * Used for autocomplete code.
     *
     * @param string $token  The token to match the start of the tag with.
     *
     * @return A tag_id => tag_name hash
     */
    public function listTags($token)
    {
        return $this->_tagger->getTags(array('q' => $token,
                                             'userId' => Horde_Auth::getAuth()));
    }

    /**
     * Returns the data needed to build a tag cloud based on the passed in
     * user's tag data set.
     *
     * @param string $user    The user whose tags should be included.
     * @param integer $limit  The maximum number of tags to include.
     *
     * @return An array of hashes, each containing tag_id, tag_name, and count.
     */
    public function getCloud($user, $limit = 5)
    {
        return $this->_tagger->getTagCloud(array('userId' => $user,
                                                 'limit' => $limit));
    }
}
