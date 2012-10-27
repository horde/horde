<?php
/**
 * Nag_TagBrowser:: class provides logic for dealing with tag browsing.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
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
    protected $_app = 'nag';

    /**
     * The 'completed' filter value.
     *
     * @var integer
     */
    protected $_completed = Nag::VIEW_ALL;

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
     * @return Nag_Task  A list of tasks.
     */
    public function getSlice($page = 0, $perpage = null)
    {
        // Refresh the search
        $this->runSearch();
        $results = array_slice($this->_results, $page * (empty($perpage) ? 0 : $perpage), $perpage);

        $driver = $GLOBALS['injector']
            ->getInstance('Nag_Factory_Driver')
            ->create('');
        $tasks = new Nag_Task();
        foreach ($results as $id) {
            try {
                $tasks->add($driver->getByUID($id));
            } catch (Nag_Exception $e) {
                Horde::logMessage(sprintf('Error loading task: %s', $e->getMessage()), 'ERR');
            } catch (Horde_Exception_NotFound $e) {
                Horde::logMessage('Task not found: ' . $id, 'ERR');
            }
        }

        return $tasks;
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
     * Override the default tag search in orde to filter by the 'completed'
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

        $ids = array();
        while ($task = $tasks->each()) {
            $ids[] = $task->uid;
        }

        return $ids;
    }

}
