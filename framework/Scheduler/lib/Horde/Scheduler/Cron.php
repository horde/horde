<?php
/**
 * Horde_Scheduler_cron:: Sort of a cron replacement in a PHP cli
 * script.
 *
 * Date Syntax Examples.
 *
 * Remember:
 *   - Whitespace (space, tab, newline) delimited fields
 *   - Single values, sets, ranges, wildcards
 *
 * SECOND   MINUTE              HOUR        DAY     MONTH
 * *        *                   *           *       *       (every second)
 * 0,30     *                   *           *       *       (every 30 seconds)
 * 0        0,10,20,30,40,50    *           *       *       (every 10 minutes)
 * 0        0                   *           *       *       (beginning of every hour)
 * 0        0                   0,6,12,18   *       *       (at midnight, 6am, noon, 6pm)
 * 0        0                   0           1-7&Fri *       (midnight, first Fri of the month)
 * 0        0                   0           1-7!Fri *       (midnight, first Mon-Thu,Sat-Sun of the month)
 *
 *
 * Example usage:
 *
 * @set_time_limit(0);
 * $cron = Horde_Scheduler::factory('Cron');
 *
 * // Run this command every 5 minutes.
 * $cron->addTask('perl somescript.pl', '0 0,5,10,15,20,25,30,35,40,45,50,55 * * *');
 *
 * // Run this command midnight of the first Friday of odd numbered months.
 * $cron->addTask('php -q somescript.php', '0 0 0 1-7&Fri 1,3,5,7,9,11');
 *
 * // Also run this command midnight of the second Thursday and Saturday of the even numbered months.
 * $cron->addTask('php -q somescript.php', '0 0 0 8-15&Thu,8-15&Sat 2,4,6,8,10,12');
 *
 * $cron->run();
 *
 * @author  Ryan Flynn <ryan@ryanflynn.com>
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Scheduler
 */
class Horde_Scheduler_Cron extends Horde_Scheduler
{
    /**
     * TODO
     *
     * @var array
     */
    protected $_tasks = array();

    /**
     * Every time a task is added it will get a fresh uid even if
     * immediately removed.
     *
     * @var integer
     */
    var $_counter = 1;

    /**
     */
    public function addTask($cmd, $rules)
    {
        $ds = new Horde_Scheduler_Cron_Date($rules);

        $this->_counter++;

        $this->_tasks[] =
            array(
                'uid' => $this->_counter,
                'rules' => $ds,
                'cmd' => $cmd
            );

        return $this->_counter;
    }

    /**
     */
    public function removeTask($uid)
    {
        $count = count($this->_tasks);
        for ($i = 0; $i < $count; $i++) {
            if ($this->_tasks['uid'] == $uid) {
                $found = $i;
                array_splice($this->_tasks, $i);
                return $i;
            }
        }

        return 0;
    }

    /**
     */
    public function run()
    {
        if (!count($this->_tasks)) {
            exit("crond: Nothing to schedule; exiting.\n");
        }

        while (true) {
            $t = time();

            // Check each task.
            foreach ($this->_tasks as $task) {
                if ($task['rules']->nowMatches()) {
                    $this->runcmd($task);
                }
            }

            // Wait until the next second.
            while (time() == $t) {
                $this->sleep(100000);
            }
        }
    }

    /**
     */
    public function runcmd(&$task)
    {
        Horde::logMessage('runcmd(): ' . $task['cmd'] . ' run by ' . $task['uid'], 'INFO');
        return shell_exec($task['cmd']);
    }

}
