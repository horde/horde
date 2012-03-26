<?php
/**
 * Trean interface to the Horde_Content tagger
 *
 * Copyright 2009-2011 Horde LLC (http://www.horde.org/)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 *
 * @package Trean
 */
class Trean_Tagger
{
    /**
     * Local cache of the type name => ids from Content, so we don't have to
     * query for them each time.
     *
     * @var array
     */
    protected $_type_ids = array();

    /**
     * Constructor.
     *
     * @return Trean_Tagger
     */
    public function __construct()
    {
        // Remember the types to avoid having Content query them again.
        $key = 'trean.tagger.type_ids';
        $ids = $GLOBALS['injector']->getInstance('Horde_Cache')->get($key, 360);
        if ($ids) {
            $this->_type_ids = unserialize($ids);
        } else {
            $type_mgr = $GLOBALS['injector']->getInstance('Content_Types_Manager');
            $types = $type_mgr->ensureTypes(array('bookmark'));
            $this->_type_ids = array('bookmark' => (int)$types[0]);
            $GLOBALS['injector']->getInstance('Horde_Cache')->set($key, serialize($this->_type_ids));
        }
    }

    /**
     * Tags a trean object with any number of tags.
     *
     * @param string $localId       The identifier of the trean object.
     * @param mixed $tags           Either a single tag string or an array of
     *                              tags.
     * @param string $owner         The tag owner (should normally be the owner
     *                              of the resource).
     * @param string $content_type  The type of object we are tagging
     *                              (bookmark).
     *
     * @return void
     */
    public function tag($localId, $tags, $owner, $content_type = 'bookmark')
    {
        // If we don't have an array - split the string.
        if (!is_array($tags)) {
            $tags = $GLOBALS['injector']->getInstance('Content_Tagger')->splitTags($tags);
        }

        $GLOBALS['injector']->getInstance('Content_Tagger')->tag(
                $owner,
                array('object' => $localId,
                      'type' => $this->_type_ids[$content_type]),
                $tags);
    }

    /**
     * Retrieves the tags on given object(s).
     *
     * @param mixed  $localId  Either the identifier of the trean object or
     *                         an array of identifiers.
     * @param string $type     The type of object $localId represents.
     *
     * @return array A tag_id => tag_name hash, possibly wrapped in a localid hash.
     */
    public function getTags($localId, $type = 'bookmark')
    {
        if (is_array($localId)) {
            return $GLOBALS['injector']->getInstance('Content_Tagger')->getTagsByObjects($localId, $type);
        }

        return $GLOBALS['injector']->getInstance('Content_Tagger')->getTags(array('objectId' => array('object' => $localId, 'type' => $this->_type_ids[$type])));
    }

    /**
     * Removes a tag from a trean object.
     *
     * Removes *all* tags - regardless of the user that added the tag.
     *
     * @param string $localId       The trean object identifier.
     * @param mixed $tags           Either a tag_id, tag_name or an array of
     *                              ids or names to remove.
     * @param string $content_type  The type of object that $localId represents.
     */
    public function untag($localId, $tags, $content_type = 'bookmark')
    {
        $GLOBALS['injector']->getInstance('Content_Tagger')->removeTagFromObject(
            array('object' => $localId, 'type' => $this->_type_ids[$content_type]), $tags);
    }

    /**
     * Tags the given resource with *only* the tags provided, removing any
     * tags that are already present but not in the list.
     *
     * @param string $localId  The identifier for the trean object.
     * @param mixed $tags      Either a tag_id, tag_name, or array of tag_ids.
     * @param string $owner    The tag owner - should normally be the resource
     *                         owner.
     * @param $content_type    The type of object that $localId represents.
     */
    public function replaceTags($localId, $tags, $owner, $content_type = 'bookmark')
    {
        // First get a list of existing tags.
        $existing_tags = $this->getTags($localId, $content_type);

        // If we don't have an array - split the string.
        if (!is_array($tags)) {
            $tags = $GLOBALS['injector']->getInstance('Content_Tagger')->splitTags($tags);
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
     *                       - user (array) - only include objects owned by
     *                         these users.
     *
     * @return  A hash of 'bookmark' that contains an array of bookmark ids
     */
    public function search($tags, $filter = array())
    {
        $args = array();

        /* Add the tags to the search */
        $args['tagId'] = $GLOBALS['injector']
            ->getInstance('Content_Tagger')
            ->getTagIds($tags);

        $args['typeId'] = $this->_type_ids['bookmark'];
        $results = $GLOBALS['injector']
            ->getInstance('Content_Tagger')
            ->getObjects($args);

        $results = array_values($results);
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
        return $GLOBALS['injector']->getInstance('Content_Tagger')->getTags(
                array('q' => $token, 'userId' => $GLOBALS['registry']->getAuth()));
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
        return $GLOBALS['injector']->getInstance('Content_Tagger')->getTagCloud(
                array('userId' => $user, 'limit' => $limit));
    }

    /**
     * Retrieve a set of tags that are related to the specifed set. A tag is
     * related if resources tagged with the specified set are also tagged with
     * the tag being considered. Used to "browse" tagged resources.
     *
     * @param array $tags   An array of tags to check. This would represent the
     *                      current "directory" of tags while browsing.
     * @param string $user  The resource must be owned by this user.
     *
     * @return array  An tag_id => tag_name hash
     */
    public function browseTags($tags, $user)
    {
        if (empty($tags)) {
            return $GLOBALS['injector']
                ->getInstance('Content_Tagger')
                ->getTags(array(
                    'userId' => $user,
                    'typeId' => $this->_type_ids['bookmark']));
        }
        try {
            $tags = array_values($GLOBALS['injector']->getInstance('Content_Tagger')->getTagIds($tags));
            return $GLOBALS['injector']
                ->getInstance('Content_Tagger')
                ->browseTags($tags, $this->_type_ids['bookmark'], $user);
        } catch (Content_Exception $e) {
            throw new Trean_Exception($e);
        }
    }

    /**
     * Get tag ids for the specified tag names.
     *
     * @param string|array $tags  Either a tag_name or array of tag_names.
     *
     * @return array  A tag_id => tag_name hash.
     * @throws Trean_Exception
     */
    public function getTagIds($tags)
    {
        try {
            return $GLOBALS['injector']
                ->getInstance('Content_Tagger')
                ->getTagIds($tags);
        } catch (Content_Exception $e) {
            throw new Trean_Exception($e);
        }
    }

}
