<?php
/**
 * Horde_Core_TagBrowser:: class provides logic for dealing with tag browsing.
 *
 * Copyright 2011 - 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Core
 */
abstract class Horde_Core_TagBrowser
{
    /**
     * The application this browser is for.
     *
     * @var string
     */
    protected $_app;

    /**
     * Array of tag_name => tag_id hashes for the current search.
     * Tags are always added to the search by name and stored by name=>id.
     *
     * @var array
     */
    protected $_tags = array();

    /**
     * Total count of matches.
     *
     * @var integer
     */
    protected $_totalCount = null;

    /**
     * The user whose resources we are searching.
     *
     * @var string
     */
    protected $_owner = '';

    /**
     * Dirty flag
     *
     * @var boolean
     */
    protected $_dirty = false;

    /**
     * Results cache. Holds the results of the current search.
     *
     * @var array
     */
    protected $_results = array();

    /**
     * The Tagger object.
     *
     * @var Horde_Core_Tagger
     */
    protected $_tagger;

    /**
     * Const'r
     *
     * @param Horde_Core_Tagger $tagger  The tagger object.
     * @param array $tags                Tags to add to initially search on.
     * @param string $owner              Restrict to resources owned by owner.
     */
    public function __construct(
        Horde_Core_Tagger $tagger,
        $tags = null,
        $owner = null)
    {
        $this->_tagger = $tagger;
        if (!empty($tags)) {
            $this->_tags = $this->_tagger->getTagIds($tags);
        } else {
            $this->_tags = $GLOBALS['session']->get(
                $this->_app,
                'browsetags',
                Horde_Session::TYPE_ARRAY);
        }

        $this->_owner = empty($owner) ? $GLOBALS['registry']->getAuth() : $owner;
    }

    /**
     * Saves current state to the session.
     */
    public function save()
    {
        $GLOBALS['session']->set($this->_app, 'browsetags', $this->_tags);
        $this->_dirty = false;
    }

    /**
     * Add a tag to the cumulative tag search
     *
     * @param string $tag  The tag name to add.
     */
    public function addTag($tag)
    {
        $tag_id = (int)current($this->_tagger->getTagIds($tag));
        if (array_search($tag_id, $this->_tags) === false) {
            $this->_tags[$tag] = $tag_id;
            $this->_dirty = true;
        }
    }

    /**
     * Remove a tag from the cumulative search
     *
     * @param string $tag  The tag name to remove.
     */
    public function removeTag($tag)
    {
        if (!empty($this->_tags[$tag])) {
            unset($this->_tags[$tag]);
            $this->_dirty = true;
        }
    }

    /**
     * Get the list of currently choosen tags
     *
     * @return array  A hash of the currently selected tag_name => tag_id.
     */
    public function getTags()
    {
        return $this->_tags;
    }

    /**
     * Get the total number of tags included in this search.
     *
     * @return integer  The number of tags used in the current search.
     */
    public function tagCount()
    {
        return count($this->_tags);
    }

    /**
     * Get the total number of resources that match.
     *
     * @return integer  The count of matching bookmarks.
     */
    public function count()
    {
        if (!is_array($this->_tags) || !count($this->_tags)) {
            return 0;
        }

        $count = count($this->_results);
        $this->_totalCount = $count;

        return $count;
    }

    /**
     * Get a list of tags related to this search
     *
     * @return array An array  tag_id => {tag_name, total}
     */
    public function getRelatedTags()
    {
        $tags = $this->_tagger->browseTags($this->getTags(), $this->_owner);
        $class = get_class($this);
        $search = new $class($this->_tagger, null, $this->_owner);
        $results = array();
        foreach ($tags as $id => $tag) {
            $search->addTag($tag);
            $search->runSearch();
            $count = $search->count();
            if ($count > 0) {
                $results[$id] = array('tag_name' => $tag, 'total' => $count);
            }
            $search->removeTag($tag);
        }

        // Get the results sorted by available totals for this user
        uasort($results, array($this, '_sortTagInfo'));
        return $results;
    }

    /**
     * Perform, and cache the search.
     *
     */
    public function runSearch()
    {
        $this->_results = $this->_runSearch();
    }

    /**
     * Clears the session cache of tags currently included in the search.
     */
    public function clearSearch()
    {
        $GLOBALS['session']->remove($this->_app, 'browsetags');
        $this->_tags = array();
    }

    /**
     * Helper for uasort.  Sorts the results by count.
     *
     */
    protected function _sortTagInfo($a, $b)
    {
        return $a['total']  <  $b['total'];
    }

    /**
     * Default implementation for runSearch.
     *
     * @return array
     */
    protected function _runSearch()
    {
        if (!empty($this->_owner)) {
            $filter = array('user' => $this->_owner);
        } else {
            $filter = array();
        }
        if (empty($this->_results) || $this->_dirty) {
            $this->_results = $this->_tagger
                    ->search($this->_tags, $filter);
        }
    }

    /**
     * Fetch the matching resources that should appear on the current page
     *
     * @param integer $page     The page to get slice for.
     * @param integer $perpage  The number of objects per page.
     *
     * @return array  An array of result objects.
     */
    abstract public function getSlice($page, $perpage);

    /**
     * Get breadcrumb style navigation html for choosen tags
     *
     * @return  Return information useful for building a tag trail.
     */
    abstract public function getTagTrail();

}
