<?php
/**
 * Copyright 2009-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2009-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 */

/**
 * Interface to the Horde_Content tagger.
 *
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @category  Horde
 * @copyright 2009-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 */
abstract class Horde_Core_Tagger
{
    /**
     * Cache of type name => ids from Content
     *
     * @var array
     */
    protected $_type_ids = array();

    /**
     * Application this tagger is for.
     *
     * @var string
     */
    protected $_app;

    /**
     * The types handled by this tagger. The first entry in the array is
     * taken as the default type if the type parameter is not specified in
     * tagging methods.
     *
     * @var array
     */
    protected $_types;

    /**
     * The tagger
     *
     * @var Content_Tagger
     */
    protected $_tagger;

    /**
     * Constructor.
     */
    public function __construct()
    {
        global $injector;

        $cache = $injector->getInstance('Horde_Cache');
        $key = $this->_app . '.tagger.type_ids';
        $ids = $cache->get($key, 360);

        if ($ids) {
            $this->_type_ids = unserialize($ids);
        } else {
            $types = $injector->getInstance('Content_Types_Manager')
                ->ensureTypes($this->_types);
            foreach ($this->_types as $k => $v) {
                $this->_type_ids[$v] = intval($types[$k]);
            }
            $cache->set($key, serialize($this->_type_ids));
        }

        $this->_tagger = $injector->getInstance('Content_Tagger');
    }

    /**
     * Split a tag string into an array of tags.
     *
     * Overides Content_Tagger::split to only split on commas.
     *
     * @param string $tags  A string of tags to be split.
     *
     * @return array  The split tags.
     */
    public function split($tags)
    {
        $split_tags = explode(',', $tags);
        return array_map('trim', $split_tags);
    }

    /**
     * Tags an object with any number of tags.
     *
     * @param string $localId       The identifier of the object.
     * @param mixed $tags           Either a single tag string or an array of
     *                              tags.
     * @param string $owner         The tag owner (should normally be the owner
     *                              of the resource).
     * @param string $content_type  The type of object we are tagging.
     *
     * @throws Horde_Exception
     */
    public function tag($localId, $tags, $owner, $content_type = null)
    {
        if (empty($content_type)) {
            $content_type = $this->_types[0];
        }

        // If we don't have an array - split the string.
        if (!is_array($tags)) {
            $tags = $this->split($tags);
        }

        try {
            $this->_tagger->tag(
                    $owner,
                    array('object' => $localId,
                          'type' => $this->_type_ids[$content_type]),
                    $tags);
        } catch (Content_Exception $e) {
            throw new Horde_Exception($e);
        }
    }

    /**
     * Retrieves the tags on given object(s).
     *
     * @param mixed  $localId  Either the identifier of the object or
     *                         an array of identifiers.
     * @param string $type     The type of object $localId represents.
     *
     * @return array  A tag_id => tag_name hash, possibly wrapped in a localid
     *                hash.
     * @throws Horde_Exception
     */
    public function getTags($localId, $type = null)
    {
        if (empty($type)) {
            $type = $this->_types[0];
        }
        if (is_array($localId)) {
            try {
                return $this->_tagger->getTagsByObjects($localId, $type);
            } catch (Content_Exception $e) {
                throw new Horde_Exception($e);
            }
        }

        try {
            return $this->_tagger->getTags(
                array(
                    'objectId' => array(
                        'object' => $localId,
                        'type' => $this->_type_ids[$type]
                    )
                )
            );
        } catch (Content_Exception $e) {
            throw new Horde_Exception($e);
        }
    }

    /**
     * Removes a tag from an object.
     *
     * Removes *all* tags - regardless of the user that added the tag.
     *
     * @param string $localId       The object identifier.
     * @param mixed $tags           Either a tag_id, tag_name or an array of
     *                              ids or names to remove.
     * @param string $content_type  The type of object that $localId
     *                              represents.
     *
     * @throws Horde_Exception
     */
    public function untag($localId, $tags, $content_type = null)
    {
        if (empty($content_type)) {
            $content_type = $this->_types[0];
        }

        try {
            $this->_tagger->removeTagFromObject(
                array(
                    'object' => $localId,
                    'type' => $this->_type_ids[$content_type]),
                    $tags
                );
        } catch (Content_Exception $e) {
            throw new Horde_Exception($e);
        }
    }

