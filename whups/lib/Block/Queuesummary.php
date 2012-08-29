<?php
/**
 * Show a summary of all available queues and their number of open tickets.
 */
class Whups_Block_Queuesummary extends Horde_Core_Block
{
    /**
     */
    public function __construct($app, $params = array())
    {
        parent::__construct($app, $params);

        $this->_name = _("Queue Summary");
    }

    /**
     */
    protected function _content()
    {
        global $whups_driver;

        $queues = Whups::permissionsFilter($whups_driver->getQueues(), 'queue', Horde_Perms::READ);
        $qsummary = $whups_driver->getQueueSummary(array_keys($queues));
        if (!$qsummary) {
            return '<p><em>' . _("There are no open tickets.") . '</em></p>';
        }

        $summary = $types = array();
        foreach ($qsummary as $queue) {
            $types[$queue['type']] = $queue['type'];
            if (!isset($summary[$queue['id']])) {
                $summary[$queue['id']] = $queue;
            }
            $summary[$queue['id']][$queue['type']] = $queue['open_tickets'];
        }

        $html = '<thead><tr>';
        $sortby = 'queue_name';
        foreach (array_merge(array('queue_name' => _("Queue")), $types) as $column => $name) {
            $html .= '<th' . ($sortby == $column ? ' class="sortdown"' : '') . '>' . $name . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        foreach ($summary as $queue) {
            $html .= '<tr><td>' . Horde::link(Whups::urlFor('queue', $queue, true), $queue['description']) . htmlspecialchars($queue['name']) . '</a></td>';
            foreach ($types as $type) {
                $html .= '<td>' . (isset($queue[$type]) ? $queue[$type] : '&nbsp;') . '</td>';
            }
            $html .= '</tr>';
        }

        $GLOBALS['page_output']->addScriptFile('tables.js', 'horde');

        return '<table id="whups_block_queuesummary" cellspacing="0" class="tickets striped sortable">' . $html . '</tbody></table>';
    }

}
