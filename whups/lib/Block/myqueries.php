<?php

$block_name = _("My Queries");

/**
 * Horde_Block_Whups_myqueries:: Implementation of the Horde_Block API
 * to show the current user's queries.
 *
 * @package Horde_Block
 */
class Horde_Block_Whups_myqueries extends Horde_Block
{
    protected $_app = 'whups';

    /**
     * The title to go in this block.
     *
     * @return string   The title text.
     */
    protected function _title()
    {
        return _("My Queries");
    }

    /**
     * The content to go in this block.
     *
     * @return string   The content
     */
    protected function _content()
    {
        require_once WHUPS_BASE . '/lib/Query.php';

        $qManager = new Whups_QueryManager();
        $queries = $qManager->listQueries($GLOBALS['registry']->getAuth(), true);
        if (is_a($queries, 'PEAR_Error')) {
            return $queries;
        }
        $myqueries = Whups_View::factory('SavedQueries',
                                         array('results' => $queries));

        Horde::startBuffer();
        $myqueries->html(false);
        $html = Horde::endBuffer();
        if ($html) {
            return $html;
        }
        return '<p><em>' . _("No queries have been saved.") . '</em></p>';
    }

}
