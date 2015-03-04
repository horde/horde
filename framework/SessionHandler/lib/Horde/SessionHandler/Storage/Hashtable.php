<?php
/**
 * Copyright 2013-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   SessionHandler
 */

/**
 * Horde_HashTable SessionHandler driver.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   SessionHandler
 * @since     2.2.0
 */
class Horde_SessionHandler_Storage_Hashtable extends Horde_SessionHandler_Storage
{
    /**
     * HashTable object.
     *
     * @var Horde_HashTable
     */
    protected $_hash;

    /**
     * Current session ID.
     *
     * @var string
     */
    protected $_id;

    /**
     * The ID used for session tracking.
     *
     * @var string
     */
    protected $_trackID = 'horde_sessions_track_ht';

    /**
     * Constructor.
     *
     * @param array $params  Parameters:
     * <pre>
     *   - hashtable: (Horde_HashTable) [REQUIRED] A Horde_HashTable object.
     *   - track: (boolean) Track active sessions?
     * </pre>
     */
    public function __construct(array $params = array())
    {
        if (empty($params['hashtable'])) {
            throw new InvalidArgumentException('Missing hashtable parameter.');
        }

        if (!$params['hashtable']->locking) {
            throw new InvalidArgumentException('HashTable object must support locking.');
        }

        $this->_hash = $params['hashtable'];
        unset($params['hashtable']);

        parent::__construct($params);

        if (!empty($this->_params['track']) && (!rand(0, 999))) {
            register_shutdown_function(array($this, 'trackGC'));
        }
    }

    /**
     */
    public function open($save_path = null, $session_name = null)
    {
    }

    /**
     */
    public function close()
    {
        if (isset($this->_id)) {
            $this->_hash->unlock($this->_id);
        }
    }

    /**
     */
    public function read($id)
    {
        if (!$this->readonly) {
            $this->_hash->lock($id);
        }

        if (($result = $this->_hash->get($id)) === false) {
            if (!$this->readonly) {
                $this->_hash->unlock($id);
            }

            $result = '';
        } elseif (!$this->readonly) {
            $this->_id = $id;
        }

        return $result;
    }

    /**
     */
    public function write($id, $session_data)
    {
        $base = array_filter(array(
            'timeout' => ini_get('session.gc_maxlifetime')
        ));

        if (!empty($this->_params['track'])) {
            // Do a replace - the only time it should fail is if we are
            // writing a session for the first time.  If that is the case,
            // update the session tracker.
            $res = $this->_hash->set($id, $session_data, array_merge($base, array(
                'replace' => true,
            )));
            $track = !$res;
        } else {
            $res = $track = false;
        }

        if (!$res && !$this->_hash->set($id, $session_data, $base)) {
            return false;
        }

        if ($track) {
            $this->_hash->lock($this->_trackID);
            $ids = $this->_getTrackIds();
            $ids[$id] = 1;
            $this->_hash->set($this->_trackID, json_encode($ids));
            $this->_hash->unlock($this->_trackID);
        }

        return true;
    }

    /**
     */
    public function destroy($id)
    {
        $res = $this->_hash->delete($id);
        $this->_hash->unlock($id);

        if ($res === false) {
            return false;
        }

        if (!empty($this->_params['track'])) {
            $this->_hash->lock($this->_trackID);
            if ($ids = $this->_getTrackIds()) {
                unset($ids[$id]);
                $this->_hash->set($this->_trackID, json_encode($ids));
            }
            $this->_hash->unlock($this->_trackID);
        }

        return true;
    }

    /**
     */
    public function gc($maxlifetime = 300)
    {
        return true;
    }

    /**
     */
    public function getSessionIDs()
    {
        if (empty($this->_params['track'])) {
            throw new Horde_SessionHandler_Exception('Memcache session tracking not enabled.');
        }

        $this->trackGC();

        return array_keys($this->_getTrackIds());
    }

    /**
     * Do garbage collection for session tracking information.
     */
    public function trackGC()
    {
        try {
            $this->_hash->lock($this->_trackID);

            if ($ids = $this->_getTrackIds()) {
                $alter = false;

                foreach (array_keys($ids) as $key) {
                    if (!$this->_hash->exists($key)) {
                        unset($ids[$key]);
                        $alter = true;
                    }
                }

                if ($alter) {
                    $this->_hash->set($this->_trackID, json_encode($ids));
                }
            }

            $this->_hash->unlock($this->_trackID);
        } catch (Horde_HashTable_Exception $e) {
        }
    }

    /**
     * Get the tracking IDs.
     *
     * @return array  Tracking IDs.
     */
    protected function _getTrackIds()
    {
        if ((($ids = $this->_hash->get($this->_trackID)) === false) ||
            !($ids = json_decode($ids, true))) {
            $ids = array();
        }

        return $ids;
    }

}
