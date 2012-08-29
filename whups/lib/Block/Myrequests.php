<?php
/**
 * Display a summary of the current user's requested tickets.
 */
class Whups_Block_Myrequests extends Whups_Block_Tickets
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
        $this->_name = _("My Requests");
    }

    /**
     */
    protected function _content()
    {
        global $whups_driver, $prefs;

        $queue_ids = array_keys(
            Whups::permissionsFilter($GLOBALS['whups_driver']->getQueues(),
                                     'queue',
                                     Horde_Perms::READ));
        $info = array('requester' => $GLOBALS['registry']->getAuth(),
                      'notowner' => 'user:' . $GLOBALS['registry']->getAuth(),
                      'nores' => true,
                      'queue' => $queue_ids);
        $requests = $GLOBALS['whups_driver']->getTicketsByProperties($info);
        if (!$requests) {
            return '<p><em>' . _("You have no open requests.") . '</em></p>';
        }

        return $this->_table($requests);
   }

}
