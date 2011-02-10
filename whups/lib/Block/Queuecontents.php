<?php
/**
 * Show the open tickets in a queue.
 */
class Whups_Block_Queuecontents extends Horde_Block
{
    /**
     */
    public function getName()
    {
        return _("Queue Contents");
    }

    /**
     */
    protected function _params()
    {
        global $whups_driver;

        $qParams = array();
        $qDefault = null;
        $qParams = Whups::permissionsFilter($whups_driver->getQueues(), 'queue', Horde_Perms::READ);
        if (!$qParams) {
            $qDefault = _("No queues available.");
            $qType = 'error';
        } else {
            $qType = 'enum';
        }

        return array(
            'queue' => array(
                'type' => $qType,
                'name' => _("Queue"),
                'default' => $qDefault,
                'values' => $qParams,
            )
        );
    }

    /**
     */
    protected function _title()
    {
        if ($queue = $this->_getQueue()) {
            return sprintf(_("Open Tickets in %s"), htmlspecialchars($queue['name']));
        }

        return $this->getName();
    }

    /**
     */
    protected function _content()
    {
        global $whups_driver, $prefs;

        if (!($queue = $this->_getQueue())) {
            return '<p><em>' . _("No tickets in queue.") . '</em></p>';
        }

        $info = array('queue' => $this->_params['queue'],
                      'nores' => true);
        $tickets = $whups_driver->getTicketsByProperties($info);
        if (is_a($tickets, 'PEAR_Error')) {
            return $tickets;
        }

        if (!$tickets) {
            return '<p><em>' . _("No tickets in queue.") . '</em></p>';
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

        return '<table id="whups_block_queue_' . htmlspecialchars($this->_params['queue']) . '" cellspacing="0" class="tickets striped sortable">' . $html . '</tbody></table>';
    }

    /**
     */
    private function _getQueue()
    {
        global $whups_driver;

        if (empty($this->_params['queue'])) {
            return false;
        }
        if (!Whups::permissionsFilter(array($this->_params['queue'] => true), 'queue', Horde_Perms::READ)) {
            return false;
        }
        $queue = $whups_driver->getQueue($this->_params['queue']);

        return ($queue instanceof PEAR_Error)
            ? false
            : $queue;
    }

}
