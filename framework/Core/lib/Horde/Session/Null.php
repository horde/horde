<?php
/**
 * Copyright 2010-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2010-2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 */

/**
 * The null driver provides a set of methods for handling the administration
 * and contents of the Horde session variable when the PHP session is not
 * desired. Needed so things like application authentication can work within a
 * single HTTP request when we don't need the overhead of a full PHP session.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2010-2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 */
class Horde_Session_Null extends Horde_Session
{
    public $begin = null;

    /**
     * Constructor.
     */
    public function __construct()
    {
        // Store session data internally.
        $this->_data = array();
    }

    /**
     */
    public function setup($start = true, $cache_limiter = null,
                          $session_id = null)
    {
        global $conf;

        session_cache_limiter(is_null($cache_limiter) ? $conf['session']['cache_limiter'] : $cache_limiter);

        $this->sessionHandler = new Horde_Support_Stub();

        if ($start) {
            $this->start();
        }
    }

    /**
     */
    public function start()
    {
        // We must start a session to ensure that session_id() is available,
        // but since we don't actually need to write to it, close it at once
        // to avoid session lock issues.
        session_start();
        $this->_active = true;
        $this->_data[Horde_Session::BEGIN] = time();
        session_write_close();

        // Set a beginning to pass authentication timeout checks
        $this->begin = time();
        
        register_shutdown_function(array($this, 'destroy'));
    }

    /**
     */
    public function clean()
    {
        if ($this->_cleansession) {
            return false;
        }
        session_regenerate_id(true);
        $this->destroy();
        return true;
    }

    /**
     */
    public function close()
    {
        $this->_active = false;
    }

    /**
     */
    public function destroy()
    {
        session_unset();
        $this->_data = array();
        $this->_cleansession = true;
    }

}
