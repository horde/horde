<?php
/**
 * The Ansel_Tagger:: class provides logic for dealing with tags within Ansel
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  Ansel
 */
class Ansel_Tagger
{
    /**
     * Local cache of the type name => ids from Content, so we don't have to
     * query for them each time.
     *
     * @var array
     */
    protected $_type_ids = array();

    /**
     * Local reference to the tagger.
     *
     * @var Content_Tagger
     */
    protected $_tagger;

    /**
     * Constructor.
     *
     * @return Ansel_Tagger
     */
    public function __construct(Content_Tagger $tagger)
    {
        /* Remember the types to avoid having Content query them again. */
        $key = 'ansel.tagger.type_ids';
        $ids = $GLOBALS['injector']->getInstance('Horde_Cache')->get($key, 360);
        if ($ids) {
            $this->_type_ids = unserialize($ids);
        } else {
            $type_mgr = $GLOBALS['injector']->getInstance('Content_Types_Manager');
            $types = $type_mgr->ensureTypes(array('gallery', 'image'));
            $this->_type_ids = array('gallery' => (int)$types[0],
                                     'image' => (int)$types[1]);
            $GLOBALS['injector']->getInstance('Horde_Cache')->set($key, serialize($this->_type_ids));
        }

        $this->_tagger = $tagger;
    }

    /**
     * Tags an ansel object with any number of tags.
     *
     * @param string $localId       The identifier of the ansel object.
     * @param string|array $tags    Either a single tag string or an array of
     *                              tags.
     * @param string $owner         The tag owner (should normally be the owner
     *                              of the resource).
     * @param string $content_type  The type of object we are tagging
     *                              (gallery/image).
     *
     * @return void
     * @throws Ansel_Exception
     */
    public function tag($localId, $tags, $owner, $content_type = 'image')
    {
        if (!is_array($tags)) {
            $tags = $this->_tagger->splitTags($tags);
        }

        try {
            $this->_tagger->tag(
                    $owner,
                    array('object' => $localId,
                          'type' => $this->_type_ids[$content_type]),
                    $tags);
        } catch (Content_Exception $e) {
            throw new Ansel_Exception($e);
        }
    }

    /**
     * Retrieves the tags on given object(s).
     *
     * @param mixed  $localId  Either the identifier of the ansel object or
     *                         an array of identifiers.
     * @param string $type     The type of object $localId represents.
     *
     * @return array A tag_id => tag_name hash, possibly wrapped in a localid hash.
     * @throws Ansel_Exception
     */
    public function getTags($localId, $type = 'image')
    {
        try {
            if (is_array($localId)) {
                return $this->_tagger->getTagsByObjects($localId, $type);
            }

            return $this->_tagger->getTags(array('objectId' => array('object' => $localId, 'type' => $this->_type_ids[$type])));
        } catch (Content_Exception $e) {
            throw new Ansel_Exception($e);
        }
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
        try {
            $tags = array_values($this->_tagger->getTagIds($tags));
            $gtags = $this->_tagger->browseTags($tags, $this->_type_ids['gallery'], $user);
            $itags = $this->_tagger->browseTags($tags, $this->_type_ids['image'], $user);
        } catch (Content_Exception $e) {
            throw new Ansel_Exception($e);
        }
        /* Can't use array_merge here since it would renumber the array keys */
        foreach ($gtags as $id => $name) {
            if (empty($itags[$id])) {
                $itags[$id] = $name;
            }
        }

        return $itags;
    }

    /**
     * Get tag ids for the specified tag names.
     *
     * @param string|array $tags  Either a tag_name or array of tag_names.
     *
     * @return array  A tag_id => tag_name hash.
     * @throws Ansel_Exception
     */
    public function getTagIds($tags)
    {
        try {
            return $this->_tagger->getTagIds($tags);
        } catch (Content_Exception $e) {
            throw new Ansel_Exception($e);
        }
    }

    /**
     * Untag a resource.
     *
     * Removes the tag regardless of the user that added the tag.
     *
     * @param string $localId       The ansel object identifier.
     * @param mixed $tags           Either a tag_id, tag_name or an array.
     * @param string $content_type  The type of object that $localId represents.
     *
     * @return void
     */
    public function untag($localId, $tags, $content_type = 'image')
    {
        try {
            $this->_tagger->removeTagFromObject(
                array('object' => $localId, 'type' => $this->_type_ids[$content_type]), $tags);
        } catch (Content_Exception $e) {
            throw new Ansel_Exception($e);
        }
    }

