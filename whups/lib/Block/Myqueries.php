<?php
/**
 * Show the current user's queries.
 */
class Whups_Block_Myqueries extends Horde_Core_Block
{
    /**
     */
    public function __construct($app, $params = array())
    {
        parent::__construct($app, $params);

        $this->_name = _("Saved Queries");
    }

    /**
     */
    protected function _content()
    {
        $qManager = new Whups_Query_Manager();
        $queries = $qManager->listQueries($GLOBALS['registry']->getAuth(), true);
        $myqueries = new Whups_View_SavedQueries(
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
