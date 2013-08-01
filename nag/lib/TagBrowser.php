<?php
/**
 * Copyright 2012-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Nag
 */
/**
 * Nag_TagBrowser:: class provides logic for dealing with tag browsing.
 *
 * Copyright 2012-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Nag
 */
class Nag_TagBrowser extends Horde_Core_TagBrowser
{
    /**
     * Application that the tag browser is for.
     *
     * @var string
     */
    protected $_app = 'nag';

    /**
     * The 'completed' filter value.
     *
     * @var integer
     */
    protected $_completed = Nag::VIEW_ALL;

    /**
     * Cache the last tag search to avoid having to retrieve the tags from the
     * backend twice.
     *
     * @var Nag_Task
     */
    protected $_tasks;

    /**
     * Get breadcrumb style navigation html for choosen tags
     *
     * @return  Return information useful for building a tag trail.
     */
    public function getTagTrail()
    {
    }

    /**
     * Fetch the matching resources that should appear on the current page
     *
     * @param integer $page     Start page.
     * @param integer $perpage  Number of tasks per page.
     *
     * @return Nag_Task  A list of tasks.
     */
    public function getSlice($page = 0, $perpage = null)
    {
        // Refresh the search
        $this->runSearch();
        return $this->_tasks->getSlice($page, $perpage);
    }

    /**
     * Set the Nag::VIEW_* constant for the browser.
     *
     * @param integer $completed  The Nag::VIEW_* constant to filter the results
     */
    public function setFilter($completed)
    {
        $this->_completed = $completed;
    }

    /**
     * Override the default tag search in order to filter by the 'completed'
     * filter.
     *
     * @return array  An array of task UIDs.
     */
    protected function _runSearch()
    {
        $search = new Nag_Search(
            null,
            Nag_Search::MASK_TAGS,
            array(
                'completed' => $this->_completed,
                'tags' => $this->_tags));

        $tasks = $search->getSlice();
        $tasks->reset();

        // Save the resulting task list.
        $this->_tasks = $tasks;

        // Must return the UID array since the parent class requires them.
        $ids = array();
        while ($task = $tasks->each()) {
            $ids[] = $task->uid;
        }

        return $ids;
    }

}
