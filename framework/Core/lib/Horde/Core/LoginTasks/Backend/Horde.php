<?php
/**
 * This class provides the Horde specific implementation of the LoginTasks
 * backend.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
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
     * Is the current session authenticated?
     *
     * @return boolean True if the user is authenticated, false otherwise.
     */
    public function isAuthenticated()
    {
        return ($GLOBALS['registry']->getAuth() !== false);
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
        if (isset($_SESSION['horde_logintasks'][$this->_app])) {
            return @unserialize($_SESSION['horde_logintasks'][$this->_app]);
        }
        return false;
    }

    /**
     * Store a login tasklist in the cache.
     *
     * @param Horde_LoginTasks_Tasklist|boolean $tasklist  The tasklist to be
     *                                                     stored.
     */
    public function storeTasklistInCache($tasklist)
    {
        $_SESSION['horde_logintasks'][$this->_app] = serialize($tasklist);
    }

    /**
     * Get the class names of the task classes that need to be performed.
     *
     * @return array  An array of class names.
     */
    public function getTasks()
    {
        $app_list = array($this->_app);
        $tasks = array();

        switch ($this->_app) {
        case 'horde':
            if (isset($_SESSION['horde_logintasks']['horde'])) {
                return $tasks;
            }
            break;

        default:
            if (!isset($_SESSION['horde_logintasks']['horde'])) {
                array_unshift($app_list, 'horde');
            }
            break;
        }

        foreach ($app_list as $app) {
            foreach (array_merge($GLOBALS['registry']->getAppDrivers($app, 'LoginTasks_SystemTask'), $GLOBALS['registry']->getAppDrivers($app, 'LoginTasks_Task')) as $val) {
                $tasks[$val] = $app;
            }
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
        if (($this->_app != 'horde') &&
            !isset($_SESSION['horde_logintasks']['horde'])) {
            $lasttasks['horde'] = time();
            $_SESSION['horde_logintasks']['horde'] = true;
        }
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
        return Horde::getServiceLink('logintasks', $this->_app);
    }

}
