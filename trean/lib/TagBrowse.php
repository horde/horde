<?php
/**
 * Trean_TagBrowse:: class provides logic for dealing with tag browsing.
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Trean
 */
class Trean_TagBrowse
{

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
     * The Trean_Tagger object.
     *
     * @var Trean_Tagger
     */
    protected $_tagger;

    /**
     * Const'r
     *
     * @param Trean_Tagger $tagger  The trean tagger object.
     * @param array $tags           Tags to add to initially search on.
     * @param string $owner         Restrict to resources owned by owner.
     */
    public function __construct(
        Trean_Tagger $tagger,
        $tags = null,
        $owner = null)
    {
        $this->_tagger = $tagger;
        if (!empty($tags)) {
            $this->_tags = $this->_tagger->getTagIds($tags);
        } else {
            $this->_tags = $GLOBALS['session']->get(
                'trean',
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
        $GLOBALS['session']->set('trean', 'browsetags', $this->_tags);
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
     * Get breadcrumb style navigation html for choosen tags
     *
     * @return  Return information useful for building a tag trail.
     */
    public function getTagTrail()
    {

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
        $search = new Trean_TagBrowse($this->_tagger, null, $this->_owner);
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
     * @return Array  An array of Trean_Bookmark objects.
     */
    public function getSlice($page, $perpage)
    {
        global $injector;

        // Refresh the search
        $this->runSearch();
        $totals = $this->count();

        $start = $page * $perpage;
        $results = array_slice($this->_results, $start, $perpage);

        $bookmarks = array();
        foreach ($results as $id) {
            try {
                $bookmarks[] = $injector
                    ->getInstance('Trean_Bookmarks')
                    ->getBookmark($id);
            } catch (Trean_Exception $e) {
                Horde::logMessage('Bookmark not found: ' . $id, 'ERR');
            }
        }

        return $bookmarks;
    }

    /**
     * Clears the session cache of tags currently included in the search.
     */
    static public function clearSearch()
    {
        $GLOBALS['session']->remove('trean', 'browsetags');
    }

    /**
     * Helper for uasort.  Sorts the results by count.
     *
     */
    private function _sortTagInfo($a, $b)
    {
        return $a['total']  <  $b['total'];
    }

}
