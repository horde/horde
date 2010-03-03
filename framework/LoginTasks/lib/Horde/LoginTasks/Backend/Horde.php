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
     * Constructor
     *
     * @param string $app The Horde application that is currently active.
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
}