    /**
     * Tags the given resource with *only* the tags provided, removing any
     * tags that are already present but not in the list.
     *
     * @param string $localId  The identifier for the ansel object.
     * @param mixed $tags      Either a tag_id, tag_name, or array of tag_ids.
     * @param string $owner    The tag owner - should normally be the resource
     *                         owner.
     * @param $content_type    The type of object that $localId represents.
     */
    public function replaceTags($localId, $tags, $owner, $content_type = 'image')
    {
        /* First get a list of existing tags. */
        $existing_tags = $this->getTags($localId, $content_type);

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
            /* Remove any tags that were not found in the passed in list. */
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
     * @return A tag_id => tag_name hash
     * @throws Ansel_Exception
     */
    public function listTags($token)
    {
        try {
            return $this->_tagger->getTags(array('q' => $token, 'userId' => $GLOBALS['registry']->getAuth()));
        } catch (Content_Tagger $e) {
            throw new Ansel_Exception($e);
        }
    }

    /**
     * Returns the data needed to build a tag cloud based on the specified
     * user's tag dataset.
     *
     * @param string $user    The user whose tags should be included.
     *                        If null, all users' tags are returned.
     * @param integer $limit  The maximum number of tags to include.
     *
     * @return Array An array of hashes, each containing tag_id, tag_name, and count.
     * @throws Ansel_Exception
     */
    public function getCloud($user, $limit = 5)
    {
        $filter = array('limit' => $limit,
                        'typeId' => array_values($this->_type_ids));
        if (!empty($user)) {
            $filter['userId'] = $user;
        }
        try {
            return $this->_tagger->getTagCloud($filter);
        } catch (Content_Exception $e) {
            throw new Ansel_Exception($e);
        }
    }

    /**
     * Returns cloud-like information, but only for a specified set of tags.
     * Useful for displaying the counts of other images tagged with the same
     * tag as the currently displayed image.
     *
     * @param array $tags     An array of either tag names or ids.
     * @param integer $limit  Limit results to this many.
     * 
     * @return array  An array of hashes, tag_id, tag_name, and count.
     * @throws Ansel_Exception
     */
    public function getTagInfo($tags, $limit = 500, $type = null)
    {
        $filter = array('typeId' => empty($type) ? array_values($this->_type_ids) : $this->_type_ids[$type],
                        'tagIds' => $tags,
                        'limit' => $limit);

        try {
            return $this->_tagger->getTagCloud($filter);
        } catch (Content_Exception $e) {
            throw new Ansel_Exception($e);
        }
    }

    /**
     * Searches for resources that are tagged with all of the requested tags.
     *
     * @param array $tags    Either a tag_id, tag_name or an array.
     * @param array $filter  Array of filter parameters.
     *                       - type (string) - 'gallery' or 'image'
     *                       - user (array) - only include objects owned by
     *                         these users.
     *
     * @return  A hash of 'calendars' and 'events' that each contain an array
     *          of calendar_ids and event_uids respectively.
     * @throws Ansel_Exception
     */
    public function search($tags, $filter = array())
    {
        $args = array();

        /* These filters are mutually exclusive */
        if (array_key_exists('user', $filter)) {
            $args['userId'] = $filter['user'];
        } elseif (!empty($filter['gallery'])) {
            // Only events located in specific calendar(s)
            if (!is_array($filter['gallery'])) {
                $filter['gallry'] = array($filter['gallery']);
            }
            $args['gallery'] = $filter['gallery'];
        }

        try {
            /* Add the tags to the search */
            $args['tagId'] = $this->_tagger->getTagIds($tags);

            /* Restrict to images or galleries */
            $gal_results = $image_results = array();
            if (empty($filter['type']) || $filter['type'] == 'gallery') {
                $args['typeId'] = $this->_type_ids['gallery'];
                $gal_results = $this->_tagger->getObjects($args);
            }

            if (empty($filter['type']) || $filter['type'] == 'image') {
                $args['typeId'] = $this->_type_ids['image'];
                $image_results = $this->_tagger->getObjects($args);
            }
        } catch (Content_Exception $e) {
            throw new Ansel_Exception($e);
        }

        /* TODO: Filter out images whose gallery has already matched? */
        $results = array('galleries' => array_values($gal_results),
                         'images' => array_values($image_results));

        return $results;
    }

}
