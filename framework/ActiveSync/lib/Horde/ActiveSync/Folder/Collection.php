<?php
/**
 * Horde_ActiveSync_Folder_Collection
 *
 * PHP Version 5
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 * @copyright 2010-2012 Horde LLC (http://www.horde.org/)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @link      http://pear.horde.org/index.php?package=ActiveSync
 * @package   ActiveSync
 */
/**
  * The class contains functionality for maintaining state for a generic
  * collection folder. This would include Appointments, Contacts, and Tasks.
  *
  * @license   http://www.horde.org/licenses/gpl GPLv2
  * @copyright 2012 Horde LLC (http://www.horde.org/)
  * @author    Michael J Rubinsky <mrubinsk@horde.org>
  * @link      http://pear.horde.org/index.php?package=ActiveSync
  * @package   ActiveSync
  */

class Horde_ActiveSync_Folder_Collection extends Horde_ActiveSync_Folder_Base
{

    /**
     * Updates the internal UID cache, and clears the internal
     * update/deleted/changed cache.
     */
    public function updateState()
    {
    }

    /**
     * Serialize this object.
     *
     * @return string  The serialized data.
     */
    public function serialize()
    {
        return serialize(array($this->_status, $this->_serverid, $this->_class));
    }

    /**
     * Reconstruct the object from serialized data.
     *
     * @param string $data  The serialized data.
     */
    public function unserialize($data)
    {
        list($this->_status, $this->_serverid, $this->_class) = @unserialize($data);
    }

    /**
     * Convert the instance into a string.
     *
     * @return string The string representation for this instance.
     */
    public function __toString()
    {
        return sprintf(
            "serverid: %s\nclass: %s\n",
            $this->serverid(),
            $this->collectionClass());
    }

}
