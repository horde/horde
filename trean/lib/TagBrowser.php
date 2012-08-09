<?php
/**
 * Trean_TagBrowser:: class provides logic for dealing with tag browsing.
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
class Trean_TagBrowser extends Horde_Core_TagBrowser
{

    protected $_app = 'trean';

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

}
