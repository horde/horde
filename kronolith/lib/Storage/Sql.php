<?php
/**
 * Kronolith_Storage:: defines an API for storing free/busy information.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @author  Michael J Rubinsky <mrubinsk@horde.org>
 * @package Kronolith
 */
class Kronolith_Storage_Sql extends Kronolith_Storage
{
    /**
     * Handle for the current database connection, used for reading.
     *
     * @var Horde_Db_Adapter
     */
    protected $_db;

    /**
     * Hash containing connection parameters.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * Constructs a new Kronolith_Storage SQL instance.
     *
     * @param string $user   The user the fb info belongs to.
     * @param array $params  A hash containing connection parameters.
     *
     * @return Kronolith_Storage_Sql
     */
    public function __construct($user, array $params = array())
    {
        $this->_user = $user;
        if (empty($params['db'])) {
            throw new InvalidArgumentException('Missing required db parameter');
        }

        $this->_db = $params['db'];
        $this->_params = $params;
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
        $query = 'SELECT vfb_serialized FROM kronolith_storage WHERE vfb_email = ? AND (vfb_owner = ?';
        $values = array($email, $this->_user);

        if ($private_only) {
            $query .= ')';
        } else {
            $query .= " OR vfb_owner = '')";
        }

        /* Execute the query. */
        try {
            $result = $this->_db->selectValue($query, $values);
            if (empty($result)) {
                throw new Horde_Exception_NotFound();
            }
            return Horde_Serialize::unserialize($result, Horde_Serialize::BASIC);
        } catch (Horde_Db_Exception $e) {
            throw new Kronolith_Exception($e);
        }
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
        $query = 'INSERT INTO kronolith_storage (vfb_owner, vfb_email, vfb_serialized) VALUES (?, ?, ?)';
        $values = array($public ? '' : $this->_user, $email, Horde_Serialize::serialize($vfb, Horde_Serialize::BASIC));

        /* Execute the query. */
        try {
            $this->_db->insert($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Kronolith_Exception($e);
        }
    }

}
