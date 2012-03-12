<?php
/**
 * Show the open tickets in a queue.
 */
class Whups_Block_Queuecontents extends Whups_Block_Tickets
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
        $this->_name = _("Queue Contents");
    }

    /**
     */
    protected function _params()
    {
        $qParams = array();
        $qDefault = null;
        $qParams = Whups::permissionsFilter(
            $GLOBALS['whups_driver']->getQueues(),
            'queue',
            Horde_Perms::READ);
        if (!$qParams) {
            $qDefault = _("No queues available.");
            $qType = 'error';
        } else {
            $qType = 'enum';
        }

        return array_merge(array(
            'queue' => array(
                'type' => $qType,
                'name' => _("Queue"),
                'default' => $qDefault,
                'values' => $qParams,
            )),
            parent::_params()
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
        if (!($queue = $this->_getQueue())) {
            return '<p><em>' . _("No tickets in queue.") . '</em></p>';
        }

        $info = array('queue' => $this->_params['queue'],
                      'nores' => true);
        $tickets = $GLOBALS['whups_driver']->getTicketsByProperties($info);
        if (!$tickets) {
            return '<p><em>' . _("No tickets in queue.") . '</em></p>';
        }

        return $this->_table($tickets, 'whups_block_queue_' . $this->_params['queue']);
    }

    /**
     */
    private function _getQueue()
    {
        if (empty($this->_params['queue'])) {
            return false;
        }
        if (!Whups::permissionsFilter(array($this->_params['queue'] => true), 'queue', Horde_Perms::READ)) {
            return false;
        }

        try {
            return $GLOBALS['whups_driver']->getQueue($this->_params['queue']);
        } catch (Whups_Exception $e) {
            return false;
        }
    }

}
