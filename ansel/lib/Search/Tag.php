<?php
/**
 * Ansel_Search_Tags:: class provides logic for dealing with tag searching.
 *
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  Ansel
 */
class Ansel_Search_Tag
{
    /**
     * Array of tag_name => tag_id hashes for the current search.
     * Tags are always added to the search by name and stored by name=>id.
     *
     * @var array
     */
    protected $_tags = array();

    /**
     * Total count of all resources that match (both Galleries and Images).
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
     * The Ansel_Tagger object.
     *
     * @var Ansel_Tagger
     */
    protected $_tagger;

    /**
     * Constructor
     *
     * @param array $tags    An array of tag names to match. If null is passed
     *                       then the tags will be loaded from the session.
     * @param string $owner  Restrict search to resources owned by specified
     *                       owner.
     *
     * @return Ansel_Search_Tag
     */
    public function __construct(Ansel_Tagger $tagger, $tags = null, $owner = null)
    {
        $this->_tagger = $tagger;
        if (!empty($tags)) {
            $this->_tags = $this->_tagger->getTagIds($tags);
        } else {
            $this->_tags = $GLOBALS['session']->get('ansel', 'tags_search', Horde_Session::TYPE_ARRAY);
        }

        $this->_owner = $owner;

    }

    /**
     * Save the current search to the session
     *
     */
    public function save()
    {
        $GLOBALS['session']->set('ansel', 'tags_search', $this->_tags);
        $this->_dirty = false;
    }

    /**
     * Fetch the matching resources that should appear on the current page
     *
     * @TODO: Implement an Interface that Ansel_Gallery and Ansel_Image should
     *        implement that the client search code will use.
     *
     * @return Array of Ansel_Images and Ansel_Galleries
     */
    public function getSlice($page, $perpage)
    {
        global $conf, $registry;

        /* Refresh the search */
        $this->runSearch();
        $totals = $this->count();

        /* First, the galleries */
        $gstart = $page * $perpage;
        $gresults = array_slice($this->_results['galleries'], $gstart, $perpage);

        /* Instantiate the Gallery objects */
        $galleries = array();
        foreach ($gresults as $gallery) {
            $galleries[] = $GLOBALS['injector']->getInstance('Ansel_Injector_Factory_Storage')->create()->getGallery($gallery);
        }

        /* Do we need to get images? */
        $istart = max(0, $page * $perpage - $totals['galleries']);
        $count = $perpage - count($galleries);
        if ($count > 0) {
            $iresults = array_slice($this->_results['images'], $istart, $count);
            $images = count($iresults) ? array_values($GLOBALS['injector']->getInstance('Ansel_Injector_Factory_Storage')->create()->getImages(array('ids' => $iresults))) : array();
            if (($conf['comments']['allow'] == 'all' || ($conf['comments']['allow'] == 'authenticated' && $GLOBALS['registry']->getAuth())) &&
                $registry->hasMethod('forums/numMessagesBatch')) {

                $ids = array_keys($images);
                $ccounts = $GLOBALS['registry']->call('forums/numMessagesBatch', array($ids, 'ansel'));
                if (!($ccounts instanceof PEAR_Error)) {
                    foreach ($images as $image) {
                        $image->commentCount = (!empty($ccounts[$image->id]) ? $ccounts[$image->id] : 0);
                    }
                }
            }
        } else {
            $images = array();
        }

        return array_merge($galleries, $images);
    }

    /**
     * Add a tag to the cumulative tag search
     *
     * @param string $tag  The tag name to add.
     *
     * @return void
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
     *
     * @return void
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
     * @return array  An array of selected tag_name => tag_id hashes.
     */
    public function getTags()
    {
        return $this->_tags;
    }

    /**
     * Get breadcrumb style navigation html for choosen tags
     *
     * @TODO: Remove the html generation to the view class
     *
     * @return string  The html representing the tag trail for browsing tags.
     */
    public function getTagTrail()
    {
        global $registry;

        $html = '<ul class="tag-list">';

        /* Use the local cache to preserve the order */
        $count = 0;
        foreach ($this->_tags as $tagname => $tagid) {
            $remove_url = Horde::url('view.php', true)->add(
                    array('view' => 'Results',
                          'tag' => $tagname,
                          'actionID' => 'remove'));
            if (!empty($this->_owner)) {
                $remove_url->add('owner', $this->_owner);
            }
            $delete_label = sprintf(_("Remove %s from search"), htmlspecialchars($tagname));
            $html .= '<li>' . htmlspecialchars($tagname) . $remove_url->link(array('title' => $delete_label)) . Horde::img('delete-small.png', $delete_label) . '</a></li>';
        }

        return $html . '</ul>';
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
     * @return array  Hash containing totals for both 'galleries' and 'images'.
     */
    public function count()
    {
        if (!is_array($this->_tags) || !count($this->_tags)) {
            return 0;
        }

        $count = array('galleries' => count($this->_results['galleries']), 'images' => count($this->_results['images']));
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
        $search = new Ansel_Search_Tag($this->_tagger, null, $this->_owner);
        $results = array();
        foreach ($tags as $id => $tag) {
            $search->addTag($tag);
            $search->runSearch();
            $count = $search->count();
            if ($count['images'] + $count['galleries'] > 0) {
                $results[$id] = array('tag_name' => $tag, 'total' => $count['images'] + $count['galleries']);
            }
            $search->removeTag($tag);
        }

        /* Get the results sorted by available totals for this user */
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
     * Clears the session cache of tags currently included in the search.
     */
    static public function clearSearch()
    {
        $GLOBALS['session']->remove('ansel', 'tags_search');
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
