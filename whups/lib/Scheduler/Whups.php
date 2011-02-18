<?php
/**
 * Horde_Scheduler_whups:: Send reminders for tickets based on the
 * reminders configuration file.
 *
 * @package Horde_Scheduler
 */
class Horde_Scheduler_Whups extends Horde_Scheduler
{
    protected $_reminders;
    protected $_runtime;
    protected $_filestamp = 0;

    public function run()
    {
        $this->_runtime = time();

        // See if we need to include the reminders config file.
        if (filemtime(WHUPS_BASE . '/config/reminders.php') > $this->_filestamp) {
            $this->_filestamp = $this->_runtime;
            $this->_reminders = Horde::loadConfiguration('reminders.php', 'reminders', 'whups');
        }

        foreach ($this->_reminders as $reminder) {
            $ds = new Horde_Scheduler_Cron_Date($reminder['frequency']);
            if ($ds->scheduledAt($this->_runtime)) {
                if (!empty($reminder['server_name'])) {
                    $GLOBALS['conf']['server']['name'] = $reminder['server_name'];
                }
                $vars = new Horde_Variables($reminder);
                Whups::sendReminders($vars);
            }
        }
    }

}