    /**
     * Tags the given resource with *only* the tags provided, removing any
     * tags that are already present but not in the list.
     *
     * @param string $localId       The identifier for the object.
     * @param mixed $tags           Either a tag_id, tag_name, or array of
     *                              tag_ids.
     * @param string $owner         The tag owner - should normally be the
     *                              resource owner.
     * @param string $content_type  The type of object that $localId
     *                              represents.
     */
    public function replaceTags($localId, $tags, $owner, $content_type = null)
    {
        if (empty($content_type)) {
            $content_type = $this->_types[0];
        }

        // First get a list of existing tags.
        $existing_tags = $this->getTags($localId, $content_type);

        // If we don't have an array - split the string.
        if (!is_array($tags)) {
            $tags = $this->split($tags);
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
     * Returns tags belonging to the current user beginning with $token.
     *
     * Used for autocomplete code.
     *
     * @param string $token  The token to match the start of the tag with.
     *
     * @return array  A tag_id => tag_name hash
     * @throws Horde_Exception
     */
    public function listTags($token)
    {
        try {
            return $this->_tagger->getTags(
                array(
                    'q' => $token,
                    'userId' => $GLOBALS['registry']->getAuth())
                );
        } catch (Content_Exception $e) {
            throw new Horde_Exception($e);
        }
    }

    /**
     * Returns the data needed to build a tag cloud based on the passed in
     * user's tag data set.
     *
     * @param string $user    The user whose tags should be included.
     * @param integer $limit  The maximum number of tags to include.
     * @param boolean $all    Return all tags, not just tags for the current
     *                        types.
     *
     * @return array  An array of hashes, each containing tag_id, tag_name,
     *                and count.
     * @throws Horde_Exception
     */
    public function getCloud($user, $limit = 5, $all = false)
    {
        try {
            return $this->_tagger->getTagCloud(
                array(
                    'userId' => $user,
                    'limit' => $limit,
                    'typeId' => ($all ? null : $this->_types)
                )
            );
        } catch (Content_Exception $e) {
            throw new Horde_Exception($e);
        }
    }

    /**
     * Get the number of times tags are used within a specific set of objects
     * basically a tag cloud, restricted to objects of a specific type.
     *
     * @param array $ids     An array of local object ids.
     * @param integer $type  The type identifier of of the objects.
     *
     * @return array  An array of tag_ids => counts.
     */
    public function getTagCountsByObjects(array $ids, $type = null)
    {
        if (empty($ids)) {
            return array();
        }

        if (!isset($type)) {
            $type = (int)$this->_type_ids[$this->_types[0]];
        }

        return $this->_tagger->getTagCloud(array(
            'objectId' => $ids,
            'typeId' => $type
        ));
    }

    /**
     * Retrieve a set of tags that are related to the specifed set. A tag is
     * related if resources tagged with the specified set are also tagged with
     * the tag being considered. Used to "browse" tagged resources.
     *
     *
     * @param array $tags   An array of tags to check. This would represent
     *                      the current "directory" of tags while browsing.
     * @param string $user  The resource must be owned by this user.
     *
     * @return array  A tag_id => tag_name hash.
     * @throws Horde_Exception
     */
    public function browseTags($tags, $user)
    {
        if (empty($tags)) {
            return $this->_tagger->getTags(array(
                'userId' => $user,
                'typeId' => $this->_type_ids)
            );
        }
        try {
            $tags = array_values($this->_tagger->getTagIds($tags));
            $all_tags = array();
            foreach ($this->_type_ids as $tid) {
                $iTags = $this->_tagger->browseTags($tags, $tid, $user);
                foreach ($iTags as $id => $name) {
                    if (empty($all_tags[$id])) {
                        $all_tags[$id] = $name;
                    }
                }
            }
        } catch (Content_Exception $e) {
            throw new Horde_Exception($e);
        }

        return $all_tags;
    }

    /**
     * Get tag ids for the specified tag names.
     *
     * @param string|array $tags  Either a tag_name or array of tag_names.
     *
     * @return array  A tag_id => tag_name hash.
     * @throws Horde_Exception
     */
    public function getTagIds($tags)
    {
        try {
            return $this->_tagger->getTagIds($tags);
        } catch (Content_Exception $e) {
            throw new Horde_Exception($e);
        }
    }

    /**
     * Returns cloud-like information, but only for a specified set of tags.
     *
     * @param array $tags     An array of either tag names or ids.
     * @param integer $limit  Limit results to this many.
     * @param string $type    The type of resource.
     * @param string $user    Restrict results to those tagged by $user.
     *
     * @return array  An array of hashes, tag_id, tag_name, and count.
     * @throws Horde_Exception
     */
    public function getTagInfo(
        $tags = null, $limit = 500, $type = null, $user = null
    )
    {
        $filter = array(
            'typeId' => empty($type) ? array_values($this->_type_ids) : $this->_type_ids[$type],
            'tagIds' => $tags,
            'limit' => $limit,
            'userId' => $user
        );

        try {
            return $this->_tagger->getTagCloud($filter);
        } catch (Content_Exception $e) {
            throw new Horde_Exception($e);
        }
    }

    /**
     * Searches for resources that are tagged with all of the requested tags.
     *
     * @param array $tags    Either a tag_id, tag_name or an array.
     * @param array $filter  Array of filter parameters.
     *
     * @return array  A hash of results.
     */
    abstract public function search($tags, $filter = array());

}
