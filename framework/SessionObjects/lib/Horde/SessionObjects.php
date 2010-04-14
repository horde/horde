<?php
/**
 * The Horde_SessionObjects:: class provides a way for storing data
 * (usually, but not necessarily, objects) in the current user's
 * session.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If youq
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @package  Horde_SessionObjects
 * @category Horde
 */
class Horde_SessionObjects
{
    /**
     * The name of the store.
     *
     * @var string
     */
    protected $_name = 'horde_session_objects';

    /**
     * Allow store() to overwrite current objects?
     *
     * @var boolean
     */
    protected $_overwrite = false;

    /**
     * The maximum number of objects that the store should hold.
     *
     * @var integer
     */
    protected $_size = 20;

    /**
     * Serialized cache item.
     *
     * @var string
     */
    protected $_sdata = null;

    /**
     * Constructor.
     *
     * @param array $params  Optional parameters:
     * <pre>
     * 'name' - (string) The name of the session variable to store the objects
     *          in.
     * 'size' - (integer) The maximum size of the (non-prunable) object store.
     * </pre>
     */
    public function __construct($params = array())
    {
        if (isset($params['name'])) {
            $this->_name = $params['name'];
        }

        if (isset($params['size']) && is_int($params['size'])) {
            $this->_size = $params['size'];
        }

        register_shutdown_function(array($this, 'shutdown'));
    }

    /**
     * Tasks to run on shutdown.
     */
    public function shutdown()
    {
        /* Prune old entries. */
        if (isset($_SESSION[$this->_name]['__prune']) &&
            ($_SESSION[$this->_name]['__prune'] > $this->_size)) {
            $pruneList = array();
            $prune_count = $_SESSION[$this->_name]['__prune'] - $this->_size;

            foreach ($_SESSION[$this->_name] as $key => $val) {
                if ($val['prune']) {
                    $pruneList[] = $key;
                    if (!--$prune_count) {
                        break;
                    }
                }
            }

            $this->prune($pruneList);
        }
    }

    /**
     * Wrapper around store that will return the oid instead.
     *
     * @see Horde_SessionObjects::store()
     *
     * @param mixed $data     The data to store in the session store.
     * @param boolean $prune  If false, this object will not be pruned from the
     *                        store if the maximum store size is exceeded.
     *
     * @return string  The MD5 string representing the object's ID.
     */
    public function storeOid($data, $prune = true)
    {
        $this->_sdata = true;
        $oid = $this->oid($data);
        $this->store($oid, $data, $prune);
        $this->_sdata = null;
        return $oid;
    }

    /**
     * Attempts to store an object in the session store.
     *
     * @param string $oid     Object ID used as the storage key.
     * @param mixed $data     The data to store in the session store.
     * @param boolean $prune  If false, this object will not be pruned from the
     *                        store if the maximum store size is exceeded.
     *
     * @return boolean  True on success.
     */
    public function store($oid, $data, $prune = true)
    {
        if (!isset($_SESSION[$this->_name])) {
            $_SESSION[$this->_name] = array('__prune' => 0);
        }
        $ptr = &$_SESSION[$this->_name];

        if ($this->_overwrite || !isset($ptr[$oid])) {
            $modes = array();
            if (!is_null($this->_sdata)) {
                $data = $this->_sdata;
            } else {
                $modes[] = Horde_Serialize::BASIC;
            }
            if (Horde_Serialize::hasCapability(Horde_Serialize::LZF)) {
                $modes[] = Horde_Serialize::LZF;
            }
            $ptr[$oid] = array(
                'data' => ((empty($modes)) ? $data : Horde_Serialize::serialize($data, $modes)),
                'prune' => $prune
            );

            if ($prune) {
                ++$ptr['__prune'];
            }
        }

        return true;
    }

    /**
     * Overwrites a current element in the object store.
     *
     * @param string $oid     Object ID used as the storage key.
     * @param mixed $data     The data to store in the session store.
     * @param boolean $prune  If false, this object will not be pruned from the
     *                        store if the maximum store size is exceeded.
     *
     * @return boolean  True on success, false on failure.
     */
    public function overwrite($oid, $data, $prune = true)
    {
        $this->_overwrite = true;
        $success = $this->store($oid, $data, $prune);
        $this->_overwrite = false;
        return $success;
    }

    /**
     * Attempts to retrive an object from the store.
     *
     * @param string $oid   Object ID to query.
     * @param enum $type    NOT USED
     * @param integer $val  NOT USED
     *
     * @return mixed  The requested object, or false on failure.
     */
    public function query($oid, $type = null, $val = null)
    {
        if (!isset($_SESSION[$this->_name]) ||
            (is_null($oid) || !isset($_SESSION[$this->_name][$oid]))) {
            $object = false;
        } else {
            $modes = array();
            if (Horde_Serialize::hasCapability(Horde_Serialize::LZF)) {
                $modes[] = Horde_Serialize::LZF;
            }
            $object = Horde_Serialize::unserialize($_SESSION[$this->_name][$oid]['data'], array_merge($modes, array(Horde_Serialize::BASIC)));
            if (is_a($object, 'PEAR_Error')) {
                $this->setPruneFlag($oid, true);
                $object = false;
            }
        }

        return $object;
    }

    /**
     * Sets the prune flag on a store object.  The object will be pruned
     * when the maximum storage size is reached.
     *
     * @param string $oid     The object ID.
     * @param boolean $prune  True to allow pruning, false for no pruning.
     */
    public function setPruneFlag($oid, $prune)
    {
        if (!isset($_SESSION[$this->_name])) {
            return;
        }

        $ptr = &$_SESSION[$this->_name];
        if (isset($ptr[$oid]) && ($ptr[$oid]['prune'] != $prune)) {
            $ptr[$oid]['prune'] = $prune;
            ($prune) ? ++$ptr['__prune'] : --$ptr['__prune'];
        }
    }

    /**
     * Immediately prune an object.
     *
     * @param mixed $oid  The object ID or an array of object IDs.
     */
    public function prune($oid)
    {
        if (!isset($_SESSION[$this->_name])) {
            return;
        }

        if (!is_array($oid)) {
            $oid = array($oid);
        }

        $prune_count = 0;

        foreach ($oid as $val) {
            if (isset($_SESSION[$this->_name][$val])) {
                ++$prune_count;
                unset($_SESSION[$this->_name][$val]);
            }
        }

        $_SESSION[$this->_name]['__prune'] -= $prune_count;
    }

    /**
     * Generates an OID for an object.
     *
     * @param mixed $data  The data to store in the store.
     *
     * @return string $oid  An object ID to use as the storage key.
     */
    public function oid($data)
    {
        $data = serialize($data);
        $oid = md5($data);
        if ($this->_sdata === true) {
            $this->_sdata = $data;
        }
        return $oid;
    }

}
