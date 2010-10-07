<?php
/**
 * The Horde_Session:: class provides a set of methods for handling the
 * administration and contents of the Horde session variable.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Core
 */
class Horde_Session
{
    /**
     * The session handler object.
     *
     * @var Horde_SessionHandler
     */
    public $sessionHandler = null;

    /**
     * Indicate that a new session ID has been generated for this page load.
     *
     * @var boolean
     */
    protected $_cleansession = false;

    /**
     * Constructor.
     *
     * @param boolean $start  Initiate the session?
     */
    public function __construct($start = true)
    {
        $this->setup($start);
    }

    /**
     * Sets a custom session handler up, if there is one.
     *
     * @param boolean $start  Initiate the session?
     *
     * @throws Horde_Exception
     */
    public function setup($start = true)
    {
        global $conf, $registry;

        ini_set('url_rewriter.tags', 0);
        if (empty($conf['session']['use_only_cookies'])) {
            ini_set('session.use_only_cookies', 0);
        } else {
            ini_set('session.use_only_cookies', 1);
            if (!empty($conf['cookie']['domain']) &&
                (strpos($conf['server']['name'], '.') === false)) {
                throw new Horde_Exception('Session cookies will not work without a FQDN and with a non-empty cookie domain. Either use a fully qualified domain name like "http://www.example.com" instead of "http://example" only, or set the cookie domain in the Horde configuration to an empty value, or enable non-cookie (url-based) sessions in the Horde configuration.');
            }
        }

        session_set_cookie_params(
            $conf['session']['timeout'],
            $conf['cookie']['path'],
            $conf['cookie']['domain'],
            $conf['use_ssl'] == 1 ? 1 : 0
        );
        session_cache_limiter(is_null($registry->initParams['session_cache_limiter']) ? $conf['session']['cache_limiter'] : $registry->initParams['session_cache_limiter']);
        session_name(urlencode($conf['session']['name']));

        /* We want to create an instance here, not get, since we may be
         * destroying the previous instances in the page. */
        $this->sessionHandler = $GLOBALS['injector']->createInstance('Horde_Core_Factory_SessionHandler');

        if ($start) {
            session_start();
        }
    }

    /**
     * Destroys any existing session on login and make sure to use a new
     * session ID, to avoid session fixation issues. Should be called before
     * checking a login.
     *
     * @return boolean  True if the session was cleaned.
     */
    public function clean()
    {
        if ($this->_cleansession) {
            return false;
        }

        // Make sure to force a completely new session ID and clear all
        // session data.
        session_regenerate_id(true);
        session_unset();

        $this->_cleansession = true;

        return true;
    }

    /**
     * Close the current session.
     */
    public function close()
    {
        session_write_close();
    }

    /**
     * Destroy the current session.
     */
    public function destroy()
    {
        session_destroy();
        $this->_cleansession = true;
    }

}
