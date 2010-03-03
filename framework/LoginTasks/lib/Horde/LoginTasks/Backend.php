<?php
/**
 * The Horde_LoginTasks_Backend:: class provides the specific backend providing
 * the dependencies of the LoginTasks system (e.g. preferences, session storage,
 * redirection facilites, shutdown management etc.)
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
abstract class Horde_LoginTasks_Backend
{
    /**
     * Is the current session authenticated?
     *
     * @return boolean True if the user is authenticated, false otherwise.
     */
    abstract public function isAuthenticated();

    /**
     * Retrieve a cached tasklist if it exists.
     *
     * @return Horde_LoginTasks_Tasklist|boolean The cached task list or false
     * if no task list was cached.
     */
    abstract public function getTasklistFromCache();
}