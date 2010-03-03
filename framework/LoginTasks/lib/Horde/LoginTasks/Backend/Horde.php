<?php
/**
 * The Horde_LoginTasks_Backend_Horde:: class provides the Horde specific
 * implementation of the LoginTasks backend
 *
 * Copyright 2001-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @package Horde_LoginTasks
 */
class Horde_LoginTasks_Backend_Horde
extends Horde_LoginTasks_Backend
{
    /**
     * The Horde application that is currently active.
     *
     * @var string
     */
    private $_app;

    /**
     * The Horde registry.
     *
     * @var Horde_Registry
     */
    private $_registry;

    /**
     * The Horde preferences system
     *
     * @var Horde_Prefs
     */
    private $_prefs;

    /**
     * Constructor
     *
     * @param string $app The Horde application that is currently active.
     */
    public function __construct(
        Horde_Registry $registry,
        Horde_Prefs    $prefs,
        $app
    ) {
        $this->_registry = $registry;
        $this->_prefs    = $prefs;
        $this->_app      = $app;
    }
    
    /**
     * Is the current session authenticated?
     *
     * @return boolean True if the user is authenticated, false otherwise.
     */
    public function isAuthenticated()
    {
        return (Horde_Auth::getAuth() !== false);
    }

    /**
     * Retrieve a cached tasklist if it exists.
     *
     * @return Horde_LoginTasks_Tasklist|boolean The cached task list or false
     * if no task list was cached.
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
     * @param Horde_LoginTasks_Tasklist|boolean The tasklist to be stored.
     *
     * @return NULL
     */
    public function storeTasklistInCache($tasklist)
    {
        $_SESSION['horde_logintasks'][$this->_app] = serialize($tasklist);
    }

    /**
     * Register the shutdown handler.
     *
     * @param array The shutdown function
     *
     * @return NULL
     */
    public function registerShutdown($shutdown)
    {
        register_shutdown_function($shutdown);
    }

    /**
     * Get the class names of the task classes that need to be performed.
     *
     * @return array An array of class names.
     */
    public function getTasks()
    {
        /* Add Horde tasks here if not yet run. */
        $app_list = array($this->_app);
        if (($this->_app != 'horde') &&
            !isset($_SESSION['horde_logintasks']['horde'])) {
            array_unshift($app_list, 'horde');
        }

        $tasks = array();

        foreach ($app_list as $app) {
            foreach (array_merge($this->_registry->getAppDrivers($app, 'LoginTasks_SystemTask'), $this->_registry->getAppDrivers($app, 'LoginTasks_Task')) as $val) {
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
     * @return array The information about the last time the tasks were run.
     */
    public function getLastRun()
    {
        $lasttask_pref = @unserialize($this->_prefs->getValue('last_logintasks'));
        if (!is_array($lasttask_pref)) {
            $lasttask_pref = array();
        }
        return $lasttask_pref;
    }

    /**
     * Store the information about the last time the tasks were run.
     *
     * @param array $last The information about the last time the tasks were run.
     *
     * @return NULL
     */
    public function setLastRun(array $last)
    {
        $this->_prefs->setValue('last_logintasks', serialize($last));
    }

    /**
     * Mark the current time as time the login tasks were run for the last time.
     *
     * @return NULL
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
        $GLOBALS['prefs']->setValue('last_logintasks', serialize($lasttasks));
    }

    /**
     * Redirect to the given URL.
     *
     * @param string $url The URL to redirect to.
     *
     * @return NULL
     */
    public function redirect($url)
    {
        header('Location: ' . $url);
        exit;
    }

    /**
     * Return the URL of the login tasks view.
     *
     * @return string The URL of the login tasks view
     */
    public function getLoginTasksUrl()
    {
        return Horde::url(Horde_Util::addParameter($this->_registry->get('webroot', 'horde') . '/services/logintasks.php', array('app' => $this->_app)), true);
    }
}