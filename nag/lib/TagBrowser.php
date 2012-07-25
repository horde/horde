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

        $tasks = new Nag_Task();
        foreach ($results as $id) {
            try {
                $task = $GLOBALS['injector']
                    ->getInstance('Nag_Factory_Driver')
                    ->create()->getByUID($id);
                $tasks->addTask($task);
            } catch (Nag_Exception $e) {
                Horde::logMessage('Task not found: ' . $id, 'ERR');
            }
        }

        return $tasks;
    }

}
