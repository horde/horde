<?php
/**
 * Copyright 2012-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Mnemo
 */
/**
 * Mnemo_TagBrowser:: class provides logic for dealing with tag browsing.
 *
 * Copyright 2012-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Mnemo
 */
class Mnemo_TagBrowser extends Horde_Core_TagBrowser
{
    /**
     * Application that the tag browser is for.
     *
     * @var string
     */
    protected $_app = 'mnemo';

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
     * @return
     */
    public function getSlice($page = 0, $perpage = null)
    {
        $this->runSearch();
        $totals = $this->count();

        $start = $page * $perpage;
        $results = array_slice($this->_results, $start, $perpage);
        $notes = array();
        foreach ($results as $id) {
            $notes[] = $GLOBALS['injector']->getInstance('Mnemo_Factory_Driver')
                ->create()
                ->getByUID($id);
        }

        return $notes;
    }

}
