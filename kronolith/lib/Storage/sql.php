<?php
/**
 * Kronolith_Storage:: defines an API for storing free/busy information.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @package Kronolith
 */
class Kronolith_Storage_sql extends Kronolith_Storage
{
    /**
     * Handle for the current database connection, used for reading.
     *
     * @var DB
     */
    protected $_db;

    /**
     * Handle for the current database connection, used for writing. Defaults
     * to the same handle as $_db if a separate write database is not required.
     *
     * @var DB
     */
    protected $_write_db;

    /**
     * Hash containing connection parameters.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * Constructs a new Kronolith_Storage SQL instance.
     *
     * @param array $params  A hash containing connection parameters.
     */
    public function __construct($user, $params = array())
    {
        $this->_user = $user;

        /* Use defaults where needed. */
        $this->_params = $params;
        $this->_params['table'] = isset($params['table']) ? $params['table'] : 'kronolith_storage';
    }

    /**
     * Connect to the database
     *
     * @throws Kronolith_Exception
     */
    public function initialize()
    {
        try {
            $this->_db = $GLOBALS['injector']->getInstance('Horde_Db_Pear')->getDb('read', 'kronolith', 'storage');
            $this->_write_db = $GLOBALS['injector']->getInstance('Horde_Db_Pear')->getDb('rw', 'kronolith', 'storage');
        } catch (Horde_Exception $e) {
            throw new Kronolith_Exception($e);
        }
    }

    /**
     * Search for a user's free/busy information.
     *
     * @param string  $email        The email address to lookup
     * @param boolean $private_only (optional) Only return free/busy
     *                              information owned by this used.
     *
     * @return Horde_Icalendar_Vfreebusy
     * @throws Kronolith_Exception
     */
    public function search($email, $private_only = false)
    {
        /* Build the SQL query. */
        $query = sprintf('SELECT vfb_serialized FROM %s WHERE vfb_email=? AND (vfb_owner=?',
                         $this->_params['table']);
        $values = array($email, $this->_user);

        if ($private_only) {
            $query .= ')';
        } else {
            $query .= " OR vfb_owner='')";
        }

        /* Log the query at debug level. */
        Horde::logMessage(sprintf('SQL search by %s: query = "%s"',
                                  $GLOBALS['registry']->getAuth(), $query), 'DEBUG');

        /* Execute the query. */
        $result = $this->_db->query($query, $values);
        if (!($result instanceof PEAR_Error)) {
            $row = $result->fetchRow(DB_GETMODE_ASSOC);
            $result->free();
            if (is_array($row)) {
                /* Retrieve Freebusy object.  TODO: check for multiple
                 * results and merge them into one and return. */
                $vfb = Horde_Serialize::unserialize($row['vfb_serialized'], Horde_Serialize::BASIC);
                return $vfb;
            }
        }
        throw new Horde_Exception_NotFound();
    }

    /**
     * Store the freebusy information for a given email address.
     *
     * @param string                     $email        The email address to store fb info for.
     * @param Horde_Icalendar_Vfreebusy  $vfb          TODO
     * @param boolean                    $private_only (optional) TODO
     *
     * @throws Kronolith_Exception
     */
    public function store($email, $vfb, $public = false)
    {
        /* Build the SQL query. */
        $query = sprintf('INSERT INTO %s (vfb_owner, vfb_email, vfb_serialized) VALUES (?, ?, ?)',
                         $this->_params['table']);
        $values = array($public ? '' : $this->_user, $email, Horde_Serialize::serialize($vfb, Horde_Serialize::BASIC));

        /* Log the query at debug level. */
        Horde::logMessage(sprintf('SQL insert by %s: query = "%s"',
                                  $GLOBALS['registry']->getAuth(), $query), 'DEBUG');

        /* Execute the query. */
        $result = $this->_write_db->query($query, $values);
        $this->handleError($result);
    }

    /**
     * Determines if the given result is a PEAR error. If it is, logs the event
     * and throws an exception.
     *
     * @param mixed $result The result to check.
     *
     * @throws Horde_Exception
     */
    protected function handleError($result)
    {
        if ($result instanceof PEAR_Error) {
            Horde::logMessage($result, 'ERR');
            throw new Kronolith_Exception($result);
        }
    }

}
