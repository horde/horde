<?php
/**
 * Display a summary of the current user's assigned tickets.
 */
class Whups_Block_Mytickets extends Horde_Core_Block
{
    /**
     */
    public function getName()
    {
        return _("My Tickets");
    }

    /**
     */
    protected function _content()
    {
        global $whups_driver, $prefs;

        $queue_ids = array_keys(Whups::permissionsFilter($whups_driver->getQueues(), 'queue', Horde_Perms::READ));
        $info = array('owner' => Whups::getOwnerCriteria($GLOBALS['registry']->getAuth()),
                      'nores' => true,
                      'queue' => $queue_ids);
        $assigned = $whups_driver->getTicketsByProperties($info);
        if ($assigned instanceof PEAR_Error) {
            return $assigned;
        }

        if (!$assigned) {
            return '<p><em>' . _("No tickets are assigned to you.") . '</em></p>';
        }

        $html = '<thead><tr>';
        $sortby = $prefs->getValue('sortby');
        $sortdirclass = ' class="' . ($prefs->getValue('sortdir') ? 'sortup' : 'sortdown') . '"';
        foreach (Whups::getSearchResultColumns('block') as $name => $column) {
            $html .= '<th' . ($sortby == $column ? $sortdirclass : '') . '>' . $name . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        Whups::sortTickets($assigned);
        foreach ($assigned as $ticket) {
            $link = Horde::link(Whups::urlFor('ticket', $ticket['id'], true));
            $html .= '<tr><td>' . $link . htmlspecialchars($ticket['id']) . '</a></td>' .
                '<td>' . $link . htmlspecialchars($ticket['summary']) . '</a></td>' .
                '<td>' . htmlspecialchars($ticket['priority_name']) . '</td>' .
                '<td>' . htmlspecialchars($ticket['state_name']) . '</td></tr>';
        }

        Horde::addScriptFile('tables.js', 'horde', true);

        return '<table id="whups_block_mytickets" cellspacing="0" class="tickets striped sortable">' . $html . '</tbody></table>';
    }

}
