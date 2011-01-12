<?php
/**
 * Kronolith_Storage:: defines an API for storing free/busy information.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @author  Michael J Rubinsky <mrubinsk@horde.org>
 * @package Kronolith
 */
class Kronolith_Storage_sql extends Kronolith_Storage
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
     * @param array $params  A hash containing connection parameters.
     */
    public function __construct($user, $params = array())
    {
        $this->_user = $user;
        if (empty($params['db'])) {
            throw new InvalidArgumentException(_("Missing required db parameter"));
        }

        $this->_db = $params['db'];
        $this->_params = $params;
        $this->_params['table'] = isset($params['table']) ? $params['table'] : 'kronolith_storage';
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
        $query = sprintf('SELECT vfb_serialized FROM %s WHERE vfb_email = ? AND (vfb_owner = ?',
                         $this->_params['table']);
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
        $query = sprintf('INSERT INTO %s (vfb_owner, vfb_email, vfb_serialized) VALUES (?, ?, ?)',
                         $this->_params['table']);
        $values = array($public ? '' : $this->_user, $email, Horde_Serialize::serialize($vfb, Horde_Serialize::BASIC));

        /* Execute the query. */
        try {
            $result = $this->_db->insert($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Kronolith_Exception($e);
        }
    }

}
