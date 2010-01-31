<?php
/**
 * Horde_SessionHandler implementation for memcache.
 *
 * Required parameters:<pre>
 * 'memcache' - (Horde_Memcache) A memcache object.
 * </pre>
 *
 * Optional parameters:<pre>
 * 'persistent_driver' - (string) If set, uses this backend to store session
 *                       data persistently.
 * 'persistent_params' - (array) If using a persistent backend, the params
 *                       to use for the persistent backend.
 * 'track' - (boolean) Track active sessions?
 * 'track_lifetime' - (integer) The number of seconds after which tracked
 *                    sessions will be treated as expired.
 * </pre>
 *
 * Copyright 2005-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Rong-En Fan <rafan@infor.org>
 * @author  Michael Slusarz <slusarz@curecanti.org>
 * @package Horde_SessionHandler
 */
class Horde_SessionHandler_Memcache extends Horde_SessionHandler
{
    /**
     * Memcache object.
     *
     * @var Horde_Memcache
     */
    protected $_memcache;

    /**
     * Current session ID.
     *
     * @var string
     */
    protected $_id;

    /**
     * Persistent backend driver.
     *
     * @var Horde_SessionHandler
     */
    protected $_persistent;

    /**
     * Do read-only get?
     *
     * @var boolean
     */
    protected $_readonly = false;

    /**
     * The ID used for session tracking.
     *
     * @var string
     */
    protected $_trackID = 'horde_memcache_sessions_track';

    /**
     * Constructor.
     *
     * @param array $params  A hash containing connection parameters.
     *
     * @throws Horde_Exception
     * @throws InvalidArgumentException
     */
    protected function __construct($params = array())
    {
        if (empty($params['memcache'])) {
            throw InvalidArgumentException('Missing memcache object.');
        }

        $this->_memcache = $params['memcache'];

        if (!empty($params['persistent_driver'])) {
            try {
                $this->_persistent = self::singleton($params['persistent_driver'], empty($params['persistent_params']) ? null : $params['persistent_params']);
            } catch (Horde_Exception $e) {
                throw new Horde_Exception('Horde is unable to correctly start the persistent session handler.');
            }
        }

        parent::__construct($params);

        // If using a persistent backend, don't track sessions in memcache
        if (isset($this->_persistent)) {
            $this->_params['track'] = false;
        }

        if (empty($this->_params['track_lifetime'])) {
            $this->_params['track_lifetime'] = ini_get('session.gc_maxlifetime');
        }

        if (!empty($this->_params['track']) && (rand(0, 999) == 0)) {
            register_shutdown_function(array($this, 'trackGC'));
        }
    }

    /**
     * Open the backend.
     *
     * @param string $save_path     The path to the session object.
     * @param string $session_name  The name of the session.
     *
     * @throws Horde_Exception
     */
    protected function _open($save_path = null, $session_name = null)
    {
        if (isset($this->_persistent)) {
            if (!$this->_persistent->open($save_path, $session_name)) {
                throw new Horde_Exception('Could not open persistent backend.');
            }
        }
    }

    /**
     * Close the backend.
     *
     * @throws Horde_Exception
     */
    protected function _close()
    {
        if (isset($this->_id)) {
            $this->_memcache->unlock($this->_id);
        }
        if (isset($this->_persistent)) {
            $this->_persistent->close();
        }
    }

    /**
     * Read the data for a particular session identifier.
     *
     * @param string $id  The session identifier.
     *
     * @return string  The session data.
     */
    protected function _read($id)
    {
        if (!$this->_readonly) {
            $this->_memcache->lock($id);
        }
        $result = $this->_memcache->get($id);

        if ($result === false) {
            if (!$this->_readonly) {
                $this->_memcache->unlock($id);
            }

            if (isset($this->_persistent)) {
                $result = $this->_persistent->read($id);
            }

            if ($result === false) {
                Horde::logMessage('Error retrieving session data (id = ' . $id . ')', __FILE__, __LINE__, PEAR_LOG_DEBUG);
                return false;
            }

            $this->_persistent->write($id, $session_data);
        }

        if (!$this->_readonly) {
            $this->_id = $id;
        }

        Horde::logMessage('Read session data (id = ' . $id . ')', __FILE__, __LINE__, PEAR_LOG_DEBUG);
        return $result;
    }

