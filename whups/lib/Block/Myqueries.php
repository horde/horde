<?php
/**
 * Show the current user's queries.
 */
class Whups_Block_Myqueries extends Horde_Block
{
    /**
     */
    public function getName()
    {
        return _("My Queries");
    }

    /**
     */
    protected function _title()
    {
        return $this->getName();
    }

    /**
     */
    protected function _content()
    {
        require_once WHUPS_BASE . '/lib/Query.php';

        $qManager = new Whups_QueryManager();
        $queries = $qManager->listQueries($GLOBALS['registry']->getAuth(), true);
        if ($queries instanceof PEAR_Error) {
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
