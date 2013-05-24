<?php
/**
 * Horde_ActiveSync_Folder_Base::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2012-2013 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * The class contains functionality for maintaining state for a generic
 * collection folder. This would include Appointments, Contacts, and Tasks.
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2012-2013 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
abstract class Horde_ActiveSync_Folder_Base
{
    /**
     * The folder's current internal property state.
     *
     * @var array
     */
    protected $_status = array();

    /**
     * The backend server id for this folder.
     *
     * @var string
     */
    protected $_serverid;

    /**
     * The collection class.
     *
     * @var string
     */
    protected $_class;

    /**
     * Const'r
     *
     * @param string $serverid  The backend serverid of this folder.
     * @param string $class     The collection class.
     * @param array $status     Internal folder state.
     */
    public function __construct(
        $serverid, $class, array $status = array())
    {
        $this->_serverid = $serverid;
        $this->_status = $status;
        $this->_class = $class;
    }

    /**
     * Return the serverid for this collection.
     *
     * @return string  The serverid.
     */
    public function serverid()
    {
        return $this->_serverid;
    }

    /**
     * Set a new value for the serverid.
     *
     * @param string $id  The new id.
     * @since 2.4.0
     * @todo  For H6 make these all __get/__set calls.
     */
    public function setServerId($id)
    {
        $this->_serverid = $id;
    }

    /**
     * Return the collection class for this collection.
     *
     * @return string  The collection class.
     */
    public function collectionClass()
    {
        return $this->_class;
    }

    /**
     * Set the status for this collection.
     *
     * @param array  A status array.
     */
    public function setStatus(array $status)
    {
        $this->_status = $status;
    }

    /**
     * Updates the internal UID cache, and clears the internal
     * update/deleted/changed cache.
     */
    abstract public function updateState();

}
