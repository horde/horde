<?php
/**
 * Display a summary of unassigned tickets.
 */
class Whups_Block_Unassigned extends Whups_Block_Tickets
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
        $this->_name = _("Unassigned Tickets");
    }

    /**
     */
    protected function _content()
    {
        $queue_ids = array_keys(Whups::permissionsFilter($GLOBALS['whups_driver']->getQueues(), 'queue', Horde_Perms::READ));
        $info = array('notowner' => true,
                      'nores' => true,
                      'queue' => $queue_ids);
        $unassigned = $GLOBALS['whups_driver']->getTicketsByProperties($info);
        if (!$unassigned) {
            return '<p><em>' . _("No tickets are unassigned!") . '</em></p>';
        }

        return $this->_table($unassigned);
    }

}
