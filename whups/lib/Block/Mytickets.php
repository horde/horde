<?php
/**
 * Display a summary of the current user's assigned tickets.
 */
class Whups_Block_Mytickets extends Whups_Block_Tickets
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
        $this->_name = _("My Tickets");
    }

    /**
     */
    protected function _content()
    {
        $queue_ids = array_keys(
            Whups::permissionsFilter($GLOBALS['whups_driver']->getQueues(),
                                     'queue',
                                     Horde_Perms::READ));
        $info = array(
            'owner' => Whups::getOwnerCriteria($GLOBALS['registry']->getAuth()),
            'nores' => true,
            'queue' => $queue_ids);
        $assigned = $GLOBALS['whups_driver']->getTicketsByProperties($info);
        if (!$assigned) {
            return '<p><em>' . _("No tickets are assigned to you.") . '</em></p>';
        }

        return $this->_table($assigned);
    }

}
