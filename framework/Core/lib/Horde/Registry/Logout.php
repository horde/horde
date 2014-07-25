<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 */

/**
 * Manage the logout tasks registered with Horde_Registry.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 * @since     2.13.0
 */
class Horde_Registry_Logout
{
    /** Session storage key. */
    const SESSION_KEY = 'registry_logout';

    /**
     * Add a class to the logout queue.
     *
     * @param mixed $classname  The classname to add (or an object of that
     *                          class). The class must be able to be
     *                          instantiated via Horde_Injector and must
     *                          implement the Horde_Registry_Logout_Task
     *                          interface.
     */
    public function add($classname)
    {
        $classname = is_object($classname)
            ? get_class($classname)
            : strval($classname);

        $queue = $this->_getTasks();

        if (!in_array($classname, $queue)) {
            $queue[] = $classname;
            $this->_setTasks($queue);
        }
    }

    /**
     * Runs the list of logout tasks and clears the queue.
     */
    public function run()
    {
        global $injector;

        foreach ($this->_getTasks() as $val) {
            try {
                $ob = $injector->getInstance($val);
                if ($ob instanceof Horde_Registry_Logout_Task) {
                    $ob->logoutTask();
                }
            } catch (Exception $e) {}
        }

        $this->_setTasks(array());
    }

    /**
     * Return the list of logout tasks.
     */
    private function _getTasks()
    {
        global $session;

        return $session->get(
            'horde',
            self::SESSION_KEY,
            $session::TYPE_ARRAY
        );
    }

    /**
     * Set the list of logout tasks.
     *
     * @param array $queue  List of classnames.
     */
    private function _setTasks($queue)
    {
        $GLOBALS['session']->set(
            'horde',
            self::SESSION_KEY,
            $queue
        );
    }

}
