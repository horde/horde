<?php
/**
 * Display the results of a saved Query in a block.
 */
class Whups_Block_Query extends Horde_Core_Block
{
    /**
     */
    public function getName()
    {
        return _("Query Results");
    }

    /**
     */
    protected function _params()
    {
        require_once WHUPS_BASE . '/lib/Query.php';

        $qManager = new Whups_QueryManager();
        $qDefault = null;
        $qParams = $qManager->listQueries($GLOBALS['registry']->getAuth());
        if (count($qParams)) {
            $qType = 'enum';
        } else {
            $qDefault = _("You have no saved queries.");
            $qType = 'error';
        }

        return array(
            'query' => array(
                'type' => $qType,
                'name' => _("Query to run"),
                'default' => $qDefault,
                'values' => $qParams
            )
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
        global $whups_driver, $prefs;

        if (!($query = $this->_getQuery())) {
            return '<p><em>' . _("No query to run") . '</em></p>';
        }

        $vars = Horde_Variables::getDefaultVariables();
        $tickets = $whups_driver->executeQuery($query, $vars);
        if ($tickets instanceof PEAR_Error) {
            return $tickets;
        }

        $html = '<thead><tr>';
        $sortby = $prefs->getValue('sortby');
        $sortdirclass = ' class="' . ($prefs->getValue('sortdir') ? 'sortup' : 'sortdown') . '"';
        foreach (Whups::getSearchResultColumns('block') as $name => $column) {
            $html .= '<th' . ($sortby == $column ? $sortdirclass : '') . '>' . $name . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        Whups::sortTickets($tickets);
        foreach ($tickets as $ticket) {
            $link = Horde::link(Whups::urlFor('ticket', $ticket['id'], true));
            $html .= '<tr><td>' . $link . htmlspecialchars($ticket['id']) . '</a></td>' .
                '<td>' . $link . htmlspecialchars($ticket['summary']) . '</a></td>' .
                '<td>' . htmlspecialchars($ticket['priority_name']) . '</td>' .
                '<td>' . htmlspecialchars($ticket['state_name']) . '</td></tr>';
        }

        Horde::addScriptFile('tables.js', 'horde', true);

        return '<table id="whups_block_query_' . $query->id . '" cellspacing="0" class="tickets striped sortable">' . $html . '</tbody></table>';
    }

    /**
     */
    private function _getQuery()
    {
        if (empty($this->_params['query'])) {
            return false;
        }

        require_once WHUPS_BASE . '/lib/Query.php';

        $qManager = new Whups_QueryManager();
        $query = $qManager->getQuery($this->_params['query']);
        if ($query instanceof PEAR_Error) {
            return false;
        }
        if (!$query->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::READ)) {
            return false;
        }

        return $query;
    }

}
