<?php
/**
 * Display the results of a saved Query in a block.
 */
class Whups_Block_Query extends Whups_Block_Tickets
{
    /**
     * Is this block enabled?
     *
     * @var boolean
     */
    public $enabled = true;

    /**
     */
    public function __construct($app, $params = array())
    {
        parent::__construct($app, $params);
        $this->_name = _("Query Results");
    }

    /**
     */
    protected function _params()
    {
        $qManager = new Whups_Query_Manager();
        $qDefault = null;
        $qParams = $qManager->listQueries($GLOBALS['registry']->getAuth());
        if (count($qParams)) {
            $qType = 'enum';
        } else {
            $qDefault = _("You have no saved queries.");
            $qType = 'error';
        }

        return array_merge(array(
            'query' => array(
                'type' => $qType,
                'name' => _("Query to run"),
                'default' => $qDefault,
                'values' => $qParams
            )),
            parent::_params()
        );
    }

    /**
     */
    protected function _title()
    {
        if (($query = $this->_getQuery()) && $query->name) {
            return Horde::link(Whups::urlFor('query', empty($query->slug) ? array('id' => $query->id) : array('slug' => $query->slug)))
                . htmlspecialchars($query->name) . '</a>';
        }

        return $this->getName();
    }

    /**
     */
    protected function _content()
    {
        if (!($query = $this->_getQuery())) {
            return '<p><em>' . _("No query to run") . '</em></p>';
        }

        $vars = Horde_Variables::getDefaultVariables();
        $tickets = $GLOBALS['whups_driver']->executeQuery($query, $vars);

        return $this->_table($tickets, 'whups_block_query_' . $query->id);
    }

    /**
     */
    private function _getQuery()
    {
        if (empty($this->_params['query'])) {
            return false;
        }

        $qManager = new Whups_Query_Manager();
        try {
            $query = $qManager->getQuery($this->_params['query']);
        } catch (Whups_Exception $e) {
            return false;
        }
        if (!$query->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::READ)) {
            return false;
        }

        return $query;
    }

}