    /**
     * Write session data to the backend.
     *
     * @param string $id            The session identifier.
     * @param string $session_data  The session data.
     *
     * @return boolean  True on success, false otherwise.
     */
    protected function _write($id, $session_data)
    {
        if (!empty($this->_params['track'])) {
            // Do a replace - the only time it should fail is if we are
            // writing a session for the first time.  If that is the case,
            // update the session tracker.
            $res = $this->_memcache->replace($id, $session_data);
            $track = !$res;
        } else {
            $res = $track = false;
        }

        if (!$res &&
            !$this->_memcache->set($id, $session_data)) {
            Horde::logMessage('Error writing session data (id = ' . $id . ')', __FILE__, __LINE__, PEAR_LOG_ERR);
            return false;
        }

        if (isset($this->_persistent)) {
            $result = $this->_persistent->write($id, $session_data);
        }

        if ($track) {
            $this->_memcache->lock($this->_trackID);
            $ids = $this->_memcache->get($this->_trackID);
            if ($ids === false) {
                $ids = array();
            }

            $ids[$id] = time();
            $this->_memcache->set($this->_trackID, $ids);
            $this->_memcache->unlock($this->_trackID);
        }

        Horde::logMessage('Wrote session data (id = ' . $id . ')', __FILE__, __LINE__, PEAR_LOG_DEBUG);
        return true;
    }

    /**
     * Destroy the data for a particular session identifier.
     *
     * @param string $id  The session identifier.
     *
     * @return boolean  True on success, false otherwise.
     */
    public function destroy($id)
    {
        $result = $this->_memcache->delete($id);
        $this->_memcache->unlock($id);

        if ($result === false) {
            Horde::logMessage('Failed to delete session (id = ' . $id . ')', __FILE__, __LINE__, PEAR_LOG_DEBUG);
            return false;
        }

        if (isset($this->_persistent)) {
            $result = $this->_persistent->destroy($id);
        }

        if (!empty($this->_params['track'])) {
            $this->_memcache->lock($this->_trackID);
            $ids = $this->_memcache->get($this->_trackID);
            if ($ids !== false) {
                unset($ids[$id]);
                $this->_memcache->set($this->_trackID, $ids);
            }
            $this->_memcache->unlock($this->_trackID);
        }

        Horde::logMessage('Deleted session data (id = ' . $id . ')', __FILE__, __LINE__, PEAR_LOG_DEBUG);
        return true;
    }

    /**
     * Garbage collect stale sessions from the backend.
     *
     * @param integer $maxlifetime  The maximum age of a session.
     *
     * @return boolean  True on success, false otherwise.
     */
    public function gc($maxlifetime = 300)
    {
        $result = true;

        if (isset($this->_persistent)) {
            $result = $this->_persistent->gc($maxlifetime);
        }

        // Memcache does its own garbage collection.
        return $result;
    }

    /**
     * Get a list of (possibly) valid session identifiers.
     *
     * @return array  A list of session identifiers.
     * @throws Horde_Exception
     */
    public function getSessionIDs()
    {
        if (isset($this->_persistent)) {
            return $this->_persistent->getSessionIDs();
        }

        try {
            $this->_open();

            if (empty($this->_params['track'])) {
                throw new Horde_Exception(_("Memcache session tracking not enabled."));
            }
        } catch (Horde_Exception $e) {
            if (isset($this->_persistent)) {
                return $this->_persistent->getSessionIDs();
            }
            throw $e;
        }

        $this->trackGC();

        $ids = $this->_memcache->get($this->_trackID);
        return ($ids === false) ? array() : array_keys($ids);
    }

    /**
     * Get session data read-only.
     *
     * @param string $id  The session identifier.
     *
     * @return string  The session data.
     */
    protected function _readOnly($id)
    {
        $this->_readonly = true;
        $result = $this->_memcache->get($id);
        $this->_readonly = false;
        return $result;
    }

    /**
     * Do garbage collection for session tracking information.
     */
    public function trackGC()
    {
        $this->_memcache->lock($this->_trackID);
        $ids = $this->_memcache->get($this->_trackID);
        if (empty($ids)) {
            $this->_memcache->unlock($this->_trackID);
            return;
        }

        $tstamp = time() - $this->_params['track_lifetime'];
        $alter = false;

        foreach ($ids as $key => $val) {
            if ($tstamp > $val) {
                unset($ids[$key]);
                $alter = true;
            }
        }

        if ($alter) {
            $this->_memcache->set($this->_trackID, $ids);
        }

        $this->_memcache->unlock($this->_trackID);
    }

}
