<?php
/**
 * This class provides the Horde specific implementation of the LoginTasks
 * backend.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @category Horde
 * @package  Core
 */
class Horde_Core_LoginTasks_Backend_Horde extends Horde_LoginTasks_Backend
{
    /**
     * The Horde application that is currently active.
     *
     * @var string
     */
    private $_app;

    /**
     * Constructor.
     *
     * @param string $app  The currently active Horde application.
     */
    public function __construct($app)
    {
        $this->_app = $app;
    }

    /**
     * Retrieve a cached tasklist if it exists.
     *
     * @return Horde_LoginTasks_Tasklist|boolean  The cached task list or
     *                                            false if no task list was
     *                                            cached.
     */
    public function getTasklistFromCache()
    {
        return $GLOBALS['session']->get($this->_app, 'logintasks');
    }

    /**
     * Store a login tasklist in the cache.
     *
     * @param Horde_LoginTasks_Tasklist|boolean $tasklist  The tasklist to be
     *                                                     stored.
     */
    public function storeTasklistInCache($tasklist)
    {
        $GLOBALS['session']->set($this->_app, 'logintasks', $tasklist);
    }

    /**
     * Get the class names of the task classes that need to be performed.
     *
     * @return array  An array of class names.
     */
    public function getTasks()
    {
        $tasks = array();

        foreach (array_merge($GLOBALS['registry']->getAppDrivers($this->_app, 'LoginTasks_SystemTask'), $GLOBALS['registry']->getAppDrivers($this->_app, 'LoginTasks_Task')) as $val) {
            $tasks[$val] = $this->_app;
        }

        return $tasks;
    }

    /**
     * Get the information about the last time the tasks were run. Array keys
     * are app names, values are last run timestamps. Special key '_once'
     * contains list of ONCE tasks previously run.
     *
     * @return array  The information about the last time the tasks were run.
     */
    public function getLastRun()
    {
        $lasttask_pref = @unserialize($GLOBALS['prefs']->getValue('last_logintasks'));

        return is_array($lasttask_pref)
            ? $lasttask_pref
            : array();
    }

    /**
     * Store the information about the last time the tasks were run.
     *
     * @param array $last  The information about the last time the tasks were
     *                     run.
     */
    public function setLastRun(array $last)
    {
        $GLOBALS['prefs']->setValue('last_logintasks', serialize($last));
    }

    /**
     * Mark the current time as time the login tasks were run for the last
     * time.
     */
    public function markLastRun()
    {
        $lasttasks = $this->getLastRun();
        $lasttasks[$this->_app] = time();
        $this->setLastRun($lasttasks);
    }

    /**
     * Redirect to the given URL.
     *
     * @param Horde_Url|string $url  The URL to redirect to.
     */
    public function redirect($url)
    {
        $url = new Horde_Url($url);
        $url->redirect();
    }

    /**
     * Return the URL of the login tasks view.
     *
     * @return string  The URL of the login tasks view.
     */
    public function getLoginTasksUrl()
    {
        return $GLOBALS['registry']->getServiceLink('logintasks', $this->_app);
    }

}
