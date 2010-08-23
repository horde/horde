<?php
/**
 * Horde_SessionHandler implementation for memcache.
 *
 * Copyright 2005-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Rong-En Fan <rafan@infor.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @package  SessionHandler
 */
class Horde_SessionHandler_Memcache extends Horde_SessionHandler_Base
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
     * @param array $params  Parameters:
     * <pre>
     * 'memcache' - (Horde_Memcache) [REQUIRED] A memcache object.
     * 'track' - (boolean) Track active sessions?
     * 'track_lifetime' - (integer) The number of seconds after which tracked
     *                    sessions will be treated as expired.
     * </pre>
     *
     * @throws InvalidArgumentException
     */
    public function __construct(array $params = array())
    {
        if (empty($params['memcache'])) {
            throw new InvalidArgumentException('Missing memcache argument.');
        }

        $this->_memcache = $params['memcache'];
        unset($params['memcache']);

        parent::__construct($params);

        if (empty($this->_params['track_lt'])) {
            $this->_params['track_lt'] = ini_get('session.gc_maxlifetime');
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
     * @throws Horde_SessionHandler_Exception
     */
    protected function _open($save_path = null, $session_name = null)
    {
    }

    /**
     * Close the backend.
     */
    protected function _close()
    {
        if (isset($this->_id)) {
            $this->_memcache->unlock($this->_id);
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

            if ($result === false) {
                if ($this->_logger) {
                    $this->_logger->log('Error retrieving session data (id = ' . $id . ')', 'DEBUG');
                }
                return false;
            }
        }

        if (!$this->_readonly) {
            $this->_id = $id;
        }

        if ($this->_logger) {
            $this->_logger->log('Read session data (id = ' . $id . ')', 'DEBUG');
        }

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
            if ($this->_logger) {
                $this->_logger->log('Error writing session data (id = ' . $id . ')', 'ERR');
            }
            return false;
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

        if ($this->_logger) {
            $this->_logger->log('Wrote session data (id = ' . $id . ')', 'DEBUG');
        }

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
            if ($this->_logger) {
                $this->_logger->log('Failed to delete session (id = ' . $id . ')', 'DEBUG');
            }
            return false;
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

        if ($this->_logger) {
            $this->_logger->log('Deleted session data (id = ' . $id . ')', 'DEBUG');
        }

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
        // Memcache does its own garbage collection.
        return true;
    }

    /**
     * Get a list of (possibly) valid session identifiers.
     *
     * @return array  A list of session identifiers.
     * @throws Horde_SessionHandler_Exception
     */
    public function getSessionIDs()
    {
        if (empty($this->_params['track'])) {
            throw new Horde_SessionHandler_Exception('Memcache session tracking not enabled.');
        }

        $this->trackGC();

        $ids = $this->_memcache->get($this->_trackID);

        return ($ids === false)
            ? array()
            : array_keys($ids);
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

        $tstamp = time() - $this->_params['track_lt'];
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
